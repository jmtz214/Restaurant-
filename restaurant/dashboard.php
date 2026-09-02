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
    empty($_SESSION['user_role'])
) {

    header("Location: ../login.php");
    exit;

}


// =====================================================
// RESTAURANT ADMIN ONLY
// =====================================================

if ($_SESSION['user_role'] !== 'restaurant_admin') {

    header("Location: ../index.php");
    exit;

}


$userId = (int) $_SESSION['user_id'];


// =====================================================
// GET RESTAURANT OWNED BY LOGGED-IN ADMIN
// =====================================================

$stmt = $pdo->prepare("
    SELECT *
    FROM restaurants
    WHERE owner_id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$restaurant = $stmt->fetch();


if (!$restaurant) {

    die("
        <div style='
            font-family: Arial;
            padding: 40px;
            text-align: center;
        '>

            <h2>Restaurant Not Found</h2>

            <p>
                Your account is not currently assigned
                to a restaurant.
            </p>

            <a href='../index.php'>
                Back to MloGo
            </a>

        </div>
    ");

}


$restaurantId = (int) $restaurant['id'];


// =====================================================
// RESTAURANT STATISTICS
// =====================================================


// Total orders

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE restaurant_id = ?
");

$stmt->execute([$restaurantId]);

$totalOrders = (int) $stmt->fetchColumn();


// Pending orders

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE restaurant_id = ?
    AND status = 'pending'
");

$stmt->execute([$restaurantId]);

$pendingOrders = (int) $stmt->fetchColumn();


// Preparing orders

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE restaurant_id = ?
    AND status = 'preparing'
");

$stmt->execute([$restaurantId]);

$preparingOrders = (int) $stmt->fetchColumn();


// Completed orders

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE restaurant_id = ?
    AND status = 'completed'
");

$stmt->execute([$restaurantId]);

$completedOrders = (int) $stmt->fetchColumn();


// =====================================================
// TODAY'S SALES
// =====================================================

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM orders
    WHERE restaurant_id = ?
    AND status = 'completed'
    AND DATE(created_at) = CURDATE()
");

$stmt->execute([$restaurantId]);

$todaySales = (float) $stmt->fetchColumn();


// =====================================================
// TOTAL MENU ITEMS
// =====================================================

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM menu_items
    WHERE restaurant_id = ?
");

$stmt->execute([$restaurantId]);

$totalMenuItems = (int) $stmt->fetchColumn();


// =====================================================
// AVAILABLE MENU ITEMS
// =====================================================

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM menu_items
    WHERE restaurant_id = ?
    AND is_available = 1
");

$stmt->execute([$restaurantId]);

$availableMenuItems = (int) $stmt->fetchColumn();


// =====================================================
// RECENT ORDERS
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        o.id,
        o.order_number,
        o.order_type,
        o.status,
        o.total_amount,
        o.created_at,
        u.first_name,
        u.last_name

    FROM orders o

    INNER JOIN users u
        ON o.customer_id = u.id

    WHERE o.restaurant_id = ?

    ORDER BY o.created_at DESC

    LIMIT 8
");

$stmt->execute([$restaurantId]);

$recentOrders = $stmt->fetchAll();


// =====================================================
// RESTAURANT ADMIN NAME
// =====================================================

$adminName =
    trim(
        ($_SESSION['first_name'] ?? '')
        . ' '
        . ($_SESSION['last_name'] ?? '')
    );

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
        Restaurant Dashboard - MloGo
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >


    <style>

        body {

            background: #f5f7fa;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

        }


        /* ==========================================
           SIDEBAR
        ========================================== */

        .sidebar {

            position: fixed;

            left: 0;

            top: 0;

            bottom: 0;

            width: 250px;

            background: #17202a;

            color: white;

            padding: 25px 15px;

            z-index: 1000;

        }


        .brand {

            font-size: 27px;

            font-weight: 800;

            text-decoration: none;

            color: white;

            padding-left: 15px;

            display: block;

            margin-bottom: 35px;

        }


        .brand span {

            color: #20c997;

        }


        .restaurant-name {

            background: rgba(
                255,
                255,
                255,
                .08
            );

            border-radius: 12px;

            padding: 15px;

            margin-bottom: 25px;

        }


        .restaurant-name small {

            color: #adb5bd;

        }


        .sidebar-menu {

            list-style: none;

            padding: 0;

            margin: 0;

        }


        .sidebar-menu li {

            margin-bottom: 7px;

        }


        .sidebar-menu a {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 13px 15px;

            color: #ced4da;

            text-decoration: none;

            border-radius: 10px;

            transition: .2s;

        }


        .sidebar-menu a:hover,
        .sidebar-menu a.active {

            background: #20c997;

            color: white;

        }


        .sidebar-menu i {

            font-size: 19px;

        }


        /* ==========================================
           MAIN
        ========================================== */

        .main {

            margin-left: 250px;

            min-height: 100vh;

        }


        .topbar {

            background: white;

            padding: 18px 30px;

            border-bottom: 1px solid #e9ecef;

            display: flex;

            align-items: center;

            justify-content: space-between;

        }


        .topbar-title {

            font-size: 22px;

            font-weight: 700;

        }


        .admin-profile {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .profile-circle {

            width: 40px;

            height: 40px;

            background: #20c997;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            color: white;

            font-weight: bold;

        }


        .content {

            padding: 30px;

        }


        /* ==========================================
           STAT CARDS
        ========================================== */

        .stat-card {

            background: white;

            border: none;

            border-radius: 16px;

            padding: 22px;

            height: 100%;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.04);

        }


        .stat-icon {

            width: 52px;

            height: 52px;

            border-radius: 13px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 24px;

            margin-bottom: 15px;

        }


        .stat-number {

            font-size: 28px;

            font-weight: 800;

        }


        .stat-label {

            color: #6c757d;

            font-size: 14px;

        }


        /* ==========================================
           TABLE
        ========================================== */

        .dashboard-card {

            background: white;

            border-radius: 16px;

            border: none;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.04);

        }


        .dashboard-card-header {

            padding: 20px;

            border-bottom: 1px solid #eee;

            display: flex;

            align-items: center;

            justify-content: space-between;

        }


        .dashboard-card-header h5 {

            margin: 0;

            font-weight: 700;

        }


        .order-table th {

            color: #6c757d;

            font-size: 13px;

            text-transform: uppercase;

            font-weight: 600;

        }


        .order-table td {

            vertical-align: middle;

        }


        .status-badge {

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

        }


        .status-pending {

            background: #fff3cd;

            color: #856404;

        }


        .status-accepted {

            background: #cff4fc;

            color: #055160;

        }


        .status-preparing {

            background: #cfe2ff;

            color: #084298;

        }


        .status-ready {

            background: #d1e7dd;

            color: #0f5132;

        }


        .status-completed {

            background: #d1e7dd;

            color: #0f5132;

        }


        .status-denied,
        .status-cancelled {

            background: #f8d7da;

            color: #842029;

        }


        /* ==========================================
           RESPONSIVE
        ========================================== */

        @media (max-width: 991px) {

            .sidebar {

                width: 75px;

                padding: 20px 10px;

            }


            .brand {

                font-size: 0;

                text-align: center;

                padding: 0;

            }


            .brand span {

                font-size: 25px;

            }


            .restaurant-name,
            .sidebar-menu span {

                display: none;

            }


            .sidebar-menu a {

                justify-content: center;

            }


            .main {

                margin-left: 75px;

            }

        }


        @media (max-width: 576px) {

            .content {

                padding: 15px;

            }


            .topbar {

                padding: 15px;

            }


            .admin-text {

                display: none;

            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">


    <a
        href="../index.php"
        class="brand"
    >

        Mlo<span>Go</span>

    </a>


    <div class="restaurant-name">

        <small>
            Restaurant
        </small>

        <div class="fw-bold mt-1">

            <?= htmlspecialchars(
                $restaurant['name']
            ) ?>

        </div>

    </div>


    <ul class="sidebar-menu">


        <li>

            <a
                href="dashboard.php"
                class="active"
            >

                <i class="bi bi-grid-1x2-fill"></i>

                <span>
                    Dashboard
                </span>

            </a>

        </li>


        <li>

            <a href="orders.php">

                <i class="bi bi-receipt"></i>

                <span>
                    Orders
                </span>


                <?php if ($pendingOrders > 0): ?>

                    <span
                        class="badge bg-danger ms-auto"
                    >

                        <?= $pendingOrders ?>

                    </span>

                <?php endif; ?>

            </a>

        </li>


        <li>

            <a href="menu.php">

                <i class="bi bi-egg-fried"></i>

                <span>
                    Menu
                </span>

            </a>

        </li>


        <li>

            <a href="analytics.php">

                <i class="bi bi-bar-chart-fill"></i>

                <span>
                    Analytics
                </span>

            </a>

        </li>


        <li>

            <a href="restaurant-settings.php">

                <i class="bi bi-shop"></i>

                <span>
                    Restaurant Settings
                </span>

            </a>

        </li>


        <li class="mt-4">

            <a href="../index.php">

                <i class="bi bi-arrow-left"></i>

                <span>
                    Back to MloGo
                </span>

            </a>

        </li>


        <li>

            <a href="../logout.php">

                <i class="bi bi-box-arrow-right"></i>

                <span>
                    Logout
                </span>

            </a>

        </li>

    </ul>

</aside>



<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <!-- TOPBAR -->

    <header class="topbar">


        <div>

            <div class="topbar-title">

                Restaurant Dashboard

            </div>

            <small class="text-muted">

                Manage your restaurant and orders

            </small>

        </div>


        <div class="admin-profile">


            <div class="admin-text text-end">

                <div class="fw-semibold">

                    <?= htmlspecialchars(
                        $adminName
                    ) ?>

                </div>

                <small class="text-muted">

                    Restaurant Admin

                </small>

            </div>


            <div class="profile-circle">

                <?= strtoupper(
                    substr(
                        $_SESSION['first_name']
                        ?? 'A',
                        0,
                        1
                    )
                ) ?>

            </div>


        </div>


    </header>



    <!-- CONTENT -->

    <div class="content">


        <!-- WELCOME -->

        <div class="mb-4">

            <h3 class="fw-bold">

                Good morning,
                <?= htmlspecialchars(
                    $_SESSION['first_name']
                    ?? 'Admin'
                ) ?>
                👋

            </h3>

            <p class="text-muted mb-0">

                Here's what's happening at
                <?= htmlspecialchars(
                    $restaurant['name']
                ) ?>
                today.

            </p>

        </div>



        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div class="row g-4 mb-4">


            <!-- TOTAL ORDERS -->

            <div class="col-sm-6 col-xl-3">

                <div class="stat-card">

                    <div
                        class="stat-icon"
                        style="
                            background:#e7f1ff;
                            color:#0d6efd;
                        "
                    >

                        <i class="bi bi-receipt"></i>

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


            <!-- PENDING -->

            <div class="col-sm-6 col-xl-3">

                <div class="stat-card">

                    <div
                        class="stat-icon"
                        style="
                            background:#fff3cd;
                            color:#ffc107;
                        "
                    >

                        <i class="bi bi-hourglass-split"></i>

                    </div>

                    <div class="stat-number">

                        <?= number_format(
                            $pendingOrders
                        ) ?>

                    </div>

                    <div class="stat-label">

                        Pending Orders

                    </div>

                </div>

            </div>


            <!-- PREPARING -->

            <div class="col-sm-6 col-xl-3">

                <div class="stat-card">

                    <div
                        class="stat-icon"
                        style="
                            background:#cfe2ff;
                            color:#0d6efd;
                        "
                    >

                        <i class="bi bi-fire"></i>

                    </div>

                    <div class="stat-number">

                        <?= number_format(
                            $preparingOrders
                        ) ?>

                    </div>

                    <div class="stat-label">

                        Preparing

                    </div>

                </div>

            </div>


            <!-- TODAY SALES -->

            <div class="col-sm-6 col-xl-3">

                <div class="stat-card">

                    <div
                        class="stat-icon"
                        style="
                            background:#d1e7dd;
                            color:#198754;
                        "
                    >

                        <i class="bi bi-cash-stack"></i>

                    </div>

                    <div class="stat-number">

                        <?= number_format(
                            $todaySales
                        ) ?>

                    </div>

                    <div class="stat-label">

                        Today's Sales (TZS)

                    </div>

                </div>

            </div>

        </div>



        <!-- =================================================
             SECONDARY STATS
        ================================================== -->

        <div class="row g-4 mb-4">


            <div class="col-md-4">

                <div class="dashboard-card p-4">

                    <div class="d-flex justify-content-between">

                        <div>

                            <small class="text-muted">

                                Completed Orders

                            </small>

                            <h4 class="fw-bold mt-2">

                                <?= number_format(
                                    $completedOrders
                                ) ?>

                            </h4>

                        </div>


                        <i
                            class="bi bi-check-circle-fill text-success"
                            style="font-size:30px;"
                        ></i>

                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="dashboard-card p-4">

                    <div class="d-flex justify-content-between">

                        <div>

                            <small class="text-muted">

                                Menu Items

                            </small>

                            <h4 class="fw-bold mt-2">

                                <?= number_format(
                                    $totalMenuItems
                                ) ?>

                            </h4>

                        </div>


                        <i
                            class="bi bi-egg-fried text-warning"
                            style="font-size:30px;"
                        ></i>

                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="dashboard-card p-4">

                    <div class="d-flex justify-content-between">

                        <div>

                            <small class="text-muted">

                                Available Now

                            </small>

                            <h4 class="fw-bold mt-2">

                                <?= number_format(
                                    $availableMenuItems
                                ) ?>

                            </h4>

                        </div>


                        <i
                            class="bi bi-check2-square text-success"
                            style="font-size:30px;"
                        ></i>

                    </div>

                </div>

            </div>


        </div>



        <!-- =================================================
             RECENT ORDERS
        ================================================== -->

        <div class="dashboard-card">


            <div class="dashboard-card-header">

                <h5>

                    Recent Orders

                </h5>


                <a
                    href="orders.php"
                    class="btn btn-sm btn-outline-success"
                >

                    View All

                    <i class="bi bi-arrow-right"></i>

                </a>

            </div>


            <div class="table-responsive">

                <table
                    class="table table-hover order-table mb-0"
                >

                    <thead>

                        <tr>

                            <th class="ps-4">
                                Order
                            </th>

                            <th>
                                Customer
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Amount
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Date
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (
                        empty($recentOrders)
                    ): ?>


                        <tr>

                            <td
                                colspan="6"
                                class="text-center py-5"
                            >

                                <i
                                    class="bi bi-receipt"
                                    style="
                                        font-size:40px;
                                        color:#adb5bd;
                                    "
                                ></i>

                                <p class="text-muted mt-2 mb-0">

                                    No orders yet.

                                </p>

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $recentOrders
                            as $order
                        ): ?>


                            <tr>


                                <td class="ps-4">

                                    <strong>

                                        <?= htmlspecialchars(
                                            $order['order_number']
                                        ) ?>

                                    </strong>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $order['first_name']
                                        . ' '
                                        . $order['last_name']
                                    ) ?>

                                </td>


                                <td>

                                    <?php if (
                                        $order['order_type']
                                        === 'delivery'
                                    ): ?>

                                        🚚 Delivery

                                    <?php else: ?>

                                        🥡 Pickup

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <strong>

                                        TZS
                                        <?= number_format(
                                            (float)$order[
                                                'total_amount'
                                            ]
                                        ) ?>

                                    </strong>

                                </td>


                                <td>

                                    <?php

                                    $status =
                                        strtolower(
                                            $order['status']
                                        );

                                    ?>


                                <span class="status-badge status-<?= htmlspecialchars($status) ?>">
    <?= htmlspecialchars(ucfirst($status)) ?>
</span>

                                </td>


                                <td>

                                    <small class="text-muted">

                                        <?= date(
                                            'd M Y, H:i',
                                            strtotime(
                                                $order['created_at']
                                            )
                                        ) ?>

                                    </small>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </div>


    </div>

</main>



<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>