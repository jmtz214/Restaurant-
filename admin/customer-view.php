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
// GET CUSTOMER ID
// =====================================================

$customerId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$customerId) {

    redirect('customers.php');

    exit;
}


// =====================================================
// GET CUSTOMER
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        id,
        first_name,
        last_name,
        email,
        phone,
        profile_image,
        is_active,
        email_verified_at,
        last_login_at,
        created_at,
        updated_at
    FROM users
    WHERE id = ?
      AND role = 'customer'
    LIMIT 1
");

$stmt->execute([
    $customerId
]);

$customer = $stmt->fetch();


if (!$customer) {

    redirect('customers.php');

    exit;
}


// =====================================================
// GET CUSTOMER ORDER STATISTICS
// =====================================================

$orderStatsStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_orders,
        COALESCE(
            SUM(
                CASE
                    WHEN status = 'completed'
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS completed_orders,
        COALESCE(
            SUM(
                CASE
                    WHEN status = 'pending'
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS pending_orders,
        COALESCE(
            SUM(
                CASE
                    WHEN status = 'cancelled'
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS cancelled_orders,
        COALESCE(
            SUM(
                CASE
                    WHEN status = 'completed'
                    THEN total_amount
                    ELSE 0
                END
            ),
            0
        ) AS total_spent
    FROM orders
    WHERE customer_id = ?
");

$orderStatsStmt->execute([
    $customerId
]);

$orderStats = $orderStatsStmt->fetch();


// =====================================================
// GET RECENT ORDERS
// =====================================================

$ordersStmt = $pdo->prepare("
    SELECT
        o.id,
        o.order_number,
        o.status,
        o.total_amount,
        o.order_type,
        o.placed_at,
        r.name AS restaurant_name
    FROM orders o

    LEFT JOIN restaurants r
        ON r.id = o.restaurant_id

    WHERE o.customer_id = ?

    ORDER BY o.created_at DESC

    LIMIT 10
");

$ordersStmt->execute([
    $customerId
]);

$orders = $ordersStmt->fetchAll();

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
        Customer Details - MloGo Admin
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

            border-radius: 15px;

            padding: 20px 25px;

            margin-bottom: 25px;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.05);

        }


        .profile-card {

            background: white;

            border: none;

            border-radius: 18px;

            padding: 30px;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.05);

        }


        .profile-avatar {

            width: 110px;

            height: 110px;

            border-radius: 50%;

            background: #e8f7ee;

            color: #198754;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 42px;

            font-weight: 700;

            overflow: hidden;

            margin: 0 auto 20px;

        }


        .profile-avatar img {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }


        .status-badge {

            display: inline-block;

            padding: 7px 13px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

        }


        .status-active {

            background: #d1e7dd;

            color: #0f5132;

        }


        .status-inactive {

            background: #f8d7da;

            color: #842029;

        }


        .info-card {

            background: white;

            border: none;

            border-radius: 18px;

            padding: 25px;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.05);

            height: 100%;

        }


        .info-row {

            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding: 13px 0;

            border-bottom: 1px solid #eee;

        }


        .info-row:last-child {

            border-bottom: none;

        }


        .info-label {

            color: #6c757d;

        }


        .stat-card {

            background: white;

            border: none;

            border-radius: 15px;

            padding: 20px;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.05);

        }


        .stat-number {

            font-size: 25px;

            font-weight: 700;

        }


        .table-card {

            background: white;

            border-radius: 18px;

            overflow: hidden;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.05);

        }


        .table-card .card-header {

            background: white;

            padding: 20px;

            border-bottom: 1px solid #eee;

        }


        .table td {

            vertical-align: middle;

        }


        .order-status {

            font-size: 12px;

            padding: 6px 10px;

            border-radius: 20px;

            font-weight: 600;

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


        .status-completed {

            background: #d1e7dd;

            color: #0f5132;

        }


        .status-cancelled,

        .status-denied {

            background: #f8d7da;

            color: #842029;

        }


        .status-out_for_delivery {

            background: #e2d9f3;

            color: #432874;

        }

    </style>

</head>


<body>


<div class="container-fluid p-4">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="page-header">


        <div class="d-flex justify-content-between align-items-center">


            <div>

                <h3 class="fw-bold mb-1">

                    <i class="bi bi-person me-2 text-success"></i>

                    Customer Details

                </h3>

                <p class="text-muted mb-0">

                    View customer profile and order activity.

                </p>

            </div>


            <a
                href="customers.php"
                class="btn btn-outline-secondary"
            >

                <i class="bi bi-arrow-left me-1"></i>

                Back to Customers

            </a>


        </div>


    </div>



    <!-- =================================================
         PROFILE + INFORMATION
    ================================================== -->

    <div class="row g-4 mb-4">


        <!-- PROFILE -->

        <div class="col-lg-4">


            <div class="profile-card text-center">


                <div class="profile-avatar">


                    <?php if (!empty($customer['profile_image'])): ?>

                        <img
                            src="../uploads/profiles/<?= htmlspecialchars($customer['profile_image']) ?>"
                            alt="Customer profile"
                        >

                    <?php else: ?>

                        <?= strtoupper(
                            substr(
                                $customer['first_name'],
                                0,
                                1
                            ) .
                            substr(
                                $customer['last_name'],
                                0,
                                1
                            )
                        ) ?>

                    <?php endif; ?>


                </div>


                <h4 class="fw-bold mb-1">

                    <?= htmlspecialchars(
                        $customer['first_name']
                    ) ?>

                    <?= htmlspecialchars(
                        $customer['last_name']
                    ) ?>

                </h4>


                <p class="text-muted mb-3">

                    <?= htmlspecialchars(
                        $customer['email']
                    ) ?>

                </p>


                <?php if ((int)$customer['is_active'] === 1): ?>

                    <span class="status-badge status-active">

                        <i class="bi bi-check-circle me-1"></i>

                        Active Account

                    </span>

                <?php else: ?>

                    <span class="status-badge status-inactive">

                        <i class="bi bi-x-circle me-1"></i>

                        Inactive Account

                    </span>

                <?php endif; ?>


            </div>


        </div>



        <!-- INFORMATION -->

        <div class="col-lg-8">


            <div class="info-card">


                <h5 class="fw-bold mb-3">

                    Account Information

                </h5>


                <div class="info-row">

                    <span class="info-label">

                        Customer ID

                    </span>

                    <strong>

                        #<?= (int)$customer['id'] ?>

                    </strong>

                </div>


                <div class="info-row">

                    <span class="info-label">

                        Full Name

                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            $customer['first_name']
                        ) ?>

                        <?= htmlspecialchars(
                            $customer['last_name']
                        ) ?>

                    </strong>

                </div>


                <div class="info-row">

                    <span class="info-label">

                        Email

                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            $customer['email']
                        ) ?>

                    </strong>

                </div>


                <div class="info-row">

                    <span class="info-label">

                        Phone

                    </span>

                    <strong>

                        <?= !empty($customer['phone'])
                            ? htmlspecialchars($customer['phone'])
                            : 'Not provided'
                        ?>

                    </strong>

                </div>


                <div class="info-row">

                    <span class="info-label">

                        Email Verification

                    </span>


                    <?php if (!empty($customer['email_verified_at'])): ?>

                        <span class="text-success fw-semibold">

                            <i class="bi bi-check-circle-fill"></i>

                            Verified

                        </span>

                    <?php else: ?>

                        <span class="text-muted">

                            Not verified

                        </span>

                    <?php endif; ?>


                </div>


                <div class="info-row">

                    <span class="info-label">

                        Registered

                    </span>

                    <strong>

                        <?= date(
                            'd M Y, H:i',
                            strtotime(
                                $customer['created_at']
                            )
                        ) ?>

                    </strong>

                </div>


                <div class="info-row">

                    <span class="info-label">

                        Last Login

                    </span>

                    <strong>

                        <?php if (!empty($customer['last_login_at'])): ?>

                            <?= date(
                                'd M Y, H:i',
                                strtotime(
                                    $customer['last_login_at']
                                )
                            ) ?>

                        <?php else: ?>

                            Never

                        <?php endif; ?>

                    </strong>

                </div>


            </div>


        </div>


    </div>



    <!-- =================================================
         ORDER STATISTICS
    ================================================== -->

    <div class="row g-4 mb-4">


        <div class="col-md-3">

            <div class="stat-card">

                <small class="text-muted">

                    Total Orders

                </small>

                <div class="stat-number text-primary">

                    <?= number_format(
                        (int)$orderStats['total_orders']
                    ) ?>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="stat-card">

                <small class="text-muted">

                    Completed

                </small>

                <div class="stat-number text-success">

                    <?= number_format(
                        (int)$orderStats['completed_orders']
                    ) ?>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="stat-card">

                <small class="text-muted">

                    Pending

                </small>

                <div class="stat-number text-warning">

                    <?= number_format(
                        (int)$orderStats['pending_orders']
                    ) ?>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="stat-card">

                <small class="text-muted">

                    Total Spent

                </small>

                <div class="stat-number text-success">

                    TZS
                    <?= number_format(
                        (float)$orderStats['total_spent']
                    ) ?>

                </div>

            </div>

        </div>


    </div>



    <!-- =================================================
         RECENT ORDERS
    ================================================== -->

    <div class="table-card">


        <div class="card-header">


            <h5 class="fw-bold mb-0">

                Recent Orders

            </h5>


        </div>


        <div class="table-responsive">


            <?php if (count($orders) > 0): ?>


                <table class="table table-hover mb-0">


                    <thead class="table-light">

                        <tr>

                            <th>Order</th>

                            <th>Restaurant</th>

                            <th>Order Type</th>

                            <th>Status</th>

                            <th>Total</th>

                            <th>Date</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach ($orders as $order): ?>


                        <tr>


                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $order['order_number']
                                    ) ?>

                                </strong>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $order['restaurant_name']
                                    ?? 'Unknown'
                                ) ?>

                            </td>


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


                            <td>


                                <?php

                                $statusClass =
                                    'status-' .
                                    strtolower(
                                        $order['status']
                                    );

                                ?>


                                <span
                                    class="order-status <?= htmlspecialchars($statusClass) ?>"
                                >

                                    <?= htmlspecialchars(
                                        ucfirst(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $order['status']
                                            )
                                        )
                                    ) ?>

                                </span>


                            </td>


                            <td>

                                <strong>

                                    TZS
                                    <?= number_format(
                                        (float)$order['total_amount']
                                    ) ?>

                                </strong>

                            </td>


                            <td>

                                <?= date(
                                    'd M Y, H:i',
                                    strtotime(
                                        $order['placed_at']
                                    )
                                ) ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                </table>


            <?php else: ?>


                <div class="text-center py-5">

                    <i
                        class="bi bi-receipt text-muted"
                        style="font-size:50px;"
                    ></i>

                    <h5 class="mt-3">

                        No Orders Yet

                    </h5>

                    <p class="text-muted">

                        This customer has not placed any orders.

                    </p>

                </div>


            <?php endif; ?>


        </div>


    </div>


</div>


</body>

</html>