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

$userId = (int) $_SESSION['user_id'];


// =====================================================
// FIND RESTAURANT OWNED BY LOGGED-IN ADMIN
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
    die("No restaurant has been assigned to your account.");
}

$restaurantId = (int) $restaurant['id'];


// =====================================================
// HANDLE AVAILABILITY TOGGLE
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['toggle_availability'])
) {

    $menuId = (int) ($_POST['menu_id'] ?? 0);

    if ($menuId > 0) {

        /*
         * IMPORTANT:
         * We verify restaurant_id so an admin cannot
         * modify another restaurant's food.
         */

        $stmt = $pdo->prepare("
            UPDATE menu_items
            SET is_available =
                CASE
                    WHEN is_available = 1 THEN 0
                    ELSE 1
                END
            WHERE id = ?
            AND restaurant_id = ?
        ");

        $stmt->execute([
            $menuId,
            $restaurantId
        ]);
    }

    header("Location: menu.php");
    exit;
}


// =====================================================
// HANDLE DELETE
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_menu'])
) {

    $menuId = (int) ($_POST['menu_id'] ?? 0);

    if ($menuId > 0) {

        $stmt = $pdo->prepare("
            DELETE FROM menu_items
            WHERE id = ?
            AND restaurant_id = ?
        ");

        $stmt->execute([
            $menuId,
            $restaurantId
        ]);
    }

    header("Location: menu.php");
    exit;
}


// =====================================================
// GET MENU ITEMS
// =====================================================

$stmt = $pdo->prepare("
SELECT
    m.*,
    c.name AS category_name
FROM menu_items m
LEFT JOIN categories c
    ON m.category_id = c.id
WHERE m.restaurant_id = ?
ORDER BY m.created_at DESC
");

$stmt->execute([
    $restaurantId
]);

$menuItems = $stmt->fetchAll();


// =====================================================
// STATISTICS
// =====================================================

$totalItems = count($menuItems);

$availableItems = 0;
$unavailableItems = 0;
$featuredItems = 0;

foreach ($menuItems as $item) {

    if ((int)$item['is_available'] === 1) {
        $availableItems++;
    } else {
        $unavailableItems++;
    }

    if ((int)$item['is_featured'] === 1) {
        $featuredItems++;
    }
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
        Menu Management - <?= htmlspecialchars($restaurant['name']) ?>
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

            background: #f6f8fa;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

        }


        .sidebar {

            position: fixed;

            left: 0;

            top: 0;

            bottom: 0;

            width: 250px;

            background: #17202a;

            color: white;

            padding: 25px 15px;

        }


        .brand {

            font-size: 27px;

            font-weight: 800;

            text-decoration: none;

            color: white;

            padding: 0 15px;

        }


        .brand span {

            color: #20c997;

        }


        .restaurant-name {

            color: #adb5bd;

            font-size: 13px;

            padding: 20px 15px;

            border-bottom: 1px solid #343a40;

            margin-bottom: 15px;

        }


        .nav-link {

            color: #adb5bd;

            padding: 12px 15px;

            border-radius: 10px;

            margin-bottom: 5px;

        }


        .nav-link:hover,
        .nav-link.active {

            background: #20c997;

            color: white;

        }


        .nav-link i {

            width: 25px;

        }


        .main {

            margin-left: 250px;

            padding: 30px;

        }


        .topbar {

            background: white;

            border-radius: 15px;

            padding: 18px 25px;

            margin-bottom: 25px;

            box-shadow:
                0 4px 18px
                rgba(0,0,0,.04);

        }


        .stat-card {

            background: white;

            border-radius: 16px;

            padding: 22px;

            box-shadow:
                0 4px 18px
                rgba(0,0,0,.04);

            height: 100%;

        }


        .stat-icon {

            width: 48px;

            height: 48px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #e8f8f3;

            color: #20c997;

            font-size: 22px;

        }


        .stat-number {

            font-size: 27px;

            font-weight: 800;

            margin-top: 12px;

        }


        .menu-card {

            background: white;

            border-radius: 16px;

            overflow: hidden;

            box-shadow:
                0 4px 18px
                rgba(0,0,0,.04);

            height: 100%;

            transition: .2s;

        }


        .menu-card:hover {

            transform: translateY(-3px);

            box-shadow:
                0 8px 25px
                rgba(0,0,0,.08);

        }


        .food-image {

            height: 190px;

            width: 100%;

            object-fit: cover;

            background: #e9ecef;

        }


        .food-placeholder {

            height: 190px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #e9ecef;

            color: #adb5bd;

            font-size: 55px;

        }


        .menu-content {

            padding: 20px;

        }


        .food-name {

            font-size: 19px;

            font-weight: 700;

        }


        .food-price {

            color: #20c997;

            font-weight: 800;

            font-size: 18px;

        }


        .badge-available {

            background: #d1e7dd;

            color: #0f5132;

        }


        .badge-unavailable {

            background: #f8d7da;

            color: #842029;

        }


        .badge-featured {

            background: #fff3cd;

            color: #856404;

        }


        .empty {

            background: white;

            border-radius: 18px;

            padding: 70px 20px;

            text-align: center;

        }


        @media(max-width: 991px) {

            .sidebar {

                position: static;

                width: 100%;

            }

            .main {

                margin-left: 0;

            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">

    <a
        href="dashboard.php"
        class="brand"
    >

        Mlo<span>Go</span>

    </a>


    <div class="restaurant-name">

        <i class="bi bi-shop"></i>

        <?= htmlspecialchars(
            $restaurant['name']
        ) ?>

    </div>


    <a
        href="dashboard.php"
        class="nav-link"
    >

        <i class="bi bi-speedometer2"></i>

        Dashboard

    </a>


    <a
        href="orders.php"
        class="nav-link"
    >

        <i class="bi bi-receipt"></i>

        Orders

    </a>


    <a
        href="menu.php"
        class="nav-link active"
    >

        <i class="bi bi-menu-button-wide"></i>

        Menu

    </a>


    <a
        href="#"
        class="nav-link"
    >

        <i class="bi bi-bar-chart"></i>

        Analytics

    </a>


    <a
        href="#"
        class="nav-link"
    >

        <i class="bi bi-gear"></i>

        Restaurant Settings

    </a>


    <hr>


    <a
        href="../logout.php"
        class="nav-link text-danger"
    >

        <i class="bi bi-box-arrow-right"></i>

        Logout

    </a>

</div>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="main">


    <div class="topbar">


        <div class="d-flex justify-content-between align-items-center">


            <div>

                <h3 class="fw-bold mb-1">

                    Menu Management

                </h3>


                <p class="text-muted mb-0">

                    Manage food available at
                    <?= htmlspecialchars(
                        $restaurant['name']
                    ) ?>

                </p>

            </div>


            <a
                href="add-menu.php"
                class="btn btn-success"
            >

                <i class="bi bi-plus-lg"></i>

                Add Food

            </a>


        </div>


    </div>



    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="row g-4 mb-4">


        <div class="col-md-4">

            <div class="stat-card">


                <div class="stat-icon">

                    <i class="bi bi-menu-button-wide"></i>

                </div>


                <div class="stat-number">

                    <?= $totalItems ?>

                </div>


                <div class="text-muted">

                    Total Menu Items

                </div>


            </div>

        </div>



        <div class="col-md-4">

            <div class="stat-card">


                <div class="stat-icon">

                    <i class="bi bi-check-circle"></i>

                </div>


                <div class="stat-number">

                    <?= $availableItems ?>

                </div>


                <div class="text-muted">

                    Currently Available

                </div>


            </div>

        </div>



        <div class="col-md-4">

            <div class="stat-card">


                <div class="stat-icon">

                    <i class="bi bi-star"></i>

                </div>


                <div class="stat-number">

                    <?= $featuredItems ?>

                </div>


                <div class="text-muted">

                    Featured Foods

                </div>


            </div>

        </div>


    </div>



    <!-- =================================================
         MENU
    ================================================== -->

    <?php if (empty($menuItems)): ?>


        <div class="empty">


            <i
                class="bi bi-basket fs-1 text-muted"
            ></i>


            <h4 class="fw-bold mt-3">

                Your menu is empty

            </h4>


            <p class="text-muted">

                Start adding delicious food to your restaurant menu.

            </p>


            <a
                href="add-menu.php"
                class="btn btn-success"
            >

                <i class="bi bi-plus-lg"></i>

                Add Your First Food

            </a>


        </div>


    <?php else: ?>


        <div class="row g-4">


            <?php foreach (
                $menuItems
                as $item
            ): ?>


                <div class="col-md-6 col-xl-4">


                    <div class="menu-card">


                        <?php if (
                            !empty(
                                $item['image']
                            )
                        ): ?>


                            <img
                                src="../<?= htmlspecialchars(
                                    $item['image']
                                ) ?>"
                                class="food-image"
                                alt="<?= htmlspecialchars(
                                    $item['name']
                                ) ?>"
                            >


                        <?php else: ?>


                            <div class="food-placeholder">

                                <i class="bi bi-egg-fried"></i>

                            </div>


                        <?php endif; ?>


                        <div class="menu-content">


                            <div class="d-flex justify-content-between align-items-start gap-2">


                                <div class="food-name">

                                    <?= htmlspecialchars(
                                        $item['name']
                                    ) ?>

                                </div>


                                <?php if (
                                    (int)$item[
                                        'is_featured'
                                    ] === 1
                                ): ?>


                                    <span
                                        class="badge badge-featured"
                                    >

                                        <i class="bi bi-star-fill"></i>

                                        Featured

                                    </span>


                                <?php endif; ?>


                            </div>


                            <div class="food-price mt-2">

                                TZS
                                <?= number_format(
                                    (float)
                                    $item['price']
                                ) ?>

                            </div>


                            <?php if (
                                !empty(
                                    $item['description']
                                )
                            ): ?>


                                <p class="text-muted small mt-2">

                                    <?= htmlspecialchars(
                                        $item['description']
                                    ) ?>

                                </p>


                            <?php endif; ?>


                            <div class="mt-3">


                                <?php if (
                                    (int)$item[
                                        'is_available'
                                    ] === 1
                                ): ?>


                                    <span
                                        class="badge badge-available"
                                    >

                                        <i class="bi bi-check-circle"></i>

                                        Available

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="badge badge-unavailable"
                                    >

                                        <i class="bi bi-x-circle"></i>

                                        Unavailable

                                    </span>


                                <?php endif; ?>


                                <span
                                    class="badge bg-light text-dark"
                                >

                                    <?= (int)$item[
                                        'preparation_time'
                                    ] ?>

                                    min

                                </span>


                            </div>


                            <hr>


                            <div class="d-flex gap-2">


                                <a
                                    href="edit-menu.php?id=<?= (int)$item['id'] ?>"
                                    class="btn btn-outline-primary btn-sm flex-fill"
                                >

                                    <i class="bi bi-pencil"></i>

                                    Edit

                                </a>


                                <form
                                    method="POST"
                                    class="flex-fill"
                                >

                                    <input
                                        type="hidden"
                                        name="menu_id"
                                        value="<?= (int)$item['id'] ?>"
                                    >


                                    <button
                                        type="submit"
                                        name="toggle_availability"
                                        class="btn btn-outline-warning btn-sm w-100"
                                    >

                                        <?php if (
                                            (int)$item[
                                                'is_available'
                                            ] === 1
                                        ): ?>

                                            <i class="bi bi-eye-slash"></i>

                                            Disable

                                        <?php else: ?>

                                            <i class="bi bi-eye"></i>

                                            Enable

                                        <?php endif; ?>

                                    </button>

                                </form>


                                <form
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this food?');"
                                >

                                    <input
                                        type="hidden"
                                        name="menu_id"
                                        value="<?= (int)$item['id'] ?>"
                                    >


                                    <button
                                        type="submit"
                                        name="delete_menu"
                                        class="btn btn-outline-danger btn-sm"
                                    >

                                        <i class="bi bi-trash"></i>

                                    </button>

                                </form>


                            </div>


                        </div>


                    </div>


                </div>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


</div>


</body>

</html>