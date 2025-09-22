<?php
// recipe_details.php - Страница для отображения детального расчёта по сырью
include 'config.php';
include 'header.php';

if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
    echo '<div class="alert alert-danger">Ошибка: Не указан ID продукта.</div>';
    include 'footer.php';
    exit;
}

$product_id = $_GET['product_id'];
$stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo '<div class="alert alert-danger">Ошибка: Продукт не найден.</div>';
    include 'footer.php';
    exit;
}
?>

<h1>Расчёт по сырью для продукта: <?= $product['name'] ?></h1>
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Детали рецептуры</h5>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Компонент</th>
                        <th>Количество</th>
                        <th>Тип</th>
                        <th>Цена за единицу</th>
                        <th>Расчёт</th>
                        <th>Итоговая стоимость</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recipes = $pdo->prepare("SELECT r.*, c.name, c.price_per_unit, c.unit FROM recipes r JOIN components c ON r.component_id = c.id WHERE r.product_id = ?");
                    $recipes->execute([$product_id]);
                    $raw_cost = 0;
                    foreach ($recipes->fetchAll() as $rec) {
                        $amount = $rec['amount'];
                        if ($rec['amount_type'] == 'percent') $amount /= 100;
                        $cost = $amount * $rec['price_per_unit'];
                        $raw_cost += $cost;
                        $calculation = $rec['amount_type'] == 'percent' ? "{$rec['amount']} / 100 * {$rec['price_per_unit']}" : "{$rec['amount']} * {$rec['price_per_unit']}";
                        echo "<tr>
                            <td>{$rec['name']}</td>
                            <td>{$rec['amount']} {$rec['unit']}</td>
                            <td>" . ($rec['amount_type'] == 'absolute' ? 'кг/литр' : '%') . "</td>
                            <td>{$rec['price_per_unit']} руб.</td>
                            <td>{$calculation}</td>
                            <td>" . number_format($cost, 2) . " руб.</td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <h5 class="mt-3">Итоговая себестоимость по сырью: <?= number_format($raw_cost, 2) ?> руб.</h5>
        <a href="products.php" class="btn btn-primary mt-3">Вернуться к списку продуктов</a>
    </div>
</div>

<?php include 'footer.php'; ?>