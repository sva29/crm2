<?php
// products.php - Страница для готовой продукции
include 'config.php';
include 'cost_calculator.php';
include 'header.php';

// Добавление продукта
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $target_volume = $_POST['target_volume_select'] === 'percent' ? 'percent' : floatval($_POST['target_volume']);
    $stmt = $pdo->prepare("INSERT INTO products (name, target_volume) VALUES (?, ?)");
    $stmt->execute([$name, $target_volume]);
    $product_id = $pdo->lastInsertId();

    // Добавление рецептуры
    foreach ($_POST['recipes'] as $rec) {
        if (!empty($rec['component_id']) && !empty($rec['amount'])) {
            $stmt = $pdo->prepare("INSERT INTO recipes (product_id, component_id, amount, amount_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$product_id, $rec['component_id'], $rec['amount'], $rec['amount_type']]);
        }
    }

    // Добавление вариантов тары
    foreach ($_POST['containers'] as $cont) {
        if (!empty($cont['type']) && !empty($cont['cost'])) {
            $stmt = $pdo->prepare("INSERT INTO product_containers (product_id, type, cost) VALUES (?, ?, ?)");
            $stmt->execute([$product_id, $cont['type'], $cont['cost']]);
            $container_id = $pdo->lastInsertId();

            // Добавление трудозатрат к таре
            if (!empty($cont['labors'])) {
                foreach ($cont['labors'] as $labor) {
                    if (!empty($labor['name']) && !empty($labor['cost'])) {
                        $stmt = $pdo->prepare("INSERT INTO product_container_labors (container_id, name, cost) VALUES (?, ?, ?)");
                        $stmt->execute([$container_id, $labor['name'], $labor['cost']]);
                    }
                }
            }
        }
    }
}

