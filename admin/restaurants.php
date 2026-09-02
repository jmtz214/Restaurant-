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
// SEARCH
// =====================================================

$search = trim($_GET['search'] ?? '');


// =====================================================
// STATUS FILTER
// =====================================================

$status = $_GET['status'] ?? '';

$allowedStatuses = [
    'pending',
    'approved',
    'suspended',
    'rejected'
];


// =====================================================
// BUILD QUERY
// =====================================================

$sql = "
    SELECT

        r.id,
        r.owner_id,
        r.name,
        r.slug,
        r.description,
        r.phone,
        r.email,
        r.logo,
        r.cover_image,
        r.address,
        r.city,
        r.region,
        r.latitude,
        r.longitude,
        r.opening_time,
        r.closing_time,
        r.delivery_available,
        r.pickup_available,
        r.minimum_order_amount,
        r.delivery_fee,
        r.status,
        r.rating,
        r.total_reviews,
        r.created_at,

        u.first_name,
        u.last_name,
        u.email AS owner_email,
        u.phone AS owner_phone

    FROM restaurants r

    INNER JOIN users u
        ON u.id = r.owner_id

    WHERE 1 = 1
";


$params = [];


// =====================================================
// SEARCH FILTER
// =====================================================

if ($search !== '') {

    $sql .= "
        AND (
            r.name LIKE ?
            OR r.phone LIKE ?
            OR r.email LIKE ?
            OR r.city LIKE ?
            OR r.region LIKE ?
            OR u.first_name LIKE ?
            OR u.last_name LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params = [
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue
    ];
}


// =====================================================
// STATUS FILTER
// =====================================================

if (
    $status !== '' &&
    in_array(
        $status,
        $allowedStatuses,
        true
    )
) {

    $sql .= "
        AND r.status = ?
    ";

    $params[] = $status;
}


// =====================================================
// ORDER
// =====================================================

$sql .= "
    ORDER BY r.created_at DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$restaurants = $stmt->fetchAll();


// =====================================================
// STATUS COUNTS
// =====================================================

$stmt = $pdo->query("
    SELECT
        status,
        COUNT(*) AS total

    FROM restaurants

    GROUP BY status
");

$statusCounts = [];

foreach (
    $stmt->fetchAll()
    as $row
) {

    $statusCounts[
        $row['status']
    ] = (int)$row['total'];

}


$totalRestaurants =
    array_sum($statusCounts);

$pendingCount =
    $statusCounts['pending'] ?? 0;

$approvedCount =
    $statusCounts['approved'] ?? 0;

$suspendedCount =
    $statusCounts['suspended'] ?? 0;

$rejectedCount =
    $statusCounts['rejected'] ?? 0;

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
        Restaurants - MloGo Admin
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <style>

        body {

            background: #f5f7fa;

        }


        .admin-wrapper {

            max-width: 1500px;

            margin: auto;

        }


        .dashboard-card {

            background: white;

            border: none;

            border-radius: 16px;

            box-shadow:
                0 4px 20px
                rgba(0,0,0,.05);

        }


        .stat-card {

            background: white;

            border: none;

            border-radius: 16px;

            padding: 20px;

            box-shadow:
                0 4px 20px
                rgba(0,0,0,.05);

        }


        .stat-number {

            font-size: 27px;

            font-weight: 800;

        }


        .restaurant-logo {

            width: 50px;

            height: 50px;

            border-radius: 12px;

            object-fit: cover;

            background: #eee;

        }


        .restaurant-logo-placeholder {

            width: 50px;

            height: 50px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #e8f8f1;

            color: #198754;

            font-size: 22px;

        }


        .status-badge {

            display: inline-block;

            padding: 7px 12px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

        }


        .table > :not(caption) > * > * {

            padding: 15px 10px;

        }


        .filter-card {

            background: white;

            border-radius: 16px;

            box-shadow:
                0 4px 20px
                rgba(0,0,0,.05);

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

                Restaurants

            </h2>

            <p class="text-muted mb-0">

                Manage restaurants registered on MloGo

            </p>

        </div>


        <a
            href="dashboard.php"
            class="btn btn-outline-success"
        >

            <i class="bi bi-arrow-left"></i>

            Dashboard

        </a>

    </div>



    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="row g-4 mb-4">


        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="text-muted">

                    Total Restaurants

                </div>

                <div class="stat-number">

                    <?= number_format(
                        $totalRestaurants
                    ) ?>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="text-muted">

                    Pending

                </div>

                <div class="stat-number text-warning">

                    <?= number_format(
                        $pendingCount
                    ) ?>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="text-muted">

                    Approved

                </div>

                <div class="stat-number text-success">

                    <?= number_format(
                        $approvedCount
                    ) ?>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="text-muted">

                    Suspended

                </div>

                <div class="stat-number text-danger">

                    <?= number_format(
                        $suspendedCount
                    ) ?>

                </div>

            </div>

        </div>


    </div>



    <!-- =================================================
         SEARCH / FILTER
    ================================================== -->

    <div class="filter-card p-4 mb-4">

        <form
            method="GET"
            class="row g-3 align-items-end"
        >


            <div class="col-md-6">

                <label
                    class="form-label fw-semibold"
                >

                    Search Restaurant

                </label>


                <div class="input-group">

                    <span
                        class="input-group-text"
                    >

                        <i class="bi bi-search"></i>

                    </span>


                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        value="<?= htmlspecialchars(
                            $search
                        ) ?>"
                        placeholder="
                            Restaurant name,
                            owner, phone, city...
                        "
                    >

                </div>

            </div>



            <div class="col-md-3">

                <label
                    class="form-label fw-semibold"
                >

                    Status

                </label>


                <select
                    name="status"
                    class="form-select"
                >

                    <option value="">

                        All Statuses

                    </option>


                    <?php foreach (
                        $allowedStatuses
                        as $allowedStatus
                    ): ?>

                        <option
                            value="<?= $allowedStatus ?>"
                            <?= $status === $allowedStatus
                                ? 'selected'
                                : '' ?>
                        >

                            <?= ucfirst(
                                $allowedStatus
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>



            <div class="col-md-3">

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-success flex-grow-1"
                    >

                        <i class="bi bi-filter"></i>

                        Filter

                    </button>


                    <a
                        href="restaurants.php"
                        class="btn btn-outline-secondary"
                    >

                        <i class="bi bi-x-lg"></i>

                    </a>

                </div>

            </div>


        </form>

    </div>



    <!-- =================================================
         RESTAURANTS TABLE
    ================================================== -->

    <div class="dashboard-card p-4">


        <div
            class="d-flex
                   justify-content-between
                   align-items-center
                   mb-3"
        >

            <div>

                <h5 class="fw-bold mb-1">

                    Restaurant List

                </h5>

                <small class="text-muted">

                    <?= count($restaurants) ?>

                    restaurant(s) found

                </small>

            </div>

        </div>



        <div class="table-responsive">

            <table
                class="table
                       align-middle
                       table-hover"
            >

                <thead>

                <tr>

                    <th>Restaurant</th>

                    <th>Owner</th>

                    <th>Location</th>

                    <th>Contact</th>

                    <th>Services</th>

                    <th>Rating</th>

                    <th>Status</th>

                    <th>Action</th>

                </tr>

                </thead>


                <tbody>


                <?php if (
                    !empty($restaurants)
                ): ?>


                    <?php foreach (
                        $restaurants
                        as $restaurant
                    ): ?>


                        <tr>


                            <!-- RESTAURANT -->

                            <td>

                                <div
                                    class="d-flex
                                           align-items-center
                                           gap-3"
                                >


                                    <?php if (
                                        !empty(
                                            $restaurant['logo']
                                        )
                                    ): ?>

                                        <img
                                            src="../uploads/restaurants/<?= htmlspecialchars(
                                                $restaurant['logo']
                                            ) ?>"
                                            class="restaurant-logo"
                                            alt=""
                                        >

                                    <?php else: ?>

                                        <div
                                            class="restaurant-logo-placeholder"
                                        >

                                            <i
                                                class="bi bi-shop"
                                            ></i>

                                        </div>

                                    <?php endif; ?>


                                    <div>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $restaurant[
                                                    'name'
                                                ]
                                            ) ?>

                                        </strong>


                                        <div>

                                            <small
                                                class="text-muted"
                                            >

                                                ID:
                                                #<?= (int)$restaurant['id'] ?>

                                            </small>

                                        </div>

                                    </div>


                                </div>

                            </td>



                            <!-- OWNER -->

                            <td>

                                <?= htmlspecialchars(
                                    $restaurant[
                                        'first_name'
                                    ] .
                                    ' ' .
                                    $restaurant[
                                        'last_name'
                                    ]
                                ) ?>


                                <br>


                                <small
                                    class="text-muted"
                                >

                                    <?= htmlspecialchars(
                                        $restaurant[
                                            'owner_email'
                                        ]
                                    ) ?>

                                </small>

                            </td>



                            <!-- LOCATION -->

                            <td>

                                <?= htmlspecialchars(
                                    $restaurant['city']
                                ) ?>


                                <?php if (
                                    !empty(
                                        $restaurant['region']
                                    )
                                ): ?>

                                    <br>

                                    <small
                                        class="text-muted"
                                    >

                                        <?= htmlspecialchars(
                                            $restaurant[
                                                'region'
                                            ]
                                        ) ?>

                                    </small>

                                <?php endif; ?>

                            </td>



                            <!-- CONTACT -->

                            <td>

                                <?= htmlspecialchars(
                                    $restaurant['phone']
                                ) ?>


                                <?php if (
                                    !empty(
                                        $restaurant['email']
                                    )
                                ): ?>

                                    <br>

                                    <small
                                        class="text-muted"
                                    >

                                        <?= htmlspecialchars(
                                            $restaurant['email']
                                        ) ?>

                                    </small>

                                <?php endif; ?>

                            </td>



                            <!-- SERVICES -->

                            <td>

                                <?php if (
                                    $restaurant[
                                        'delivery_available'
                                    ]
                                ): ?>

                                    <span
                                        class="badge
                                               bg-success"
                                    >

                                        Delivery

                                    </span>

                                <?php endif; ?>


                                <?php if (
                                    $restaurant[
                                        'pickup_available'
                                    ]
                                ): ?>

                                    <span
                                        class="badge
                                               bg-primary"
                                    >

                                        Pickup

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- RATING -->

                            <td>

                                <span
                                    class="text-warning"
                                >

                                    <i
                                        class="bi bi-star-fill"
                                    ></i>

                                </span>


                                <?= number_format(
                                    (float)$restaurant[
                                        'rating'
                                    ],
                                    1
                                ) ?>


                                <small
                                    class="text-muted"
                                >

                                    (<?= (int)$restaurant[
                                        'total_reviews'
                                    ] ?>)

                                </small>

                            </td>



                            <!-- STATUS -->

                            <td>


                                <?php

                                $statusClass =
                                    match (
                                        $restaurant['status']
                                    ) {

                                        'approved'
                                            => 'bg-success',

                                        'pending'
                                            => 'bg-warning text-dark',

                                        'suspended'
                                            => 'bg-danger',

                                        'rejected'
                                            => 'bg-secondary',

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
                                        $restaurant['status']
                                    ) ?>

                                </span>

                            </td>



                            <!-- ACTION -->

                            <td>

                                <a
                                    href="restaurant-view.php?id=<?= (int)$restaurant['id'] ?>"
                                    class="btn
                                           btn-sm
                                           btn-outline-success"
                                >

                                    <i
                                        class="bi bi-eye"
                                    ></i>

                                    View

                                </a>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="8"
                            class="text-center
                                   py-5"
                        >

                            <div
                                class="text-muted"
                            >

                                <i
                                    class="bi bi-shop
                                           fs-1"
                                ></i>


                                <h5 class="mt-3">

                                    No restaurants found

                                </h5>


                                <p>

                                    Try changing your
                                    search or filter.

                                </p>

                            </div>

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </div>


</div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>