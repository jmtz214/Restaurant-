<?php

session_start();

require_once "../config/database.php";
require_once "../includes/functions.php";


if (
    empty($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'customer'
) {
    header("Location: ../login.php");
    exit;
}


$customerId = (int) $_SESSION['user_id'];


$stmt = $pdo->prepare("
    SELECT
        o.id,
        o.order_number,
        o.restaurant_id,
        o.order_type,
        o.status,
        o.total_amount,
        o.payment_method,
        o.payment_status,
        o.placed_at,
        o.created_at,

        r.name AS restaurant_name

    FROM orders o

    INNER JOIN restaurants r
        ON o.restaurant_id = r.id

    WHERE o.customer_id = ?

    ORDER BY o.created_at DESC
");

$stmt->execute([
    $customerId
]);

$orders = $stmt->fetchAll();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Orders - MloGo</title>


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

            background: #f7f8fa;

        }


        .navbar {

            background: white;

            box-shadow:
                0 2px 10px
                rgba(0,0,0,.05);

        }


        .brand {

            font-size: 27px;

            font-weight: 800;

            text-decoration: none;

            color: #17202a;

        }


        .brand span {

            color: #20c997;

        }


        .page {

            max-width: 1100px;

            margin: auto;

            padding: 35px 15px;

        }


        .order-card {

            background: white;

            border-radius: 16px;

            padding: 22px;

            margin-bottom: 15px;

            box-shadow:
                0 4px 18px
                rgba(0,0,0,.04);

            transition: .2s;

        }


        .order-card:hover {

            transform: translateY(-2px);

            box-shadow:
                0 8px 25px
                rgba(0,0,0,.08);

        }


        .status {

            padding: 7px 13px;

            border-radius: 30px;

            font-size: 12px;

            font-weight: 700;

        }


        .pending {

            background: #fff3cd;

            color: #856404;

        }


        .accepted,
        .preparing {

            background: #cff4fc;

            color: #055160;

        }


        .ready,
        .completed {

            background: #d1e7dd;

            color: #0f5132;

        }


        .out_for_delivery {

            background: #e2d9f3;

            color: #59359a;

        }


        .denied,
        .cancelled {

            background: #f8d7da;

            color: #842029;

        }

    </style>

</head>


<body>


<nav class="navbar">

    <div class="container">


        <a
            href="dashboard.php"
            class="brand"
        >

            Mlo<span>Go</span>

        </a>


        <div class="d-flex gap-2">

            <a
                href="dashboard.php"
                class="btn btn-outline-secondary btn-sm"
            >

                <i class="bi bi-house"></i>

                Home

            </a>


            <a
                href="../logout.php"
                class="btn btn-outline-danger btn-sm"
            >

                Logout

            </a>

        </div>

    </div>

</nav>



<div class="page">


    <div class="mb-4">

        <h2 class="fw-bold">

            My Orders

        </h2>

        <p class="text-muted">

            Track your current and previous orders.

        </p>

    </div>



    <?php if (empty($orders)): ?>


        <div class="text-center py-5">

            <i
                class="bi bi-receipt fs-1 text-muted"
            ></i>


            <h4 class="mt-3">

                No orders yet

            </h4>


            <p class="text-muted">

                Your food orders will appear here.

            </p>


            <a
                href="../restaurants.php"
                class="btn btn-success"
            >

                Browse Restaurants

            </a>

        </div>


    <?php else: ?>


        <?php foreach (
            $orders
            as $order
        ): ?>


            <?php

            $statusLabel = ucwords(
                str_replace(
                    '_',
                    ' ',
                    $order['status']
                )
            );

            ?>


            <div class="order-card">


                <div class="row align-items-center">


                    <div class="col-md-4">


                        <small class="text-muted">

                            Order

                        </small>


                        <h5 class="fw-bold mb-1">

                            #<?= htmlspecialchars(
                                $order[
                                    'order_number'
                                ]
                            ) ?>

                        </h5>


                        <div class="text-muted">

                            <?= htmlspecialchars(
                                $order[
                                    'restaurant_name'
                                ]
                            ) ?>

                        </div>


                    </div>



                    <div class="col-md-2 mt-3 mt-md-0">


                        <small class="text-muted d-block">

                            Type

                        </small>


                        <strong>

                            <?= htmlspecialchars(
                                ucfirst(
                                    $order[
                                        'order_type'
                                    ]
                                )
                            ) ?>

                        </strong>


                    </div>



                    <div class="col-md-2 mt-3 mt-md-0">


                        <small class="text-muted d-block">

                            Total

                        </small>


                        <strong>

                            TZS
                            <?= number_format(
                                (float)
                                $order[
                                    'total_amount'
                                ]
                            ) ?>

                        </strong>


                    </div>



                    <div class="col-md-2 mt-3 mt-md-0">


                        <span
                            class="status <?= htmlspecialchars(
                                $order['status']
                            ) ?>"
                        >

                            <?= htmlspecialchars(
                                $statusLabel
                            ) ?>

                        </span>


                    </div>



                    <div class="col-md-2 mt-3 mt-md-0 text-md-end">


                        <a
                            href="order-details.php?id=<?= (int) $order['id'] ?>"
                            class="btn btn-success btn-sm"
                        >

                            View Order

                            <i class="bi bi-arrow-right"></i>

                        </a>


                    </div>


                </div>


            </div>


        <?php endforeach; ?>


    <?php endif; ?>


</div>


</body>

</html>