// Редактирование продукта
if (isset($_POST['edit_product'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $target_volume = $_POST['target_volume_select'] === 'percent' ? 'percent' : floatval($_POST['target_volume']);
    $stmt = $pdo->prepare("UPDATE products SET name = ?, target_volume = ? WHERE id = ?");
    $stmt->execute([$name, $target_volume, $id]);

    // Удаляем существующую рецептуру
    $stmt = $pdo->prepare("DELETE FROM recipes WHERE product_id = ?");
    $stmt->execute([$id]);

    // Добавляем новую рецептуру
    foreach ($_POST['recipes'] as $rec) {
        if (!empty($rec['component_id']) && !empty($rec['amount'])) {
            $stmt = $pdo->prepare("INSERT INTO recipes (product_id, component_id, amount, amount_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id, $rec['component_id'], $rec['amount'], $rec['amount_type']]);
        }
    }
}

// Список компонентов для формы
$components = $pdo->query("SELECT id, name FROM components")->fetchAll();

// Список продуктов
$products = $pdo->query("SELECT * FROM products")->fetchAll();

// Если выбран продукт для редактирования
$edit_prod = null;
$edit_recipes = [];
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $edit_prod = $stmt->fetch();

    // Получаем текущую рецептуру
    $stmt = $pdo->prepare("SELECT * FROM recipes WHERE product_id = ?");
    $stmt->execute([$id]);
    $edit_recipes = $stmt->fetchAll();
}
?>

<h1>Готовая продукция</h1>
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title"><?= $edit_prod ? 'Редактировать продукт' : 'Добавить продукт' ?></h5>
        <form method="post">
            <?php if ($edit_prod): ?>
                <input type="hidden" name="id" value="<?= $edit_prod['id'] ?>">
            <?php endif; ?>
            <div class="mb-3">
                <label for="name" class="form-label">Название</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= $edit_prod ? $edit_prod['name'] : '' ?>" required>
            </div>
            <div class="mb-3">
                <label for="target_volume" class="form-label">На какой объём продукции расчёт</label>
                <div class="input-group">
                    <select class="form-select" id="target_volume_select" name="target_volume_select" onchange="toggleVolumeInput(this)">
                        <option value="percent" <?= $edit_prod && $edit_prod['target_volume'] === 'percent' ? 'selected' : '' ?>>Расчёт в процентах</option>
                        <option value="liters" <?= $edit_prod && $edit_prod['target_volume'] !== 'percent' ? 'selected' : '' ?>>Литры</option>
                    </select>
                    <input type="number" step="0.01" class="form-control" id="target_volume_input" name="target_volume" value="<?= $edit_prod && $edit_prod['target_volume'] !== 'percent' ? number_format($edit_prod['target_volume'], 2, ',', ' ') : '' ?>" placeholder="Объём в литрах" style="display: <?= $edit_prod && $edit_prod['target_volume'] !== 'percent' ? 'block' : 'none' ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Рецептура</label>
                <div id="recipes">
                    <?php if ($edit_prod && $edit_recipes): ?>
                        <?php foreach ($edit_recipes as $index => $rec): ?>
                            <div class="row mb-2">
                                <div class="col">
                                    <select class="form-select" name="recipes[<?= $index ?>][component_id]">
                                        <?php foreach ($components as $comp): ?>
                                            <option value="<?= $comp['id'] ?>" <?= $comp['id'] == $rec['component_id'] ? 'selected' : '' ?>><?= $comp['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col">
                                    <input type="number" step="0.01" class="form-control" name="recipes[<?= $index ?>][amount]" value="<?= number_format($rec['amount'], 2, ',', ' ') ?>" placeholder="Количество">
                                </div>
                                <div class="col">
                                    <select class="form-select" name="recipes[<?= $index ?>][amount_type]">
                                        <option value="absolute" <?= $rec['amount_type'] == 'absolute' ? 'selected' : '' ?>>кг/литр</option>
                                        <option value="percent" <?= $rec['amount_type'] == 'percent' ? 'selected' : '' ?>>%</option>
                                    </select>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="row mb-2">
                            <div class="col">
                                <select class="form-select" name="recipes[0][component_id]">
                                    <?php foreach ($components as $comp): ?>
                                        <option value="<?= $comp['id'] ?>"><?= $comp['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col">
                                <input type="number" step="0.01" class="form-control" name="recipes[0][amount]" placeholder="Количество">
                            </div>
                            <div class="col">
                                <select class="form-select" name="recipes[0][amount_type]">
                                    <option value="absolute">кг/литр</option>
                                    <option value="percent">%</option>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-secondary mt-2" onclick="addRecipe()">Добавить компонент</button>
            </div>
            <?php if (!$edit_prod): // Тары и трудозатраты только при добавлении ?>
            <div class="mb-3">
                <label class="form-label">Варианты тары</label>
                <div id="containers">
                    <div class="mb-4">
                        <div class="row mb-2">
                            <div class="col">
                                <input type="text" class="form-control" name="containers[0][type]" placeholder="Тип тары">
                            </div>
                            <div class="col">
                                <input type="number" step="0.01" class="form-control" name="containers[0][cost]" placeholder="Стоимость тары">
                            </div>
                        </div>
                        <div class="mt-2">
                            <label class="form-label">Трудозатраты (необязательно)</label>
                            <div id="labors_0">
                                <div class="row mb-2">
                                    <div class="col">
                                        <input type="text" class="form-control" name="containers[0][labors][0][name]" placeholder="Название (Труд, Этикетка и т.д.)">
                                    </div>
                                    <div class="col">
                                        <input type="number" step="0.01" class="form-control" name="containers[0][labors][0][cost]" placeholder="Стоимость">
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary mt-2" onclick="addLabor(0)">Добавить трудозатрату</button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary mt-2" onclick="addContainer()">Добавить тару</button>
            </div>
            <?php endif; ?>
            <button type="submit" name="<?= $edit_prod ? 'edit_product' : 'add_product' ?>" class="btn btn-primary"><?= $edit_prod ? 'Сохранить изменения' : 'Добавить продукт' ?></button>
        </form>
    </div>
</div>

<script>
let recCount = <?= $edit_prod && $edit_recipes ? count($edit_recipes) : 1 ?>;
function addRecipe() {
    const div = document.createElement('div');
    div.className = 'row mb-2';
    div.innerHTML = `
        <div class="col">
            <select class="form-select" name="recipes[${recCount}][component_id]">
                <?= implode('', array_map(fn($c) => "<option value=\"{$c['id']}\">{$c['name']}</option>", $components)) ?>
            </select>
        </div>
        <div class="col">
            <input type="number" step="0.01" class="form-control" name="recipes[${recCount}][amount]" placeholder="Количество">
        </div>
        <div class="col">
            <select class="form-select" name="recipes[${recCount}][amount_type]">
                <option value="absolute">кг/литр</option>
                <option value="percent">%</option>
            </select>
        </div>
    `;
    document.getElementById('recipes').appendChild(div);
    recCount++;
}

let contCount = 1;
function addContainer() {
    const div = document.createElement('div');
    div.className = 'mb-4';
    div.innerHTML = `
        <div class="row mb-2">
            <div class="col">
                <input type="text" class="form-control" name="containers[${contCount}][type]" placeholder="Тип тары">
            </div>
            <div class="col">
                <input type="number" step="0.01" class="form-control" name="containers[${contCount}][cost]" placeholder="Стоимость тары">
            </div>
        </div>
        <div class="mt-2">
            <label class="form-label">Трудозатраты (необязательно)</label>
            <div id="labors_${contCount}">
                <div class="row mb-2">
                    <div class="col">
                        <input type="text" class="form-control" name="containers[${contCount}][labors][0][name]" placeholder="Название (Труд, Этикетка и т.д.)">
                    </div>
                    <div class="col">
                        <input type="number" step="0.01" class="form-control" name="containers[${contCount}][labors][0][cost]" placeholder="Стоимость">
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-secondary mt-2" onclick="addLabor(${contCount})">Добавить трудозатрату</button>
        </div>
    `;
    document.getElementById('containers').appendChild(div);
    contCount++;
}

let laborCounts = Array(contCount).fill(1);
function addLabor(contIndex) {
    const div = document.createElement('div');
    div.className = 'row mb-2';
    div.innerHTML = `
        <div class="col">
            <input type="text" class="form-control" name="containers[${contIndex}][labors][${laborCounts[contIndex]}][name]" placeholder="Название (Труд, Этикетка и т.д.)">
        </div>
        <div class="col">
            <input type="number" step="0.01" class="form-control" name="containers[${contIndex}][labors][${laborCounts[contIndex]}][cost]" placeholder="Стоимость">
        </div>
    `;
    document.getElementById(`labors_${contIndex}`).appendChild(div);
    laborCounts[contIndex]++;
}

function toggleVolumeInput(select) {
    const input = document.getElementById('target_volume_input');
    if (select.value === 'percent') {
        input.style.display = 'none';
        input.value = '';
    } else {
        input.style.display = 'block';
    }
}
</script>

<h2>Список продуктов</h2>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Объём расчёта</th>
                <th>Рецептура</th>
                <th>Расчёт по сырью</th>
                <th>Варианты тары</th>
                <th>Себестоимость по сырью</th>
                <th>Себестоимость с тарой</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $prod): ?>
            <tr>
                <td><?= $prod['id'] ?></td>
                <td><?= $prod['name'] ?></td>
                <td><?= $prod['target_volume'] === 'percent' ? 'Процентный расчёт' : number_format($prod['target_volume'], 2, ',', ' ') . ' литров' ?></td>
                <td>
                    <?php
                    $recipes = $pdo->prepare("SELECT r.*, c.name, c.price_per_unit, c.unit FROM recipes r JOIN components c ON r.component_id = c.id WHERE r.product_id = ?");
                    $recipes->execute([$prod['id']]);
                    $recipes_data = $recipes->fetchAll();
                    foreach ($recipes_data as $rec) {
                        echo "<div>{$rec['name']}: " . number_format($rec['amount'], 2, ',', ' ') . " ({$rec['amount_type']})</div>";
                    }
                    ?>
                </td>
                <td>
                    <a href="recipe_details.php?product_id=<?= $prod['id'] ?>" class="btn btn-sm btn-info">Посмотреть расчёт</a>
                </td>
                <td>
                    <?php
                    $containers = $pdo->prepare("SELECT * FROM product_containers WHERE product_id = ?");
                    $containers->execute([$prod['id']]);
                    foreach ($containers->fetchAll() as $cont) {
                        $total_cont_cost = $cont['cost'];
                        $labors_q = $pdo->prepare("SELECT * FROM product_container_labors WHERE container_id = ?");
                        $labors_q->execute([$cont['id']]);
                        $labor_details = '';
                        foreach ($labors_q->fetchAll() as $labor) {
                            $total_cont_cost += $labor['cost'];
                            $labor_details .= "<div>{$labor['name']}: " . number_format($labor['cost'], 2, ',', ' ') . " руб.</div>";
                        }
                        echo "<div>{$cont['type']}: тара " . number_format($cont['cost'], 2, ',', ' ') . " + труд " . number_format($total_cont_cost - $cont['cost'], 2, ',', ' ') . " = " . number_format($total_cont_cost, 2, ',', ' ') . " руб. Трудозатраты: {$labor_details}</div>";
                    }
                    ?>
                </td>
                <td>
                    <?php
                    $cost_data = calculateProductCost($pdo, $prod['id']);
                    $raw_cost = $cost_data['cost'];
                    echo number_format($raw_cost, 2, ',', ' ') . " руб./литр";
                    ?>
                </td>
                <td>Рассчитывается по таре</td>
                <td>
                    <a href="products.php?edit=<?= $prod['id'] ?>" class="btn btn-sm btn-warning">Редактировать</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>