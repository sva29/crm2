<?php
// cost_calculator.php - Расчёт стоимости продукта
function calculateProductCost($pdo, $product_id, $total_volume = 1, $log_function = null) {
    $total_cost = 0;
    $components = [];
    $warnings = [];

    // Получаем base_volume для продукта
    $stmt = $pdo->prepare("SELECT base_volume FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    $base_volume = $product['base_volume'] ?? 1000.00; // По умолчанию 1000 литров
    if ($log_function) {
        $log_function("Base volume for product_id=$product_id: $base_volume");
    }

    // Получаем данные о рецептуре
    $stmt = $pdo->prepare("
        SELECT r.component_id, r.amount, r.amount_type, c.name as component_name, c.price_per_unit, c.unit, c.is_container, c.concentration
        FROM recipes r
        JOIN components c ON r.component_id = c.id
        WHERE r.product_id = ?
    ");
    $stmt->execute([$product_id]);
    $recipes = $stmt->fetchAll();

    if ($log_function) {
        $log_function("Fetching recipe for product_id=$product_id: " . json_encode($recipes));
    }

    foreach ($recipes as $row) {
        $component_id = $row['component_id'];
        $amount = floatval($row['amount']);
        $amount_type = $row['amount_type'];

        // Масштабирование количества
        $scale_factor = $total_volume / $base_volume; // Например, 100 / 1000 = 0.1
        $adjusted_amount = ($amount_type == 'percent') ? $amount / 100 * $total_volume : $amount * $scale_factor;

        $components[$component_id] = [
            'name' => $row['component_name'],
            'amount' => $adjusted_amount, // Количество раствора
            'price_per_unit' => floatval($row['price_per_unit']),
            'unit' => $row['unit'],
            'is_container' => $row['is_container'],
            'concentration' => $row['concentration'],
            'amount_type' => $amount_type
        ];

        if ($row['price_per_unit'] === null) {
            $warnings[] = "Компонент '{$row['component_name']}' (ID $component_id) имеет нулевую цену.";
        }

        if ($log_function) {
            $log_function("Component ID $component_id: amount=$amount, amount_type=$amount_type, scale_factor=$scale_factor, adjusted_amount=$adjusted_amount");
        }
    }

    return [
        'components' => $components,
        'total_cost' => $total_cost,
        'warnings' => $warnings
    ];
}
?>