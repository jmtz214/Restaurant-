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
// GET RESTAURANT
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
    die("Restaurant not found.");
}


$restaurantId = (int) $restaurant['id'];


// =====================================================
// FILTER
// =====================================================

$filter = $_GET['status'] ?? 'all';

$allowedStatuses = [
    'pending',
    'accepted',
    'denied',
    'preparing',
    'ready',
    'out_for_delivery',
    'completed',
    'cancelled'
];


if (
    $filter !== 'all' &&
    !in_array($filter, $allowedStatuses, true)
) {
    $filter = 'all';
}


// =====================================================
// SEARCH
// =====================================================

$search = trim($_GET['search'] ?? '');


// =====================================================
// BUILD QUERY
// =====================================================

$sql = "
    SELECT
        o.id,
        o.order_number,
        o.order_type,
        o.status,
        o.subtotal,
        o.delivery_fee,
        o.discount_amount,
        o.total_amount,
        o.payment_method,
        o.payment_status,
        o.delivery_address,
        o.delivery_city,
        o.customer_notes,
        o.restaurant_notes,
        o.denial_reason,
        o.estimated_preparation_minutes,
        o.placed_at,
        o.created_at,

        u.first_name,
        u.last_name,
        u.email,
        u.phone

    FROM orders o

    INNER JOIN users u
        ON o.customer_id = u.id

    WHERE o.restaurant_id = ?

";

$params = [$restaurantId];


// Status filter

if ($filter !== 'all') {

    $sql .= "
        AND o.status = ?
    ";

    $params[] = $filter;

}


// Search filter

if ($search !== '') {

    $sql .= "
        AND (
            o.order_number LIKE ?
            OR u.first_name LIKE ?
            OR u.last_name LIKE ?
            OR u.phone LIKE ?
        )
    ";

    $searchTerm = '%' . $search . '%';

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;

}


$sql .= "
    ORDER BY
        CASE
            WHEN o.status = 'pending' THEN 0
            WHEN o.status = 'accepted' THEN 1
            WHEN o.status = 'preparing' THEN 2
            WHEN o.status = 'ready' THEN 3
            WHEN o.status = 'out_for_delivery' THEN 4
            ELSE 5
        END,
        o.created_at DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$orders = $stmt->fetchAll();


// =====================================================
// COUNT PENDING
// =====================================================

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE restaurant_id = ?
    AND status = 'pending'
");

$stmt->execute([$restaurantId]);

$pendingCount = (int) $stmt->fetchColumn();


// =====================================================
// STATUS COUNTS
// =====================================================

$statusCounts = [];

