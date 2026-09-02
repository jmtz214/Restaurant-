<?php

session_start();

require_once "config/database.php";
require_once "includes/functions.php";

// Check whether customer registration is enabled
$customerRegistrationEnabled =
    getSetting('customer_registration', '1') === '1';

// Check whether restaurant registration is enabled
$restaurantRegistrationEnabled =
    getSetting('restaurant_registration', '1') === '1';
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Register - MloGo</title>


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

        body {
            background: #f8f9fa;
            min-height: 100vh;
        }


        .register-wrapper {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 40px 15px;

        }


        .register-container {

            width: 100%;

            max-width: 1000px;

        }


        .brand {

            text-align: center;

            margin-bottom: 40px;

        }


        .brand h1 {

            font-weight: 800;

            color: #198754;

            margin-bottom: 8px;

        }


        .brand p {

            color: #6c757d;

            margin: 0;

        }


        .registration-card {

            background: white;

            border: none;

            border-radius: 20px;

            overflow: hidden;

            height: 100%;

            transition: all 0.3s ease;

        }


        .registration-card:hover {

            transform: translateY(-8px);

            box-shadow: 0 15px 35px rgba(0,0,0,0.12);

        }


        .card-icon {

            width: 90px;

            height: 90px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            margin: 0 auto 25px;

            font-size: 42px;

        }


        .customer-icon {

            background: #e8f5e9;

            color: #198754;

        }


        .restaurant-icon {

            background: #fff3cd;

            color: #f0ad00;

        }


        .registration-card h3 {

            font-weight: 700;

        }


        .registration-card p {

            color: #6c757d;

            min-height: 70px;

        }


        .feature-list {

            list-style: none;

            padding: 0;

            margin: 25px 0;

            text-align: left;

        }


        .feature-list li {

            margin-bottom: 12px;

            color: #495057;

        }


        .feature-list i {

            color: #198754;

            margin-right: 8px;

        }


        .restaurant-features i {

            color: #f0ad00;

        }


        .login-link {

            text-align: center;

            margin-top: 30px;

        }


        .login-link a {

            color: #198754;

            font-weight: 600;

            text-decoration: none;

        }


        .login-link a:hover {

            text-decoration: underline;

        }

    </style>

</head>


<body>


<div class="register-wrapper">

    <div class="register-container">


        <!-- BRAND -->

        <div class="brand">

            <h1>

                <i class="bi bi-egg-fried"></i>

                MloGo

            </h1>

            <p>

                Join MloGo and enjoy great food or grow your
                restaurant business.

            </p>

        </div>



        <div class="row g-4">


            <!-- =========================================
                 CUSTOMER
            ========================================== -->

            <div class="col-md-6">

                <div class="card registration-card shadow-sm">

                    <div class="card-body text-center p-5">


                        <div class="card-icon customer-icon">

                            <i class="bi bi-person-fill"></i>

                        </div>


                        <h3>

                            I'm a Customer

                        </h3>


                        <p>

                            Create an account to discover
                            restaurants, browse menus and order
                            your favorite food.

                        </p>


                        <ul class="feature-list">

                            <li>

                                <i class="bi bi-check-circle-fill"></i>

                                Discover restaurants

                            </li>


                            <li>

                                <i class="bi bi-check-circle-fill"></i>

                                Browse food menus

                            </li>


                            <li>

                                <i class="bi bi-check-circle-fill"></i>

                                Order food online

                            </li>


                            <li>

                                <i class="bi bi-check-circle-fill"></i>

                                Track your orders

                            </li>

                        </ul>


                        <a
                            href="customer-register.php"
                            class="btn btn-success btn-lg w-100"
                        >

                            <i class="bi bi-person-plus"></i>

                            Create Customer Account

                        </a>


                    </div>

                </div>

            </div>



            <!-- =========================================
                 RESTAURANT
            ========================================== -->

            <div class="col-md-6">

                <div class="card registration-card shadow-sm">

                    <div class="card-body text-center p-5">


                        <div class="card-icon restaurant-icon">

                            <i class="bi bi-shop"></i>

                        </div>


                        <h3>

                            I'm a Restaurant Owner

                        </h3>


                        <p>

                            Register your restaurant on MloGo,
                            manage your menu and receive orders
                            from customers.

                        </p>


                        <ul class="feature-list restaurant-features">

                            <li>

                                <i class="bi bi-check-circle-fill"></i>

                                Create your restaurant profile

                            </li>


                            <li>

                                <i class="bi bi-check-circle-fill"></i>

                                Manage food menus

                            </li>


                            <li>

                                <i class="bi bi-check-circle-fill"></i>

                                Receive customer orders

                            </li>


                            <li>

                                <i class="bi bi-check-circle-fill"></i>

                                Track restaurant performance

                            </li>

                        </ul>


                        <a
                            href="restaurant-register.php"
                            class="btn btn-warning btn-lg w-100"
                        >

                            <i class="bi bi-shop"></i>

                            Register Your Restaurant

                        </a>


                    </div>

                </div>

            </div>


        </div>



        <!-- LOGIN -->

        <div class="login-link">

            Already have an account?

            <a href="login.php">

                Login here

            </a>

        </div>


    </div>

</div>


</body>

</html>