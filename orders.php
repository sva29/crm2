<?php
// orders.php - Страница для заявок
ob_start(); // Начинаем буферизацию вывода
session_start(); // Сессия в начале
include 'config.php';
include 'cost_calculator.php';

// Обработка ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Функция логирования
function logMessage($message) {
    $logFile = __DIR__ . '/orders_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Очистка старых данных сессии перед новой заявкой
if (isset($_POST['preview_order'])) {
    unset($_SESSION['order_preview']); // Очищаем предыдущий предварительный просмотр
    $_SESSION['order_preview'] = $_POST['items'];
    logMessage("Saved new items to session for preview: " . json_encode($_POST['items']));
    header("Location: orders.php?preview=1");
    exit;
}

// Обработка обновления предварительного просмотра
if (isset($_POST['update_preview'])) {
    $components = $_POST['components'] ?? [];
    logMessage("Updating preview with components: " . json_encode($components));
    foreach ($_SESSION['order_preview'] as &$item) {
        $item_id = $item['item_id'];
        // Проверяем, является ли item_id продуктом
        $stmt = $pdo->prepare("SELECT id as product_id, name as product_name FROM products WHERE id = ?");
        $stmt->execute([$item_id]);
        $product = $stmt->fetch();
        if ($product) {
            // Это продукт
            $selected_item = ['product_id' => $product['product_id'], 'product_name' => $product['product_name'], 'component_id' => null, 'is_container' => 0];
            logMessage("Update preview: item_id=$item_id, selected_item=" . json_encode($selected_item));
            $cost_data = calculateProductCost($pdo, $selected_item['product_id'], floatval($item['quantity']), 'logMessage');
            foreach ($components as $comp_id => $data) {
                if (isset($cost_data['components'][$comp_id])) {
                    $item['quantity'] = floatval($data['quantity']) / $cost_data['components'][$comp_id]['amount'];
                    logMessage("Updated product item_id=$item_id for component_id=$comp_id: new quantity=" . $item['quantity']);
                }
            }
        } else {
            // Проверяем, является ли item_id тарой
            $stmt = $pdo->prepare("SELECT id as component_id, name as component_name, is_container, price_per_unit FROM components WHERE id = ? AND is_container = 1");
            $stmt->execute([$item_id]);
            $component = $stmt->fetch();
            if ($component) {
                // Это тара
                $selected_item = ['product_id' => null, 'component_id' => $component['component_id'], 'component_name' => $component['component_name'], 'is_container' => 1, 'price_per_unit' => $component['price_per_unit']];
                logMessage("Update preview: item_id=$item_id, selected_item=" . json_encode($selected_item));
                foreach ($components as $comp_id => $data) {
                    if ($selected_item['component_id'] == $comp_id) {
                        $item['quantity'] = floatval($data['quantity']);
                        logMessage("Updated container component_id=$comp_id for item_id=$item_id: quantity=" . $data['quantity']);
                    }
                }
            } else {
                logMessage("Update preview: item_id=$item_id not found as product or container");
            }
        }
    }
    // Сохраняем выбранные фасовки в сессии
    $_SESSION['selected_packagings'] = $components ? array_column($components, 'packaging_id', 'component_id') : [];
    header("Location: orders.php?preview=1");
    exit;
}

// Обработка подтверждения заявки
if (isset($_POST['confirm_order'])) {
    try {
        logMessage("Starting order creation");
        $stmt = $pdo->prepare("INSERT INTO purchase_orders (total_cost) VALUES (0)");
        $stmt->execute();
        $order_id = $pdo->lastInsertId();
        logMessage("Created order ID: $order_id");

        $total_cost = 0;
        $used_components = [];
        $warnings = [];

        $items = $_SESSION['order_preview'] ?? [];
        foreach ($items as $index => $item) {
            if (!empty($item['item_id']) && !empty($item['quantity']) && $item['quantity'] > 0) {
                $item_id = $item['item_id'];
                $quantity = floatval($item['quantity']);
                logMessage("Processing item $index: item_id=$item_id, quantity=$quantity");

                // Проверяем, продукт это или тара
                $stmt = $pdo->prepare("SELECT id as product_id, name as product_name FROM products WHERE id = ?");
                $stmt->execute([$item_id]);
                $product = $stmt->fetch();
                if ($product) {
                    // Это продукт
                    $selected_item = ['product_id' => $product['product_id'], 'product_name' => $product['product_name'], 'component_id' => null, 'is_container' => 0];
                    logMessage("Confirm order: item_id=$item_id, selected_item=" . json_encode($selected_item));
                    $product_id = $selected_item['product_id'];
                    $container_component_id = null;

                    // Проверка существования продукта
                    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
                    $stmt->execute([$product_id]);
                    if (!$stmt->fetch()) {
                        $warnings[] = "Продукт с ID $product_id не существует в таблице products.";
                        continue;
                    }

                    // Добавление позиции (продукт)
                    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, container_component_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$order_id, $product_id, $quantity, $container_component_id]);
                    logMessage("Product item: product_id=$product_id, name={$selected_item['product_name']}, quantity=$quantity");

                    // Расчёт сырья для продукта
                    $cost_data = calculateProductCost($pdo, $product_id, $quantity, 'logMessage');
                    logMessage("Components for product_id=$product_id: " . json_encode($cost_data['components']));
                    foreach ($cost_data['components'] as $comp_id => $comp_data) {
                        if ($comp_data['price_per_unit'] > 0) {
                            $comp_quantity = $comp_data['amount']; // Количество раствора
                            $stmt = $pdo->prepare("SELECT name, price_per_unit FROM components WHERE id = ? AND is_container = 0");
                            $stmt->execute([$comp_id]);
                            $comp_info = $stmt->fetch();
                            if (!$comp_info) {
                                $warnings[] = "Компонент с ID $comp_id не существует или является тарой.";
                                continue;
                            }
                            if (!isset($used_components[$comp_id])) {
                                $used_components[$comp_id] = [
                                    'name' => $comp_info['name'],
                                    'quantity' => $comp_quantity,
                                    'price_per_unit' => $comp_info['price_per_unit'],
                                    'unit' => $comp_data['unit'],
                                    'is_container' => false
                                ];
                            } else {
                                $used_components[$comp_id]['quantity'] += $comp_quantity;
                            }
                            $total_cost += $comp_info['price_per_unit'] * $comp_quantity;
                            logMessage("Added component_id=$comp_id for product_id=$product_id: quantity=$comp_quantity, price_per_unit={$comp_info['price_per_unit']}");
                        }
                    }
                    $warnings = array_merge($warnings, $cost_data['warnings']);
                } else {
                    // Проверяем, является ли item_id тарой
                    $stmt = $pdo->prepare("SELECT id as component_id, name as component_name, is_container, price_per_unit FROM components WHERE id = ? AND is_container = 1");
                    $stmt->execute([$item_id]);
                    $component = $stmt->fetch();
                    if ($component) {
                        // Это тара
                        $selected_item = ['product_id' => null, 'component_id' => $component['component_id'], 'component_name' => $component['component_name'], 'is_container' => 1, 'price_per_unit' => $component['price_per_unit']];
                        logMessage("Confirm order: item_id=$item_id, selected_item=" . json_encode($selected_item));
                        $container_component_id = $selected_item['component_id'];
                        $product_id = null;
                        $item_cost = ($selected_item['price_per_unit'] ?? 0) * $quantity;
                        logMessage("Container item: component_id=$container_component_id, name={$selected_item['component_name']}, price_per_unit={$selected_item['price_per_unit']}, cost=$item_cost");

                        if ($selected_item['price_per_unit'] === null) {
                            $warnings[] = "Тара '{$selected_item['component_name']}' (ID $container_component_id) имеет нулевую цену.";
                        }

                        // Проверка существования component_id
                        $stmt = $pdo->prepare("SELECT id FROM components WHERE id = ?");
                        $stmt->execute([$container_component_id]);
                        if (!$stmt->fetch()) {
                            $warnings[] = "Тара с ID $container_component_id не существует в таблице components.";
                            continue;
                        }

                        // Добавление позиции (тара)
                        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, container_component_id) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$order_id, $product_id, $quantity, $container_component_id]);

                        if (!isset($used_components[$container_component_id]) && $selected_item['price_per_unit'] !== null) {
                            $used_components[$container_component_id] = [
                                'name' => $selected_item['component_name'],
                                'quantity' => $quantity,
                                'price_per_unit' => $selected_item['price_per_unit'],
                                'unit' => 'piece',
                                'is_container' => true
                            ];
                        } else {
                            $used_components[$container_component_id]['quantity'] += $quantity;
                        }
                        $total_cost += $item_cost;
                    } else {
                        $warnings[] = "Элемент с ID $item_id не найден ни среди продуктов, ни среди тары.";
                    }
                }
            }
        }

        // Сохранение цен компонентов
        foreach ($used_components as $comp_id => $data) {
            $stmt = $pdo->prepare("SELECT id FROM components WHERE id = ?");
            $stmt->execute([$comp_id]);
            if ($stmt->fetch() && $data['quantity'] > 0 && $data['price_per_unit'] !== null) {
                $stmt = $pdo->prepare("INSERT INTO order_component_prices (order_id, component_id, price_per_unit, quantity) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $comp_id, $data['price_per_unit'], $data['quantity'] ?? 0]);
                logMessage("Saved component price: component_id=$comp_id, price_per_unit={$data['price_per_unit']}, quantity={$data['quantity']}");
            } else {
                $warnings[] = "Пропущено сохранение цены для component_id=$comp_id: компонент не существует или данные некорректны (quantity=" . ($data['quantity'] ?? 'null') . ", price_per_unit=" . ($data['price_per_unit'] ?? 'null') . ").";
            }
        }
        logMessage("Saved component prices");

        // Обновление общей стоимости
        $stmt = $pdo->prepare("UPDATE purchase_orders SET total_cost = ? WHERE id = ?");
        $stmt->execute([$total_cost, $order_id]);
        logMessage("Updated total_cost: $total_cost");

        // Сохранение предупреждений
        if (!empty($warnings)) {
            $_SESSION['warnings'] = $warnings;
        }

        // Очистка предварительного просмотра и фасовок
        unset($_SESSION['order_preview']);
        unset($_SESSION['selected_packagings']);

        // Редирект
        header("Location: orders.php?success=1");
        exit;
    } catch (PDOException $e) {
        $error_message = "Ошибка при создании заявки: " . $e->getMessage();
        logMessage("Error: $error_message");
    }
}

