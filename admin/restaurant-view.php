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
// GET RESTAURANT ID
// =====================================================

$restaurantId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$restaurantId) {
    header("Location: restaurants.php");
    exit;
}


// =====================================================
// GET RESTAURANT
// =====================================================

$stmt = $pdo->prepare("
    SELECT

        r.*,

        u.first_name AS owner_first_name,
        u.last_name AS owner_last_name,
        u.email AS owner_email,
        u.phone AS owner_phone,
        u.profile_image AS owner_image

    FROM restaurants r

    INNER JOIN users u
        ON u.id = r.owner_id

    WHERE r.id = ?

    LIMIT 1
");

$stmt->execute([
    $restaurantId
]);

$restaurant = $stmt->fetch();


if (!$restaurant) {
    header("Location: restaurants.php");
    exit;
}


// =====================================================
// MENU STATISTICS
// =====================================================

$stmt = $pdo->prepare("
    SELECT

        COUNT(*) AS total_items,

        SUM(
            CASE
                WHEN is_available = 1
                THEN 1
                ELSE 0
            END
        ) AS available_items,

        SUM(
            CASE
                WHEN is_available = 0
                THEN 1
                ELSE 0
            END
        ) AS unavailable_items

    FROM menu_items

    WHERE restaurant_id = ?
");

$stmt->execute([
    $restaurantId
]);

$menuStats = $stmt->fetch();


// =====================================================
// ORDER STATISTICS
// =====================================================

$stmt = $pdo->prepare("
    SELECT

        COUNT(*) AS total_orders,

        SUM(
            CASE
                WHEN status = 'completed'
                THEN 1
                ELSE 0
            END
        ) AS completed_orders,

        SUM(
            CASE
                WHEN status = 'pending'
                THEN 1
                ELSE 0
            END
        ) AS pending_orders,

        SUM(
            CASE
                WHEN status = 'cancelled'
                THEN 1
                ELSE 0
            END
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
        ) AS total_revenue

    FROM orders

    WHERE restaurant_id = ?
");

$stmt->execute([
    $restaurantId
]);

$orderStats = $stmt->fetch();


// =====================================================
// RECENT ORDERS
// =====================================================

$stmt = $pdo->prepare("
    SELECT

        o.id,
        o.order_number,
        o.status,
        o.total_amount,
        o.order_type,
        o.payment_status,
        o.created_at,

        u.first_name,
        u.last_name

    FROM orders o

    INNER JOIN users u
        ON u.id = o.customer_id

    WHERE o.restaurant_id = ?

    ORDER BY o.created_at DESC

    LIMIT 8
");

$stmt->execute([
    $restaurantId
]);

$recentOrders = $stmt->fetchAll();


// =====================================================
// RECENT MENU
// =====================================================

$stmt = $pdo->prepare("
    SELECT

        id,
        name,
        price,
        image,
        is_available,
        is_featured

    FROM menu_items

    WHERE restaurant_id = ?

    ORDER BY created_at DESC

    LIMIT 8
");

$stmt->execute([
    $restaurantId
]);

$recentMenu = $stmt->fetchAll();


// =====================================================
// STATUS CLASS
// =====================================================

$statusClass = match (
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

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>

        <?= htmlspecialchars(
            $restaurant['name']
        ) ?>

        - MloGo Admin

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


        .card-box {

            background: white;

            border: none;

            border-radius: 18px;

            box-shadow:
                0 5px 25px
                rgba(0,0,0,.05);

        }


        .restaurant-cover {

            height: 260px;

            border-radius: 18px;

            background-size: cover;

            background-position: center;

            position: relative;

        }


        .restaurant-cover-overlay {

            position: absolute;

            inset: 0;

            border-radius: 18px;

            background:
                linear-gradient(
                    to top,
                    rgba(0,0,0,.75),
                    rgba(0,0,0,.05)
                );

        }


        .restaurant-profile {

            position: absolute;

            bottom: 25px;

            left: 30px;

            right: 30px;

            color: white;

        }


        .restaurant-logo {

            width: 90px;

            height: 90px;

            border-radius: 18px;

            object-fit: cover;

            border: 4px solid white;

            background: white;

        }


        .logo-placeholder {

            width: 90px;

            height: 90px;

            border-radius: 18px;

            background: white;

            color: #198754;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 35px;

        }


        .stat-card {

            background: white;

            border-radius: 16px;

            padding: 22px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.05);

        }


        .stat-number {

            font-size: 28px;

            font-weight: 800;

        }


        .info-row {

            padding: 12px 0;

            border-bottom: 1px solid #eee;

        }


        .info-row:last-child {

            border-bottom: none;

        }


        .menu-image {

            width: 55px;

            height: 55px;

            object-fit: cover;

            border-radius: 10px;

        }


        .menu-placeholder {

            width: 55px;

            height: 55px;

            border-radius: 10px;

            background: #eee;

            display: flex;

            align-items: center;

            justify-content: center;

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

            <a
                href="restaurants.php"
                class="text-decoration-none"
            >

                <i class="bi bi-arrow-left"></i>

                Back to Restaurants

            </a>

        </div>


        <span
            class="badge <?= $statusClass ?>"
            style="
                padding:10px 16px;
                border-radius:20px;
            "
        >

            <?= ucfirst(
                $restaurant['status']
            ) ?>

        </span>

        <div class="d-flex gap-2">

    <?php if (
        $restaurant['status'] === 'pending'
    ): ?>

        <!-- APPROVE -->

        <form
            method="POST"
            action="restaurant-action.php"
            onsubmit="
                return confirm(
                    'Are you sure you want to approve this restaurant?'
                );
            "
        >

            <input
                type="hidden"
                name="restaurant_id"
                value="<?= (int)$restaurant['id'] ?>"
            >

            <input
                type="hidden"
                name="action"
                value="approve"
            >

            <button
                type="submit"
                class="btn btn-success"
            >

                <i class="bi bi-check-circle"></i>

                Approve

            </button>

        </form>


        <!-- REJECT -->

        <form
            method="POST"
            action="restaurant-action.php"
            onsubmit="
                return confirm(
                    'Are you sure you want to reject this restaurant?'
                );
            "
        >

            <input
                type="hidden"
                name="restaurant_id"
                value="<?= (int)$restaurant['id'] ?>"
            >

            <input
                type="hidden"
                name="action"
                value="reject"
            >

            <button
                type="submit"
                class="btn btn-danger"
            >

                <i class="bi bi-x-circle"></i>

                Reject

            </button>

        </form>

    <?php endif; ?>


    <?php if (
        $restaurant['status'] === 'approved'
    ): ?>

        <!-- SUSPEND -->

        <form
            method="POST"
            action="restaurant-action.php"
            onsubmit="
                return confirm(
                    'Are you sure you want to suspend this restaurant?'
                );
            "
        >

            <input
                type="hidden"
                name="restaurant_id"
                value="<?= (int)$restaurant['id'] ?>"
            >

            <input
                type="hidden"
                name="action"
                value="suspend"
            >

            <button
                type="submit"
                class="btn btn-danger"
            >

                <i class="bi bi-pause-circle"></i>

                Suspend

            </button>

        </form>

    <?php endif; ?>


    <?php if (
        $restaurant['status'] === 'suspended'
    ): ?>

        <!-- ACTIVATE -->

        <form
            method="POST"
            action="restaurant-action.php"
            onsubmit="
                return confirm(
                    'Are you sure you want to activate this restaurant?'
                );
            "
        >

            <input
                type="hidden"
                name="restaurant_id"
                value="<?= (int)$restaurant['id'] ?>"
            >

            <input
                type="hidden"
                name="action"
                value="activate"
            >

            <button
                type="submit"
                class="btn btn-success"
            >

                <i class="bi bi-play-circle"></i>

                Activate

            </button>

        </form>

    <?php endif; ?>

</div>

    </div>



    <!-- =================================================
         RESTAURANT COVER
    ================================================== -->

    <?php

    $coverImage = !empty(
        $restaurant['cover_image']
    )
        ? "../uploads/restaurants/" .
          $restaurant['cover_image']
        : "https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1600&q=80";

    ?>


    <div
        class="restaurant-cover mb-4"
        style="
            background-image:
            url('<?= htmlspecialchars(
                $coverImage
            ) ?>');
        "
    >

        <div
            class="restaurant-cover-overlay"
        ></div>


        <div
            class="restaurant-profile"
        >

            <div
                class="d-flex
                       align-items-center
                       gap-4"
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
                        class="logo-placeholder"
                    >

                        <i
                            class="bi bi-shop"
                        ></i>

                    </div>

                <?php endif; ?>


                <div>

                    <h1 class="fw-bold mb-1">

                        <?= htmlspecialchars(
                            $restaurant['name']
                        ) ?>

                    </h1>


                    <div>

                        <i
                            class="bi bi-geo-alt"
                        ></i>

                        <?= htmlspecialchars(
                            $restaurant['city']
                        ) ?>

                        <?php if (
                            !empty(
                                $restaurant['region']
                            )
                        ): ?>

                            ,

                            <?= htmlspecialchars(
                                $restaurant['region']
                            ) ?>

                        <?php endif; ?>

                    </div>


                    <small>

                        Restaurant ID:
                        #<?= (int)$restaurant['id'] ?>

                    </small>

                </div>


            </div>

        </div>

    </div>



    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="row g-4 mb-4">


        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="text-muted">

                    Total Orders

                </div>

                <div class="stat-number">

                    <?= number_format(
                        (int)$orderStats[
                            'total_orders'
                        ]
                    ) ?>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="text-muted">

                    Completed Orders

                </div>

                <div class="stat-number text-success">

                    <?= number_format(
                        (int)$orderStats[
                            'completed_orders'
                        ]
                    ) ?>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="text-muted">

                    Menu Items

                </div>

                <div class="stat-number">

                    <?= number_format(
                        (int)$menuStats[
                            'total_items'
                        ]
                    ) ?>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="text-muted">

                    Revenue

                </div>

                <div class="stat-number text-success">

                    TZS
                    <?= number_format(
                        (float)$orderStats[
                            'total_revenue'
                        ]
                    ) ?>

                </div>

            </div>

        </div>


    </div>



    <div class="row g-4">


        <!-- =================================================
             RESTAURANT INFORMATION
        ================================================== -->

        <div class="col-lg-5">

            <div class="card-box p-4 mb-4">

                <h5 class="fw-bold mb-3">

                    <i
                        class="bi bi-shop"
                    ></i>

                    Restaurant Information

                </h5>


                <div class="info-row">

                    <strong>Name</strong>

                    <div class="text-muted">

                        <?= htmlspecialchars(
                            $restaurant['name']
                        ) ?>

                    </div>

                </div>


                <div class="info-row">

                    <strong>Phone</strong>

                    <div class="text-muted">

                        <?= htmlspecialchars(
                            $restaurant['phone']
                        ) ?>

                    </div>

                </div>


                <div class="info-row">

                    <strong>Email</strong>

                    <div class="text-muted">

                        <?= htmlspecialchars(
                            $restaurant['email']
                            ?? 'Not provided'
                        ) ?>

                    </div>

                </div>


                <div class="info-row">

                    <strong>Address</strong>

                    <div class="text-muted">

                        <?= htmlspecialchars(
                            $restaurant['address']
                        ) ?>

                    </div>

                </div>


                <div class="info-row">

                    <strong>City</strong>

                    <div class="text-muted">

                        <?= htmlspecialchars(
                            $restaurant['city']
                        ) ?>

                    </div>

                </div>


                <div class="info-row">

                    <strong>Region</strong>

                    <div class="text-muted">

                        <?= htmlspecialchars(
                            $restaurant['region']
                            ?? 'Not provided'
                        ) ?>

                    </div>

                </div>


                <div class="info-row">

                    <strong>Opening Hours</strong>

                    <div class="text-muted">

                        <?= $restaurant[
                            'opening_time'
                        ]
                            ? date(
                                'h:i A',
                                strtotime(
                                    $restaurant[
                                        'opening_time'
                                    ]
                                )
                            )
                            : 'Not specified'
                        ?>

                        -

                        <?= $restaurant[
                            'closing_time'
                        ]
                            ? date(
                                'h:i A',
                                strtotime(
                                    $restaurant[
                                        'closing_time'
                                    ]
                                )
                            )
                            : 'Not specified'
                        ?>

                    </div>

                </div>


                <div class="info-row">

                    <strong>Delivery Fee</strong>

                    <div class="text-muted">

                        TZS
                        <?= number_format(
                            (float)$restaurant[
                                'delivery_fee'
                            ]
                        ) ?>

                    </div>

                </div>


                <div class="info-row">

                    <strong>Minimum Order</strong>

                    <div class="text-muted">

                        TZS
                        <?= number_format(
                            (float)$restaurant[
                                'minimum_order_amount'
                            ]
                        ) ?>

                    </div>

                </div>


                <div class="info-row">

                    <strong>Services</strong>

                    <div>

                        <?php if (
                            $restaurant[
                                'delivery_available'
                            ]
                        ): ?>

                            <span
                                class="badge bg-success"
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
                                class="badge bg-primary"
                            >

                                Pickup

                            </span>

                        <?php endif; ?>

                    </div>

                </div>


            </div>



            <!-- OWNER -->

            <div class="card-box p-4">

                <h5 class="fw-bold mb-3">

                    <i
                        class="bi bi-person"
                    ></i>

                    Restaurant Owner

                </h5>


                <div class="info-row">

                    <strong>Name</strong>

                    <div class="text-muted">

                        <?= htmlspecialchars(
                            $restaurant[
                                'owner_first_name'
                            ] .
                            ' ' .
                            $restaurant[
                                'owner_last_name'
                            ]
                        ) ?>

                    </div>

                </div>


                <div class="info-row">

                    <strong>Email</strong>

                    <div class="text-muted">

                        <?= htmlspecialchars(
                            $restaurant[
                                'owner_email'
                            ]
                        ) ?>

                    </div>

                </div>


                <div class="info-row">

                    <strong>Phone</strong>

                    <div class="text-muted">

                        <?= htmlspecialchars(
                            $restaurant[
                                'owner_phone'
                            ] ??
                            'Not provided'
                        ) ?>

                    </div>

                </div>


            </div>

        </div>



        <!-- =================================================
             RIGHT SIDE
        ================================================== -->

        <div class="col-lg-7">


            <!-- MENU -->

            <div class="card-box p-4 mb-4">

                <div
                    class="d-flex
                           justify-content-between
                           align-items-center
                           mb-3"
                >

                    <h5 class="fw-bold mb-0">

                        <i
                            class="bi bi-egg-fried"
                        ></i>

                        Recent Menu

                    </h5>


                    <span
                        class="badge bg-light text-dark"
                    >

                        <?= (int)$menuStats[
                            'available_items'
                        ] ?>

                        Available

                    </span>

                </div>


                <?php if (
                    !empty($recentMenu)
                ): ?>


                    <?php foreach (
                        $recentMenu
                        as $menuItem
                    ): ?>


                        <div
                            class="d-flex
                                   align-items-center
                                   justify-content-between
                                   py-2
                                   border-bottom"
                        >


                            <div
                                class="d-flex
                                       align-items-center
                                       gap-3"
                            >


                                <?php if (
                                    !empty(
                                        $menuItem['image']
                                    )
                                ): ?>

                                    <img
                                        src="../uploads/foods/<?= htmlspecialchars(
                                            $menuItem['image']
                                        ) ?>"
                                        class="menu-image"
                                        alt=""
                                    >

                                <?php else: ?>

                                    <div
                                        class="menu-placeholder"
                                    >

                                        🍽️

                                    </div>

                                <?php endif; ?>


                                <div>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $menuItem['name']
                                        ) ?>

                                    </strong>


                                    <div
                                        class="text-muted"
                                    >

                                        TZS
                                        <?= number_format(
                                            (float)$menuItem[
                                                'price'
                                            ]
                                        ) ?>

                                    </div>

                                </div>

                            </div>


                            <?php if (
                                $menuItem[
                                    'is_available'
                                ]
                            ): ?>

                                <span
                                    class="badge bg-success"
                                >

                                    Available

                                </span>

                            <?php else: ?>

                                <span
                                    class="badge bg-secondary"
                                >

                                    Unavailable

                                </span>

                            <?php endif; ?>


                        </div>


                    <?php endforeach; ?>


                <?php else: ?>


                    <div
                        class="text-center
                               text-muted
                               py-4"
                    >

                        No menu items found.

                    </div>


                <?php endif; ?>


            </div>



            <!-- RECENT ORDERS -->

            <div class="card-box p-4">

                <h5 class="fw-bold mb-3">

                    <i
                        class="bi bi-receipt"
                    ></i>

                    Recent Orders

                </h5>


                <?php if (
                    !empty($recentOrders)
                ): ?>


                    <div class="table-responsive">

                        <table
                            class="table
                                   align-middle"
                        >

                            <thead>

                            <tr>

                                <th>Order</th>

                                <th>Customer</th>

                                <th>Type</th>

                                <th>Total</th>

                                <th>Status</th>

                            </tr>

                            </thead>


                            <tbody>


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
                                                'd M Y H:i',
                                                strtotime(
                                                    $order[
                                                        'created_at'
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

                                        <?= ucfirst(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $order[
                                                    'order_type'
                                                ]
                                            )
                                        ) ?>

                                    </td>


                                    <td>

                                        TZS
                                        <?= number_format(
                                            (float)$order[
                                                'total_amount'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?php

                                        $orderStatusClass =
                                            match (
                                                $order['status']
                                            ) {

                                                'completed'
                                                    => 'bg-success',

                                                'pending'
                                                    => 'bg-warning text-dark',

                                                'accepted',
                                                'preparing'
                                                    => 'bg-primary',

                                                'ready'
                                                    => 'bg-info text-dark',

                                                'out_for_delivery'
                                                    => 'bg-dark',

                                                'denied',
                                                'cancelled'
                                                    => 'bg-danger',

                                                default
                                                    => 'bg-secondary'

                                            };

                                        ?>


                                        <span
                                            class="
                                                badge
                                                <?= $orderStatusClass ?>
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


                            </tbody>

                        </table>

                    </div>


                <?php else: ?>


                    <div
                        class="text-center
                               text-muted
                               py-4"
                    >

                        No orders found.

                    </div>


                <?php endif; ?>


            </div>


        </div>


    </div>


</div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>