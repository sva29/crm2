<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// create_tables.php - Скрипт для создания таблиц в БД (запустите один раз)
include 'config.php';

try {
    // Таблица компонентов
    $pdo->exec("CREATE TABLE IF NOT EXISTS components (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        price_per_unit DECIMAL(10,2) NOT NULL,  -- цена за кг/литр
        unit ENUM('kg', 'liter') NOT NULL DEFAULT 'kg'
    )");

    // Таблица фасовок для компонентов
    $pdo->exec("CREATE TABLE IF NOT EXISTS component_packagings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        component_id INT NOT NULL,
        type VARCHAR(255) NOT NULL,  -- ведро, бочка и т.д.
        volume DECIMAL(10,2) NOT NULL,  -- объем в кг/литрах
        price_per_package DECIMAL(10,2) NOT NULL,  -- стоимость за 1 единицу фасовки
        FOREIGN KEY (component_id) REFERENCES components(id) ON DELETE CASCADE
    )");

    // Таблица готовой продукции
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL
    )");

    // Таблица рецептур (компоненты в продукте)
    $pdo->exec("CREATE TABLE IF NOT EXISTS recipes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        component_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,  -- количество
        amount_type ENUM('percent', 'absolute') NOT NULL DEFAULT 'absolute',  -- % или кг/литр
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (component_id) REFERENCES components(id) ON DELETE CASCADE
    )");

    // Таблица типов трудозатрат
    $pdo->exec("CREATE TABLE IF NOT EXISTS labor_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,  -- труд, этикетка, реклама и т.д.
        cost DECIMAL(10,2) NOT NULL  -- стоимость
    )");

    // Таблица вариантов тары для продуктов
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_containers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        type VARCHAR(255) NOT NULL,  -- тип тары
        cost DECIMAL(10,2) NOT NULL,  -- стоимость тары
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");

    // Связь трудозатрат с вариантами тары продуктов (many-to-many)
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_container_labors (
        container_id INT NOT NULL,
        labor_id INT NOT NULL,
        PRIMARY KEY (container_id, labor_id),
        FOREIGN KEY (container_id) REFERENCES product_containers(id) ON DELETE CASCADE,
        FOREIGN KEY (labor_id) REFERENCES labor_types(id) ON DELETE CASCADE
    )");

    // Таблица заявок на закупку
    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        total_cost DECIMAL(10,2) NOT NULL DEFAULT 0
    )");

    // Таблица позиций в заявке (продукты и количества)
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,  -- сколько произвести
        container_id INT,  -- вариант тары (опционально для расчёта себестоимости)
        FOREIGN KEY (order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (container_id) REFERENCES product_containers(id) ON DELETE SET NULL
    )");

    echo "Таблицы созданы успешно.";
} catch (PDOException $e) {
    die("Ошибка создания таблиц: " . $e->getMessage());
}
?>