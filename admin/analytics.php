<?php

session_start();

require_once "../config/database.php";
require_once "../includes/functions.php";


// =====================================================
// SUPER ADMIN ACCESS
// =====================================================

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'super_admin'
) {

    redirect('../login.php');
    exit;
}


// =====================================================
// BASIC STATISTICS
// =====================================================

// Total customers
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'customer'
");

$totalCustomers = (int)$stmt->fetchColumn();


// Total restaurants
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM restaurants
");

$totalRestaurants = (int)$stmt->fetchColumn();


// Approved restaurants
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM restaurants
    WHERE status = 'approved'
");

$approvedRestaurants = (int)$stmt->fetchColumn();


// Pending restaurants
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM restaurants
    WHERE status = 'pending'
");

$pendingRestaurants = (int)$stmt->fetchColumn();


// Suspended restaurants
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM restaurants
    WHERE status = 'suspended'
");

$suspendedRestaurants = (int)$stmt->fetchColumn();


// =====================================================
// ORDER STATISTICS
// =====================================================

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
");

$totalOrders = (int)$stmt->fetchColumn();


// Completed orders
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE status = 'completed'
");

$completedOrders = (int)$stmt->fetchColumn();


// Pending orders
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE status = 'pending'
");

$pendingOrders = (int)$stmt->fetchColumn();


// Preparing orders
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE status = 'preparing'
");

$preparingOrders = (int)$stmt->fetchColumn();


// Ready orders
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE status = 'ready'
");

$readyOrders = (int)$stmt->fetchColumn();


// Out for delivery
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE status = 'out_for_delivery'
");

$outForDelivery = (int)$stmt->fetchColumn();


// Accepted
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE status = 'accepted'
");

$acceptedOrders = (int)$stmt->fetchColumn();


// Denied
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE status = 'denied'
");

$deniedOrders = (int)$stmt->fetchColumn();


// Cancelled
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE status = 'cancelled'
");

$cancelledOrders = (int)$stmt->fetchColumn();


// =====================================================
// REVENUE
// =====================================================

// Total revenue from completed orders
$stmt = $pdo->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM orders
    WHERE status = 'completed'
");

$totalRevenue = (float)$stmt->fetchColumn();


// Today's revenue
$stmt = $pdo->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM orders
    WHERE status = 'completed'
    AND DATE(completed_at) = CURDATE()
");

$todayRevenue = (float)$stmt->fetchColumn();


// This month's revenue
$stmt = $pdo->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM orders
    WHERE status = 'completed'
    AND YEAR(completed_at) = YEAR(CURDATE())
    AND MONTH(completed_at) = MONTH(CURDATE())
");

$monthlyRevenue = (float)$stmt->fetchColumn();


// =====================================================
// TODAY'S ORDERS
// =====================================================

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE DATE(created_at) = CURDATE()
");

$todayOrders = (int)$stmt->fetchColumn();


// =====================================================
// NEW CUSTOMERS THIS MONTH
// =====================================================

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'customer'
    AND YEAR(created_at) = YEAR(CURDATE())
    AND MONTH(created_at) = MONTH(CURDATE())
");

$newCustomers = (int)$stmt->fetchColumn();


// =====================================================
// LAST 7 DAYS REVENUE
// =====================================================

$revenueLabels = [];
$revenueData = [];

