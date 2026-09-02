<?php

session_start();

require_once "../config/database.php";
require_once "../includes/functions.php";


// =====================================================
// CHECK SUPER ADMIN
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
// FILTERS
// =====================================================

$status = trim($_GET['status'] ?? '');

$restaurantId = filter_input(
    INPUT_GET,
    'restaurant_id',
    FILTER_VALIDATE_INT
);

$search = trim($_GET['search'] ?? '');


// =====================================================
// GET RESTAURANTS
// =====================================================

$restaurantStmt = $pdo->query("
    SELECT
        id,
        name
    FROM restaurants
    ORDER BY name ASC
");

$restaurants = $restaurantStmt->fetchAll();


// =====================================================
// ORDER STATISTICS
// =====================================================

$statsStmt = $pdo->query("
    SELECT

        COUNT(*) AS total_orders,

        SUM(
            CASE
                WHEN status = 'pending'
                THEN 1
                ELSE 0
            END
        ) AS pending_orders,

        SUM(
            CASE
                WHEN status = 'preparing'
                THEN 1
                ELSE 0
            END
        ) AS preparing_orders,

        SUM(
            CASE
                WHEN status = 'out_for_delivery'
                THEN 1
                ELSE 0
            END
        ) AS delivery_orders,

        SUM(
            CASE
                WHEN status = 'completed'
                THEN 1
                ELSE 0
            END
        ) AS completed_orders,

        SUM(
            CASE
                WHEN status IN ('cancelled', 'denied')
                THEN 1
                ELSE 0
            END
        ) AS cancelled_orders

    FROM orders
");

$stats = $statsStmt->fetch();


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
        o.placed_at,
        o.created_at,

        u.first_name,
        u.last_name,
        u.email,
        u.phone,

        r.name AS restaurant_name

    FROM orders o

    INNER JOIN users u
        ON u.id = o.customer_id

    INNER JOIN restaurants r
        ON r.id = o.restaurant_id

    WHERE 1 = 1
";


$params = [];


// =====================================================
// STATUS FILTER
// =====================================================

if ($status !== '') {

    $sql .= "
        AND o.status = ?
    ";

    $params[] = $status;
}


// =====================================================
// RESTAURANT FILTER
// =====================================================

if ($restaurantId) {

    $sql .= "
        AND o.restaurant_id = ?
    ";

    $params[] = $restaurantId;
}


// =====================================================
// SEARCH
// =====================================================

if ($search !== '') {

    $sql .= "
        AND (
            o.order_number LIKE ?
            OR u.first_name LIKE ?
            OR u.last_name LIKE ?
            OR u.email LIKE ?
            OR r.name LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}


// =====================================================
// ORDERING
// =====================================================

$sql .= "
    ORDER BY o.created_at DESC
";


// =====================================================
// EXECUTE
// =====================================================

$orderStmt = $pdo->prepare($sql);

$orderStmt->execute($params);

$orders = $orderStmt->fetchAll();


// =====================================================
// STATUS CLASS FUNCTION
// =====================================================

function orderStatusClass($status)
{

    switch ($status) {

        case 'pending':
            return 'status-pending';

        case 'accepted':
            return 'status-accepted';

        case 'preparing':
            return 'status-preparing';

        case 'ready':
            return 'status-ready';

        case 'out_for_delivery':
            return 'status-delivery';

        case 'completed':
            return 'status-completed';

        case 'cancelled':
        case 'denied':
            return 'status-cancelled';

        default:
            return 'status-default';
    }
}


// =====================================================
// STATUS LABEL
// =====================================================

function orderStatusLabel($status)
{

    return ucwords(
        str_replace(
            '_',
            ' ',
            $status
        )
    );
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
        Orders Management - MloGo Admin
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


    <style>

        body {

            background: #f5f7fb;

        }


        .page-header {

            background: white;

            border-radius: 16px;

            padding: 22px 25px;

            margin-bottom: 25px;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.05);

        }


        .stat-card {

            background: white;

            border: none;

            border-radius: 16px;

            padding: 20px;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.05);

            height: 100%;

        }


        .stat-icon {

            width: 48px;

            height: 48px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;

        }


        .stat-number {

            font-size: 26px;

            font-weight: 700;

        }


        .filter-card {

            background: white;

            border-radius: 16px;

            padding: 20px;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.05);

        }


        .orders-card {

            background: white;

            border-radius: 16px;

            overflow: hidden;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.05);

        }


        .orders-card .card-header {

            background: white;

            border-bottom: 1px solid #eee;

            padding: 20px;

        }


        .table {

            margin-bottom: 0;

        }


        .table td {

            vertical-align: middle;

        }


        .order-number {

            font-weight: 700;

            color: #198754;

        }


        .customer-name {

            font-weight: 600;

        }


        .restaurant-name {

            color: #555;

        }


        .status-badge {

            display: inline-block;

            padding: 6px 11px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 600;

            white-space: nowrap;

        }


        .status-pending {

            background: #fff3cd;

            color: #664d03;

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


        .status-delivery {

            background: #e2d9f3;

            color: #432874;

        }


        .status-completed {

            background: #d1e7dd;

            color: #0f5132;

        }


        .status-cancelled {

            background: #f8d7da;

            color: #842029;

        }


        .status-default {

            background: #e2e3e5;

            color: #41464b;

        }


        .payment-paid {

            color: #198754;

            font-weight: 600;

        }


        .payment-unpaid {

            color: #dc3545;

            font-weight: 600;

        }


        .empty-state {

            padding: 70px 20px;

            text-align: center;

        }


        .empty-state i {

            font-size: 55px;

            color: #adb5bd;

        }

    </style>

</head>


<body>


<div class="container-fluid p-4">


    <!-- =================================================
         PAGE HEADER
    ================================================== -->

    <div class="page-header">


        <div class="d-flex justify-content-between align-items-center">


            <div>

                <h3 class="fw-bold mb-1">

                    <i class="bi bi-receipt-cutoff text-success me-2"></i>

                    Orders Management

                </h3>

                <p class="text-muted mb-0">

                    Monitor and manage all orders across MloGo.

                </p>

            </div>


            <a
                href="dashboard.php"
                class="btn btn-success"
            >

                <i class="bi bi-speedometer2 me-1"></i>

                Admin Dashboard

            </a>


        </div>


    </div>



    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="row g-4 mb-4">


        <!-- TOTAL -->

        <div class="col-xl-2 col-md-4 col-sm-6">

            <div class="stat-card">


                <div class="d-flex justify-content-between">


                    <div>

                        <small class="text-muted">

                            Total Orders

                        </small>

                        <div class="stat-number">

                            <?= number_format(
                                (int)$stats['total_orders']
                            ) ?>

                        </div>

                    </div>


                    <div
                        class="stat-icon bg-primary-subtle text-primary"
                    >

                        <i class="bi bi-receipt"></i>

                    </div>


                </div>


            </div>

        </div>



        <!-- PENDING -->

        <div class="col-xl-2 col-md-4 col-sm-6">

            <div class="stat-card">


                <div class="d-flex justify-content-between">


                    <div>

                        <small class="text-muted">

                            Pending

                        </small>

                        <div class="stat-number text-warning">

                            <?= number_format(
                                (int)$stats['pending_orders']
                            ) ?>

                        </div>

                    </div>


                    <div
                        class="stat-icon bg-warning-subtle text-warning"
                    >

                        <i class="bi bi-hourglass-split"></i>

                    </div>


                </div>


            </div>

        </div>



        <!-- PREPARING -->

        <div class="col-xl-2 col-md-4 col-sm-6">

            <div class="stat-card">


                <div class="d-flex justify-content-between">


                    <div>

                        <small class="text-muted">

                            Preparing

                        </small>

                        <div class="stat-number text-primary">

                            <?= number_format(
                                (int)$stats['preparing_orders']
                            ) ?>

                        </div>

                    </div>


                    <div
                        class="stat-icon bg-primary-subtle text-primary"
                    >

                        <i class="bi bi-fire"></i>

                    </div>


                </div>


            </div>

        </div>



        <!-- DELIVERY -->

        <div class="col-xl-2 col-md-4 col-sm-6">

            <div class="stat-card">


                <div class="d-flex justify-content-between">


                    <div>

                        <small class="text-muted">

                            Out for Delivery

                        </small>

                        <div class="stat-number text-purple">

                            <?= number_format(
                                (int)$stats['delivery_orders']
                            ) ?>

                        </div>

                    </div>


                    <div
                        class="stat-icon bg-info-subtle text-info"
                    >

                        <i class="bi bi-bicycle"></i>

                    </div>


                </div>


            </div>

        </div>



        <!-- COMPLETED -->

        <div class="col-xl-2 col-md-4 col-sm-6">

            <div class="stat-card">


                <div class="d-flex justify-content-between">


                    <div>

                        <small class="text-muted">

                            Completed

                        </small>

                        <div class="stat-number text-success">

                            <?= number_format(
                                (int)$stats['completed_orders']
                            ) ?>

                        </div>

                    </div>


                    <div
                        class="stat-icon bg-success-subtle text-success"
                    >

                        <i class="bi bi-check-circle"></i>

                    </div>


                </div>


            </div>

        </div>



        <!-- CANCELLED -->

        <div class="col-xl-2 col-md-4 col-sm-6">

            <div class="stat-card">


                <div class="d-flex justify-content-between">


                    <div>

                        <small class="text-muted">

                            Cancelled / Denied

                        </small>

                        <div class="stat-number text-danger">

                            <?= number_format(
                                (int)$stats['cancelled_orders']
                            ) ?>

                        </div>

                    </div>


                    <div
                        class="stat-icon bg-danger-subtle text-danger"
                    >

                        <i class="bi bi-x-circle"></i>

                    </div>


                </div>


            </div>

        </div>


    </div>



    <!-- =================================================
         FILTERS
    ================================================== -->

    <div class="filter-card mb-4">


        <form
            method="GET"
            class="row g-3 align-items-end"
        >


            <!-- SEARCH -->

            <div class="col-lg-4">

                <label class="form-label fw-semibold">

                    Search

                </label>

                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Order number, customer or restaurant..."
                    value="<?= htmlspecialchars($search) ?>"
                >

            </div>


            <!-- STATUS -->

            <div class="col-lg-3">

                <label class="form-label fw-semibold">

                    Status

                </label>

                <select
                    name="status"
                    class="form-select"
                >

                    <option value="">

                        All Statuses

                    </option>

                    <?php

                    $statuses = [
                        'pending',
                        'accepted',
                        'denied',
                        'preparing',
                        'ready',
                        'out_for_delivery',
                        'completed',
                        'cancelled'
                    ];

                    ?>


                    <?php foreach ($statuses as $statusOption): ?>

                        <option
                            value="<?= htmlspecialchars($statusOption) ?>"
                            <?= $status === $statusOption ? 'selected' : '' ?>
                        >

                            <?= htmlspecialchars(
                                orderStatusLabel($statusOption)
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- RESTAURANT -->

            <div class="col-lg-3">

                <label class="form-label fw-semibold">

                    Restaurant

                </label>

                <select
                    name="restaurant_id"
                    class="form-select"
                >

                    <option value="">

                        All Restaurants

                    </option>


                    <?php foreach ($restaurants as $restaurant): ?>

                        <option
                            value="<?= (int)$restaurant['id'] ?>"
                            <?= (int)$restaurantId === (int)$restaurant['id']
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= htmlspecialchars(
                                $restaurant['name']
                            ) ?>

                        </option>

                    <?php endforeach; ?>


                </select>

            </div>


            <!-- BUTTONS -->

            <div class="col-lg-2">

                <button
                    type="submit"
                    class="btn btn-success w-100 mb-2"
                >

                    <i class="bi bi-funnel me-1"></i>

                    Filter

                </button>


                <a
                    href="orders.php"
                    class="btn btn-outline-secondary w-100"
                >

                    Reset

                </a>

            </div>


        </form>


    </div>



    <!-- =================================================
         ORDERS TABLE
    ================================================== -->

    <div class="orders-card">


        <div class="card-header">


            <div class="d-flex justify-content-between align-items-center">


                <div>

                    <h5 class="fw-bold mb-1">

                        All Orders

                    </h5>

                    <small class="text-muted">

                        <?= number_format(count($orders)) ?>

                        order(s) found

                    </small>

                </div>


            </div>


        </div>



        <?php if (count($orders) > 0): ?>


            <div class="table-responsive">


                <table class="table table-hover">


                    <thead class="table-light">

                        <tr>

                            <th>Order</th>

                            <th>Customer</th>

                            <th>Restaurant</th>

                            <th>Type</th>

                            <th>Status</th>

                            <th>Payment</th>

                            <th>Total</th>

                            <th>Date</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach ($orders as $order): ?>


                        <tr>


                            <!-- ORDER -->

                            <td>

                                <div class="order-number">

                                    #<?= htmlspecialchars(
                                        $order['order_number']
                                    ) ?>

                                </div>

                            </td>


                            <!-- CUSTOMER -->

                            <td>

                                <div class="customer-name">

                                    <?= htmlspecialchars(
                                        $order['first_name']
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $order['last_name']
                                    ) ?>

                                </div>

                                <small class="text-muted">

                                    <?= htmlspecialchars(
                                        $order['phone']
                                        ?? $order['email']
                                    ) ?>

                                </small>

                            </td>


                            <!-- RESTAURANT -->

                            <td>

                                <div class="restaurant-name">

                                    <?= htmlspecialchars(
                                        $order['restaurant_name']
                                    ) ?>

                                </div>

                            </td>


                            <!-- TYPE -->

                            <td>

                                <?= htmlspecialchars(
                                    ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $order['order_type']
                                        )
                                    )
                                ) ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span
                                    class="status-badge
                                    <?= htmlspecialchars(
                                        orderStatusClass(
                                            $order['status']
                                        )
                                    ) ?>"
                                >

                                    <?= htmlspecialchars(
                                        orderStatusLabel(
                                            $order['status']
                                        )
                                    ) ?>

                                </span>

                            </td>


                            <!-- PAYMENT -->

                            <td>

                                <?php if (
                                    strtolower(
                                        $order['payment_status']
                                    ) === 'paid'
                                ): ?>

                                    <span class="payment-paid">

                                        <i class="bi bi-check-circle me-1"></i>

                                        Paid

                                    </span>

                                <?php else: ?>

                                    <span class="payment-unpaid">

                                        <i class="bi bi-clock me-1"></i>

                                        <?= htmlspecialchars(
                                            ucfirst(
                                                $order['payment_status']
                                            )
                                        ) ?>

                                    </span>

                                <?php endif; ?>


                            </td>


                            <!-- TOTAL -->

                            <td>

                                <strong>

                                    TZS
                                    <?= number_format(
                                        (float)$order['total_amount']
                                    ) ?>

                                </strong>

                            </td>


                            <!-- DATE -->

                            <td>

                                <small>

                                    <?= date(
                                        'd M Y',
                                        strtotime(
                                            $order['placed_at']
                                        )
                                    ) ?>

                                    <br>

                                    <span class="text-muted">

                                        <?= date(
                                            'H:i',
                                            strtotime(
                                                $order['placed_at']
                                            )
                                        ) ?>

                                    </span>

                                </small>

                            </td>


                            <!-- ACTION -->

                            <td>

                                <a
                                    href="order-view.php?id=<?= (int)$order['id'] ?>"
                                    class="btn btn-sm btn-outline-success"
                                    title="View Order"
                                >

                                    <i class="bi bi-eye"></i>

                                </a>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


        <?php else: ?>


            <div class="empty-state">


                <i class="bi bi-receipt"></i>


                <h5 class="mt-3">

                    No Orders Found

                </h5>


                <p class="text-muted">

                    There are no orders matching your current filters.

                </p>


                <a
                    href="orders.php"
                    class="btn btn-outline-success"
                >

                    Clear Filters

                </a>


            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>