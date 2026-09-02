<?php

session_start();

require_once "../config/database.php";
require_once "../includes/functions.php";

$restaurant = requireRestaurantApproval($pdo);

// =====================================================
// AUTHENTICATION
// =====================================================

if (
    empty($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'restaurant_admin'
) {
    header("Location: ../login.php");
    exit;
}


// =====================================================
// GET RESTAURANT OWNED BY ADMIN
// =====================================================

$stmt = $pdo->prepare("
    SELECT *
    FROM restaurants
    WHERE owner_id = ?
    LIMIT 1
");

$stmt->execute([
    $_SESSION['user_id']
]);

$restaurant = $stmt->fetch();


if (!$restaurant) {

    die("Restaurant not found.");

}


$restaurantId =
    (int)$restaurant['id'];


// =====================================================
// DATE RANGE
// =====================================================

$today =
    date('Y-m-d');

$weekStart =
    date(
        'Y-m-d',
        strtotime('monday this week')
    );

$monthStart =
    date(
        'Y-m-01'
    );


// =====================================================
// TODAY'S ORDERS
// =====================================================

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE restaurant_id = ?
      AND DATE(created_at) = ?
      AND status NOT IN ('cancelled', 'denied')
");

$stmt->execute([
    $restaurantId,
    $today
]);

$todayOrders =
    (int)$stmt->fetchColumn();


// =====================================================
// TODAY'S SALES
// =====================================================

$stmt = $pdo->prepare("
    SELECT COALESCE(
        SUM(total_amount),
        0
    )

    FROM orders

    WHERE restaurant_id = ?

      AND DATE(created_at) = ?

      AND status NOT IN (
          'cancelled',
          'denied'
      )
");

$stmt->execute([
    $restaurantId,
    $today
]);

$todaySales =
    (float)$stmt->fetchColumn();


// =====================================================
// WEEKLY SALES
// =====================================================

$stmt = $pdo->prepare("
    SELECT COALESCE(
        SUM(total_amount),
        0
    )

    FROM orders

    WHERE restaurant_id = ?

      AND DATE(created_at) >= ?

      AND status NOT IN (
          'cancelled',
          'denied'
      )
");

$stmt->execute([
    $restaurantId,
    $weekStart
]);

$weeklySales =
    (float)$stmt->fetchColumn();


// =====================================================
// MONTHLY SALES
// =====================================================

$stmt = $pdo->prepare("
    SELECT COALESCE(
        SUM(total_amount),
        0
    )

    FROM orders

    WHERE restaurant_id = ?

      AND DATE(created_at) >= ?

      AND status NOT IN (
          'cancelled',
          'denied'
      )
");

$stmt->execute([
    $restaurantId,
    $monthStart
]);

$monthlySales =
    (float)$stmt->fetchColumn();


// =====================================================
// TOTAL ORDERS
// =====================================================

$stmt = $pdo->prepare("
    SELECT COUNT(*)

    FROM orders

    WHERE restaurant_id = ?

      AND status NOT IN (
          'cancelled',
          'denied'
      )
");

$stmt->execute([
    $restaurantId
]);

$totalOrders =
    (int)$stmt->fetchColumn();


// =====================================================
// AVERAGE ORDER VALUE
// =====================================================

$stmt = $pdo->prepare("
    SELECT COALESCE(
        AVG(total_amount),
        0
    )

    FROM orders

    WHERE restaurant_id = ?

      AND status NOT IN (
          'cancelled',
          'denied'
      )
");

$stmt->execute([
    $restaurantId
]);

$averageOrder =
    (float)$stmt->fetchColumn();


// =====================================================
// ORDER STATUS SUMMARY
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        status,
        COUNT(*) AS total

    FROM orders

    WHERE restaurant_id = ?

    GROUP BY status

    ORDER BY total DESC
");

$stmt->execute([
    $restaurantId
]);

$statusData =
    $stmt->fetchAll();


// =====================================================
// TOP SELLING FOODS
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        oi.menu_item_id,
        oi.item_name,

        SUM(
            oi.quantity
        ) AS total_quantity,

        SUM(
            oi.subtotal
        ) AS total_revenue

    FROM order_items oi

    INNER JOIN orders o
        ON o.id = oi.order_id

    WHERE o.restaurant_id = ?

      AND o.status NOT IN (
          'cancelled',
          'denied'
      )

    GROUP BY
        oi.menu_item_id,
        oi.item_name

    ORDER BY
        total_quantity DESC

    LIMIT 10
");

$stmt->execute([
    $restaurantId
]);

$topFoods =
    $stmt->fetchAll();


// =====================================================
// SALES BY DAY — LAST 7 DAYS
// =====================================================

$stmt = $pdo->prepare("
    SELECT

        DATE(created_at) AS sale_date,

        COALESCE(
            SUM(total_amount),
            0
        ) AS total_sales,

        COUNT(*) AS total_orders

    FROM orders

    WHERE restaurant_id = ?

      AND DATE(created_at)
          >= DATE_SUB(
              CURDATE(),
              INTERVAL 6 DAY
          )

      AND status NOT IN (
          'cancelled',
          'denied'
      )

    GROUP BY DATE(created_at)

    ORDER BY sale_date ASC
");

$stmt->execute([
    $restaurantId
]);

$dailySales =
    $stmt->fetchAll();


// =====================================================
// ORDERS BY HOUR
// =====================================================

$stmt = $pdo->prepare("
    SELECT

        HOUR(created_at) AS order_hour,

        COUNT(*) AS total_orders

    FROM orders

    WHERE restaurant_id = ?

      AND status NOT IN (
          'cancelled',
          'denied'
      )

    GROUP BY HOUR(created_at)

    ORDER BY order_hour ASC
");

$stmt->execute([
    $restaurantId
]);

$hourlyOrders =
    $stmt->fetchAll();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Analytics - <?= htmlspecialchars($restaurant['name']) ?>
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <script
        src="https://cdn.jsdelivr.net/npm/chart.js"
    ></script>


    <style>

        body {

            background: #f6f8fb;

        }


        .analytics-card {

            border: none;

            border-radius: 16px;

            background: white;

            box-shadow:
                0 4px 20px
                rgba(0,0,0,.05);

        }


        .stat-card {

            border: none;

            border-radius: 16px;

            background: white;

            box-shadow:
                0 4px 20px
                rgba(0,0,0,.05);

            padding: 24px;

            height: 100%;

        }


        .stat-icon {

            width: 50px;

            height: 50px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;

            background: #e8f8f1;

            color: #198754;

        }


        .stat-value {

            font-size: 28px;

            font-weight: 800;

        }


        .chart-container {

            position: relative;

            height: 350px;

        }


        .food-rank {

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 15px 0;

            border-bottom: 1px solid #eee;

        }


        .food-rank:last-child {

            border-bottom: none;

        }


        .food-number {

            width: 35px;

            height: 35px;

            border-radius: 50%;

            background: #e8f8f1;

            color: #198754;

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: bold;

        }

    </style>

</head>


<body>


<div class="container-fluid px-4 py-4">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">

                Analytics

            </h2>

            <p class="text-muted mb-0">

                <?= htmlspecialchars(
                    $restaurant['name']
                ) ?>

            </p>

        </div>


        <a
            href="dashboard.php"
            class="btn btn-outline-secondary"
        >

            <i class="bi bi-arrow-left"></i>

            Dashboard

        </a>

    </div>


    <!-- =================================================
         STAT CARDS
    ================================================== -->

    <div class="row g-4 mb-4">


        <!-- TODAY SALES -->

        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-icon mb-3">

                    <i class="bi bi-cash-stack"></i>

                </div>


                <div class="text-muted">

                    Today's Sales

                </div>


                <div class="stat-value">

                    TZS
                    <?= number_format(
                        $todaySales
                    ) ?>

                </div>

            </div>

        </div>


        <!-- TODAY ORDERS -->

        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-icon mb-3">

                    <i class="bi bi-bag-check"></i>

                </div>


                <div class="text-muted">

                    Today's Orders

                </div>


                <div class="stat-value">

                    <?= number_format(
                        $todayOrders
                    ) ?>

                </div>

            </div>

        </div>


        <!-- MONTHLY SALES -->

        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-icon mb-3">

                    <i class="bi bi-graph-up-arrow"></i>

                </div>


                <div class="text-muted">

                    This Month

                </div>


                <div class="stat-value">

                    TZS
                    <?= number_format(
                        $monthlySales
                    ) ?>

                </div>

            </div>

        </div>


        <!-- AVERAGE ORDER -->

        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-icon mb-3">

                    <i class="bi bi-receipt"></i>

                </div>


                <div class="text-muted">

                    Average Order

                </div>


                <div class="stat-value">

                    TZS
                    <?= number_format(
                        $averageOrder
                    ) ?>

                </div>

            </div>

        </div>


    </div>


    <!-- =================================================
         SALES CHART
    ================================================== -->

    <div class="row g-4 mb-4">


        <div class="col-lg-8">

            <div class="analytics-card p-4">

                <h5 class="fw-bold">

                    Sales — Last 7 Days

                </h5>


                <p class="text-muted">

                    Daily restaurant revenue

                </p>


                <div class="chart-container">

                    <canvas id="salesChart"></canvas>

                </div>

            </div>

        </div>


        <!-- ORDER STATUS -->

        <div class="col-lg-4">

            <div class="analytics-card p-4">

                <h5 class="fw-bold">

                    Order Status

                </h5>


                <p class="text-muted">

                    Overall order distribution

                </p>


                <div class="chart-container">

                    <canvas id="statusChart"></canvas>

                </div>

            </div>

        </div>


    </div>


    <!-- =================================================
         POPULAR FOODS
    ================================================== -->

    <div class="row g-4 mb-4">


        <div class="col-lg-6">

            <div class="analytics-card p-4">

                <h5 class="fw-bold">

                    Top Selling Foods

                </h5>


                <p class="text-muted">

                    Foods with the highest number
                    of orders

                </p>


                <?php if (!empty($topFoods)): ?>


                    <?php
                    $rank = 1;
                    ?>


                    <?php foreach ($topFoods as $food): ?>


                        <div class="food-rank">


                            <div class="d-flex align-items-center gap-3">


                                <div class="food-number">

                                    <?= $rank ?>

                                </div>


                                <div>

                                    <div class="fw-bold">

                                        <?= htmlspecialchars(
                                            $food['item_name']
                                        ) ?>

                                    </div>


                                    <small class="text-muted">

                                        <?= number_format(
                                            $food['total_quantity']
                                        ) ?>

                                        sold

                                    </small>

                                </div>

                            </div>


                            <strong>

                                TZS
                                <?= number_format(
                                    $food['total_revenue']
                                ) ?>

                            </strong>


                        </div>


                        <?php
                        $rank++;
                        ?>


                    <?php endforeach; ?>


                <?php else: ?>


                    <div class="text-center py-5 text-muted">

                        No sales data available yet.

                    </div>


                <?php endif; ?>


            </div>

        </div>


        <!-- PEAK HOURS -->

        <div class="col-lg-6">

            <div class="analytics-card p-4">

                <h5 class="fw-bold">

                    Customer Ordering Time

                </h5>


                <p class="text-muted">

                    When customers place orders

                </p>


                <div class="chart-container">

                    <canvas id="hourChart"></canvas>

                </div>

            </div>

        </div>


    </div>


</div>



<script>


// =====================================================
// SALES DATA
// =====================================================

const salesLabels = [

<?php foreach ($dailySales as $day): ?>

    "<?= date(
        'D',
        strtotime($day['sale_date'])
    ) ?>",

<?php endforeach; ?>

];


const salesValues = [

<?php foreach ($dailySales as $day): ?>

    <?= (float)$day['total_sales'] ?>,

<?php endforeach; ?>

];


// =====================================================
// SALES CHART
// =====================================================

new Chart(

    document.getElementById(
        'salesChart'
    ),

    {

        type: 'line',

        data: {

            labels: salesLabels,

            datasets: [

                {

                    label: 'Sales (TZS)',

                    data: salesValues,

                    tension: 0.4,

                    fill: true

                }

            ]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            plugins: {

                legend: {

                    display: false

                }

            }

        }

    }

);


// =====================================================
// ORDER STATUS
// =====================================================

const statusLabels = [

<?php foreach ($statusData as $status): ?>

    "<?= ucfirst(
        str_replace(
            '_',
            ' ',
            $status['status']
        )
    ) ?>",

<?php endforeach; ?>

];


const statusValues = [

<?php foreach ($statusData as $status): ?>

    <?= (int)$status['total'] ?>,

<?php endforeach; ?>

];


new Chart(

    document.getElementById(
        'statusChart'
    ),

    {

        type: 'doughnut',

        data: {

            labels: statusLabels,

            datasets: [

                {

                    data: statusValues

                }

            ]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false

        }

    }

);


// =====================================================
// ORDERING HOURS
// =====================================================

const hourLabels = [

<?php foreach ($hourlyOrders as $hour): ?>

    "<?= str_pad(
        $hour['order_hour'],
        2,
        '0',
        STR_PAD_LEFT
    ) ?>:00",

<?php endforeach; ?>

];


const hourValues = [

<?php foreach ($hourlyOrders as $hour): ?>

    <?= (int)$hour['total_orders'] ?>,

<?php endforeach; ?>

];


new Chart(

    document.getElementById(
        'hourChart'
    ),

    {

        type: 'bar',

        data: {

            labels: hourLabels,

            datasets: [

                {

                    label: 'Orders',

                    data: hourValues

                }

            ]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            plugins: {

                legend: {

                    display: false

                }

            }

        }

    }

);

</script>


</body>

</html>