<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/database.php";


// =====================================================
// GET FILTER VALUES
// =====================================================

$search = trim($_GET['search'] ?? '');

$city = trim($_GET['city'] ?? '');

$service = trim($_GET['service'] ?? '');

$sort = $_GET['sort'] ?? 'rating';


// =====================================================
// BUILD QUERY
// =====================================================

$sql = "
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
    WHERE status = 'approved'
";

$params = [];


// =====================================================
// SEARCH
// =====================================================

if ($search !== '') {

    $sql .= "
        AND (
            name LIKE ?
            OR description LIKE ?
            OR city LIKE ?
        )
    ";

    $searchTerm = "%{$search}%";

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}


// =====================================================
// CITY FILTER
// =====================================================

if ($city !== '') {

    $sql .= " AND city = ? ";

    $params[] = $city;
}


// =====================================================
// SERVICE FILTER
// =====================================================

if ($service === 'delivery') {

    $sql .= "
        AND delivery_available = 1
    ";

}

elseif ($service === 'pickup') {

    $sql .= "
        AND pickup_available = 1
    ";

}


// =====================================================
// SORTING
// =====================================================

switch ($sort) {

    case 'rating':

        $sql .= "
            ORDER BY rating DESC,
                     total_reviews DESC
        ";

        break;


    case 'reviews':

        $sql .= "
            ORDER BY total_reviews DESC
        ";

        break;


    case 'name':

        $sql .= "
            ORDER BY name ASC
        ";

        break;


    default:

        $sql .= "
            ORDER BY rating DESC
        ";

        break;
}


// =====================================================
// EXECUTE QUERY
// =====================================================

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$restaurants = $stmt->fetchAll();


// =====================================================
// GET CITIES
// =====================================================

