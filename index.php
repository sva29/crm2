<?php
// index.php - Главная страница сайта
include 'config.php';
include 'header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h1 class="card-title">Добро пожаловать в систему управления закупками</h1>
                <p class="card-text">Выберите раздел в меню выше для работы с компонентами, продукцией или заявками.</p>
                <p class="card-text">Эта система позволяет:</p>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Управлять компонентами (добавлять, просматривать).</li>
                    <li class="list-group-item">Создавать готовую продукцию с рецептурами и вариантами тары.</li>
                    <li class="list-group-item">Формировать заявки на закупку с автоматическим расчётом сырья.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>