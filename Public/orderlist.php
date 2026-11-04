<?php

require_once(__DIR__. '\App\check_session.php');

$host = 'localhost';
$database = 'forlagetconvallaria_dk_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $error) {
    error_log($error->getMessage());
    http_response_code(500);
    exit('Kunne ikke oprette forbindelse til databasen.');
}


$status="'trash', 'wc-cancelled'";

if (isset($_POST['include_wc_completed'])) {
    $status .= ", 'wc-completed'";
}

if (isset($_POST['disclude_wc_completed'])) {
    $status .="'trash', 'wc-cancelled'";
}
$sql = "SELECT 
            wco.`id`,
            wco.`date_created_gmt`,
            wco.`billing_email`,
            wco.`status`,
            wco_a.`last_name`,
            wco_a.`first_name`,
            GROUP_CONCAT(
                DISTINCT CONCAT(wco_a.`address_1`, ' ', IFNULL(wco_a.`address_2`, ''), ' ', wco_a.`postcode`, ' ', wco_a.`city`)
                SEPARATOR ', '
            ) AS `Adresse`,
            GROUP_CONCAT(
                DISTINCT CONCAT(wco_pl.`product_id`, ' (', wco_pl.`product_qty`, ' stk.)')
                SEPARATOR ', '
        ) AS `Produkter`
        FROM `clk2c89aed85f_wc_orders` AS wco
        LEFT JOIN `clk2c89aed85f_wc_order_addresses` AS wco_a ON wco.`id` = wco_a.order_id AND wco_a.address_type = 'shipping'
        LEFT JOIN `clk2c89aed85f_wc_order_product_lookup` AS wco_pl ON wco.`id` = wco_pl.order_id
        WHERE `status` NOT IN ($status)
        GROUP BY 
            wco.id,
            wco_a.first_name,
            wco_a.last_name,
            wco_a.address_1
        ORDER BY wco.id DESC";
$orders = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$stocksql="SELECT DISTINCT
            wc_ml.`product_id`,
            wc_ml.`stock_quantity`
        FROM `clk2c89aed85f_wc_product_meta_lookup` AS wc_ml
        ORDER BY wc_ml.product_id DESC";

$stockorders = $conn->query($stocksql)->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_orders'])) {

    $sentOrders = $_POST['sent'] ?? [];

    $ordersql = "UPDATE `clk2c89aed85f_wc_orders`
                 SET `status` = 'wc-completed'
                 WHERE `id` = ?";
        $orderstmt = $conn->prepare($ordersql);

        foreach ($sentOrders as $orderID){  
            $orderstmt->execute([$orderID]);
        }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Alle ordrer</title>
    <link rel="stylesheet" type="text/css" href="App/Style/stylesheet.css">
</head>

<header>
    <section id="navi">
        <nav>
            <ul>
                <li><a href="update_password.php">Opdater passwords</a></li>
                <li><a href="create_user.php">Lav en ny bruger</a></li>
            </ul>
        </nav>
    </section>
</header>
<body>
    <section class="order-lager">
        <div class="order-section">
            <h1>Alle ordrer</h1>
            <form method="POST">
                <table class="order-list">
                    <thead>
                        <tr>
                            <th>Order-ID</th>
                            <th>Dato og tid i GMT</th>
                            <th>Status</th>
                            <th>Produkt id og antal</th>
                            <th>Kunde</th>
                            <th>Email</th>
                            <th>Adresse</th>
                            <th>Afsend</th>
                        </tr>
                    </thead>
                    <tbody class="order-list-body">
                        <?php foreach ($orders as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $row['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['date_created_gmt'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['Produkter'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['billing_email'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['Adresse'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <input type="checkbox" name="sent[]" value="<?= htmlspecialchars((string) $row['id'], ENT_QUOTES, 'UTF-8') ?>">
                                    <label>Afsendt?</label>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <section class="buttons">
                <button type="submit" name="include_wc_completed">Skjul wc-completed?</button>
                <button type="submit" name="disclude_wc_completed">Vis wc-completed?</button>
                <button type="submit" name="send_orders">Marker som afsendt</button>
                </section>
            </form>
        </div>
        <div class="order-section">
            <h1>Lager</h1>
            <table class="lager-liste">
                <thead>
                    <tr>
                        <th>Produkt-ID</th>
                        <th>Antal på lager</th>
                        <p>Bliver taget fra lagret efter køb (wc-processing).</p>
                    </tr>
                </thead>
                <tbody class="lager-liste-body">
                    <?php foreach ($stockorders as $produkt):?>
                        <tr>
                            <td><?= htmlspecialchars((string) $produkt['product_id'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="<?= (int) $produkt['stock_quantity'] <= 10 ? 'low-stock' : '' ?>">
                                <?= htmlspecialchars((string) $produkt['stock_quantity'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</body>