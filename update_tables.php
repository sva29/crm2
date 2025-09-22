<?php
// update_tables.php - Скрипт для обновления всех таблиц базы данных (запустите один раз)
include 'config.php';

try {
    // Отключаем проверку внешних ключей для упрощения
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");

    // Создаём таблицу components первой с явным PRIMARY KEY
    $pdo->exec("CREATE TABLE IF NOT EXISTS components (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        price_per_unit DECIMAL(10,2) NOT NULL,
        unit ENUM('kg', 'liter') NOT NULL DEFAULT 'kg',
        is_container TINYINT(1) DEFAULT 0
    )");

    // Добавляем поле is_container в components, если не существует
    $columns = $pdo->query("SHOW COLUMNS FROM components LIKE 'is_container'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE components ADD COLUMN is_container TINYINT(1) DEFAULT 0");
    }

    // Удаляем таблицу component_containers, если существует
    $pdo->exec("DROP TABLE IF EXISTS component_containers");

    // Удаляем все внешние ключи в order_items
    $constraints = $pdo->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'order_items' AND CONSTRAINT_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL")->fetchAll();
    foreach ($constraints as $constraint) {
        $pdo->exec("ALTER TABLE order_items DROP FOREIGN KEY {$constraint['CONSTRAINT_NAME']}");
    }

    // Удаляем столбцы container_id и container_component_id из order_items, если существуют
    $columns = $pdo->query("SHOW COLUMNS FROM order_items LIKE 'container_id'")->fetchAll();
    if (!empty($columns)) {
        $pdo->exec("ALTER TABLE order_items DROP COLUMN container_id");
    }
    $columns = $pdo->query("SHOW COLUMNS FROM order_items LIKE 'container_component_id'")->fetchAll();
    if (!empty($columns)) {
        $pdo->exec("ALTER TABLE order_items DROP COLUMN container_component_id");
    }

    // Создаём таблицу order_items заново с правильными внешними ключами
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity DECIMAL(10,2) NOT NULL,
        container_component_id INT NULL,
        FOREIGN KEY (order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (container_component_id) REFERENCES components(id) ON DELETE SET NULL
    )");

    // Создаём/обновляем остальные таблицы
    $pdo->exec("CREATE TABLE IF NOT EXISTS component_packagings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        component_id INT NOT NULL,
        type VARCHAR(255) NOT NULL,
        volume DECIMAL(10,2) NOT NULL,
        price_per_package DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (component_id) REFERENCES components(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS recipes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        component_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        amount_type ENUM('absolute', 'percent') NOT NULL DEFAULT 'absolute',
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (component_id) REFERENCES components(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS product_containers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        type VARCHAR(255) NOT NULL,
        cost DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS product_container_labors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        container_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        cost DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (container_id) REFERENCES product_containers(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        total_cost DECIMAL(10,2) NOT NULL DEFAULT 0
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS order_component_prices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        component_id INT NOT NULL,
        price_per_unit DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
        FOREIGN KEY (component_id) REFERENCES components(id) ON DELETE CASCADE
    )");

    // Создаём таблицы для системы пользователей и ролей
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        login VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE
    )");

    // Проверяем наличие столбца login в таблице users
    $columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'login'")->fetchAll();
    if (empty($columns)) {
        // Если столбца login нет, добавляем его
        $pdo->exec("ALTER TABLE users ADD COLUMN login VARCHAR(50) UNIQUE NOT NULL AFTER id");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) UNIQUE NOT NULL,
        description TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS user_roles (
        user_id INT NOT NULL,
        role_id INT NOT NULL,
        PRIMARY KEY (user_id, role_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
    )");

    // Добавляем стандартные роли, если они ещё не существуют
    $roles = [
        ['admin', 'Администратор системы'],
        ['technologist_lkm', 'Технолог ЛКМ'],
        ['technologist_bh', 'Технолог БХ'],
        ['employee_lkm', 'Сотрудник ЛКМ']
    ];

    foreach ($roles as $role) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM roles WHERE name = ?");
        $stmt->execute([$role[0]]);
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $stmt = $pdo->prepare("INSERT INTO roles (name, description) VALUES (?, ?)");
            $stmt->execute([$role[0], $role[1]]);
        }
    }

    // Создаём администратора по умолчанию, если нет пользователей
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    
    if ($userCount == 0) {
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)");
        $stmt->execute(['admin', $defaultPassword]);
        
        // Назначаем администратору все роли
        $adminId = $pdo->lastInsertId();
        $stmt = $pdo->query("SELECT id FROM roles");
        $roleIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($roleIds as $roleId) {
            $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $stmt->execute([$adminId, $roleId]);
        }
    }

    // Включаем проверку внешних ключей
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");

    echo "Все таблицы обновлены успешно. ";
    echo "Создан администратор по умолчанию: логин 'admin', пароль 'admin123'";
} catch (PDOException $e) {
    // Включаем проверку внешних ключей в случае ошибки
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
    die("Ошибка обновления таблиц: " . $e->getMessage());
}
?>