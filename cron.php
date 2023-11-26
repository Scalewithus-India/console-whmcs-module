<?php
// Check if the script is running from the command line
if (php_sapi_name() != 'cli') {
    // If not CLI, then exit the script
    exit('This script can only be run from the command line.');
}