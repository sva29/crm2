<?php
$apiKey = "eyJhbGciOiJFUzI1NiIsImtpZCI6IjIwMjUwOTA0djEiLCJ0eXAiOiJKV1QifQ.eyJlbnQiOjEsImV4cCI6MTc3NDA4MzU4OSwiaWQiOiIwMTk5NjNjNi1hZGQ4LTc5NjgtYmM3My05NmVjYThlYmUyYzkiLCJpaWQiOjE0OTEyMTMwLCJvaWQiOjE0MDUwNzksInMiOjE2MTI2LCJzaWQiOiIxZDBhODM5NS02Yjc5LTRjN2MtYTg4OS04NWU5MmMxMGE2MzAiLCJ0IjpmYWxzZSwidWlkIjoxNDkxMjEzMH0.iKvj-NNMDQFsprWHuzfIHWWO2P9VlWQCDt1bLzgwkkuFB0IrcEblEngfCbEU6Pa7L-v_WCQhwNLPHCGIxnfblQ";

$response = "";
$httpCode = null;
$salesTable = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1️⃣ Получаем карточки товаров
    $ch = curl_init("https://marketplace-api.wildberries.ru/api/v3/cards/list");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]);

    $allCards = [];
    $next = null;
    do {
        $body = ["settings" => ["cursor" => ["limit" => 100], "filter" => (object)[]]];
        if ($next) {
            $body["settings"]["cursor"]["updatedAt"] = $next["updatedAt"];
            $body["settings"]["cursor"]["nmID"] = $next["nmID"];
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $json = json_decode($resp, true);
        if (isset($json["cards"])) {
            $allCards = array_merge($allCards, $json["cards"]);
        }
        $next = $json["cursor"] ?? null;
    } while ($next && isset($next["nmID"]));
    curl_close($ch);

    $response .= "Получено карточек: " . count($allCards) . "<br>";

    // Создаем карту nmID => title
    $cardMap = [];
    foreach ($allCards as $card) {
        $cardMap[$card['nmID']] = $card['title'];
    }

    // 2️⃣ Получаем статистику заказов
    $dateFrom = date("Y-m-d\TH:i:s", strtotime("-3 months")); // последние 3 месяца
    $dateTo = date("Y-m-d\TH:i:s");
    $urlOrders = "https://statistics-api.wildberries.ru/api/v1/supplier/orders?dateFrom=$dateFrom&dateTo=$dateTo&take=1000&skip=0";

    $ch = curl_init($urlOrders);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]);

    $respOrders = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ordersData = json_decode($respOrders, true);
    curl_close($ch);

    if (isset($ordersData['orders'])) {
        // 3️⃣ Суммируем продажи по nmID
        $sales = [];
        foreach ($ordersData['orders'] as $order) {
            $nmID = $order['nmID'];
            $sales[$nmID]['title'] = $cardMap[$nmID] ?? "Не найдено";
            $sales[$nmID]['sold'] = ($sales[$nmID]['sold'] ?? 0) + $order['quantity'];
        }
        $salesTable = $sales;
        $response .= "Получено заказов: " . count($ordersData['orders']) . "<br>";
    } else {
        $response .= "Ошибка при получении статистики";
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <title>WB API — статистика товаров</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h3 class="mb-4">Wildberries API — статистика по товарам</h3>

  <form method="post" class="mb-4">
    <button class="btn btn-primary">Получить статистику по товарам</button>
  </form>

  <?php if ($response): ?>
    <div class="alert alert-info"><?= $response ?></div>
  <?php endif; ?>

  <?php if ($salesTable): ?>
    <div class="table-responsive" style="max-height:600px; overflow:auto;">
      <table class="table table-striped table-sm">
        <thead>
          <tr>
            <th>nmID</th>
            <th>Название</th>
            <th>Продано</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($salesTable as $nmID => $info): ?>
            <tr>
              <td><?= $nmID ?></td>
              <td><?= htmlspecialchars($info['title']) ?></td>
              <td><?= $info['sold'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
