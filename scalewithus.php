<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;
use WHMCS\Exception;


define("API_BASE_URL", "https://console.scalewithus.com/api/v1");
define("MODULE_NAME", "scalewithus");
define("DEBUG", false);
function do_request(string $uri, $method, $apiKey, $body = [])
{
    $headers = array(
        'Authorization: ' . $apiKey, // Fixed variable name
        'Content-Type: application/json'
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, API_BASE_URL . $uri);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SCALEWITHUS_WHMCS');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method == "POST") {
        $payload = json_encode($body);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);
    if (!$result) {
        $result = [];
    }

    // Add HTTP status code to the result
    $result['http_status'] = $statusCode;

    // Determine success or failure based on status code
    if ($statusCode >= 200 && $statusCode < 300) {
        $result['success'] = true;
    } else {
        $result['success'] = false;
        if (!$result['message']) {
            $result['message'] = 'Request failed with status code ' . $statusCode;
        }
    }

    return $result;
}
function scalewithus_formatSize($size, $inputSizeType = "MB")
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $unitIndex = array_search(strtoupper($inputSizeType), $units);

    if ($unitIndex === false) {
        return "Unknown size type";
    }

    // Convert input size to bytes
    for ($i = 0; $i < $unitIndex; $i++) {
        $size *= 1024;
    }

    // Convert bytes to the largest possible unit
    $unitIndex = 0;
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }

    return round($size, 2) . ' ' . $units[$unitIndex];
}

function scalewithus_MetaData()
{
    return array(
        'DisplayName' => 'ScaleWithUs Module', // Display Name,
        'APIVersion' => '1.1', // Use API Version 1.1
        'RequiresServer' => true, // Set true if module requires a server to work
    );
}

function scalewithus_TestConnection(array $params)
{
    try {
        // Replace with a valid endpoint for testing
        $result = do_request('/profile', 'GET', $params['serveraccesshash']);
        if ($result['success']) {
            return array('success' => true);
        } else {
            return array('error' => $result['message']);
        }
    } catch (Exception $e) {
        return array('error' => 'Connection Test Failed: ' . $e->getMessage());
    }
}

function scalewithus_ConfigOptions(array $params)
{
    // var_dump($params);
    return array(
        "Scalewithus Package Name" => array(
            "Type" => "text",
            "Size" => "64",
            "Loader" => "scalewithus_Packages",
            "SimpleMode" => true
        ),
        "Project" => array(
            "Type" => "text",
            "Size" => "64",
            "Loader" => "scalewithus_Projects",
            "SimpleMode" => true
        ),
    );
}



function scalewithus_EnsureCustomFields(array $params)
{
    $command = 'GetProducts';
    $postData = array(
        // Optional filters
    );
    $results = localAPI($command, $postData);

    if ($results['result'] == 'success') {
        $products = $results['products']['product'];
        // filter ["module"] == "scalewithus"
        $products = array_filter($products, function ($product) {
            return $product['module'] == 'scalewithus';
        });
        // if products empty return 
        if (count($products) === 0) {
            return false;
        }

        $packages = do_request('/packages', 'GET', $params['serveraccesshash']);
        if (!$packages["success"]) {
            return false;
        }
        $packageID = $packages["data"][0]["_id"];

        $templates = do_request('/packages' . '/' . $packageID . '/templates', 'GET', $params['serveraccesshash']);
        if (!$templates["success"]) {
            return false;
        }
        $operatingSystemOptions = "";

        foreach ($templates["data"] as $templateGroup) {
            foreach ($templateGroup["templates"] as $template) {
                $operatingSystemOptions = $operatingSystemOptions . "," . $template["_id"] . "|" . $template["name"];
            }
        }
        // remove first "," from operatingSystemOptions
        $operatingSystemOptions = preg_replace('/,/', '', $operatingSystemOptions, 1);

        foreach ($products as $product) {
            // Check and add Hostname field if it doesn't exist
            $hostnameExists = Capsule::table('tblcustomfields')
                ->where('type', 'product')
                ->where('relid',  $product["pid"])
                ->where('fieldname', 'like', '%Hostname%')
                ->exists();

            if (!$hostnameExists) {
                Capsule::table('tblcustomfields')->insert([
                    'type' => 'product',
                    'relid' => $product["pid"],
                    'fieldname' => 'Hostname',
                    'regexpr' => "/^([a-zA-Z0-9-]+\.){2,}[a-zA-Z]{2,}$/",
                    'fieldtype' => 'text',
                    'description' => 'Enter the Hostname for the server  (Example: server.example.com)',
                    'required' => "on",
                    'showorder' => 'on',
                    'showinvoice' => 'on',
                ]);
            }

            // Check and add Operating System field if it doesn't exist
            $osFieldExists = Capsule::table('tblcustomfields')
                ->where('type', 'product')
                ->where('relid', $product["pid"])
                ->where('fieldname', 'like', '%Operating System%')
                ->exists();

            if (!$osFieldExists) {
                Capsule::table('tblcustomfields')->insert([
                    'type' => 'product',
                    'relid' => $product["pid"],
                    'fieldname' => 'Operating System',
                    'fieldtype' => 'dropdown',
                    'fieldoptions' => $operatingSystemOptions,
                    'description' => 'Select an Operating System',
                    'required' => "on",
                    'showorder' => 'on',
                    'showinvoice' => 'on',
                ]);
            }


            // Check and add Operating System field if it doesn't exist
            $serviceid = Capsule::table('tblcustomfields')
                ->where('type', 'product')
                ->where('relid', $product["pid"])
                ->where('fieldname', 'like', '%serviceid%')
                ->exists();

            if (!$serviceid) {
                Capsule::table('tblcustomfields')->insert([
                    'type' => 'product',
                    'relid' => $product["pid"],
                    'fieldname' => 'serviceid',
                    'fieldtype' => 'text',
                    'description' => 'Field to store Service ID (Do not edit/remove. For Internal Use Only.)',
                    'adminonly' => 'on',
                ]);
            }

            // Check and add Operating System field if it doesn't exist
            $projectId = Capsule::table('tblcustomfields')
                ->where('type', 'product')
                ->where('relid', $product["pid"])
                ->where('fieldname', 'like', '%projectid%')
                ->exists();

            if (!$projectId) {
                Capsule::table('tblcustomfields')->insert([
                    'type' => 'product',
                    'relid' => $product["pid"],
                    'fieldname' => 'projectid',
                    'fieldtype' => 'text',
                    'description' => 'Field to store Project ID (Do not edit/remove. For Internal Use Only.)',
                    'adminonly' => 'on',
                ]);
            }
        }
    } else {
        throw new Exception('Failed to retrieve products: ' . $results['message']);
    }
}

