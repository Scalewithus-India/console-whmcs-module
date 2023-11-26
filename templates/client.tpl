<link href="modules/servers/scalewithus/assets/css/style.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
<div class="text-left">
    <h2 class="text-2xl font-semibold">Server Details</h2>
    <div class="grid grid-cols-2 gap-4 mt-4">
        <div>
            <p class="text-gray-700">
                <strong>State:</strong>
                {$service.vm.state|escape}
                {if $service.vm.state eq 'running'}
                <i class="fas fa-circle text-green-500"></i>
                {else}
                <i class="fas fa-circle text-red-500"></i>
                {/if}
            </p>
            <p class="text-gray-700"><strong>Username:</strong> {$service.vm.username|escape}</p>
            <p class="text-gray-700">
                <strong>Password:</strong>
                <span class="password" style="display: inline-block;">
                    <input type="password" value="{$service.vm.password|escape}" id="passwordField" disabled>
                </span>
                <button class="btn" onclick="copyPassword()">
                    <i class="far fa-copy"></i> Copy
                </button>
            <div id="copyMessage" style="display: none;">Copied!</div>
            </p>
            <p class="text-gray-700"><strong>Operating System:</strong> {$service.service.template.name|escape}</p>

        </div>
        <div>
            <p class="text-gray-700"><strong>Hostname:</strong> {$service.service.hostname|escape}</p>
            <p class="text-gray-700"><strong>IP Address:</strong> {$service.ips.0.address|escape}</p>
            <p class="text-gray-700"><strong>Disk Space:</strong> {$service.service.package.diskSpace|escape} GB <i
                    class="fas fa-hdd"></i></p>
            <p class="text-gray-700"><strong>Memory:</strong> {$service.service.package.memory|escape} MB <i
                    class="fas fa-memory"></i></p>
            <p class="text-gray-700"><strong>Cores:</strong> {$service.service.package.cores|escape} <i
                    class="fas fa-microchip"></i></p>
        </div>
    </div>

    <h2 class="font-semibold text-md mb-2 mt-4">Actions</h2>
    <div class="flex gap-1 mt-4 text-left">
        {if $service.vm.state eq 'running'}
        <form method="post" action="clientarea.php?action=productdetails" class="block"
            onsubmit="return confirm('Are you sure you want to power off?')">
            <input type="hidden" name="id" value="{$serviceid}" />
            <input type="hidden" name="modop" value="custom" />
            <input type="hidden" name="a" value="poweroff" />
            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Power off</button>
        </form>
        {/if}

        {if $service.vm.state ne 'running'}
        <form method="post" action="clientarea.php?action=productdetails" class="block"
            onsubmit="return confirm('Are you sure you want to start the server?')">
            <input type="hidden" name="id" value="{$serviceid}" />
            <input type="hidden" name="modop" value="custom" />
            <input type="hidden" name="a" value="poweron" />
            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Start VPS
                server</button>
        </form>
        {/if}

        <form method="post" action="clientarea.php?action=productdetails"
            onsubmit="return confirm('Are you sure you want to reset the password?')">
            <input type="hidden" name="id" value="{$serviceid}" />
            <input type="hidden" name="modop" value="custom" />
            <input type="hidden" name="a" value="resetpassword" />
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Reset
                Password</button>
        </form>

    </div>
    <div class="mt-4">
        <h2 class="font-semibold text-md mb-3">Reverse DNS</h2>
        {foreach $service.ips as $ip}
        <div class="grid grid-cols-4 gap-4">
            <p class="text-gray-700">{$ip.address|escape}</p>
            <form class="col-span-3 ">
                <!-- Add input field for reverse DNS -->
                <input type="text" class="w-full" placeholder="Set Reverse DNS">
            </form>
        </div>
        {/foreach}
    </div>
</div>

<script>
    function copyPassword() {
        var passwordField = document.getElementById("passwordField");
        var textArea = document.createElement("textarea");
        textArea.value = passwordField.value;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand("Copy");
        textArea.remove();

        // Show the "Copied!" message
        alert("Password Copied")

    }
</script>