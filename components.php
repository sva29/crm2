<?php
// components.php - Страница для компонентов
include 'config.php';
include 'header.php';

// Добавление компонента
if (isset($_POST['add_component'])) {
    $name = $_POST['name'];
    $price_per_unit = $_POST['price_per_unit'];
    $unit = $_POST['unit'];
    $is_container = isset($_POST['is_container']) ? 1 : 0;
    $volume = $is_container && !empty($_POST['volume']) ? $_POST['volume'] : null;
    $stmt = $pdo->prepare("INSERT INTO components (name, price_per_unit, unit, is_container, volume) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $price_per_unit, $unit, $is_container, $volume]);

    $component_id = $pdo->lastInsertId();

    // Добавление фасовок (только для сырья)
    if (!$is_container && !empty($_POST['packagings'])) {
        foreach ($_POST['packagings'] as $pkg) {
            if (!empty($pkg['type']) && !empty($pkg['volume'])) {
                $price_per_package = $price_per_unit * $pkg['volume'];
                $stmt = $pdo->prepare("INSERT INTO component_packagings (component_id, type, volume, price_per_package) VALUES (?, ?, ?, ?)");
                $stmt->execute([$component_id, $pkg['type'], $pkg['volume'], $price_per_package]);
            }
        }
    }
}

// Редактирование компонента
if (isset($_POST['edit_component'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price_per_unit = $_POST['price_per_unit'];
    $unit = $_POST['unit'];
    $is_container = isset($_POST['is_container']) ? 1 : 0;
    $volume = $is_container && !empty($_POST['volume']) ? $_POST['volume'] : null;
    $stmt = $pdo->prepare("UPDATE components SET name = ?, price_per_unit = ?, unit = ?, is_container = ?, volume = ? WHERE id = ?");
    $stmt->execute([$name, $price_per_unit, $unit, $is_container, $volume, $id]);

    // Удаляем существующие фасовки
    $stmt = $pdo->prepare("DELETE FROM component_packagings WHERE component_id = ?");
    $stmt->execute([$id]);

    // Добавляем новые фасовки (только если не тара)
    if (!$is_container && !empty($_POST['packagings'])) {
        foreach ($_POST['packagings'] as $pkg) {
            if (!empty($pkg['type']) && !empty($pkg['volume'])) {
                $price_per_package = $price_per_unit * $pkg['volume'];
                $stmt = $pdo->prepare("INSERT INTO component_packagings (component_id, type, volume, price_per_package) VALUES (?, ?, ?, ?)");
                $stmt->execute([$id, $pkg['type'], $pkg['volume'], $price_per_package]);
            }
        }
    }
}

// Список компонентов
$components = $pdo->query("SELECT * FROM components")->fetchAll();

// Если выбран компонент для редактирования
$edit_comp = null;
$edit_packagings = [];
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM components WHERE id = ?");
    $stmt->execute([$id]);
    $edit_comp = $stmt->fetch();

    // Получаем текущие фасовки (только для сырья)
    if (!$edit_comp['is_container']) {
        $stmt = $pdo->prepare("SELECT * FROM component_packagings WHERE component_id = ?");
        $stmt->execute([$id]);
        $edit_packagings = $stmt->fetchAll();
    }
}
?>

<h1>Компоненты</h1>
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title"><?= $edit_comp ? 'Редактировать компонент' : 'Добавить компонент' ?></h5>
        <form method="post">
            <?php if ($edit_comp): ?>
                <input type="hidden" name="id" value="<?= $edit_comp['id'] ?>">
            <?php endif; ?>
            <div class="mb-3">
                <label for="name" class="form-label">Название</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= $edit_comp ? $edit_comp['name'] : '' ?>" required>
            </div>
            <div class="mb-3">
                <label for="price_per_unit" class="form-label">Цена за единицу</label>
                <input type="number" step="0.01" min="0" class="form-control" id="price_per_unit" name="price_per_unit" value="<?= $edit_comp ? $edit_comp['price_per_unit'] : '' ?>" required>
            </div>
            <div class="mb-3">
                <label for="unit" class="form-label">Единица</label>
                <select class="form-select" id="unit" name="unit">
                    <option value="kg" <?= $edit_comp && $edit_comp['unit'] == 'kg' ? 'selected' : '' ?>>кг</option>
                    <option value="liter" <?= $edit_comp && $edit_comp['unit'] == 'liter' ? 'selected' : '' ?>>литр</option>
                    <option value="piece" <?= $edit_comp && $edit_comp['unit'] == 'piece' ? 'selected' : '' ?>>штука</option>
                </select>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_container" name="is_container" <?= $edit_comp && $edit_comp['is_container'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_container">Это тара (закупается независимо)</label>
            </div>
            <div class="mb-3" id="volume_section" style="<?= $edit_comp && $edit_comp['is_container'] ? '' : 'display:none;' ?>">
                <label for="volume" class="form-label">Объём тары (литры)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="volume" name="volume" value="<?= $edit_comp && $edit_comp['is_container'] ? $edit_comp['volume'] : '' ?>" placeholder="Например, 5 или 10">
            </div>
            <div id="packagings_section" style="<?= $edit_comp && $edit_comp['is_container'] ? 'display:none;' : '' ?>">
                <label class="form-label">Фасовки (необязательно, только для сырья)</label>
                <div id="packagings">
                    <?php if ($edit_comp && !$edit_comp['is_container'] && $edit_packagings): ?>
                        <?php foreach ($edit_packagings as $index => $pkg): ?>
                            <div class="row mb-2">
                                <div class="col">
                                    <input type="text" class="form-control" name="packagings[<?= $index ?>][type]" value="<?= $pkg['type'] ?>" placeholder="Тип (ведро, бочка)">
                                </div>
                                <div class="col">
                                    <input type="number" step="0.01" min="0" class="form-control" name="packagings[<?= $index ?>][volume]" value="<?= $pkg['volume'] ?>" placeholder="Объем">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif (!$edit_comp || !$edit_comp['is_container']): ?>
                        <div class="row mb-2">
                            <div class="col">
                                <input type="text" class="form-control" name="packagings[0][type]" placeholder="Тип (ведро, бочка)">
                            </div>
                            <div class="col">
                                <input type="number" step="0.01" min="0" class="form-control" name="packagings[0][volume]" placeholder="Объем">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-secondary mt-2" onclick="addPackaging()">Добавить фасовку</button>
            </div>
            <button type="submit" name="<?= $edit_comp ? 'edit_component' : 'add_component' ?>" class="btn btn-primary"><?= $edit_comp ? 'Сохранить изменения' : 'Добавить компонент' ?></button>
        </form>
    </div>
</div>

<script>
let pkgCount = <?= $edit_comp && !$edit_comp['is_container'] && $edit_packagings ? count($edit_packagings) : 1 ?>;
function addPackaging() {
    const div = document.createElement('div');
    div.className = 'row mb-2';
    div.innerHTML = `
        <div class="col">
            <input type="text" class="form-control" name="packagings[${pkgCount}][type]" placeholder="Тип (ведро, бочка)">
        </div>
        <div class="col">
            <input type="number" step="0.01" min="0" class="form-control" name="packagings[${pkgCount}][volume]" placeholder="Объем">
        </div>
    `;
    document.getElementById('packagings').appendChild(div);
    pkgCount++;
}

// Скрываем/показываем фасовки и объём тары при изменении чекбокса
document.getElementById('is_container').addEventListener('change', function() {
    const volumeSection = document.getElementById('volume_section');
    const packagingsSection = document.getElementById('packagings_section');
    if (this.checked) {
        volumeSection.style.display = '';
        packagingsSection.style.display = 'none';
    } else {
        volumeSection.style.display = 'none';
        packagingsSection.style.display = '';
    }
});
</script>

<h2>Список компонентов</h2>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Тип</th>
                <th>Цена за единицу</th>
                <th>Единица</th>
                <th>Объём</th>
                <th>Фасовки</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($components as $comp): ?>
            <tr>
                <td><?= $comp['id'] ?></td>
                <td><?= $comp['name'] ?></td>
                <td><?= $comp['is_container'] ? 'Тара' : 'Сырье' ?></td>
                <td><?= $comp['price_per_unit'] ?> руб.</td>
                <td><?= $comp['unit'] == 'kg' ? 'кг' : ($comp['unit'] == 'liter' ? 'литр' : 'штука') ?></td>
                <td><?= $comp['is_container'] && $comp['volume'] ? $comp['volume'] . ' л' : '-' ?></td>
                <td>
                    <?php if (!$comp['is_container']): ?>
                        <?php
                        $packagings = $pdo->prepare("SELECT * FROM component_packagings WHERE component_id = ?");
                        $packagings->execute([$comp['id']]);
                        $pkgs = $packagings->fetchAll();
                        if ($pkgs) {
                            foreach ($pkgs as $pkg) {
                                echo "<div>{$pkg['type']}: {$pkg['volume']} ({$pkg['price_per_package']} руб.)</div>";
                            }
                        } else {
                            echo "Без фасовок";
                        }
                        ?>
                    <?php else: ?>
                        Н/Д (тара)
                    <?php endif; ?>
                </td>
                <td>
                    <a href="components.php?edit=<?= $comp['id'] ?>" class="btn btn-sm btn-warning">Редактировать</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>