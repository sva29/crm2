<?php
// clear_orders.php - Скрипт для очистки истории заявок
include 'config.php';

try {
    // Отключаем проверку внешних ключей для упрощения
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");

    // Очищаем таблицу order_component_prices
    $pdo->exec("TRUNCATE TABLE order_component_prices");

    // Очищаем таблицу order_items
    $pdo->exec("TRUNCATE TABLE order_items");

    // Очищаем таблицу purchase_orders
    $pdo->exec("TRUNCATE TABLE purchase_orders");

    // Включаем проверку внешних ключей
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");

    echo "История заявок успешно очищена.";
} catch (PDOException $e) {
    // Включаем проверку внешних ключей в случае ошибки
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
    die("Ошибка при очистке истории заявок: " . $e->getMessage());
}
?>