foreach ($allowedStatuses as $status) {

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM orders
        WHERE restaurant_id = ?
        AND status = ?
    ");

    $stmt->execute([
        $restaurantId,
        $status
    ]);

    $statusCounts[$status] =
        (int) $stmt->fetchColumn();
}


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
        Orders - <?= htmlspecialchars($restaurant['name']) ?> | MloGo
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
           STATUS FILTERS
        ========================================== */

        .filter-card {

            background: white;

            border-radius: 16px;

            padding: 20px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.04);

            margin-bottom: 25px;

        }


        .status-filter {

            display: flex;

            gap: 10px;

            flex-wrap: wrap;

        }


        .status-filter a {

            text-decoration: none;

            padding: 9px 15px;

            border-radius: 30px;

            background: #f1f3f5;

            color: #495057;

            font-size: 14px;

            font-weight: 600;

        }


        .status-filter a:hover {

            background: #e9ecef;

        }


        .status-filter a.active {

            background: #20c997;

            color: white;

        }


        .count {

            margin-left: 5px;

            font-size: 12px;

            opacity: .8;

        }


        /* ==========================================
           ORDER CARD
        ========================================== */

        .order-card {

            background: white;

            border-radius: 16px;

            border: none;

            margin-bottom: 18px;

            padding: 22px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.04);

            transition: .2s;

        }


        .order-card:hover {

            transform: translateY(-2px);

            box-shadow:
                0 8px 25px
                rgba(0,0,0,.07);

        }


        .order-number {

            font-size: 17px;

            font-weight: 800;

        }


        .customer-name {

            font-weight: 700;

        }


        .customer-info {

            color: #6c757d;

            font-size: 14px;

        }


        .order-meta {

            display: flex;

            flex-wrap: wrap;

            gap: 15px;

            color: #6c757d;

            font-size: 14px;

        }


        .order-total {

            font-size: 20px;

            font-weight: 800;

        }


        /* ==========================================
           STATUS
        ========================================== */

        .status-badge {

            display: inline-block;

            padding: 7px 12px;

            border-radius: 30px;

            font-size: 12px;

            font-weight: 700;

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


        .status-out_for_delivery {

            background: #e2d9f3;

            color: #59359a;

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
           EMPTY
        ========================================== */

        .empty-state {

            background: white;

            border-radius: 16px;

            text-align: center;

            padding: 70px 20px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.04);

        }


        .empty-state i {

            font-size: 60px;

            color: #adb5bd;

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

            <a href="dashboard.php">

                <i class="bi bi-grid-1x2-fill"></i>

                <span>
                    Dashboard
                </span>

            </a>

        </li>


        <li>

            <a
                href="orders.php"
                class="active"
            >

                <i class="bi bi-receipt"></i>

                <span>
                    Orders
                </span>


                <?php if ($pendingCount > 0): ?>

                    <span
                        class="badge bg-danger ms-auto"
                    >

                        <?= $pendingCount ?>

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

                Orders

            </div>

            <small class="text-muted">

                Manage customer orders

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


        <!-- PAGE HEADER -->

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h3 class="fw-bold mb-1">

                    Restaurant Orders

                </h3>

                <p class="text-muted mb-0">

                    View and manage orders from your customers.

                </p>

            </div>

        </div>



        <!-- =================================================
             FILTER CARD
        ================================================== -->

        <div class="filter-card">


            <form
                method="GET"
                class="row g-3 mb-3"
            >

                <div class="col-md-8">

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-search"></i>

                        </span>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search order number, customer or phone..."
                            value="<?= htmlspecialchars($search) ?>"
                        >

                    </div>

                </div>


                <div class="col-md-4">

                    <button
                        type="submit"
                        class="btn btn-success w-100"
                    >

                        <i class="bi bi-search"></i>

                        Search Orders

                    </button>

                </div>

            </form>


            <div class="status-filter">


                <a
                    href="orders.php"
                    class="<?= $filter === 'all' ? 'active' : '' ?>"
                >

                    All

                    <span class="count">

                        <?= array_sum($statusCounts) ?>

                    </span>

                </a>


                <?php foreach (
                    $allowedStatuses
                    as $status
                ): ?>


                    <a
                        href="orders.php?status=<?= urlencode($status) ?>"
                        class="<?= $filter === $status ? 'active' : '' ?>"
                    >

                        <?= ucwords(
                            str_replace(
                                '_',
                                ' ',
                                $status
                            )
                        ) ?>


                        <span class="count">

                            <?= $statusCounts[$status] ?>

                        </span>

                    </a>


                <?php endforeach; ?>


            </div>

        </div>



        <!-- =================================================
             ORDERS
        ================================================== -->

        <?php if (empty($orders)): ?>


            <div class="empty-state">

                <i class="bi bi-receipt-cutoff"></i>

                <h4 class="mt-3">

                    No Orders Found

                </h4>

                <p class="text-muted">

                    There are no orders matching your current filter.

                </p>

            </div>


        <?php else: ?>


            <?php foreach (
                $orders
                as $order
            ): ?>


                <div class="order-card">


                    <!-- TOP -->

                    <div
                        class="d-flex justify-content-between align-items-start mb-3"
                    >


                        <div>

                            <div class="order-number">

                                #<?= htmlspecialchars(
                                    $order['order_number']
                                ) ?>

                            </div>

                            <small class="text-muted">

                                <?= date(
                                    'd M Y, H:i',
                                    strtotime(
                                        $order['created_at']
                                    )
                                ) ?>

                            </small>

                        </div>


                   <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
                            <?= ucwords(
                                str_replace(
                                    '_',
                                    ' ',
                                    $order['status']
                                )
                            ) ?>

                        </span>


                    </div>



                    <div class="row g-4">


                        <!-- CUSTOMER -->

                        <div class="col-md-4">

                            <div class="customer-name">

                                <i class="bi bi-person-fill"></i>

                                <?= htmlspecialchars(
                                    $order['first_name']
                                    . ' '
                                    . $order['last_name']
                                ) ?>

                            </div>


                            <?php if (
                                !empty(
                                    $order['phone']
                                )
                            ): ?>

                                <div class="customer-info mt-1">

                                    <i class="bi bi-telephone"></i>

                                    <?= htmlspecialchars(
                                        $order['phone']
                                    ) ?>

                                </div>

                            <?php endif; ?>


                            <div class="customer-info mt-1">

                                <i class="bi bi-envelope"></i>

                                <?= htmlspecialchars(
                                    $order['email']
                                ) ?>

                            </div>

                        </div>



                        <!-- ORDER TYPE -->

                        <div class="col-md-3">

                            <div class="customer-info mb-1">

                                Order Type

                            </div>


                            <?php if (
                                $order['order_type']
                                === 'delivery'
                            ): ?>

                                <strong>

                                    🚚 Delivery

                                </strong>

                            <?php else: ?>

                                <strong>

                                    🥡 Pickup

                                </strong>

                            <?php endif; ?>


                            <?php if (
                                $order['order_type']
                                === 'delivery'
                                &&
                                !empty(
                                    $order['delivery_address']
                                )
                            ): ?>

                                <div
                                    class="customer-info mt-2"
                                >

                                    <i class="bi bi-geo-alt"></i>

                                    <?= htmlspecialchars(
                                        $order[
                                            'delivery_address'
                                        ]
                                    ) ?>

                                </div>

                            <?php endif; ?>

                        </div>



                        <!-- PAYMENT -->

                        <div class="col-md-2">

                            <div class="customer-info mb-1">

                                Payment

                            </div>


                            <strong>

                                <?= htmlspecialchars(
                                    ucfirst(
                                        $order[
                                            'payment_method'
                                        ]
                                    )
                                ) ?>

                            </strong>


                            <div class="small text-muted">

                                <?= htmlspecialchars(
                                    ucfirst(
                                        $order[
                                            'payment_status'
                                        ]
                                    )
                                ) ?>

                            </div>

                        </div>



                        <!-- TOTAL -->

                        <div
                            class="col-md-3 text-md-end"
                        >

                            <div class="customer-info mb-1">

                                Total

                            </div>


                            <div class="order-total">

                                TZS
                                <?= number_format(
                                    (float)
                                    $order[
                                        'total_amount'
                                    ]
                                ) ?>

                            </div>

                        </div>


                    </div>



                    <!-- NOTES -->

                    <?php if (
                        !empty(
                            $order['customer_notes']
                        )
                    ): ?>


                        <div
                            class="
                                alert
                                alert-light
                                border
                                mt-4
                                mb-0
                            "
                        >

                            <strong>

                                <i
                                    class="bi bi-chat-left-text"
                                ></i>

                                Customer Note:

                            </strong>

                            <?= htmlspecialchars(
                                $order[
                                    'customer_notes'
                                ]
                            ) ?>

                        </div>


                    <?php endif; ?>



                    <!-- BOTTOM -->

                    <div
                        class="
                            d-flex
                            justify-content-between
                            align-items-center
                            mt-4
                            pt-3
                            border-top
                        "
                    >


                        <div class="order-meta">

                            <span>

                                <i class="bi bi-clock"></i>

                                <?php

                                if (
                                    !empty(
                                        $order[
                                            'estimated_preparation_minutes'
                                        ]
                                    )
                                ) {

                                    echo
                                        (int)
                                        $order[
                                            'estimated_preparation_minutes'
                                        ]
                                        . " min preparation";

                                } else {

                                    echo
                                        "Preparation time not set";

                                }

                                ?>

                            </span>

                        </div>


                        <a
                            href="order-details.php?id=<?= (int)$order['id'] ?>"
                            class="btn btn-outline-success"
                        >

                            <i class="bi bi-eye"></i>

                            View Order

                        </a>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php endif; ?>


    </div>

</main>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>