$stmt = $pdo->query("
    SELECT
        DATE(completed_at) AS order_date,
        COALESCE(SUM(total_amount), 0) AS revenue

    FROM orders

    WHERE status = 'completed'

    AND completed_at >= DATE_SUB(
        CURDATE(),
        INTERVAL 6 DAY
    )

    GROUP BY DATE(completed_at)

    ORDER BY order_date ASC
");

$revenueRows = $stmt->fetchAll();

$revenueMap = [];

foreach ($revenueRows as $row) {

    $revenueMap[$row['order_date']] =
        (float)$row['revenue'];
}


for ($i = 6; $i >= 0; $i--) {

    $date = date(
        'Y-m-d',
        strtotime("-$i days")
    );

    $revenueLabels[] = date(
        'D',
        strtotime($date)
    );

    $revenueData[] =
        $revenueMap[$date] ?? 0;
}


// =====================================================
// LAST 7 DAYS ORDERS
// =====================================================

$orderLabels = [];
$orderData = [];

$stmt = $pdo->query("
    SELECT
        DATE(created_at) AS order_date,
        COUNT(*) AS total

    FROM orders

    WHERE created_at >= DATE_SUB(
        CURDATE(),
        INTERVAL 6 DAY
    )

    GROUP BY DATE(created_at)

    ORDER BY order_date ASC
");

$orderRows = $stmt->fetchAll();

$orderMap = [];

foreach ($orderRows as $row) {

    $orderMap[$row['order_date']] =
        (int)$row['total'];
}


for ($i = 6; $i >= 0; $i--) {

    $date = date(
        'Y-m-d',
        strtotime("-$i days")
    );

    $orderLabels[] = date(
        'D',
        strtotime($date)
    );

    $orderData[] =
        $orderMap[$date] ?? 0;
}


// =====================================================
// TOP RESTAURANTS
// =====================================================

$stmt = $pdo->query("
    SELECT

        r.name,

        COUNT(o.id) AS total_orders,

        COALESCE(
            SUM(o.total_amount),
            0
        ) AS revenue

    FROM restaurants r

    LEFT JOIN orders o
        ON o.restaurant_id = r.id
        AND o.status = 'completed'

    GROUP BY r.id

    ORDER BY revenue DESC

    LIMIT 5
");

$topRestaurants = $stmt->fetchAll();


// =====================================================
// TOP SELLING FOOD
// =====================================================

$stmt = $pdo->query("
    SELECT

        oi.item_name,

        SUM(oi.quantity) AS quantity_sold,

        COALESCE(
            SUM(oi.subtotal),
            0
        ) AS revenue

    FROM order_items oi

    INNER JOIN orders o
        ON o.id = oi.order_id

    WHERE o.status = 'completed'

    GROUP BY oi.menu_item_id, oi.item_name

    ORDER BY quantity_sold DESC

    LIMIT 5
");

$topFoods = $stmt->fetchAll();


// =====================================================
// AVERAGE ORDER VALUE
// =====================================================

$averageOrderValue = 0;

if ($completedOrders > 0) {

    $averageOrderValue =
        $totalRevenue / $completedOrders;
}


// =====================================================
// COMPLETION RATE
// =====================================================

$completionRate = 0;

if ($totalOrders > 0) {

    $completionRate =
        ($completedOrders / $totalOrders) * 100;
}

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
        Analytics - MloGo Admin
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- Chart.js -->

    <script
        src="https://cdn.jsdelivr.net/npm/chart.js"
    ></script>


    <style>

        body {

            background: #f5f7fb;

        }


        .page-header {

            background: white;

            border-radius: 16px;

            padding: 25px;

            margin-bottom: 25px;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.05);

        }


        .stat-card {

            background: white;

            border: none;

            border-radius: 16px;

            padding: 22px;

            height: 100%;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.05);

            transition: 0.2s;

        }


        .stat-card:hover {

            transform: translateY(-3px);

        }


        .stat-icon {

            width: 52px;

            height: 52px;

            border-radius: 14px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 24px;

            margin-bottom: 15px;

            background: #e8f7ef;

            color: #198754;

        }


        .stat-number {

            font-size: 28px;

            font-weight: 700;

            margin-bottom: 3px;

        }


        .stat-label {

            color: #6c757d;

            font-size: 14px;

        }


        .analytics-card {

            background: white;

            border-radius: 16px;

            border: none;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.05);

            overflow: hidden;

        }


        .card-header-custom {

            padding: 20px 22px;

            border-bottom: 1px solid #eee;

            background: white;

        }


        .card-header-custom h5 {

            font-weight: 700;

            margin: 0;

        }


        .chart-container {

            position: relative;

            height: 330px;

            padding: 20px;

        }


        .status-item {

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 13px 0;

            border-bottom: 1px solid #eee;

        }


        .status-item:last-child {

            border-bottom: none;

        }


        .status-dot {

            width: 10px;

            height: 10px;

            border-radius: 50%;

            display: inline-block;

            margin-right: 8px;

        }


        .table thead th {

            font-size: 13px;

            color: #6c757d;

            font-weight: 600;

            border-bottom: 1px solid #eee;

        }


        .table tbody td {

            vertical-align: middle;

        }


        .rank {

            width: 35px;

            height: 35px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background: #e8f7ef;

            color: #198754;

            font-weight: 700;

        }


        .back-btn {

            text-decoration: none;

        }

    </style>

</head>


<body>


<div class="container-fluid p-4">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="page-header">


        <div
            class="d-flex justify-content-between align-items-center flex-wrap gap-3"
        >


            <div>

                <h2 class="fw-bold mb-1">

                    <i class="bi bi-bar-chart-line text-success me-2"></i>

                    Analytics & Reports

                </h2>

                <p class="text-muted mb-0">

                    Monitor MloGo platform performance and business activity.

                </p>

            </div>


            <div class="d-flex gap-2">


                <a
                    href="dashboard.php"
                    class="btn btn-outline-secondary back-btn"
                >

                    <i class="bi bi-arrow-left me-1"></i>

                    Dashboard

                </a>


                <button
                    onclick="window.print()"
                    class="btn btn-success"
                >

                    <i class="bi bi-printer me-1"></i>

                    Print Report

                </button>


            </div>


        </div>


    </div>



    <!-- =================================================
         REVENUE STATISTICS
    ================================================== -->

    <div class="row g-4 mb-4">


        <div class="col-xl-3 col-md-6">


            <div class="stat-card">


                <div class="stat-icon">

                    <i class="bi bi-cash-stack"></i>

                </div>


                <div class="stat-number">

                    TZS
                    <?= number_format(
                        $totalRevenue
                    ) ?>

                </div>


                <div class="stat-label">

                    Total Revenue

                </div>


            </div>


        </div>



        <div class="col-xl-3 col-md-6">


            <div class="stat-card">


                <div class="stat-icon">

                    <i class="bi bi-calendar-day"></i>

                </div>


                <div class="stat-number">

                    TZS
                    <?= number_format(
                        $todayRevenue
                    ) ?>

                </div>


                <div class="stat-label">

                    Today's Revenue

                </div>


            </div>


        </div>



        <div class="col-xl-3 col-md-6">


            <div class="stat-card">


                <div class="stat-icon">

                    <i class="bi bi-calendar3"></i>

                </div>


                <div class="stat-number">

                    TZS
                    <?= number_format(
                        $monthlyRevenue
                    ) ?>

                </div>


                <div class="stat-label">

                    This Month

                </div>


            </div>


        </div>



        <div class="col-xl-3 col-md-6">


            <div class="stat-card">


                <div class="stat-icon">

                    <i class="bi bi-receipt"></i>

                </div>


                <div class="stat-number">

                    TZS
                    <?= number_format(
                        $averageOrderValue
                    ) ?>

                </div>


                <div class="stat-label">

                    Average Order Value

                </div>


            </div>


        </div>


    </div>



    <!-- =================================================
         PLATFORM STATISTICS
    ================================================== -->

    <div class="row g-4 mb-4">


        <div class="col-xl-3 col-md-6">


            <div class="stat-card">


                <div class="stat-icon">

                    <i class="bi bi-cart-check"></i>

                </div>


                <div class="stat-number">

                    <?= number_format(
                        $totalOrders
                    ) ?>

                </div>


                <div class="stat-label">

                    Total Orders

                </div>


            </div>


        </div>



        <div class="col-xl-3 col-md-6">


            <div class="stat-card">


                <div class="stat-icon">

                    <i class="bi bi-people"></i>

                </div>


                <div class="stat-number">

                    <?= number_format(
                        $totalCustomers
                    ) ?>

                </div>


                <div class="stat-label">

                    Total Customers

                </div>


            </div>


        </div>



        <div class="col-xl-3 col-md-6">


            <div class="stat-card">


                <div class="stat-icon">

                    <i class="bi bi-shop"></i>

                </div>


                <div class="stat-number">

                    <?= number_format(
                        $approvedRestaurants
                    ) ?>

                </div>


                <div class="stat-label">

                    Approved Restaurants

                </div>


            </div>


        </div>



        <div class="col-xl-3 col-md-6">


            <div class="stat-card">


                <div class="stat-icon">

                    <i class="bi bi-person-plus"></i>

                </div>


                <div class="stat-number">

                    <?= number_format(
                        $newCustomers
                    ) ?>

                </div>


                <div class="stat-label">

                    New Customers This Month

                </div>


            </div>


        </div>


    </div>



    <!-- =================================================
         CHARTS
    ================================================== -->

    <div class="row g-4 mb-4">


        <!-- REVENUE CHART -->

        <div class="col-lg-8">


            <div class="analytics-card">


                <div class="card-header-custom">


                    <h5>

                        <i class="bi bi-graph-up-arrow text-success me-2"></i>

                        Revenue - Last 7 Days

                    </h5>


                </div>


                <div class="chart-container">

                    <canvas id="revenueChart"></canvas>

                </div>


            </div>


        </div>



        <!-- ORDER CHART -->

        <div class="col-lg-4">


            <div class="analytics-card">


                <div class="card-header-custom">


                    <h5>

                        <i class="bi bi-bar-chart text-success me-2"></i>

                        Orders - Last 7 Days

                    </h5>


                </div>


                <div class="chart-container">

                    <canvas id="ordersChart"></canvas>

                </div>


            </div>


        </div>


    </div>



    <!-- =================================================
         ORDER STATUS + RESTAURANTS
    ================================================== -->

    <div class="row g-4 mb-4">


        <!-- ORDER STATUS -->

        <div class="col-lg-5">


            <div class="analytics-card">


                <div class="card-header-custom">


                    <h5>

                        <i class="bi bi-pie-chart text-success me-2"></i>

                        Order Status

                    </h5>


                </div>


                <div class="p-4">


                    <div class="status-item">

                        <span>

                            <span
                                class="status-dot"
                                style="background:#ffc107;"
                            ></span>

                            Pending

                        </span>

                        <strong>

                            <?= $pendingOrders ?>

                        </strong>

                    </div>


                    <div class="status-item">

                        <span>

                            <span
                                class="status-dot"
                                style="background:#0dcaf0;"
                            ></span>

                            Accepted

                        </span>

                        <strong>

                            <?= $acceptedOrders ?>

                        </strong>

                    </div>


                    <div class="status-item">

                        <span>

                            <span
                                class="status-dot"
                                style="background:#0d6efd;"
                            ></span>

                            Preparing

                        </span>

                        <strong>

                            <?= $preparingOrders ?>

                        </strong>

                    </div>


                    <div class="status-item">

                        <span>

                            <span
                                class="status-dot"
                                style="background:#198754;"
                            ></span>

                            Ready

                        </span>

                        <strong>

                            <?= $readyOrders ?>

                        </strong>

                    </div>


                    <div class="status-item">

                        <span>

                            <span
                                class="status-dot"
                                style="background:#6f42c1;"
                            ></span>

                            Out for Delivery

                        </span>

                        <strong>

                            <?= $outForDelivery ?>

                        </strong>

                    </div>


                    <div class="status-item">

                        <span>

                            <span
                                class="status-dot"
                                style="background:#20c997;"
                            ></span>

                            Completed

                        </span>

                        <strong>

                            <?= $completedOrders ?>

                        </strong>

                    </div>


                    <div class="status-item">

                        <span>

                            <span
                                class="status-dot"
                                style="background:#dc3545;"
                            ></span>

                            Denied

                        </span>

                        <strong>

                            <?= $deniedOrders ?>

                        </strong>

                    </div>


                    <div class="status-item">

                        <span>

                            <span
                                class="status-dot"
                                style="background:#6c757d;"
                            ></span>

                            Cancelled

                        </span>

                        <strong>

                            <?= $cancelledOrders ?>

                        </strong>

                    </div>


                </div>


            </div>


        </div>



        <!-- RESTAURANT STATUS -->

        <div class="col-lg-7">


            <div class="analytics-card">


                <div class="card-header-custom">


                    <h5>

                        <i class="bi bi-shop-window text-success me-2"></i>

                        Restaurant Overview

                    </h5>


                </div>


                <div class="p-4">


                    <div class="row g-3">


                        <div class="col-md-6">


                            <div class="stat-card">

                                <div class="stat-icon">

                                    <i class="bi bi-shop"></i>

                                </div>

                                <div class="stat-number">

                                    <?= $totalRestaurants ?>

                                </div>

                                <div class="stat-label">

                                    Total Restaurants

                                </div>

                            </div>


                        </div>


                        <div class="col-md-6">


                            <div class="stat-card">

                                <div class="stat-icon">

                                    <i class="bi bi-check-circle"></i>

                                </div>

                                <div class="stat-number">

                                    <?= $approvedRestaurants ?>

                                </div>

                                <div class="stat-label">

                                    Approved

                                </div>

                            </div>


                        </div>


                        <div class="col-md-6">


                            <div class="stat-card">

                                <div class="stat-icon">

                                    <i class="bi bi-hourglass-split"></i>

                                </div>

                                <div class="stat-number">

                                    <?= $pendingRestaurants ?>

                                </div>

                                <div class="stat-label">

                                    Pending Approval

                                </div>

                            </div>


                        </div>


                        <div class="col-md-6">


                            <div class="stat-card">

                                <div class="stat-icon">

                                    <i class="bi bi-slash-circle"></i>

                                </div>

                                <div class="stat-number">

                                    <?= $suspendedRestaurants ?>

                                </div>

                                <div class="stat-label">

                                    Suspended

                                </div>

                            </div>


                        </div>


                    </div>


                </div>


            </div>


        </div>


    </div>



    <!-- =================================================
         TOP RESTAURANTS
    ================================================== -->

    <div class="row g-4 mb-4">


        <div class="col-lg-7">


            <div class="analytics-card">


                <div class="card-header-custom">


                    <h5>

                        <i class="bi bi-trophy text-success me-2"></i>

                        Top Restaurants

                    </h5>


                </div>


                <div class="table-responsive">


                    <table class="table mb-0">


                        <thead>

                            <tr>

                                <th>#</th>

                                <th>Restaurant</th>

                                <th>Orders</th>

                                <th>Revenue</th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (
                            count($topRestaurants) > 0
                        ): ?>


                            <?php
                            $rank = 1;
                            ?>

                            <?php foreach (
                                $topRestaurants
                                as $restaurant
                            ): ?>


                                <tr>


                                    <td>

                                        <span class="rank">

                                            <?= $rank ?>

                                        </span>

                                    </td>


                                    <td>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $restaurant['name']
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?= number_format(
                                            $restaurant['total_orders']
                                        ) ?>

                                    </td>


                                    <td>

                                        <strong class="text-success">

                                            TZS
                                            <?= number_format(
                                                $restaurant['revenue']
                                            ) ?>

                                        </strong>

                                    </td>


                                </tr>


                                <?php
                                $rank++;
                                ?>

                            <?php endforeach; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="4"
                                    class="text-center text-muted py-4"
                                >

                                    No restaurant data available.

                                </td>

                            </tr>


                        <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            </div>


        </div>



        <!-- TOP FOODS -->

        <div class="col-lg-5">


            <div class="analytics-card">


                <div class="card-header-custom">


                    <h5>

                        <i class="bi bi-star text-success me-2"></i>

                        Top Selling Food

                    </h5>


                </div>


                <div class="table-responsive">


                    <table class="table mb-0">


                        <thead>

                            <tr>

                                <th>Food</th>

                                <th>Sold</th>

                                <th>Revenue</th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (
                            count($topFoods) > 0
                        ): ?>


                            <?php foreach (
                                $topFoods
                                as $food
                            ): ?>


                                <tr>


                                    <td>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $food['item_name']
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?= number_format(
                                            $food['quantity_sold']
                                        ) ?>

                                    </td>


                                    <td>

                                        TZS
                                        <?= number_format(
                                            $food['revenue']
                                        ) ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="3"
                                    class="text-center text-muted py-4"
                                >

                                    No food sales available.

                                </td>

                            </tr>


                        <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            </div>


        </div>


    </div>



    <!-- =================================================
         PERFORMANCE
    ================================================== -->

    <div class="row g-4 mb-4">


        <div class="col-md-4">


            <div class="stat-card">


                <div class="stat-icon">

                    <i class="bi bi-calendar-check"></i>

                </div>


                <div class="stat-number">

                    <?= $todayOrders ?>

                </div>


                <div class="stat-label">

                    Orders Today

                </div>


            </div>


        </div>


        <div class="col-md-4">


            <div class="stat-card">


                <div class="stat-icon">

                    <i class="bi bi-percent"></i>

                </div>


                <div class="stat-number">

                    <?= number_format(
                        $completionRate,
                        1
                    ) ?>%

                </div>


                <div class="stat-label">

                    Order Completion Rate

                </div>


            </div>


        </div>


        <div class="col-md-4">


            <div class="stat-card">


                <div class="stat-icon">

                    <i class="bi bi-person-plus"></i>

                </div>


                <div class="stat-number">

                    <?= $newCustomers ?>

                </div>


                <div class="stat-label">

                    New Customers This Month

                </div>


            </div>


        </div>


    </div>


</div>



<!-- =====================================================
     CHARTS
====================================================== -->

<script>

    const revenueLabels =
        <?= json_encode($revenueLabels) ?>;

    const revenueData =
        <?= json_encode($revenueData) ?>;

    const orderLabels =
        <?= json_encode($orderLabels) ?>;

    const orderData =
        <?= json_encode($orderData) ?>;


    // =================================================
    // REVENUE CHART
    // =================================================

    new Chart(
        document.getElementById(
            'revenueChart'
        ),
        {

            type: 'line',

            data: {

                labels: revenueLabels,

                datasets: [{

                    label: 'Revenue (TZS)',

                    data: revenueData,

                    borderWidth: 3,

                    tension: 0.4,

                    fill: true,

                    pointRadius: 4

                }]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                plugins: {

                    legend: {

                        display: false

                    }

                },

                scales: {

                    y: {

                        beginAtZero: true,

                        ticks: {

                            callback: function(value) {

                                return 'TZS ' +
                                    Number(value)
                                    .toLocaleString();

                            }

                        }

                    }

                }

            }

        }
    );


    // =================================================
    // ORDERS CHART
    // =================================================

    new Chart(
        document.getElementById(
            'ordersChart'
        ),
        {

            type: 'bar',

            data: {

                labels: orderLabels,

                datasets: [{

                    label: 'Orders',

                    data: orderData,

                    borderWidth: 1

                }]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                plugins: {

                    legend: {

                        display: false

                    }

                },

                scales: {

                    y: {

                        beginAtZero: true,

                        ticks: {

                            precision: 0

                        }

                    }

                }

            }

        }
    );

</script>


</body>

</html>