// Загружаем данные (продукты и тара)
$items = $pdo->query("SELECT p.id as item_id, p.name, 'product' as type FROM products p
    UNION
    SELECT c.id as item_id, c.name, 'container' as type FROM components c WHERE c.is_container = 1
    ORDER BY CASE WHEN type = 'product' THEN 1 ELSE 2 END, name ASC")->fetchAll();

$orders = $pdo->query("SELECT * FROM purchase_orders ORDER BY id DESC")->fetchAll();

// Проверка предупреждений
$warnings = isset($_SESSION['warnings']) ? $_SESSION['warnings'] : [];
unset($_SESSION['warnings']);

ob_end_flush(); // Завершаем буферизацию перед выводом
include 'header.php';
?>

<h1>Заявка на закупку</h1>
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Заявка успешно создана!</div>
<?php elseif (isset($error_message)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
<?php endif; ?>

<?php if (!empty($warnings)): ?>
    <div class="alert alert-warning">
        <strong>Предупреждения:</strong>
        <ul>
            <?php foreach ($warnings as $warning): ?>
                <li><?= htmlspecialchars($warning) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (empty($items)): ?>
    <div class="alert alert-warning">Нет доступных товаров. Добавьте продукты в разделе <a href="products.php">Готовая продукция</a> или тару в разделе <a href="components.php">Компоненты</a> (поставьте галочку "Это тара").</div>
<?php endif; ?>

<?php if (isset($_GET['preview']) && !empty($_SESSION['order_preview'])): ?>
    <h2>Предварительный расчёт закупки</h2>
    <form method="post">
        <input type="hidden" name="update_preview" value="1">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Элемент</th>
                        <th>Количество</th>
                        <th>Цена за единицу (руб.)</th>
                        <th>Общая стоимость (руб.)</th>
                        <th>Фасовка</th>
                        <th>До полной фасовки</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $used_components = []; // Очистка used_components перед расчётом
                    $total_cost = 0;
                    $selected_packagings = $_SESSION['selected_packagings'] ?? [];
                    logMessage("Starting preview calculation with order_preview: " . json_encode($_SESSION['order_preview']));
                    foreach ($_SESSION['order_preview'] as $index => $item) {
                        if (!empty($item['item_id']) && !empty($item['quantity']) && $item['quantity'] > 0) {
                            $item_id = $item['item_id'];
                            $quantity = floatval($item['quantity']);
                            $_SESSION['order_preview'][$index]['quantity'] = $quantity;
                            logMessage("Preview item $index: item_id=$item_id, quantity=$quantity");

                            // Проверяем, продукт это или тара
                            $stmt = $pdo->prepare("SELECT id as product_id, name as product_name FROM products WHERE id = ?");
                            $stmt->execute([$item_id]);
                            $product = $stmt->fetch();
                            if ($product) {
                                // Это продукт
                                $selected_item = ['product_id' => $product['product_id'], 'product_name' => $product['product_name'], 'component_id' => null, 'is_container' => 0, 'price_per_unit' => null, 'component_name' => null];
                                logMessage("Preview: item_id=$item_id, selected_item=" . json_encode($selected_item));
                                $cost_data = calculateProductCost($pdo, $selected_item['product_id'], $quantity, 'logMessage');
                                logMessage("Components for product_id={$selected_item['product_id']}: " . json_encode($cost_data['components']));
                                foreach ($cost_data['components'] as $comp_id => $comp_data) {
                                    if ($comp_data['price_per_unit'] > 0) {
                                        $comp_quantity = $comp_data['amount']; // Количество раствора
                                        $stmt = $pdo->prepare("SELECT name, price_per_unit, unit FROM components WHERE id = ? AND is_container = 0");
                                        $stmt->execute([$comp_id]);
                                        $comp_info = $stmt->fetch();
                                        if ($comp_info) {
                                            if (!isset($used_components[$comp_id])) {
                                                $used_components[$comp_id] = [
                                                    'name' => $comp_info['name'] ?? 'Без названия',
                                                    'quantity' => $comp_quantity,
                                                    'price_per_unit' => $comp_info['price_per_unit'] ?? 0,
                                                    'unit' => $comp_info['unit'],
                                                    'is_container' => false
                                                ];
                                            } else {
                                                $used_components[$comp_id]['quantity'] += $comp_quantity;
                                            }
                                            $total_cost += $comp_info['price_per_unit'] * $comp_quantity;
                                            logMessage("Added component_id=$comp_id for product_id={$selected_item['product_id']}: quantity=$comp_quantity, price_per_unit={$comp_info['price_per_unit']}");
                                        }
                                    }
                                }
                                $warnings = array_merge($warnings, $cost_data['warnings']);
                            } else {
                                // Проверяем, является ли item_id тарой
                                $stmt = $pdo->prepare("SELECT id as component_id, name as component_name, is_container, price_per_unit FROM components WHERE id = ? AND is_container = 1");
                                $stmt->execute([$item_id]);
                                $component = $stmt->fetch();
                                if ($component) {
                                    // Это тара
                                    $comp_id = $component['component_id'];
                                    if (!isset($used_components[$comp_id])) {
                                        $used_components[$comp_id] = [
                                            'name' => $component['component_name'] ?? 'Без названия',
                                            'quantity' => $quantity,
                                            'price_per_unit' => $component['price_per_unit'] ?? 0,
                                            'unit' => 'piece',
                                            'is_container' => true
                                        ];
                                    } else {
                                        $used_components[$comp_id]['quantity'] += $quantity;
                                    }
                                    $total_cost += ($component['price_per_unit'] ?? 0) * $quantity;
                                    logMessage("Added container component_id=$comp_id: quantity=$quantity, price_per_unit=" . ($component['price_per_unit'] ?? 0));
                                } else {
                                    logMessage("Preview: item_id=$item_id not found as product or container");
                                }
                            }
                        }
                    }
                    logMessage("Final used_components: " . json_encode($used_components));

                    foreach ($used_components as $comp_id => $data) {
                        // Получаем фасовки для компонента
                        $stmt = $pdo->prepare("SELECT id, packaging_type, volume FROM component_packagings WHERE component_id = ?");
                        $stmt->execute([$comp_id]);
                        $packagings = $stmt->fetchAll();
                        $selected_packaging_id = $selected_packagings[$comp_id] ?? ($packagings ? $packagings[0]['id'] : null);
                        $selected_volume = null;
                        $selected_packaging_type = '-';
                        if ($selected_packaging_id) {
                            foreach ($packagings as $pkg) {
                                if ($pkg['id'] == $selected_packaging_id) {
                                    $selected_volume = $pkg['volume'];
                                    $selected_packaging_type = $pkg['packaging_type'] . " (" . number_format($pkg['volume'], 2, ',', ' ') . " " . ($data['unit'] == 'kg' ? 'кг' : 'л') . ")";
                                    break;
                                }
                            }
                        }
                        $full_packages = ($selected_volume && !$data['is_container']) ? floor($data['quantity'] / $selected_volume) : 0;
                        $remaining_volume = ($selected_volume && !$data['is_container']) ? ($full_packages + 1) * $selected_volume - $data['quantity'] : 0;
                        $total_component_cost = $data['price_per_unit'] * $data['quantity'];
                        $unit = $data['is_container'] ? 'шт' : ($data['unit'] == 'kg' ? 'кг' : 'л');
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($data['name']) ?> <?= $data['is_container'] ? '(Тара)' : '(Сырье)' ?></td>
                        <td>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="components[<?= $comp_id ?>][quantity]" value="<?= number_format($data['quantity'], 2, '.', '') ?>" style="width: 120px;" onchange="updatePackages(<?= $comp_id ?>, this.value, <?= $selected_volume ?: 0 ?>, <?= $data['is_container'] ? 'true' : 'false' ?>)">
                        </td>
                        <td><?= number_format($data['price_per_unit'], 2, ',', ' ') ?></td>
                        <td><?= number_format($total_component_cost, 2, ',', ' ') ?></td>
                        <td>
                            <?php if (!$data['is_container'] && $packagings): ?>
                                <select name="components[<?= $comp_id ?>][packaging_id]" onchange="this.form.submit()">
                                    <?php foreach ($packagings as $pkg): ?>
                                        <option value="<?= $pkg['id'] ?>" <?= $pkg['id'] == $selected_packaging_id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($pkg['packaging_type'] . " (" . number_format($pkg['volume'], 2, ',', ' ') . " " . ($data['unit'] == 'kg' ? 'кг' : 'л') . ")") ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= ($remaining_volume > 0 && !$data['is_container']) ? number_format($remaining_volume, 2, ',', ' ') . ' ' . ($data['unit'] == 'kg' ? 'кг' : 'л') : '-' ?></td>
                        <td>
                            <?php if ($remaining_volume > 0 && !$data['is_container'] && $selected_volume): ?>
                                <button type="button" class="btn btn-sm btn-warning" onclick="roundToContainer(<?= $comp_id ?>, <?= ($full_packages + 1) * $selected_volume ?>, <?= $selected_volume ?>, <?= $data['is_container'] ? 'true' : 'false' ?>)">
                                    Округлить до <?= strtolower($selected_packaging_type) ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="mb-3">
            <strong>Общая стоимость: <?= number_format($total_cost, 2, ',', ' ') ?> руб.</strong>
        </div>
        <button type="submit" name="update_preview" class="btn btn-secondary">Обновить</button>
        <button type="submit" name="confirm_order" form="confirm_form" class="btn btn-primary">Подтвердить</button>
        <a href="orders.php" class="btn btn-secondary">Отменить</a>
    </form>
    <form id="confirm_form" method="post">
        <input type="hidden" name="confirm_order" value="1">
    </form>

    <script>
    function roundToContainer(compId, targetQuantity, containerVolume, isContainer) {
        if (!isContainer) {
            document.querySelector(`input[name="components[${compId}][quantity]"]`).value = targetQuantity.toFixed(2);
            document.forms[0].submit();
        }
    }

    function updatePackages(compId, quantity, containerVolume, isContainer) {
        if (!isContainer) {
            document.forms[0].submit();
        }
    }
    </script>
<?php else: ?>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Создать заявку</h5>
            <form method="post">
                <input type="hidden" name="preview_order" value="1">
                <div class="mb-3">
                    <label class="form-label">Позиции</label>
                    <div id="items">
                        <div class="row mb-2 item-row">
                            <div class="col">
                                <select class="form-select" name="items[0][item_id]" required>
                                    <option value="">Выберите продукт или тару</option>
                                    <?php foreach ($items as $it): ?>
                                        <option value="<?= $it['item_id'] ?>"><?= htmlspecialchars($it['name'] ?? 'Без названия') ?> (<?= $it['type'] == 'product' ? 'Продукт' : 'Тара' ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col">
                                <input type="number" step="0.01" min="0.01" class="form-control" name="items[0][quantity]" placeholder="Количество (л или шт)" required>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)">Удалить</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary mt-2" onclick="addItem()">Добавить позицию</button>
                </div>
                <button type="submit" class="btn btn-primary">Сформировать заявку</button>
            </form>
        </div>
    </div>

    <script>
    let itemCount = 1;
    function addItem() {
        const div = document.createElement('div');
        div.className = 'row mb-2 item-row';
        div.innerHTML = `
            <div class="col">
                <select class="form-select" name="items[${itemCount}][item_id]" required>
                    <option value="">Выберите продукт или тару</option>
                    <?php foreach ($items as $it): ?>
                        <option value="<?= $it['item_id'] ?>"><?= htmlspecialchars($it['name'] ?? 'Без названия') ?> (<?= $it['type'] == 'product' ? 'Продукт' : 'Тара' ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col">
                <input type="number" step="0.01" min="0.01" class="form-control" name="items[${itemCount}][quantity]" placeholder="Количество (л или шт)" required>
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)">Удалить</button>
            </div>
        `;
        document.getElementById('items').appendChild(div);
        itemCount++;
    }

    function removeItem(button) {
        const items = document.querySelectorAll('.item-row');
        if (items.length > 1) {
            button.closest('.item-row').remove();
            const remainingItems = document.querySelectorAll('.item-row');
            remainingItems.forEach((item, index) => {
                item.querySelector('select[name*="[item_id]"]').name = `items[${index}][item_id]`;
                item.querySelector('input[name*="[quantity]"]').name = `items[${index}][quantity]`;
            });
            itemCount = remainingItems.length;
        } else {
            alert('Нельзя удалить последнюю позицию. Должна остаться хотя бы одна.');
        }
    }
    </script>
<?php endif; ?>

<h2>Список заявок</h2>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Дата</th>
                <th>Общая стоимость</th>
                <th>Детали</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= htmlspecialchars($order['id'] ?? '-') ?></td>
                <td><?= htmlspecialchars($order['date'] ?? '-') ?></td>
                <td><?= number_format($order['total_cost'] ?? 0, 2, ',', ' ') ?> руб.</td>
                <td>
                    <?php
                    $items = $pdo->prepare("SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                    $items->execute([$order['id']]);
                    foreach ($items->fetchAll() as $item) {
                        $product_name = $item['product_name'] ?? ($item['product_id'] ? 'Удалённый продукт (ID: ' . $item['product_id'] . ')' : 'Тара');
                        $quantity = $item['quantity'] ?? 0;
                        $unit = $item['container_component_id'] ? 'шт' : 'л';
                        echo "<div><strong>" . htmlspecialchars($product_name) . "</strong>: " . number_format($quantity, 2, ',', ' ') . " $unit</div>";
                        if (!$item['product_name'] && $item['product_id']) {
                            logMessage("Missing product_name for product_id={$item['product_id']} in order_id={$order['id']}");
                        }
                        if ($item['quantity'] === null) {
                            logMessage("Missing quantity for product_id={$item['product_id']} in order_id={$order['id']}");
                        }
                    }
                    $components = $pdo->prepare("
                        SELECT ocp.*, c.name as component_name, c.unit, c.is_container, cp.packaging_type, cp.volume
                        FROM order_component_prices ocp
                        LEFT JOIN components c ON ocp.component_id = c.id
                        LEFT JOIN component_packagings cp ON c.id = cp.component_id
                        WHERE ocp.order_id = ?
                    ");
                    $components->execute([$order['id']]);
                    foreach ($components->fetchAll() as $comp) {
                        $component_name = $comp['component_name'] ?? 'Удалённый компонент (ID: ' . $comp['component_id'] . ')';
                        $quantity = $comp['quantity'] ?? 0;
                        $price_per_unit = $comp['price_per_unit'] ?? 0;
                        $unit = $comp['is_container'] ? 'шт' : ($comp['unit'] == 'kg' ? 'кг' : 'л');
                        $volume_info = ($comp['volume'] && !$comp['is_container']) ? " (" . htmlspecialchars($comp['packaging_type']) . " " . number_format($comp['volume'], 2, ',', ' ') . " $unit)" : '';
                        echo "<div><strong>" . htmlspecialchars($component_name) . "</strong>: " . number_format($quantity, 2, ',', ' ') . " $unit, " . number_format($price_per_unit, 2, ',', ' ') . " руб./$unit{$volume_info}</div>";
                        if (!$comp['component_name']) {
                            logMessage("Missing component_name for component_id={$comp['component_id']} in order_id={$order['id']}");
                        }
                        if ($comp['quantity'] === null || $comp['price_per_unit'] === null) {
                            logMessage("Invalid data for component_id={$comp['component_id']} in order_id={$order['id']}: quantity=" . ($comp['quantity'] ?? 'null') . ", price_per_unit=" . ($comp['price_per_unit'] ?? 'null'));
                        }
                    }
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>