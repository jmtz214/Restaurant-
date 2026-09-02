<?php

session_start();

require_once "config/database.php";
require_once "includes/functions.php";

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // =====================================================
    // OWNER INFORMATION
    // =====================================================

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');

    $password  = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';


    // =====================================================
    // RESTAURANT INFORMATION
    // =====================================================

    $restaurantName = trim($_POST['restaurant_name'] ?? '');
    $description    = trim($_POST['description'] ?? '');

    $restaurantPhone = trim($_POST['restaurant_phone'] ?? '');
    $restaurantEmail = trim($_POST['restaurant_email'] ?? '');

    $address = trim($_POST['address'] ?? '');
    $city    = trim($_POST['city'] ?? '');
    $region  = trim($_POST['region'] ?? '');

    $openingTime = $_POST['opening_time'] ?? null;
    $closingTime = $_POST['closing_time'] ?? null;

    $deliveryAvailable = isset($_POST['delivery_available']) ? 1 : 0;
    $pickupAvailable   = isset($_POST['pickup_available']) ? 1 : 0;

    $minimumOrderAmount = (float)($_POST['minimum_order_amount'] ?? 0);
    $deliveryFee        = (float)($_POST['delivery_fee'] ?? 0);


    // =====================================================
    // VALIDATION
    // =====================================================

    if (
        $firstName === '' ||
        $lastName === '' ||
        $email === '' ||
        $phone === '' ||
        $password === '' ||
        $confirmPassword === '' ||
        $restaurantName === '' ||
        $restaurantPhone === '' ||
        $address === '' ||
        $city === ''
    ) {

        $error = "Please fill in all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid owner email address.";

    } elseif (
        $restaurantEmail !== '' &&
        !filter_var(
            $restaurantEmail,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error = "Please enter a valid restaurant email address.";

    } elseif (strlen($password) < 6) {

        $error = "Password must contain at least 6 characters.";

    } elseif ($password !== $confirmPassword) {

        $error = "Passwords do not match.";

    } elseif (
        !$deliveryAvailable &&
        !$pickupAvailable
    ) {

        $error = "Please select at least one ordering method.";

    } else {

        try {

            // =================================================
            // START TRANSACTION
            // =================================================

            $pdo->beginTransaction();


            // =================================================
            // CHECK OWNER EMAIL
            // =================================================

            $stmt = $pdo->prepare("
                SELECT id
                FROM users
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->execute([
                $email
            ]);

            if ($stmt->fetch()) {

                throw new Exception(
                    "An account with this email already exists."
                );
            }


            // =================================================
            // CREATE OWNER ACCOUNT
            // =================================================

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
                INSERT INTO users (
                    role,
                    first_name,
                    last_name,
                    email,
                    phone,
                    password_hash,
                    is_active
                )

                VALUES (
                    'restaurant_admin',
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    1
                )
            ");

            $stmt->execute([
                $firstName,
                $lastName,
                $email,
                $phone,
                $passwordHash
            ]);

            $ownerId = $pdo->lastInsertId();


            // =================================================
            // CREATE SLUG
            // =================================================

            $slug = strtolower(
                trim(
                    preg_replace(
                        '/[^A-Za-z0-9-]+/',
                        '-',
                        $restaurantName
                    ),
                    '-'
                )
            );


            // Make sure slug is unique

            $baseSlug = $slug;
            $counter = 1;

            while (true) {

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM restaurants
                    WHERE slug = ?
                    LIMIT 1
                ");

                $stmt->execute([
                    $slug
                ]);

                if (!$stmt->fetch()) {
                    break;
                }

                $slug = $baseSlug . '-' . $counter;

                $counter++;
            }


            // =================================================
            // CREATE RESTAURANT
            // =================================================

            $stmt = $pdo->prepare("
                INSERT INTO restaurants (
                    owner_id,
                    name,
                    slug,
                    description,
                    phone,
                    email,
                    address,
                    city,
                    region,
                    opening_time,
                    closing_time,
                    delivery_available,
                    pickup_available,
                    minimum_order_amount,
                    delivery_fee,
                    status
                )

                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'pending'
                )
            ");

            $stmt->execute([
                $ownerId,
                $restaurantName,
                $slug,
                $description !== ''
                    ? $description
                    : null,
                $restaurantPhone,
                $restaurantEmail !== ''
                    ? $restaurantEmail
                    : null,
                $address,
                $city,
                $region !== ''
                    ? $region
                    : null,
                $openingTime !== ''
                    ? $openingTime
                    : null,
                $closingTime !== ''
                    ? $closingTime
                    : null,
                $deliveryAvailable,
                $pickupAvailable,
                $minimumOrderAmount,
                $deliveryFee
            ]);


            // =================================================
            // COMMIT
            // =================================================

            $pdo->commit();

            $success =
                "Your restaurant registration has been submitted successfully. "
                . "Your account will become active after MloGo administration approves your restaurant.";

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $e->getMessage();
        }
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

    <title>Register Your Restaurant - MloGo</title>

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

        .register-container {
            max-width: 1000px;
            margin: 50px auto;
        }

        .register-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
        }

        .register-header {
            background: #198754;
            color: white;
            padding: 35px;
        }

        .register-header h1 {
            font-weight: 700;
        }

        .section-title {
            font-weight: 700;
            margin-bottom: 20px;
            color: #212529;
        }

        .form-control,
        .form-select {
            padding: 12px;
            border-radius: 10px;
        }

        .form-label {
            font-weight: 600;
        }

        .submit-btn {
            padding: 13px;
            border-radius: 10px;
            font-weight: 600;
        }

    </style>

</head>

<body>


<div class="container register-container">

    <div class="card shadow register-card">


        <!-- HEADER -->

        <div class="register-header">

            <h1>
                <i class="bi bi-shop"></i>
                Register Your Restaurant
            </h1>

            <p class="mb-0">

                Join MloGo and start receiving
                online food orders from customers.

            </p>

        </div>


        <div class="card-body p-4 p-md-5">


            <!-- ERROR -->

            <?php if ($error !== ''): ?>

                <div class="alert alert-danger">

                    <i class="bi bi-exclamation-circle"></i>

                    <?= clean($error) ?>

                </div>

            <?php endif; ?>


            <!-- SUCCESS -->

            <?php if ($success !== ''): ?>

                <div class="alert alert-success">

                    <i class="bi bi-check-circle"></i>

                    <?= clean($success) ?>

                </div>

            <?php endif; ?>


            <?php if ($success === ''): ?>

            <form
                method="POST"
                enctype="multipart/form-data"
            >


                <!-- =========================================
                     OWNER INFORMATION
                ========================================== -->

                <h4 class="section-title">

                    <i class="bi bi-person"></i>

                    Owner Information

                </h4>


                <div class="row">


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            First Name *
                        </label>

                        <input
                            type="text"
                            name="first_name"
                            class="form-control"
                            required
                        >

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Last Name *
                        </label>

                        <input
                            type="text"
                            name="last_name"
                            class="form-control"
                            required
                        >

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Email *
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            required
                        >

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Phone *
                        </label>

                        <input
                            type="tel"
                            name="phone"
                            class="form-control"
                            placeholder="07XXXXXXXX"
                            required
                        >

                    </div>


                    <div class="col-md-6 mb-4">

                        <label class="form-label">
                            Password *
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            minlength="6"
                            required
                        >

                    </div>


                    <div class="col-md-6 mb-4">

                        <label class="form-label">
                            Confirm Password *
                        </label>

                        <input
                            type="password"
                            name="confirm_password"
                            class="form-control"
                            minlength="6"
                            required
                        >

                    </div>

                </div>


                <hr class="my-4">


                <!-- =========================================
                     RESTAURANT INFORMATION
                ========================================== -->

                <h4 class="section-title">

                    <i class="bi bi-shop-window"></i>

                    Restaurant Information

                </h4>


                <div class="row">


                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Restaurant Name *
                        </label>

                        <input
                            type="text"
                            name="restaurant_name"
                            class="form-control"
                            placeholder="Example: Mamboz Restaurant"
                            required
                        >

                    </div>


                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Description
                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="4"
                            placeholder="Tell customers about your restaurant..."
                        ></textarea>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Restaurant Phone *
                        </label>

                        <input
                            type="tel"
                            name="restaurant_phone"
                            class="form-control"
                            required
                        >

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Restaurant Email
                        </label>

                        <input
                            type="email"
                            name="restaurant_email"
                            class="form-control"
                        >

                    </div>


                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Address *
                        </label>

                        <input
                            type="text"
                            name="address"
                            class="form-control"
                            placeholder="Street / Area"
                            required
                        >

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            City *
                        </label>

                        <input
                            type="text"
                            name="city"
                            class="form-control"
                            value="Dar es Salaam"
                            required
                        >

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Region
                        </label>

                        <input
                            type="text"
                            name="region"
                            class="form-control"
                            value="Dar es Salaam"
                        >

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Opening Time
                        </label>

                        <input
                            type="time"
                            name="opening_time"
                            class="form-control"
                        >

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Closing Time
                        </label>

                        <input
                            type="time"
                            name="closing_time"
                            class="form-control"
                        >

                    </div>

                </div>


                <!-- =========================================
                     ORDER OPTIONS
                ========================================== -->

                <h5 class="mt-4 mb-3">

                    Ordering Options

                </h5>


                <div class="row">


                    <div class="col-md-6 mb-3">

                        <div class="form-check">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="delivery_available"
                                id="delivery_available"
                                checked
                            >

                            <label
                                class="form-check-label"
                                for="delivery_available"
                            >

                                <i class="bi bi-truck"></i>

                                Delivery Available

                            </label>

                        </div>

                    </div>


                    <div class="col-md-6 mb-3">

                        <div class="form-check">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="pickup_available"
                                id="pickup_available"
                                checked
                            >

                            <label
                                class="form-check-label"
                                for="pickup_available"
                            >

                                <i class="bi bi-bag"></i>

                                Pickup Available

                            </label>

                        </div>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Minimum Order Amount
                        </label>

                        <input
                            type="number"
                            name="minimum_order_amount"
                            class="form-control"
                            min="0"
                            step="0.01"
                            value="0"
                        >

                    </div>


                    <div class="col-md-6 mb-4">

                        <label class="form-label">
                            Delivery Fee
                        </label>

                        <input
                            type="number"
                            name="delivery_fee"
                            class="form-control"
                            min="0"
                            step="0.01"
                            value="0"
                        >

                    </div>

                </div>


                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="btn btn-success w-100 submit-btn"
                >

                    <i class="bi bi-shop"></i>

                    Submit Restaurant for Approval

                </button>


            </form>

            <?php endif; ?>


            <div class="text-center mt-4">

                <a
                    href="login.php"
                    class="text-decoration-none"
                >

                    Already have an account?
                    Login

                </a>

            </div>


        </div>

    </div>

</div>


</body>

</html>