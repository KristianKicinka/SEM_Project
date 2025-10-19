<?php
// Force PHP settings
ini_set('upload_max_filesize', '950M');
ini_set('post_max_size', '950M');
ini_set('max_execution_time', '300');
ini_set('memory_limit', '512M');

echo "PHP Version: " . phpversion() . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "Configuration File: " . php_ini_loaded_file() . "\n";
?>