$cityStmt = $pdo->query("
    SELECT DISTINCT city
    FROM restaurants
    WHERE status = 'approved'
      AND city IS NOT NULL
      AND city != ''
    ORDER BY city ASC
");

$cities = $cityStmt->fetchAll(PDO::FETCH_COLUMN);

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
        Restaurants - MloGo
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

        .restaurant-page-header {

            background: #fffaf5;

            padding: 70px 0 50px;

        }


        .restaurant-page-header h1 {

            font-size: 44px;

            font-weight: 800;

        }


        .filter-box {

            background: white;

            border: 1px solid #eeeeee;

            border-radius: 16px;

            padding: 20px;

            box-shadow:
                0 5px 25px rgba(0, 0, 0, 0.04);

        }


        .restaurant-status {

            position: absolute;

            top: 15px;

            left: 15px;

            padding: 6px 10px;

            border-radius: 8px;

            background: white;

            font-size: 13px;

            font-weight: 600;

        }


        .restaurant-image-wrapper {

            position: relative;

        }


        .restaurant-image-wrapper img {

            width: 100%;

            height: 220px;

            object-fit: cover;

        }


        .restaurant-count {

            color: #6b7280;

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
                        class="nav-link active"
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
     PAGE HEADER
===================================================== -->

<section class="restaurant-page-header">

    <div class="container">


        <div class="text-center">

            <span class="badge text-bg-light p-2 mb-3">

                🍽️ Discover Great Food

            </span>


            <h1>

                Find Your Perfect Restaurant

            </h1>


            <p class="text-muted">

                Explore restaurants and discover delicious
                Tanzanian meals near you.

            </p>

        </div>


    </div>

</section>



<!-- =====================================================
     SEARCH & FILTERS
===================================================== -->

<section class="py-4">

    <div class="container">


        <div class="filter-box">


            <form
                method="GET"
                action="restaurants.php"
            >

                <div class="row g-3 align-items-end">


                    <!-- Search -->

                    <div class="col-lg-4">

                        <label class="form-label fw-semibold">

                            Search

                        </label>


                        <div class="input-group">

                            <span class="input-group-text">

                                <i class="bi bi-search"></i>

                            </span>


                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Restaurant, food or city..."
                                value="<?= htmlspecialchars($search) ?>"
                            >

                        </div>

                    </div>



                    <!-- City -->

                    <div class="col-md-4 col-lg-2">

                        <label class="form-label fw-semibold">

                            City

                        </label>


                        <select
                            name="city"
                            class="form-select"
                        >

                            <option value="">

                                All Cities

                            </option>


                            <?php foreach ($cities as $cityOption): ?>

                                <option
                                    value="<?= htmlspecialchars($cityOption) ?>"
                                    <?= $city === $cityOption ? 'selected' : '' ?>
                                >

                                    <?= htmlspecialchars($cityOption) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>



                    <!-- Service -->

                    <div class="col-md-4 col-lg-2">

                        <label class="form-label fw-semibold">

                            Service

                        </label>


                        <select
                            name="service"
                            class="form-select"
                        >

                            <option value="">

                                All Services

                            </option>


                            <option
                                value="delivery"
                                <?= $service === 'delivery' ? 'selected' : '' ?>
                            >

                                🚚 Delivery

                            </option>


                            <option
                                value="pickup"
                                <?= $service === 'pickup' ? 'selected' : '' ?>
                            >

                                🥡 Pickup

                            </option>

                        </select>

                    </div>



                    <!-- Sort -->

                    <div class="col-md-4 col-lg-2">

                        <label class="form-label fw-semibold">

                            Sort By

                        </label>


                        <select
                            name="sort"
                            class="form-select"
                        >

                            <option
                                value="rating"
                                <?= $sort === 'rating' ? 'selected' : '' ?>
                            >

                                Highest Rated

                            </option>


                            <option
                                value="reviews"
                                <?= $sort === 'reviews' ? 'selected' : '' ?>
                            >

                                Most Reviewed

                            </option>


                            <option
                                value="name"
                                <?= $sort === 'name' ? 'selected' : '' ?>
                            >

                                Name A-Z

                            </option>

                        </select>

                    </div>



                    <!-- Button -->

                    <div class="col-md-12 col-lg-2">

                        <button
                            type="submit"
                            class="btn btn-primary-custom w-100"
                        >

                            <i class="bi bi-search"></i>

                            Search

                        </button>

                    </div>


                </div>

            </form>


        </div>

    </div>

</section>



<!-- =====================================================
     RESTAURANT RESULTS
===================================================== -->

<section class="section-padding pt-4">

    <div class="container">


        <div class="d-flex justify-content-between align-items-center mb-4">


            <div>

                <h2 class="section-title mb-1">

                    Restaurants

                </h2>


                <p class="restaurant-count mb-0">

                    <?= count($restaurants) ?>

                    restaurant(s) found

                </p>

            </div>


            <?php if ($search !== '' || $city !== '' || $service !== ''): ?>

                <a
                    href="restaurants.php"
                    class="btn btn-outline-dark"
                >

                    Clear Filters

                </a>

            <?php endif; ?>


        </div>



        <div class="row g-4">


            <?php if (count($restaurants) > 0): ?>


                <?php foreach ($restaurants as $restaurant): ?>


                    <div class="col-md-6 col-lg-4">


                        <div class="restaurant-card">


<!-- Image -->

<div class="restaurant-image-wrapper">

    <?php if (!empty($restaurant['cover_image'])): ?>

        <img
            src="uploads/restaurants/covers/<?= htmlspecialchars($restaurant['cover_image']) ?>"
            alt="<?= htmlspecialchars($restaurant['name']) ?>"
        >

    <?php elseif (!empty($restaurant['logo'])): ?>

        <img
            src="uploads/restaurants/logos/<?= htmlspecialchars($restaurant['logo']) ?>"
            alt="<?= htmlspecialchars($restaurant['name']) ?>"
        >

    <?php else: ?>

        <img
            src="assets/images/default-restaurant.jpg"
            alt="<?= htmlspecialchars($restaurant['name']) ?>"
        >

    <?php endif; ?>




                                <span class="restaurant-status text-success">

                                    <i class="bi bi-circle-fill"></i>

                                    Open

                                </span>


                            </div>



                            <!-- Body -->

                            <div class="restaurant-body">


                                <div class="d-flex justify-content-between align-items-start">


                                    <h5>

                                        <?= htmlspecialchars(
                                            $restaurant['name']
                                        ) ?>

                                    </h5>


                                    <span class="rating">

                                        <i class="bi bi-star-fill"></i>

                                        <?= number_format(
                                            (float)$restaurant['rating'],
                                            1
                                        ) ?>

                                    </span>


                                </div>



                                <p class="restaurant-meta mb-2">

                                    <i class="bi bi-geo-alt"></i>

                                    <?= htmlspecialchars(
                                        $restaurant['city']
                                    ) ?>

                                </p>



                                <p class="restaurant-meta">

                                    <?= htmlspecialchars(
                                        $restaurant['description']
                                        ?: 'Delicious food and great service.'
                                    ) ?>

                                </p>



                                <!-- Services -->

                                <div class="d-flex flex-wrap gap-2 mb-3">


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


                                    <?php if ($restaurant['delivery_available']): ?>

                                        <span class="badge bg-light text-dark">

                                            TZS
                                            <?= number_format(
                                                (float)$restaurant['delivery_fee']
                                            ) ?>
                                            delivery

                                        </span>

                                    <?php endif; ?>


                                </div>



                                <!-- Button -->

                                <a
                                    href="restaurant.php?id=<?= $restaurant['id'] ?>"
                                    class="btn btn-primary-custom w-100"
                                >

                                    View Menu

                                    <i class="bi bi-arrow-right"></i>

                                </a>


                            </div>


                        </div>

                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <!-- No results -->

                <div class="col-12">

                    <div class="text-center py-5">


                        <div style="font-size:60px;">

                            🍽️

                        </div>


                        <h3 class="mt-3">

                            No restaurants found

                        </h3>


                        <p class="text-muted">

                            Try changing your search or filters.

                        </p>


                        <a
                            href="restaurants.php"
                            class="btn btn-primary-custom"
                        >

                            View All Restaurants

                        </a>


                    </div>

                </div>


            <?php endif; ?>


        </div>


    </div>

</section>



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



<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>