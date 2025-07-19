<?php
// Simple PHP info and Laravel test page
echo "<h1>Laravel POS Application</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";

// Test if Laravel files exist
if (file_exists('artisan')) {
    echo "<p>✅ Laravel artisan file found</p>";
    if (file_exists('vendor/autoload.php')) {
        echo "<p>✅ Composer vendor directory found</p>";
        
        // Try to include Laravel bootstrap
        try {
            require_once 'vendor/autoload.php';
            $app = require_once 'bootstrap/app.php';
            echo "<p>✅ Laravel application bootstrap loaded</p>";
            
            // Try to access Laravel
            $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
            echo "<p>✅ Laravel HTTP kernel loaded</p>";
            
        } catch (Exception $e) {
            echo "<p>❌ Laravel bootstrap error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>❌ Composer vendor directory not found</p>";
    }
} else {
    echo "<p>❌ Laravel artisan file not found</p>";
}

// Show current directory contents
echo "<h2>Directory Contents:</h2>";
echo "<ul>";
foreach (scandir('.') as $file) {
    if ($file != '.' && $file != '..') {
        echo "<li>" . $file . (is_dir($file) ? '/' : '') . "</li>";
    }
}
echo "</ul>";

// Environment variables
echo "<h2>Environment Variables:</h2>";
echo "<ul>";
foreach ($_ENV as $key => $value) {
    if (strpos($key, 'DB_') === 0 || strpos($key, 'APP_') === 0) {
        echo "<li><strong>$key:</strong> " . (strpos($key, 'PASSWORD') !== false ? '***' : $value) . "</li>";
    }
}
echo "</ul>";

?>
