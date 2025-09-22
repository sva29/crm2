<?php
// user.php - Fайл для работы со списком пользователей и управления ролями

include 'config.php'; // Подключаем файл с настройками базы данных
include 'header.php'; // Подключаем заголовок с меню

try {
    // SQL для создания таблицы users, если она не существует
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        current_role VARCHAR(255) NOT NULL DEFAULT 'Сотрудник ЛКМ',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    // Выполнение запроса на создание таблицы
    $pdo->exec($sql);
    echo "<p>Таблица 'users' успешно создана или уже существует.</p>";

} catch (PDOException $e) {
    // Обработка ошибок выполнения запроса
    die("Ошибка при создании таблицы 'users': " . $e->getMessage());
}

// TODO: Вставьте ваш код для получения списка пользователей из базы данных здесь.
// TODO: Вставьте ваш код для отображения списка пользователей и формы добавления здесь.


include 'footer.php'; // Подключаем футер
?>
