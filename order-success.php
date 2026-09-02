<?php

session_start();

$success =
    $_SESSION['order_success']
    ?? null;


if (!$success) {

    header("Location: index.php");
    exit;

}


unset(
    $_SESSION['order_success']
);

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
        Order Placed - MloGo
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >


    <style>

        .success-page {

            min-height: 75vh;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #f8f9fa;

        }


        .success-card {

            max-width: 600px;

            background: white;

            border-radius: 24px;

            padding: 50px 30px;

            text-align: center;

            border: 1px solid #eee;

            box-shadow:
                0 15px 40px
                rgba(0,0,0,.06);

        }


        .success-icon {

            width: 90px;

            height: 90px;

            border-radius: 50%;

            background: #d1e7dd;

            color: #198754;

            display: flex;

            align-items: center;

            justify-content: center;

            margin: 0 auto 25px;

            font-size: 45px;

        }


        .order-number {

            background: #f8f9fa;

            border-radius: 12px;

            padding: 15px;

            margin: 25px 0;

            font-size: 18px;

            font-weight: 600;

        }

    </style>

</head>


<body>


<nav class="navbar main-navbar">

    <div class="container">

        <a
            class="navbar-brand brand-logo"
            href="index.php"
        >

            Mlo<span>Go</span>

        </a>

    </div>

</nav>



<section class="success-page">

    <div class="container">


        <div class="success-card">


            <div class="success-icon">

                <i class="bi bi-check-lg"></i>

            </div>


            <h1 class="fw-bold">

                Order Placed Successfully!

            </h1>


            <p class="text-muted mt-3">

                Thank you for ordering with MloGo.

                Your order has been sent to the restaurant.

            </p>


            <div class="order-number">

                Order Number:

                <span class="text-success">

                    <?= htmlspecialchars(
                        $success['order_number']
                    ) ?>

                </span>

            </div>


            <p class="text-muted">

                The restaurant will review your order
                and either accept or deny it based
                on food availability.

            </p>


            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center mt-4">


                <a
                    href="index.php"
                    class="btn btn-success px-4"
                >

                    Back to Home

                </a>


                <a
                    href="restaurants.php"
                    class="btn btn-outline-dark px-4"
                >

                    Order More Food

                </a>


            </div>


        </div>

    </div>

</section>


</body>

</html>