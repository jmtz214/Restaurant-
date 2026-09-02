<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/database.php";
// Get approved restaurants
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
        cover_image
    FROM restaurants
    WHERE status = 'approved'
    ORDER BY rating DESC
    LIMIT 6
");

$restaurantStmt->execute();

$restaurants = $restaurantStmt->fetchAll();


// Get popular available food
$foodStmt = $pdo->prepare("
    SELECT
        menu_items.id,
        menu_items.restaurant_id,
        menu_items.name,
        menu_items.description,
        menu_items.price,
        menu_items.image,
        restaurants.name AS restaurant_name
    FROM menu_items
    INNER JOIN restaurants
        ON menu_items.restaurant_id = restaurants.id
    WHERE menu_items.is_available = 1
      AND restaurants.status = 'approved'
    ORDER BY menu_items.is_featured DESC,
             menu_items.id DESC
    LIMIT 8
");

$foodStmt->execute();

$foods = $foodStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>MloGo - Tanzanian Food Ordering</title>

    <meta
        name="description"
        content="Order delicious Tanzanian food from restaurants near you."
    >

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

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar navbar-expand-lg main-navbar sticky-top">

    <div class="container">

        <!-- Logo -->

        <a
            class="navbar-brand brand-logo"
            href="index.php"
        >

            Mlo<span>Go</span>

        </a>


        <!-- Mobile menu button -->

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <!-- Navigation -->

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
                        href="#categories"
                    >

                        Categories

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="#how-it-works"
                    >

                        How It Works

                    </a>

                </li>

            </ul>


            <!-- Authentication buttons -->

            <div class="d-flex gap-2">

                <a
                    href="login.php"
                    class="btn btn-outline-dark"
                >

                    Login

                </a>


                <a
                    href="register.php"
                    class="btn btn-primary-custom"
                >

                    Get Started

                </a>

            </div>

        </div>

    </div>

</nav>



<!-- =====================================================
     HERO SECTION
===================================================== -->

<section class="hero">

    <div class="container">

        <div class="row align-items-center g-5">


            <!-- Hero text -->

            <div class="col-lg-6">

                <div class="hero-content">

                    <span class="badge text-bg-light mb-3 p-2">

                        🇹🇿 Made for Tanzania

                    </span>


                    <h1>

                        Delicious food,

                        <span>
                            delivered to you.
                        </span>

                    </h1>


                    <p>

                        Discover your favourite Tanzanian meals,
                        order from local restaurants and enjoy
                        your food your way — pickup or delivery.

                    </p>


                    <!-- Search -->

                    <form
                        class="hero-search"
                        action="restaurants.php"
                        method="GET"
                    >

                        <i
                            class="bi bi-search fs-5 align-self-center ms-3"
                        ></i>


                        <input
                            type="text"
                            name="search"
                            placeholder="Search for food or restaurant..."
                        >


                        <button
                            type="submit"
                            class="btn btn-primary-custom"
                        >

                            Find Food

                        </button>

                    </form>

                </div>

            </div>



            <!-- Hero image -->

            <div class="col-lg-6">

                <div class="hero-image">

                    <img
                            src="assets/images/kisin.jpg"
    class="..."
    alt="home"
                    >

                </div>

            </div>

        </div>

    </div>

</section>



<!-- =====================================================
     CATEGORIES
===================================================== -->

<section
    id="categories"
    class="section-padding"
>

    <div class="container">


        <div class="text-center">

            <h2 class="section-title">

                Explore Food Categories

            </h2>


            <p class="section-subtitle">

                Find exactly what you're craving today.

            </p>

        </div>



        <div class="row g-4">


            <!-- Tanzanian food -->

            <div class="col-6 col-md-4 col-lg-3">

                <div class="category-card">

                    <div class="category-icon">
                            <img
        src="assets/images/trad.jpeg"
        alt="traditional food"
    >
                    </div>


                    <h5>

                        Tanzanian Food

                    </h5>


                    <p>

                        Traditional meals

                    </p>

                </div>

            </div>



            <!-- Chicken -->

            <div class="col-6 col-md-4 col-lg-3">

                <div class="category-card">

                    <div class="category-icon">
                         <img
        src="assets/images/chicken.jpg"
        alt="chicken">
                    </div>


                    <h5>

                        Chicken

                    </h5>


                    <p>

                        Delicious chicken

                    </p>

                </div>

            </div>



            <!-- Rice -->

            <div class="col-6 col-md-4 col-lg-3">

                <div class="category-card">

                    <div class="category-icon">
                             <img
        src="assets/images/rice.jpg"
        alt="Rice">
                    </div>


                    <h5>

                        Rice Dishes

                    </h5>


                    <p>

                        Pilau, biryani & more

                    </p>

                </div>

            </div>



            <!-- Fast food -->

            <div class="col-6 col-md-4 col-lg-3">

                <div class="category-card">

                    <div class="category-icon">
                         <img
        src="assets/images/fast.jpg"
        alt="Fast Food">
                    </div>


                    <h5>

                        Fast Food

                    </h5>


                    <p>

                        Quick & tasty

                    </p>

                </div>

            </div>


        </div>

    </div>

</section>


<!-- =====================================================
     POPULAR RESTAURANTS
===================================================== -->

<section
    id="restaurants"
    class="section-padding bg-light"
>

    <div class="container">


        <!-- Section heading -->

        <div class="d-flex justify-content-between align-items-end mb-4">

            <div>

                <h2 class="section-title">

                    Popular Restaurants

                </h2>


                <p class="section-subtitle mb-0">

                    Discover restaurants loved by customers.

                </p>

            </div>


            <a
                href="restaurants.php"
                class="btn btn-outline-dark"
            >

                View All

                <i class="bi bi-arrow-right"></i>

            </a>

        </div>



        <!-- Restaurant cards -->

        <div class="row g-4">


            <?php if (count($restaurants) > 0): ?>


                <?php foreach ($restaurants as $restaurant): ?>


                    <div class="col-md-6 col-lg-4">


                        <div class="restaurant-card">


                            <!-- Restaurant image -->

                            <?php if (!empty($restaurant['cover_image'])): ?>

                                <img
                                    src="uploads/restaurants/covers/<?= htmlspecialchars($restaurant['cover_image']) ?>"
                                    class="restaurant-image"
                                    alt="<?= htmlspecialchars($restaurant['name']) ?>"
                                >

                            <?php else: ?>

                                <img
                                    src="https://images.unsplash.com/photo-1515003197210-e0cd71810b5f?auto=format&fit=crop&w=800&q=80"
                                    class="restaurant-image"
                                    alt="<?= htmlspecialchars($restaurant['name']) ?>"
                                >

                            <?php endif; ?>



                            <!-- Restaurant information -->

                            <div class="restaurant-body">


                                <div class="d-flex justify-content-between align-items-start">


                                    <h5>

                                        <?= htmlspecialchars($restaurant['name']) ?>

                                    </h5>


                                    <span class="rating">

                                        <i class="bi bi-star-fill"></i>

                                        <?= number_format(
                                            (float)$restaurant['rating'],
                                            1
                                        ) ?>

                                    </span>


                                </div>



                                <!-- Location -->

                                <p class="restaurant-meta mb-2">

                                    <i class="bi bi-geo-alt"></i>

                                    <?= htmlspecialchars(
                                        $restaurant['city']
                                    ) ?>

                                </p>



                                <!-- Description -->

                                <p class="restaurant-meta">

                                    <?= htmlspecialchars(
                                        $restaurant['description']
                                        ?: 'Delicious food and great service.'
                                    ) ?>

                                </p>



                                <!-- Services -->

                                <div class="d-flex gap-2 mb-3">


                                    <?php if ($restaurant['delivery_available']): ?>

                                        <span class="badge bg-success">

                                            <i class="bi bi-bicycle"></i>

                                            Delivery

                                        </span>

                                    <?php endif; ?>


                                    <?php if ($restaurant['pickup_available']): ?>

                                        <span class="badge bg-secondary">

                                            <i class="bi bi-bag"></i>

                                            Pickup

                                        </span>

                                    <?php endif; ?>


                                </div>



                                <!-- View restaurant -->

                                <a
                                    href="restaurant.php?id=<?= $restaurant['id'] ?>"
                                    class="btn btn-primary-custom w-100"
                                >

                                    View Menu

                                </a>


                            </div>


                        </div>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <!-- No restaurants -->

                <div class="col-12">

                    <div class="alert alert-info text-center">

                        <i class="bi bi-info-circle"></i>

                        No restaurants are currently available.

                    </div>

                </div>


            <?php endif; ?>


        </div>


    </div>

</section>


<!-- =====================================================
     POPULAR FOOD
===================================================== -->

<section class="section-padding">

    <div class="container">


        <!-- Heading -->

        <div class="d-flex justify-content-between align-items-end mb-4">

            <div>

                <h2 class="section-title">

                    Popular Food

                </h2>


                <p class="section-subtitle mb-0">

                    Delicious meals available from our restaurants.

                </p>

            </div>


            <a
                href="restaurants.php"
                class="btn btn-outline-dark"
            >

                Explore More

                <i class="bi bi-arrow-right"></i>

            </a>

        </div>



        <!-- Food cards -->

        <div class="row g-4">


            <?php if (count($foods) > 0): ?>


                <?php foreach ($foods as $food): ?>


                    <div class="col-md-6 col-lg-3">


                        <div class="food-card">


                            <!-- Food image -->

                            <?php if (!empty($food['image'])): ?>

                                <img
                                    src="<?= htmlspecialchars($food['image']) ?>"
                                    class="food-image"
                                    alt="<?= htmlspecialchars($food['name']) ?>"
                                >

                            <?php else: ?>

                                <img
                                    src="https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=800&q=80"
                                    class="food-image"
                                    alt="<?= htmlspecialchars($food['name']) ?>"
                                >

                            <?php endif; ?>



                            <!-- Food information -->

                            <div class="food-body">


                                <h5>

                                    <?= htmlspecialchars($food['name']) ?>

                                </h5>


                                <p class="text-muted small mb-2">

                                    <i class="bi bi-shop"></i>

                                    <?= htmlspecialchars(
                                        $food['restaurant_name']
                                    ) ?>

                                </p>


                                <?php if (!empty($food['description'])): ?>

                                    <p class="text-muted small">

                                        <?= htmlspecialchars(
                                            $food['description']
                                        ) ?>

                                    </p>

                                <?php endif; ?>



                                <!-- Price -->

                                <div class="d-flex justify-content-between align-items-center">


                                    <span class="food-price">

                                        TZS
                                        <?= number_format(
                                            (float)$food['price']
                                        ) ?>

                                    </span>


                                    <a
                                        href="restaurant.php?id=<?= $food['restaurant_id'] ?>"
                                        class="btn btn-sm btn-outline-dark"
                                    >

                                        View

                                    </a>


                                </div>


                            </div>


                        </div>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="col-12">

                    <div class="alert alert-info text-center">

                        <i class="bi bi-info-circle"></i>

                        No food items are currently available.

                    </div>

                </div>


            <?php endif; ?>


        </div>


    </div>

</section>

<!-- =====================================================
     HOW MLOGO WORKS
===================================================== -->

<section
    id="how-it-works"
    class="section-padding bg-light"
>

    <div class="container">


        <div class="text-center">

            <h2 class="section-title">

                How MloGo Works

            </h2>


            <p class="section-subtitle">

                Ordering your favourite meal is simple.

            </p>

        </div>



        <div class="row g-4">


            <!-- Step 1 -->

            <div class="col-md-4">

                <div class="how-card">

                    <div class="how-icon">

                        <i class="bi bi-search"></i>

                    </div>


                    <h4>

                        1. Find Food

                    </h4>


                    <p class="text-muted">

                        Browse restaurants and discover
                        your favourite Tanzanian meals.

                    </p>

                </div>

            </div>



            <!-- Step 2 -->

            <div class="col-md-4">

                <div class="how-card">

                    <div class="how-icon">

                        <i class="bi bi-cart3"></i>

                    </div>


                    <h4>

                        2. Place Your Order

                    </h4>


                    <p class="text-muted">

                        Choose your food and select
                        pickup or delivery.

                    </p>

                </div>

            </div>



            <!-- Step 3 -->

            <div class="col-md-4">

                <div class="how-card">

                    <div class="how-icon">

                        <i class="bi bi-emoji-smile"></i>

                    </div>


                    <h4>

                        3. Enjoy Your Meal

                    </h4>


                    <p class="text-muted">

                        Track your order and enjoy
                        your freshly prepared food.

                    </p>

                </div>

            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     PROMOTION
===================================================== -->

<section class="section-padding">

    <div class="container">

        <div class="promo-section">


            <div class="row align-items-center">


                <div class="col-lg-8">

                    <h2 class="fw-bold mb-3">

                        Your favourite food is
                        just a few clicks away.

                    </h2>


                    <p class="mb-0">

                        Create your MloGo account and
                        start discovering restaurants near you.

                    </p>

                </div>



                <div
                    class="col-lg-4 text-lg-end mt-4 mt-lg-0"
                >

                    <a
                        href="register.php"
                        class="btn btn-light btn-lg"
                    >

                        Create Account

                    </a>

                </div>


            </div>

        </div>

    </div>

</section>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer>

    <div class="container">


        <div class="row g-4">


            <!-- About -->

            <div class="col-lg-4">

                <h3 class="brand-logo text-white">

                    Mlo<span>Go</span>

                </h3>


                <p class="mt-3 text-white-50">

                    Connecting Tanzanian food lovers
                    with great local restaurants.

                </p>

            </div>



            <!-- Explore -->

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

                        <a href="#categories">

                            Categories

                        </a>

                    </li>

                </ul>

            </div>



            <!-- Account -->

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



            <!-- Social -->

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



<!-- Bootstrap JavaScript -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>