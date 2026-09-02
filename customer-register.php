<?php

session_start();

require_once "config/database.php";
require_once "includes/functions.php";

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';


    // =================================================
    // VALIDATION
    // =================================================

    if (
        $firstName === '' ||
        $lastName === '' ||
        $email === '' ||
        $password === '' ||
        $confirm === ''
    ) {

        $error = "Please fill in all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 6) {

        $error = "Password must contain at least 6 characters.";

    } elseif ($password !== $confirm) {

        $error = "Passwords do not match.";

    } else {

        // =================================================
        // CHECK EMAIL
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

            $error = "An account with this email already exists.";

        } else {

            // =================================================
            // CREATE CUSTOMER
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
                    is_active,
                    created_at,
                    updated_at
                )
                VALUES (
                    'customer',
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    1,
                    NOW(),
                    NOW()
                )
            ");


            $stmt->execute([
                $firstName,
                $lastName,
                $email,
                $phone !== '' ? $phone : null,
                $passwordHash
            ]);


            $success =
                "Your customer account has been created successfully.";

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

    <title>Customer Registration - MloGo</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

</head>


<body class="bg-light">


<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-7 col-lg-6">


            <div class="text-center mb-4">

                <h1 class="text-success fw-bold">

                    <i class="bi bi-egg-fried"></i>

                    MloGo

                </h1>

                <p class="text-muted">

                    Create your customer account

                </p>

            </div>


            <div class="card border-0 shadow">

                <div class="card-body p-4 p-md-5">


                    <h3 class="mb-4">

                        Create Customer Account

                    </h3>


                    <?php if ($error): ?>

                        <div class="alert alert-danger">

                            <?= clean($error) ?>

                        </div>

                    <?php endif; ?>


                    <?php if ($success): ?>

                        <div class="alert alert-success">

                            <?= clean($success) ?>

                            <div class="mt-3">

                                <a
                                    href="login.php"
                                    class="btn btn-success"
                                >

                                    Login Now

                                </a>

                            </div>

                        </div>

                    <?php endif; ?>


                    <?php if (!$success): ?>


                    <form method="POST">


                        <div class="row">


                            <div class="col-md-6 mb-3">

                                <label class="form-label">

                                    First Name

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

                                    Last Name

                                </label>

                                <input
                                    type="text"
                                    name="last_name"
                                    class="form-control"
                                    required
                                >

                            </div>


                        </div>


                        <div class="mb-3">

                            <label class="form-label">

                                Email Address

                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                required
                            >

                        </div>


                        <div class="mb-3">

                            <label class="form-label">

                                Phone Number

                            </label>

                            <input
                                type="text"
                                name="phone"
                                class="form-control"
                                placeholder="+255..."
                            >

                        </div>


                        <div class="mb-3">

                            <label class="form-label">

                                Password

                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                required
                            >

                        </div>


                        <div class="mb-4">

                            <label class="form-label">

                                Confirm Password

                            </label>

                            <input
                                type="password"
                                name="confirm_password"
                                class="form-control"
                                required
                            >

                        </div>


                        <button
                            type="submit"
                            class="btn btn-success w-100"
                        >

                            <i class="bi bi-person-plus"></i>

                            Create Account

                        </button>


                    </form>


                    <?php endif; ?>


                    <div class="text-center mt-4">

                        <a href="register.php">

                            <i class="bi bi-arrow-left"></i>

                            Back to registration options

                        </a>

                    </div>


                </div>

            </div>


        </div>

    </div>

</div>


</body>

</html>