<?php
echo "<h1>Laravel POS Application</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current directory: " . getcwd() . "</p>";

// Test if Laravel files exist
if (file_exists('artisan')) {
    echo "<p>✅ Laravel artisan file found</p>";
    
    // Check if this should be serving Laravel
    if (file_exists('public/index.php')) {
        echo "<p>✅ Laravel public/index.php found - redirecting to Laravel...</p>";
        
        // Include Laravel's public index
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';
        
        require_once 'public/index.php';
        exit;
    } else {
        echo "<p>❌ Laravel public/index.php not found</p>";
    }
} else {
    echo "<p>❌ Laravel artisan file not found</p>";
}

// Show directory contents
echo "<h2>Directory Contents:</h2>";
foreach (scandir('.') as $file) {
    if ($file != '.' && $file != '..') {
        echo "- " . $file . (is_dir($file) ? '/' : '') . "<br>";
    }
}
?>