<?php

session_start();

require_once "../config/database.php";
require_once "../includes/functions.php";


// =====================================================
// AUTHENTICATION
// =====================================================

if (
    empty($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'super_admin'
) {
    header("Location: ../login.php");
    exit;
}


// =====================================================
// TOTAL CUSTOMERS
// =====================================================

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'customer'
");

$totalCustomers = (int) $stmt->fetchColumn();


// =====================================================
// RESTAURANT ADMINS
// =====================================================

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'restaurant_admin'
");

$totalRestaurantAdmins = (int) $stmt->fetchColumn();


// =====================================================
// TOTAL RESTAURANTS
// =====================================================

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM restaurants
");

$totalRestaurants = (int) $stmt->fetchColumn();


// =====================================================
// APPROVED RESTAURANTS
// =====================================================

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM restaurants
    WHERE status = 'approved'
");

$approvedRestaurants = (int) $stmt->fetchColumn();


// =====================================================
// PENDING RESTAURANTS
// =====================================================

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM restaurants
    WHERE status = 'pending'
");

$pendingRestaurants = (int) $stmt->fetchColumn();


// =====================================================
// SUSPENDED RESTAURANTS
// =====================================================

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM restaurants
    WHERE status = 'suspended'
");

$suspendedRestaurants = (int) $stmt->fetchColumn();


// =====================================================
// TOTAL ORDERS
// =====================================================

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
");

$totalOrders = (int) $stmt->fetchColumn();


// =====================================================
// TODAY'S ORDERS
// =====================================================

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE DATE(placed_at) = CURDATE()
");

$todayOrders = (int) $stmt->fetchColumn();


// =====================================================
// TODAY'S SALES
// =====================================================

$stmt = $pdo->query("
    SELECT COALESCE(
        SUM(total_amount),
        0
    )

    FROM orders

    WHERE DATE(placed_at) = CURDATE()

      AND status NOT IN (
          'cancelled',
          'denied'
      )
");

$todaySales = (float) $stmt->fetchColumn();


// =====================================================
// TOTAL PLATFORM SALES
// =====================================================

$stmt = $pdo->query("
    SELECT COALESCE(
        SUM(total_amount),
        0
    )

    FROM orders

    WHERE status NOT IN (
        'cancelled',
        'denied'
    )
");

$totalSales = (float) $stmt->fetchColumn();


// =====================================================
// RECENT ORDERS
// =====================================================

$stmt = $pdo->query("
    SELECT

        o.id,
        o.order_number,
        o.total_amount,
        o.status,
        o.order_type,
        o.placed_at,

        u.first_name,
        u.last_name,

        r.name AS restaurant_name

    FROM orders o

    INNER JOIN users u
        ON u.id = o.customer_id

    INNER JOIN restaurants r
        ON r.id = o.restaurant_id

    ORDER BY o.placed_at DESC

    LIMIT 10
");

$recentOrders = $stmt->fetchAll();


// =====================================================
// PENDING RESTAURANTS
// =====================================================

$stmt = $pdo->query("
    SELECT

        r.id,
        r.name,
        r.phone,
        r.email,
        r.city,
        r.created_at,

        u.first_name,
        u.last_name

    FROM restaurants r

    INNER JOIN users u
        ON u.id = r.owner_id

    WHERE r.status = 'pending'

    ORDER BY r.created_at DESC

    LIMIT 5
");

$pendingRestaurantList =
    $stmt->fetchAll();


// =====================================================
// ORDER STATUS
// =====================================================

$stmt = $pdo->query("
    SELECT
        status,
        COUNT(*) AS total

    FROM orders

    GROUP BY status

    ORDER BY total DESC
");

$orderStatuses =
    $stmt->fetchAll();


// =====================================================
// SALES — LAST 7 DAYS
// =====================================================

$stmt = $pdo->query("
    SELECT

        DATE(placed_at) AS sale_date,

        COALESCE(
            SUM(total_amount),
            0
        ) AS total_sales

    FROM orders

    WHERE DATE(placed_at)
        >= DATE_SUB(
            CURDATE(),
            INTERVAL 6 DAY
        )

      AND status NOT IN (
          'cancelled',
          'denied'
      )

    GROUP BY DATE(placed_at)

    ORDER BY sale_date ASC
");

$weeklySales =
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
        Admin Dashboard - MloGo
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

            background: #f5f7fa;

        }


        .admin-wrapper {

            max-width: 1500px;

            margin: auto;

        }


        .stat-card {

            background: white;

            border: none;

            border-radius: 16px;

            padding: 22px;

            height: 100%;

            box-shadow:
                0 4px 20px
                rgba(0,0,0,.05);

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


        .stat-number {

            font-size: 28px;

            font-weight: 800;

        }


        .dashboard-card {

            background: white;

            border: none;

            border-radius: 16px;

            box-shadow:
                0 4px 20px
                rgba(0,0,0,.05);

        }


        .chart-container {

            height: 330px;

            position: relative;

        }


        .restaurant-row {

            padding: 15px 0;

            border-bottom:
                1px solid #eee;

        }


        .restaurant-row:last-child {

            border-bottom: none;

        }


        .table > :not(caption) > * > * {

            padding: 14px 10px;

        }


        .status-badge {

            padding: 7px 12px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

        }


        .sidebar-link {

            text-decoration: none;

            color: #495057;

            display: block;

            padding: 10px 12px;

            border-radius: 8px;

            margin-bottom: 4px;

        }


        .sidebar-link:hover {

            background: #e8f8f1;

            color: #198754;

        }

    </style>

</head>


<body>


<div class="container-fluid py-4">

<div class="admin-wrapper">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div
        class="d-flex
               justify-content-between
               align-items-center
               mb-4"
    >

        <div>

            <h2 class="fw-bold mb-1">

                MloGo Admin Dashboard

            </h2>

            <p class="text-muted mb-0">

                Platform overview and management

            </p>

        </div>


        <div class="d-flex align-items-center gap-3">

            <div class="text-end">

                <div class="fw-bold">

                    <?= htmlspecialchars(
                        $_SESSION['first_name']
                    ) ?>

                </div>

                <small class="text-muted">

                    Super Administrator

                </small>

            </div>


            <a
                href="../logout.php"
                class="btn btn-outline-danger"
            >

                <i class="bi bi-box-arrow-right"></i>

                Logout

            </a>

        </div>

    </div>



    <!-- =================================================
         QUICK NAVIGATION
    ================================================== -->

    <div class="dashboard-card p-3 mb-4">

        <div class="d-flex flex-wrap gap-2">

            <a
                href="dashboard.php"
                class="btn btn-success"
            >

                <i class="bi bi-speedometer2"></i>

                Dashboard

            </a>


            <a
                href="restaurants.php"
                class="btn btn-light"
            >

                <i class="bi bi-shop"></i>

                Restaurants

            </a>


            <a
                href="Customers.php"
                class="btn btn-light"
            >

                <i class="bi bi-people"></i>

                Customers

            </a>


            <a
                href="orders.php"
                class="btn btn-light"
            >

                <i class="bi bi-receipt"></i>

                Orders

            </a>


            <a
                href="analytics.php"
                class="btn btn-light"
            >

                <i class="bi bi-bar-chart"></i>

                Analytics

            </a>


            <a
                href="settings.php"
                class="btn btn-light"
            >

                <i class="bi bi-gear"></i>

                Settings

            </a>

        </div>

    </div>



    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="row g-4 mb-4">


        <!-- CUSTOMERS -->

        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-icon mb-3">

                    <i class="bi bi-people"></i>

                </div>


                <div class="text-muted">

                    Customers

                </div>


                <div class="stat-number">

                    <?= number_format(
                        $totalCustomers
                    ) ?>

                </div>

            </div>

        </div>


        <!-- RESTAURANTS -->

        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-icon mb-3">

                    <i class="bi bi-shop"></i>

                </div>


                <div class="text-muted">

                    Restaurants

                </div>


                <div class="stat-number">

                    <?= number_format(
                        $totalRestaurants
                    ) ?>

                </div>


                <small class="text-success">

                    <?= $approvedRestaurants ?>

                    approved

                </small>

            </div>

        </div>


        <!-- ORDERS -->

        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-icon mb-3">

                    <i class="bi bi-bag-check"></i>

                </div>


                <div class="text-muted">

                    Total Orders

                </div>


                <div class="stat-number">

                    <?= number_format(
                        $totalOrders
                    ) ?>

                </div>

            </div>

        </div>


        <!-- SALES -->

        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-icon mb-3">

                    <i class="bi bi-cash-stack"></i>

                </div>


                <div class="text-muted">

                    Total Sales

                </div>


                <div class="stat-number">

                    TZS
                    <?= number_format(
                        $totalSales
                    ) ?>

                </div>

            </div>

        </div>


    </div>



    <!-- =================================================
         SECONDARY STATISTICS
    ================================================== -->

    <div class="row g-4 mb-4">


        <div class="col-md-4">

            <div class="stat-card">

                <div class="text-muted">

                    Today's Orders

                </div>

                <div class="stat-number">

                    <?= number_format(
                        $todayOrders
                    ) ?>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="stat-card">

                <div class="text-muted">

                    Today's Sales

                </div>

                <div class="stat-number">

                    TZS
                    <?= number_format(
                        $todaySales
                    ) ?>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="stat-card">

                <div class="text-muted">

                    Pending Restaurants

                </div>

                <div class="stat-number text-warning">

                    <?= number_format(
                        $pendingRestaurants
                    ) ?>

                </div>

            </div>

        </div>


    </div>



    <!-- =================================================
         CHARTS
    ================================================== -->

    <div class="row g-4 mb-4">


        <!-- SALES -->

        <div class="col-lg-8">

            <div class="dashboard-card p-4">

                <h5 class="fw-bold">

                    Platform Sales

                </h5>


                <p class="text-muted">

                    Sales during the last 7 days

                </p>


                <div class="chart-container">

                    <canvas id="salesChart"></canvas>

                </div>

            </div>

        </div>



        <!-- STATUS -->

        <div class="col-lg-4">

            <div class="dashboard-card p-4">

                <h5 class="fw-bold">

                    Order Status

                </h5>


                <p class="text-muted">

                    Current order distribution

                </p>


                <div class="chart-container">

                    <canvas id="statusChart"></canvas>

                </div>

            </div>

        </div>


    </div>



    <!-- =================================================
         RECENT ORDERS + PENDING RESTAURANTS
    ================================================== -->

    <div class="row g-4">


        <!-- RECENT ORDERS -->

        <div class="col-lg-8">

            <div class="dashboard-card p-4">

                <div
                    class="d-flex
                           justify-content-between
                           align-items-center
                           mb-3"
                >

                    <div>

                        <h5 class="fw-bold mb-1">

                            Recent Orders

                        </h5>

                        <small class="text-muted">

                            Latest platform activity

                        </small>

                    </div>


                    <a
                        href="orders.php"
                        class="btn btn-sm btn-outline-success"
                    >

                        View All

                    </a>

                </div>


                <div class="table-responsive">

                    <table class="table align-middle">

                        <thead>

                        <tr>

                            <th>Order</th>

                            <th>Customer</th>

                            <th>Restaurant</th>

                            <th>Amount</th>

                            <th>Status</th>

                        </tr>

                        </thead>


                        <tbody>


                        <?php if (
                            !empty($recentOrders)
                        ): ?>


                            <?php foreach (
                                $recentOrders
                                as $order
                            ): ?>


                                <tr>

                                    <td>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $order[
                                                    'order_number'
                                                ]
                                            ) ?>

                                        </strong>


                                        <br>


                                        <small
                                            class="text-muted"
                                        >

                                            <?= date(
                                                'd M H:i',
                                                strtotime(
                                                    $order[
                                                        'placed_at'
                                                    ]
                                                )
                                            ) ?>

                                        </small>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $order[
                                                'first_name'
                                            ] .
                                            ' ' .
                                            $order[
                                                'last_name'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $order[
                                                'restaurant_name'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        TZS
                                        <?= number_format(
                                            $order[
                                                'total_amount'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>


                                        <?php

                                        $statusClass =
                                            match (
                                                $order['status']
                                            ) {

                                                'completed'
                                                    => 'bg-success',

                                                'cancelled'
                                                    => 'bg-danger',

                                                'denied'
                                                    => 'bg-danger',

                                                'accepted'
                                                    => 'bg-primary',

                                                'preparing'
                                                    => 'bg-warning text-dark',

                                                'ready'
                                                    => 'bg-info text-dark',

                                                default
                                                    => 'bg-secondary'

                                            };

                                        ?>


                                        <span
                                            class="
                                                status-badge
                                                <?= $statusClass ?>
                                            "
                                        >

                                            <?= ucfirst(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    $order[
                                                        'status'
                                                    ]
                                                )
                                            ) ?>

                                        </span>

                                    </td>

                                </tr>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="5"
                                    class="text-center
                                           text-muted
                                           py-5"
                                >

                                    No orders found.

                                </td>

                            </tr>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </div>

        </div>



        <!-- PENDING RESTAURANTS -->

        <div class="col-lg-4">

            <div class="dashboard-card p-4">

                <div
                    class="d-flex
                           justify-content-between
                           align-items-center
                           mb-3"
                >

                    <div>

                        <h5 class="fw-bold mb-1">

                            Pending Restaurants

                        </h5>

                        <small class="text-muted">

                            Require your attention

                        </small>

                    </div>


                    <span class="badge bg-warning text-dark">

                        <?= $pendingRestaurants ?>

                    </span>

                </div>


                <?php if (
                    !empty(
                        $pendingRestaurantList
                    )
                ): ?>


                    <?php foreach (
                        $pendingRestaurantList
                        as $pending
                    ): ?>


                        <div
                            class="restaurant-row"
                        >

                            <div
                                class="d-flex
                                       justify-content-between"
                            >

                                <div>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $pending[
                                                'name'
                                            ]
                                        ) ?>

                                    </strong>


                                    <div>

                                        <small
                                            class="text-muted"
                                        >

                                            <?= htmlspecialchars(
                                                $pending[
                                                    'city'
                                                ]
                                            ) ?>

                                        </small>

                                    </div>

                                </div>


                                <a
                                    href="restaurant-view.php?id=<?= (int)$pending['id'] ?>"
                                    class="btn btn-sm
                                           btn-outline-success"
                                >

                                    View

                                </a>

                            </div>

                        </div>


                    <?php endforeach; ?>


                <?php else: ?>


                    <div
                        class="text-center
                               text-muted
                               py-4"
                    >

                        <i
                            class="bi bi-check-circle
                                   fs-1"
                        ></i>


                        <p class="mt-2 mb-0">

                            No pending restaurants.

                        </p>

                    </div>


                <?php endif; ?>


            </div>

        </div>


    </div>


</div>

</div>



<script>


// =====================================================
// SALES CHART
// =====================================================

const salesLabels = [

<?php foreach (
    $weeklySales
    as $sale
): ?>

    "<?= date(
        'D',
        strtotime(
            $sale['sale_date']
        )
    ) ?>",

<?php endforeach; ?>

];


const salesValues = [

<?php foreach (
    $weeklySales
    as $sale
): ?>

    <?= (float)$sale['total_sales'] ?>,

<?php endforeach; ?>

];


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
// ORDER STATUS CHART
// =====================================================

const statusLabels = [

<?php foreach (
    $orderStatuses
    as $status
): ?>

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

<?php foreach (
    $orderStatuses
    as $status
): ?>

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

</script>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>