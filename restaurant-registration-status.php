<?php

session_start();

require_once "config/database.php";
require_once "includes/functions.php";


// =====================================================
// AUTHENTICATION
// =====================================================

if (
    empty($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'restaurant_admin'
) {
    redirect('login.php');
}


// =====================================================
// GET RESTAURANT
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        status
    FROM restaurants
    WHERE owner_id = ?
    LIMIT 1
");

$stmt->execute([
    $_SESSION['user_id']
]);

$restaurant = $stmt->fetch();


// =====================================================
// NO RESTAURANT
// =====================================================

if (!$restaurant) {

    $status = 'none';
    $restaurantName = 'Your Restaurant';

} else {

    $status = $restaurant['status'];
    $restaurantName = $restaurant['name'];

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

    <title>Restaurant Registration Status - MloGo</title>

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
            background: #f8f9fa;
        }

        .status-card {
            max-width: 650px;
            margin: 100px auto;
            border: none;
            border-radius: 20px;
        }

        .status-icon {
            font-size: 70px;
        }

    </style>

</head>

<body>


<div class="container">

    <div class="card shadow status-card">

        <div class="card-body text-center p-5">


            <?php if ($status === 'pending'): ?>

                <div class="status-icon text-warning">

                    <i class="bi bi-hourglass-split"></i>

                </div>

                <h2 class="mt-3">

                    Registration Under Review

                </h2>

                <p class="text-muted">

                    Your restaurant

                    <strong>
                        <?= clean($restaurantName) ?>
                    </strong>

                    has been successfully submitted.

                </p>

                <p>

                    Our MloGo administration team is currently
                    reviewing your restaurant.

                    You will receive access to the restaurant
                    dashboard once your restaurant is approved.

                </p>


                <span class="badge bg-warning text-dark p-2">

                    Pending Approval

                </span>


            <?php elseif ($status === 'rejected'): ?>

                <div class="status-icon text-danger">

                    <i class="bi bi-x-circle"></i>

                </div>

                <h2 class="mt-3">

                    Registration Rejected

                </h2>

                <p class="text-muted">

                    Unfortunately, your restaurant registration
                    was not approved by MloGo administration.

                </p>

                <p>

                    Please contact MloGo administration for
                    more information.

                </p>


                <span class="badge bg-danger p-2">

                    Rejected

                </span>


            <?php elseif ($status === 'suspended'): ?>

                <div class="status-icon text-danger">

                    <i class="bi bi-pause-circle"></i>

                </div>

                <h2 class="mt-3">

                    Restaurant Suspended

                </h2>

                <p class="text-muted">

                    Your restaurant

                    <strong>
                        <?= clean($restaurantName) ?>
                    </strong>

                    has currently been suspended.

                </p>

                <p>

                    You cannot access restaurant management
                    features while your restaurant is suspended.

                    Please contact MloGo administration.

                </p>


                <span class="badge bg-danger p-2">

                    Suspended

                </span>


            <?php else: ?>

                <div class="status-icon text-secondary">

                    <i class="bi bi-shop"></i>

                </div>

                <h2 class="mt-3">

                    Restaurant Not Found

                </h2>

                <p class="text-muted">

                    We could not find a restaurant associated
                    with your account.

                </p>

                <a
                    href="restaurant-register.php"
                    class="btn btn-success mt-3"
                >

                    Register Restaurant

                </a>

            <?php endif; ?>


            <div class="mt-4">

                <a
                    href="logout.php"
                    class="btn btn-outline-secondary"
                >

                    <i class="bi bi-box-arrow-right"></i>

                    Logout

                </a>

            </div>


        </div>

    </div>

</div>


</body>

</html>