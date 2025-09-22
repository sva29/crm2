<!-- test_log.php -->
<?php
file_put_contents(__DIR__ . '/test_log.txt', "Test write: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
echo "Лог записан";
?>