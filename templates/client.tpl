<link href="modules/servers/scalewithus/assets/css/style.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
<div class="text-left bg-white">
    <div class="p-4 bg-gray-300">
        <h2 class="font-semibold text-md mb-3">Service Info</h2>
        <div class="text-gray-700">
            <div>
                <strong>Status: {$model.domainstatus|escape}</strong>
            </div>
            <div>
                <strong>Billing Cycle: {$model.billingcycle|escape}</strong>
            </div>
            <div>
                <strong>Start Date: {$model.regdate|escape}</strong>
            </div>
            {if $model.billingcycle ne 'Free Account' && $model.billingcycle ne 'One Time'}
                <div>
                    <strong>Next Due: {$model.regdate|escape}</strong>
                </div>
                <div>
                    <strong>Renewal: {$model.amount|escape} {$model.client.currencyCode|escape}</strong>
                </div>
            {/if}

        </div>
    </div>
    <div class="p-4">
        {if $model.domainstatus eq 'Active'}
            <h2 class="font-semibold text-md mb-3">Server Details</h2>
            <div class="grid grid-cols-2 gap-4 mb-3">
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
                        <button
                            class="bg-pink-500 text-white active:bg-pink-600 font-bold uppercase text-xs px-4 py-2 rounded hover:shadow-md outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150 ml-2"
                            onclick="copyPassword()">
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

            <h2 class="font-semibold text-md mb-3">Actions</h2>
            <div class="flex gap-1 mt-4 text-left">
                {if $service.vm.state eq 'running'}
                    <form method="post" action="clientarea.php?action=productdetails" class="block"
                        onsubmit="return confirm('Are you sure you want to power off?')">
                        <input type="hidden" name="id" value="{$serviceid}" />
                        <input type="hidden" name="modop" value="custom" />
                        <input type="hidden" name="a" value="poweroff" />
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Power
                            off</button>
                    </form>
                    <form method="post" action="clientarea.php?action=productdetails"
                        onsubmit="return confirm('Are you sure you want to reset the password?')">
                        <input type="hidden" name="id" value="{$serviceid}" />
                        <input type="hidden" name="modop" value="custom" />
                        <input type="hidden" name="a" value="resetpass" />
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Reset
                            Password</button>
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



            </div>
            <div class="mt-4">
                <h2 class="font-semibold text-md mb-3">Update Reverse DNS</h2>
                {foreach $service.ips as $ip}
                    <div class="grid grid-cols-4 gap-4 bg-gray-200 p-2 px-4 rounded">
                        <p class="text-gray-700 flex items-center">{$ip.address|escape}</p>
                        <form class="col-span-3 flex items-center" method="post" action="clientarea.php?action=productdetails">
                            <!-- Add input field for reverse DNS -->
                            <input type="hidden" name="id" value="{$serviceid}" />
                            <input type="hidden" name="modop" value="custom" />
                            <input type="hidden" name="a" value="reversedns" />
                            <input type="text" name="domain"
                                class="w-full p-1 placeholder-blueGray-300 text-blueGray-600 relative bg-white bg-white rounded text-sm border-0 outline-none focus:outline-none focus:ring w-full "
                                placeholder="domain.example.com" value="{$ip.hostname}">
                            <input type="hidden" name="ip" value="{$ip.address}">
                            <button
                                class="bg-pink-500 text-white active:bg-pink-600 font-bold uppercase text-xs px-4 py-2 rounded hover:shadow-md outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150 ml-2">SAVE</button>
                        </form>
                    </div>
                {/foreach}
            </div>
        {/if}
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