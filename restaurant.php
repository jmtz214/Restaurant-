<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/database.php";


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

$restaurantStmt = $pdo->prepare("
    SELECT
        id,
        name,
        slug,
        description,
        address,
        city,
        rating,
        total_reviews,
        delivery_available,
        pickup_available,
        delivery_fee,
        cover_image,
        status
    FROM restaurants
    WHERE id = ?
      AND status = 'approved'
    LIMIT 1
");

$restaurantStmt->execute([$restaurantId]);

$restaurant = $restaurantStmt->fetch();


// Restaurant doesn't exist

if (!$restaurant) {
    header("Location: restaurants.php");
    exit;
}


// =====================================================
// GET MENU ITEMS
// =====================================================

$menuStmt = $pdo->prepare("
    SELECT
        menu_items.id,
        menu_items.restaurant_id,
        menu_items.category_id,
        menu_items.name,
        menu_items.description,
        menu_items.price,
        menu_items.image,
        menu_items.preparation_time,
        menu_items.is_available,
        menu_items.is_featured,
        categories.name AS category_name
    FROM menu_items
    LEFT JOIN categories
        ON menu_items.category_id = categories.id
    WHERE menu_items.restaurant_id = ?
      AND menu_items.is_available = 1
    ORDER BY
        menu_items.is_featured DESC,
        categories.name ASC,
        menu_items.name ASC
");

$menuStmt->execute([$restaurantId]);

$menuItems = $menuStmt->fetchAll();


// =====================================================
// GET MENU CATEGORIES
// =====================================================

$categoryStmt = $pdo->prepare("
    SELECT DISTINCT
        categories.id,
        categories.name
    FROM menu_items
    INNER JOIN categories
        ON menu_items.category_id = categories.id
    WHERE menu_items.restaurant_id = ?
      AND menu_items.is_available = 1
    ORDER BY categories.name ASC
");

$categoryStmt->execute([$restaurantId]);

$categories = $categoryStmt->fetchAll();

// =====================================================
// CART COUNT
// =====================================================

$cartCount = 0;

if (!empty($_SESSION['cart'])) {

    foreach ($_SESSION['cart'] as $item) {

        $cartCount += (int)$item['quantity'];

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
        <?= htmlspecialchars($restaurant['name']) ?> - MloGo
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- MloGo CSS -->

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >


    <style>

        .restaurant-hero {

            position: relative;

            height: 360px;

            overflow: hidden;

        }


        .restaurant-hero img {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }


        .restaurant-hero-overlay {

            position: absolute;

            inset: 0;

            background:
                linear-gradient(
                    to top,
                    rgba(0,0,0,.75),
                    rgba(0,0,0,.10)
                );

        }


        .restaurant-hero-content {

            position: absolute;

            bottom: 35px;

            left: 0;

            right: 0;

            color: white;

        }


        .restaurant-info-card {

            background: white;

            border-radius: 18px;

            padding: 20px;

            box-shadow:
                0 5px 25px rgba(0,0,0,.06);

            margin-top: -40px;

            position: relative;

            z-index: 2;

        }


        .menu-filter {

            display: flex;

            gap: 10px;

            flex-wrap: wrap;

        }


        .menu-filter button {

            border: 1px solid #ddd;

            background: white;

            border-radius: 50px;

            padding: 8px 18px;

            cursor: pointer;

        }


        .menu-filter button.active {

            background: #198754;

            color: white;

            border-color: #198754;

        }


        .menu-card {

            background: white;

            border-radius: 16px;

            overflow: hidden;

            border: 1px solid #eee;

            height: 100%;

            transition: .2s ease;

        }


        .menu-card:hover {

            transform: translateY(-4px);

            box-shadow:
                0 10px 30px rgba(0,0,0,.08);

        }


        .menu-card-image {

            width: 100%;

            height: 200px;

            object-fit: cover;

        }


        .menu-card-body {

            padding: 18px;

        }


        .menu-price {

            font-size: 18px;

            font-weight: 800;

            color: #198754;

        }


        .cart-floating {

            position: fixed;

            right: 25px;

            bottom: 25px;

            z-index: 1000;

        }


        .cart-button {

            border: none;

            background: #198754;

            color: white;

            padding: 14px 22px;

            border-radius: 50px;

            box-shadow:
                0 8px 25px rgba(0,0,0,.20);

        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar navbar-expand-lg main-navbar sticky-top">

    <div class="container">

        <a
            class="navbar-brand brand-logo"
            href="index.php"
        >

            Mlo<span>Go</span>

        </a>


        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <div
            class="collapse navbar-collapse"
            id="mainNavbar"
        >

            <ul class="navbar-nav mx-auto">

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="index.php"
                    >

                        Home

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="restaurants.php"
                    >

                        Restaurants

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="index.php#categories"
                    >

                        Categories

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="index.php#how-it-works"
                    >

                        How It Works

                    </a>

                </li>

            </ul>


            <div class="d-flex gap-2">

                <a
                    href="cart.php"
                    class="btn btn-outline-dark"
                >

                    <i class="bi bi-cart3"></i>

                    Cart

                    <?php if ($cartCount > 0): ?>

                        <span class="badge bg-success">

                            <?= $cartCount ?>

                        </span>

                    <?php endif; ?>

                </a>


                <a
                    href="login.php"
                    class="btn btn-outline-dark"
                >

                    Login

                </a>

            </div>

        </div>

    </div>

</nav>



<!-- =====================================================
     RESTAURANT HERO
===================================================== -->

<section class="restaurant-hero">


    <?php if (!empty($restaurant['cover_image'])): ?>

        <img
            src="uploads/restaurants/covers/<?= htmlspecialchars($restaurant['cover_image']) ?>"
            alt="<?= htmlspecialchars($restaurant['name']) ?>"
        >

    <?php else: ?>

        <img
            src="https://images.unsplash.com/photo-1515003197210-e0cd71810b5f?auto=format&fit=crop&w=1600&q=85"
            alt="<?= htmlspecialchars($restaurant['name']) ?>"
        >

    <?php endif; ?>


    <div class="restaurant-hero-overlay"></div>


    <div class="restaurant-hero-content">

        <div class="container">

            <span class="badge bg-success mb-2">

                <i class="bi bi-check-circle"></i>

                Open

            </span>


            <h1 class="fw-bold">

                <?= htmlspecialchars($restaurant['name']) ?>

            </h1>


            <p class="mb-0">

                <i class="bi bi-geo-alt"></i>

                <?= htmlspecialchars($restaurant['city']) ?>

                &nbsp; • &nbsp;

                <i class="bi bi-star-fill text-warning"></i>

                <?= number_format(
                    (float)$restaurant['rating'],
                    1
                ) ?>

                (<?= number_format(
                    (int)$restaurant['total_reviews']
                ) ?> reviews)

            </p>

        </div>

    </div>

</section>



<!-- =====================================================
     RESTAURANT INFORMATION
===================================================== -->

<div class="container">

    <div class="restaurant-info-card">


        <div class="row align-items-center g-4">


            <div class="col-lg-7">

                <h3 class="fw-bold">

                    <?= htmlspecialchars($restaurant['name']) ?>

                </h3>


                <p class="text-muted mb-2">

                    <?= htmlspecialchars(
                        $restaurant['description']
                        ?: 'Delicious food and great service.'
                    ) ?>

                </p>


                <p class="text-muted mb-0">

                    <i class="bi bi-geo-alt"></i>

                    <?= htmlspecialchars($restaurant['address']) ?>

                </p>

            </div>



            <div class="col-lg-5">


                <div class="d-flex flex-wrap gap-2">


                    <?php if ($restaurant['delivery_available']): ?>

                        <span class="badge bg-success p-2">

                            <i class="bi bi-bicycle"></i>

                            Delivery

                        </span>

                    <?php endif; ?>


                    <?php if ($restaurant['pickup_available']): ?>

                        <span class="badge bg-secondary p-2">

                            <i class="bi bi-bag"></i>

                            Pickup

                        </span>

                    <?php endif; ?>


                    <?php if ($restaurant['delivery_available']): ?>

                        <span class="badge bg-light text-dark p-2">

                            Delivery:

                            TZS
                            <?= number_format(
                                (float)$restaurant['delivery_fee']
                            ) ?>

                        </span>

                    <?php endif; ?>


                </div>

            </div>


        </div>

    </div>

</div>



<!-- =====================================================
     MENU
===================================================== -->

<section class="section-padding">

    <div class="container">


        <div class="mb-4">

            <h2 class="section-title">

                Menu

            </h2>


            <p class="text-muted">

                Choose your favourite meal.

            </p>

        </div>



        <!-- Category filter -->

        <div class="menu-filter mb-4">


            <button
                type="button"
                class="active"
                onclick="filterMenu('all', this)"
            >

                All

            </button>


<?php foreach ($categories as $category): ?>

    <button
        type="button"
        onclick="filterMenu(
            'category-<?= (int)$category['id'] ?>',
            this
        )"
    >

        <?= htmlspecialchars($category['name']) ?>

    </button>

<?php endforeach; ?>


        </div>



        <!-- Menu items -->

        <div class="row g-4">


            <?php if (count($menuItems) > 0): ?>


                <?php foreach ($menuItems as $item): ?>


         <?php

$itemCategory = 'category-' . (int)$item['category_id'];

?>


                    <div
                        class="col-md-6 col-lg-4 col-xl-3 menu-item-wrapper"
                        data-category="<?= htmlspecialchars($itemCategory) ?>"
                    >


                        <div class="menu-card">


                            <!-- Image -->

                            <?php if (!empty($item['image'])): ?>

                                <img
                                    src="<?= htmlspecialchars($item['image']) ?>"
                                    class="menu-card-image"
                                    alt="<?= htmlspecialchars($item['name']) ?>"
                                >

                            <?php else: ?>

                                <img
                                    src="https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=800&q=80"
                                    class="menu-card-image"
                                    alt="<?= htmlspecialchars($item['name']) ?>"
                                >

                            <?php endif; ?>



                            <div class="menu-card-body">


                                <?php if ($item['is_featured']): ?>

                                    <span class="badge bg-warning text-dark mb-2">

                                        ⭐ Popular

                                    </span>

                                <?php endif; ?>


                                <h5>

                                    <?= htmlspecialchars(
                                        $item['name']
                                    ) ?>

                                </h5>


                                <?php if (!empty($item['category_name'])): ?>

    <span class="badge bg-light text-dark mb-2">

        <?= htmlspecialchars(
            $item['category_name']
        ) ?>

    </span>

        <?php endif; ?> 

        <?php if (!empty($item['preparation_time'])): ?>

    <p class="text-muted small mb-0">

        <i class="bi bi-clock"></i>

        <?= (int)$item['preparation_time'] ?> mins

    </p>

<?php endif; ?>


                                <div class="d-flex justify-content-between align-items-center mt-3">


                                    <span class="menu-price">

                                        TZS
                                        <?= number_format(
                                            (float)$item['price']
                                        ) ?>

                                    </span>


                                    <button
                                        type="button"
                                        class="btn btn-success btn-sm"
                                        onclick="addToCart(
                                            <?= (int)$item['id'] ?>,
                                            <?= (int)$restaurant['id'] ?>
                                        )"
                                    >

                                        <i class="bi bi-plus-lg"></i>

                                        Add

                                    </button>


                                </div>


                            </div>

                        </div>

                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="col-12">

                    <div class="text-center py-5">

                        <div style="font-size:60px;">

                            🍽️

                        </div>


                        <h3>

                            No menu available

                        </h3>


                        <p class="text-muted">

                            This restaurant currently has
                            no available food items.

                        </p>

                    </div>

                </div>


            <?php endif; ?>


        </div>


    </div>

</section>



<!-- =====================================================
     FLOATING CART
===================================================== -->

<div class="cart-floating">

    <a
        href="cart.php"
        class="cart-button text-decoration-none"
    >

        <i class="bi bi-cart3"></i>

        Cart

        <?php if ($cartCount > 0): ?>

            <span class="badge bg-light text-dark">

                <?= $cartCount ?>

            </span>

        <?php endif; ?>

    </a>

</div>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer>

    <div class="container">


        <div class="row g-4">


            <div class="col-lg-4">

                <h3 class="brand-logo text-white">

                    Mlo<span>Go</span>

                </h3>


                <p class="mt-3 text-white-50">

                    Connecting Tanzanian food lovers
                    with great local restaurants.

                </p>

            </div>



            <div class="col-6 col-lg-2">

                <h5>

                    Explore

                </h5>


                <ul class="list-unstyled mt-3">

                    <li class="mb-2">

                        <a href="index.php">

                            Home

                        </a>

                    </li>


                    <li class="mb-2">

                        <a href="restaurants.php">

                            Restaurants

                        </a>

                    </li>


                    <li>

                        <a href="index.php#categories">

                            Categories

                        </a>

                    </li>

                </ul>

            </div>



            <div class="col-6 col-lg-2">

                <h5>

                    Account

                </h5>


                <ul class="list-unstyled mt-3">

                    <li class="mb-2">

                        <a href="login.php">

                            Login

                        </a>

                    </li>


                    <li>

                        <a href="register.php">

                            Register

                        </a>

                    </li>

                </ul>

            </div>



            <div class="col-lg-4">

                <h5>

                    Follow Us

                </h5>


                <div class="d-flex gap-3 mt-3">

                    <a href="#">

                        <i class="bi bi-facebook fs-4"></i>

                    </a>


                    <a href="#">

                        <i class="bi bi-instagram fs-4"></i>

                    </a>


                    <a href="#">

                        <i class="bi bi-twitter-x fs-4"></i>

                    </a>

                </div>

            </div>


        </div>


        <div class="footer-bottom text-center">

            © <?= date('Y') ?> MloGo.
            All rights reserved.

        </div>


    </div>

</footer>



<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>

function filterMenu(category, button) {

    const items = document.querySelectorAll(
        '.menu-item-wrapper'
    );


    const buttons = document.querySelectorAll(
        '.menu-filter button'
    );


    buttons.forEach(function(btn) {

        btn.classList.remove('active');

    });


    button.classList.add('active');


    items.forEach(function(item) {

        const itemCategory =
            item.dataset.category;


        if (
            category === 'all' ||
            itemCategory === category
        ) {

            item.style.display = '';

        } else {

            item.style.display = 'none';

        }

    });

}



function addToCart(menuItemId, restaurantId) {

    fetch('cart-action.php', {

        method: 'POST',

        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },

        body:
            'action=add' +
            '&menu_item_id=' +
            encodeURIComponent(menuItemId) +
            '&restaurant_id=' +
            encodeURIComponent(restaurantId)

    })

    .then(response => response.json())

    .then(data => {

        if (data.success) {

            alert(data.message);

            location.reload();

        } else {

            alert(
                data.message ||
                'Unable to add item to cart.'
            );

        }

    })

    .catch(error => {

        console.error(error);

        alert(
            'Something went wrong. Please try again.'
        );

    });

}

</script>



<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>