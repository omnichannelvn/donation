<?php

// Autoload classes using Composer's autoloading mechanism
require_once __DIR__ . '/vendor/autoload.php';

// Register the autoloader
spl_autoload_register(function ($class) {
    $prefix = 'Donation\\';
    $base_dir = __DIR__ . '/../includes/';

    // Check if the class uses the namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // Move to the next registered autoloader
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace the namespace prefix with the base directory, replace namespace separators with directory separators
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});