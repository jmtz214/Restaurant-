<?php

require_once "../includes/auth.php";

requireRole('restaurant_admin');

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Restaurant Dashboard - MloGo</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

</head>

<body>

<div class="container mt-5">

    <h1>
        Restaurant Dashboard
    </h1>

    <p>
        Welcome,
        <?= clean($_SESSION['first_name']) ?>
    </p>

    <a
        href="../logout.php"
        class="btn btn-danger">

        Logout

    </a>

</div>

</body>

</html>