<?php
// update_tables_for_labors.php - Скрипт для обновления таблицы трудозатрат (запустите один раз)
include 'config.php';

try {
    // Удаляем старую таблицу product_container_labors (many-to-many)
    $pdo->exec("DROP TABLE IF EXISTS product_container_labors");

    // Удаляем таблицу labor_types (больше не нужна)
    $pdo->exec("DROP TABLE IF EXISTS labor_types");

    // Создаём новую таблицу product_container_labors с динамическими полями name и cost
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_container_labors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        container_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        cost DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (container_id) REFERENCES product_containers(id) ON DELETE CASCADE
    )");

    echo "Таблицы обновлены успешно.";
} catch (PDOException $e) {
    die("Ошибка обновления таблиц: " . $e->getMessage());
}
?>