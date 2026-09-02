<?php

session_start();

require_once "config/database.php";
require_once "includes/functions.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {

        $error = "Please enter your email and password.";

    } else {

        $stmt = $pdo->prepare("
            SELECT *
            FROM users
            WHERE email = ?
            AND is_active = 1
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];

            $update = $pdo->prepare("
                UPDATE users
                SET last_login_at = NOW()
                WHERE id = ?
            ");

            $update->execute([$user['id']]);


// =====================================================
// REDIRECT AFTER LOGIN
// =====================================================

if (!empty($_SESSION['redirect_after_login'])) {

    $redirect =
        $_SESSION['redirect_after_login'];

    unset(
        $_SESSION['redirect_after_login']
    );

    redirect($redirect);

}


// Normal role-based redirect

if ($user['role'] === 'customer') {

    redirect('customer/dashboard.php');

} elseif ($user['role'] === 'restaurant_admin') {

        // =================================================
    // CHECK RESTAURANT STATUS
    // =================================================

    $restaurantStmt = $pdo->prepare("
        SELECT
            id,
            name,
            status
        FROM restaurants
        WHERE owner_id = ?
        LIMIT 1
    ");

    $restaurantStmt->execute([
        $user['id']
    ]);

    $restaurant = $restaurantStmt->fetch();


    // =================================================
    // NO RESTAURANT REGISTERED
    // =================================================

    if (!$restaurant) {

        $_SESSION['restaurant_access_message'] =
            "Your account is registered as a restaurant owner, "
            . "but no restaurant has been found.";

        redirect('restaurant-registration-status.php');

    }


    // =================================================
    // APPROVED
    // =================================================

    if ($restaurant['status'] === 'approved') {

        $_SESSION['restaurant_id'] =
            $restaurant['id'];

        $_SESSION['restaurant_name'] =
            $restaurant['name'];

        redirect('restaurant/dashboard.php');

    }


    // =================================================
    // PENDING
    // =================================================

    if ($restaurant['status'] === 'pending') {

        $_SESSION['restaurant_status'] =
            'pending';

        $_SESSION['restaurant_name'] =
            $restaurant['name'];

        redirect('restaurant-registration-status.php');

    }


    // =================================================
    // REJECTED
    // =================================================

    if ($restaurant['status'] === 'rejected') {

        $_SESSION['restaurant_status'] =
            'rejected';

        $_SESSION['restaurant_name'] =
            $restaurant['name'];

        redirect('restaurant-registration-status.php');

    }


    // =================================================
    // SUSPENDED
    // =================================================

    if ($restaurant['status'] === 'suspended') {

        $_SESSION['restaurant_status'] =
            'suspended';

        $_SESSION['restaurant_name'] =
            $restaurant['name'];

        redirect('restaurant-registration-status.php');

    }


} elseif ($user['role'] === 'super_admin') {

    redirect('admin/dashboard.php');

}


} else {

    $error = "Invalid email or password.";

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

    <title>Login - MloGo</title>


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

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            min-height: 100vh;

            font-family:
                "Segoe UI",
                Tahoma,
                Geneva,
                Verdana,
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #f1f8f4 0%,
                    #ffffff 50%,
                    #fff8e8 100%
                );

        }


        .login-wrapper {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 30px 15px;

        }


        .login-container {

            width: 100%;

            max-width: 1000px;

        }


        /* =========================================
           LEFT SIDE
        ========================================== */

        .login-brand-section {

            background:
                linear-gradient(
                    145deg,
                    #198754,
                    #126c42
                );

            color: white;

            border-radius: 25px 0 0 25px;

            min-height: 580px;

            display: flex;

            flex-direction: column;

            justify-content: center;

            padding: 50px;

            position: relative;

            overflow: hidden;

        }


        .login-brand-section::before {

            content: "";

            position: absolute;

            width: 250px;

            height: 250px;

            border-radius: 50%;

            background: rgba(255,255,255,0.08);

            top: -80px;

            right: -70px;

        }


        .login-brand-section::after {

            content: "";

            position: absolute;

            width: 180px;

            height: 180px;

            border-radius: 50%;

            background: rgba(255,255,255,0.06);

            bottom: -70px;

            left: -60px;

        }


        .brand-logo {

            font-size: 42px;

            font-weight: 800;

            margin-bottom: 20px;

            position: relative;

            z-index: 2;

        }


        .brand-logo i {

            margin-right: 8px;

        }


        .brand-title {

            font-size: 34px;

            font-weight: 700;

            line-height: 1.2;

            margin-bottom: 20px;

            position: relative;

            z-index: 2;

        }


        .brand-description {

            font-size: 16px;

            line-height: 1.8;

            opacity: 0.9;

            max-width: 400px;

            position: relative;

            z-index: 2;

        }


        .food-features {

            list-style: none;

            padding: 0;

            margin-top: 30px;

            position: relative;

            z-index: 2;

        }


        .food-features li {

            margin-bottom: 15px;

            font-size: 15px;

        }


        .food-features i {

            margin-right: 10px;

            color: #ffe08a;

        }


        /* =========================================
           RIGHT SIDE
        ========================================== */

        .login-card {

            background: white;

            border: none;

            border-radius: 0 25px 25px 0;

            min-height: 580px;

            display: flex;

            align-items: center;

            box-shadow:
                0 20px 50px rgba(0,0,0,0.10);

        }


        .login-card-body {

            width: 100%;

            padding: 50px;

        }


        .login-heading {

            font-size: 30px;

            font-weight: 700;

            color: #212529;

            margin-bottom: 8px;

        }


        .login-subtitle {

            color: #6c757d;

            margin-bottom: 30px;

        }


        /* =========================================
           FORM
        ========================================== */

        .form-label {

            font-weight: 600;

            color: #343a40;

        }


        .form-control {

            height: 52px;

            border-radius: 12px;

            border: 1px solid #dee2e6;

            padding-left: 16px;

            transition: all 0.2s ease;

        }


        .form-control:focus {

            border-color: #198754;

            box-shadow:
                0 0 0 0.2rem rgba(25,135,84,0.12);

        }


        .input-group-custom {

            position: relative;

        }


        .input-icon {

            position: absolute;

            left: 16px;

            top: 50%;

            transform: translateY(-50%);

            color: #198754;

            z-index: 5;

        }


        .input-with-icon {

            padding-left: 45px;

        }


        /* =========================================
           LOGIN BUTTON
        ========================================== */

        .login-button {

            height: 52px;

            border-radius: 12px;

            font-weight: 600;

            font-size: 16px;

            background: #198754;

            border: none;

            transition: all 0.2s ease;

        }


        .login-button:hover {

            background: #157347;

            transform: translateY(-1px);

            box-shadow:
                0 8px 20px rgba(25,135,84,0.25);

        }


        /* =========================================
           ALERT
        ========================================== */

        .alert {

            border-radius: 12px;

            border: none;

        }


        /* =========================================
           REGISTER
        ========================================== */

        .register-section {

            text-align: center;

            margin-top: 30px;

            padding-top: 25px;

            border-top: 1px solid #eeeeee;

            color: #6c757d;

        }


        .register-section a {

            color: #198754;

            font-weight: 600;

            text-decoration: none;

        }


        .register-section a:hover {

            text-decoration: underline;

        }


        /* =========================================
           RESPONSIVE
        ========================================== */

        @media (max-width: 767px) {

            .login-brand-section {

                border-radius: 25px 25px 0 0;

                min-height: auto;

                padding: 35px 30px;

            }


            .login-brand-section {

                text-align: center;

            }


            .brand-description {

                margin: auto;

            }


            .food-features {

                display: none;

            }


            .login-card {

                border-radius: 0 0 25px 25px;

                min-height: auto;

            }


            .login-card-body {

                padding: 35px 25px;

            }


            .brand-title {

                font-size: 28px;

            }

        }

    </style>

</head>


<body>


<div class="login-wrapper">


    <div class="login-container">


        <div class="row g-0">


            <!-- =====================================
                 LEFT BRAND SECTION
            ====================================== -->

            <div class="col-md-6">


                <div class="login-brand-section">


                    <div class="brand-logo">

                        <i class="bi bi-egg-fried"></i>

                        MloGo

                    </div>


                    <div class="brand-title">

                        Delicious food,

                        <br>

                        delivered your way.

                    </div>


                    <p class="brand-description">

                        Discover the best Tanzanian food from
                        restaurants around you. Order your
                        favorite meals for pickup or delivery.

                    </p>


                    <ul class="food-features">

                        <li>

                            <i class="bi bi-check-circle-fill"></i>

                            Discover nearby restaurants

                        </li>


                        <li>

                            <i class="bi bi-check-circle-fill"></i>

                            Browse fresh food menus

                        </li>


                        <li>

                            <i class="bi bi-check-circle-fill"></i>

                            Pickup or delivery

                        </li>


                        <li>

                            <i class="bi bi-check-circle-fill"></i>

                            Track your orders

                        </li>

                    </ul>


                </div>

            </div>



            <!-- =====================================
                 LOGIN FORM
            ====================================== -->

            <div class="col-md-6">


                <div class="login-card">


                    <div class="login-card-body">


                        <h2 class="login-heading">

                            Welcome Back

                        </h2>


                        <p class="login-subtitle">

                            Login to continue to your MloGo account.

                        </p>


                        <?php if ($error): ?>

                            <div class="alert alert-danger">

                                <i class="bi bi-exclamation-circle me-2"></i>

                                <?= clean($error) ?>

                            </div>

                        <?php endif; ?>



                        <form method="POST">


                            <!-- EMAIL -->

                            <div class="mb-4">


                                <label class="form-label">

                                    Email Address

                                </label>


                                <div class="input-group-custom">


                                    <i class="bi bi-envelope input-icon"></i>


                                    <input
                                        type="email"
                                        name="email"
                                        class="form-control input-with-icon"
                                        placeholder="Enter your email"
                                        required
                                    >


                                </div>

                            </div>



                            <!-- PASSWORD -->

                            <div class="mb-4">


                                <label class="form-label">

                                    Password

                                </label>


                                <div class="input-group-custom">


                                    <i class="bi bi-lock input-icon"></i>


                                    <input
                                        type="password"
                                        name="password"
                                        class="form-control input-with-icon"
                                        placeholder="Enter your password"
                                        required
                                    >


                                </div>

                            </div>



                            <!-- LOGIN BUTTON -->

                            <button
                                type="submit"
                                class="btn btn-success w-100 login-button"
                            >

                                <i class="bi bi-box-arrow-in-right me-2"></i>

                                Login to MloGo

                            </button>


                        </form>



                        <!-- REGISTER -->

                        <div class="register-section">


                            <div>

                                Don't have an account?

                            </div>


                            <a
                                href="register.php"
                                class="d-inline-block mt-2"
                            >

                                <i class="bi bi-person-plus me-1"></i>

                                Create an Account

                            </a>


                        </div>


                    </div>

                </div>

            </div>


        </div>

    </div>

</div>


</body>

</html>