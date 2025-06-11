<?php

// Root-level configuration file for project_new.
// This file should primarily be used to load the main application configuration
// and define any truly global settings if necessary.

// Define the application's root directory if not already defined by web server/environment.
// This ensures that PROJECT_ROOT constant is available for the loaded config.
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', __DIR__);
}

// Load the main application configuration file
// This file contains database credentials, site settings, and other core constants.
$app_config_path = PROJECT_ROOT . '/app/config/config.php';

if (file_exists($app_config_path)) {
    require_once $app_config_path;
} else {
    // Critical error: The main application config file is missing.
    // This typically means the application is not installed or configured correctly.
    // You might want to trigger an error or redirect to an installer/error page.
    header('HTTP/1.1 503 Service Unavailable');
    echo "<h1>Error: Application Configuration Missing</h1>";
    echo "<p>The main configuration file could not be found. Please ensure the application is installed and configured correctly.</p>";
    // Log this error for debugging by the administrator.
    // error_log("Critical: Main application config file not found at: " . $app_config_path);
    exit;
}

// Any other root-level specific configurations or bootstrapping can go here,
// but it's generally recommended to keep this file minimal and delegate
// most configuration to the file loaded above from the app/config directory.

?>