function scalewithus_Packages(array $params)
{
    scalewithus_EnsureCustomFields($params);
    // var_dump($params, "",);
    $packages = do_request('/packages', 'GET', $params['serveraccesshash']);

    // Error handling
    if (!$packages["success"] || !isset($packages['data'])) {
        throw new WHMCS\Exception\Module\NotServicable("Error retrieving packages");
    }

    // Building the return array
    $return = array();
    foreach ($packages['data'] as $package) {
        $return[$package['_id']] = sprintf(
            "%s | %d Core | %s RAM | %s Disk",
            $package['name'],
            $package['cores'],
            scalewithus_formatSize($package['memory']),
            scalewithus_formatSize($package['diskSpace'], "GB")
        );
    }

    return $return;
}

// Needs to be updated After This point
function scalewithus_Projects(array $params)
{
    scalewithus_EnsureCustomFields($params);
    // var_dump($params, "",);
    $projects = do_request('/projects', 'GET', $params['serveraccesshash']);

    // Error handling
    if (!$projects["success"] || !isset($projects['data'])) {
        throw new WHMCS\Exception\Module\NotServicable("Error retrieving packages");
    }

    // Building the return array
    $return = array();
    foreach ($projects['data']["projects"] as $project) {
        $return[$project['_id']] = $project['name'];
    }

    return $return;
}


// tblhosting => Set Username To Lic ID.
function scalewithus_CreateAccount(array $params)
{
    $serviceid = $params['customfields']['serviceid'];
    if ($serviceid) return "Service ID is adready present. To recreate please remove that first.";
    $packageid = $params['configoption1'];
    $projectid = $params['configoption2'];
    $operatingSystem = $params['customfields']['Operating System'];
    $hostname = $params['customfields']['Hostname'];
    $resp = do_request('/services', "POST", $params['serveraccesshash'], [
        'hostname' => $params['customfields']['Hostname'],
        'templateId' => $params['customfields']['Operating System'],
        'packageId' => $packageid,
        'projectId' => $projectid,
        'context'   => $params['model']
    ]);
    if (!$resp['success']) {
        return "Failed to setup service";
    }

    $service = do_request("/services/" . $resp['data']['_id'], "GET", $params['serveraccesshash']);

    if (!$service['success']) {
        return "Failed to setup service";
    }

    $postData = array(
        'serviceid' => $params['serviceid'],
        'dedicatedip' => $service['data']['ips'][0]['address'],
        // 'serviceusername' =>$service['data']['vm']['username'],
        // 'servicepassword' => $service['data']['vm']['password'],
        // 'status' => 'Active',
        'customfields' => base64_encode(serialize(
            [
                "serviceid" => $resp['data']['_id'],
                "projectid" => $projectid
            ]
        )),
    );
    $results = localAPI('UpdateClientProduct', $postData);

    // save username and password
    $params["model"]->serviceProperties->save(array("username" => $resp['data']['loginUser'], "password" =>  $resp['data']['loginPassword']));
    // Call the local API to update the custom field
    return "success";
}

