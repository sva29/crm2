<?php
// update_tables_for_containers.php - Скрипт для создания таблицы тары и обновления order_items (запустите один раз)
include 'config.php';

try {
    // Создаём таблицу component_containers
    $pdo->exec("CREATE TABLE IF NOT EXISTS component_containers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        component_id INT NOT NULL,
        type VARCHAR(255) NOT NULL,
        cost DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (component_id) REFERENCES components(id) ON DELETE CASCADE
    )");

    // Добавляем поле container_id в order_items
    $pdo->exec("ALTER TABLE order_items ADD COLUMN container_id INT NULL, ADD FOREIGN KEY (container_id) REFERENCES component_containers(id) ON DELETE SET NULL");

    echo "Таблицы обновлены успешно.";
} catch (PDOException $e) {
    die("Ошибка обновления таблиц: " . $e->getMessage());
}
?>