<?php
// orders_minimal.php - Минимальная версия для тестирования
include 'config.php';
include 'header.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function logMessage($message) {
    $logFile = __DIR__ . '/orders_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

logMessage("orders_minimal.php: Script started");

?>
<h1>Заявка на закупку</h1>
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Тестовая страница</h5>
        <p>Страница работает.</p>
    </div>
</div>
<?php
logMessage("orders_minimal.php: Script completed");
include 'footer.php';
?>