// function scalewithus_AdminCustomButtonArray()
// {
//    $buttonarray = array( "Renew" => "Renew" );
//    return $buttonarray;
// }

function scalewithus_ClientAreaAllowedFunctions()
{
    // $buttonarray = array(
    //     "Power off server" => "poweroff",
    //     "Power on server"  => "poweron",
    //     "resetpass"  => "resetpass",
    // );
    return ["poweroff", "poweron", "resetpass"];
}

function scalewithus_ClientArea($params)
{
    $serviceid = $params['customfields']['serviceid'];
    $projectid = $params['customfields']['projectid'];
    if (!$serviceid || !$projectid) return "Service not found";

    $service = do_request("/services/" . $serviceid, "GET", $params['serveraccesshash']);
    if (!$service['success']) {
        return '';
    }
    return [
        'tabOverviewReplacementTemplate' => "templates/client.tpl",
        // 'templatefile' => "templates/client.tpl",
        'vars' => [
            'service' => $service['data'],
            'params' => $params
        ],
    ];
}

function scalewithus_SuspendAccount($params)
{
    $serviceid = $params['customfields']['serviceid'];
    $projectid = $params['customfields']['projectid'];
    if (!$serviceid || !$projectid) return "Service not found";

    $service = do_request("/services/" . $serviceid, "GET", $params['serveraccesshash']);
    if (!$service['success']) {
        return "Failed to get service";
    }

    $service = do_request("/services/" . $serviceid . '/power-off', "POST", $params['serveraccesshash']);
    if (!$service['success']) {
        return "Failed to poweroff service";
    }
    return "success";
}

function scalewithus_UnsuspendAccount($params)
{
    $serviceid = $params['customfields']['serviceid'];
    $projectid = $params['customfields']['projectid'];
    if (!$serviceid || !$projectid) return "Service not found";

    $service = do_request("/services/" . $serviceid, "GET", $params['serveraccesshash']);
    if (!$service['success']) {
        return "Failed to get service";
    }

    $service = do_request("/services/" . $serviceid . '/power-on', "POST", $params['serveraccesshash']);
    if (!$service['success']) {
        return "Failed to poweroff service";
    }
    return "success";
}

function scalewithus_TerminateAccount($params)
{
    $serviceid = $params['customfields']['serviceid'];
    $projectid = $params['customfields']['projectid'];
    if (!$serviceid || !$projectid) return "Service not found";

    $service = do_request("/services/" . $serviceid, "GET", $params['serveraccesshash']);
    if (!$service['success']) {
        return "Failed to get service";
    }

    $service = do_request("/services/" . $serviceid, "DELETE", $params['serveraccesshash']);
    if (!$service['success']) {
        return "Failed to Delete service. Is the service exist?";
    }
    $postData = array(
        'serviceid' => $params['serviceid'],
        'dedicatedip' => $service['data']['ips'][0]['address'],
        // 'serviceusername' =>$service['data']['vm']['username'],
        // 'servicepassword' => $service['data']['vm']['password'],
        // 'status' => 'Active',
        'customfields' => base64_encode(serialize(
            [
                "serviceid" => "",
                "projectid" => ""
            ]
        )),
    );
    $results = localAPI('UpdateClientProduct', $postData);
    return "success";
}


function scalewithus_poweroff($params)
{
    $serviceid = $params['customfields']['serviceid'];
    $projectid = $params['customfields']['projectid'];
    if (!$serviceid || !$projectid) return "Service not found";

    $service = do_request("/services/" . $serviceid, "GET", $params['serveraccesshash']);
    if (!$service['success']) {
        return "Failed to get service";
    }

    $service = do_request("/services/" . $serviceid . '/power-off', "POST", $params['serveraccesshash']);
    if (!$service['success']) {
        return "Failed to poweroff service";
    }
    return "success";
}

function scalewithus_poweron($params)
{
    $serviceid = $params['customfields']['serviceid'];
    $projectid = $params['customfields']['projectid'];
    if (!$serviceid || !$projectid) return "Service not found";

    $service = do_request("/services/" . $serviceid, "GET", $params['serveraccesshash']);
    if (!$service['success']) {
        return "Failed to get service";
    }

    $service = do_request("/services/" . $serviceid . '/power-on', "POST", $params['serveraccesshash']);
    if (!$service['success']) {
        return "Failed to poweroff service";
    }
    return "success";
}

function scalewithus_resetpass($params)
{
    $serviceid = $params['customfields']['serviceid'];
    $projectid = $params['customfields']['projectid'];
    if (!$serviceid || !$projectid) return "Service not found";

    $service = do_request("/services/" . $serviceid, "GET", $params['serveraccesshash']);
    if (!$service['success']) {
        return "Failed to get service";
    }

    $service = do_request("/services/" . $serviceid . '/reset-pass', "POST", $params['serveraccesshash']);

    if (!$service['success']) {
        return "Failed to poweroff service";
    }
    return "success";
}