<?php

session_start();

require_once "../config/database.php";
require_once "../includes/functions.php";

$restaurant = requireRestaurantApproval($pdo);

// =====================================================
// AUTHENTICATION
// =====================================================

if (
    empty($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'restaurant_admin'
) {
    header("Location: ../login.php");
    exit;
}


// =====================================================
// GET RESTAURANT
// =====================================================

$stmt = $pdo->prepare("
    SELECT *
    FROM restaurants
    WHERE owner_id = ?
    LIMIT 1
");

$stmt->execute([
    $_SESSION['user_id']
]);

$restaurant = $stmt->fetch();

if (!$restaurant) {
    die("Restaurant not found.");
}

$restaurantId = (int) $restaurant['id'];


// =====================================================
// VARIABLES
// =====================================================

$success = "";
$error = "";


// =====================================================
// IMAGE DIRECTORIES
// =====================================================

$logoDirectory = "../uploads/restaurants/logos/";
$coverDirectory = "../uploads/restaurants/covers/";


// Create directories if they don't exist

if (!is_dir($logoDirectory)) {
    mkdir($logoDirectory, 0755, true);
}

if (!is_dir($coverDirectory)) {
    mkdir($coverDirectory, 0755, true);
}


// =====================================================
// UPDATE SETTINGS
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $region = trim($_POST['region'] ?? '');

    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');

    $openingTime = trim($_POST['opening_time'] ?? '');
    $closingTime = trim($_POST['closing_time'] ?? '');

    $deliveryAvailable =
        isset($_POST['delivery_available']) ? 1 : 0;

    $pickupAvailable =
        isset($_POST['pickup_available']) ? 1 : 0;

    $minimumOrderAmount =
        (float) ($_POST['minimum_order_amount'] ?? 0);

    $deliveryFee =
        (float) ($_POST['delivery_fee'] ?? 0);


    // =================================================
    // VALIDATION
    // =================================================

    if ($name === '') {

        $error = "Restaurant name is required.";

    } elseif ($phone === '') {

        $error = "Restaurant phone number is required.";

    } elseif ($address === '') {

        $error = "Restaurant address is required.";

    } elseif ($city === '') {

        $error = "City is required.";

    } elseif ($minimumOrderAmount < 0) {

        $error = "Minimum order amount cannot be negative.";

    } elseif ($deliveryFee < 0) {

        $error = "Delivery fee cannot be negative.";

    } elseif (
        $email !== '' &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $error = "Please enter a valid email address.";

    }


    // =================================================
    // VALIDATE LOCATION
    // =================================================

    if ($error === '') {

        if ($latitude !== '') {

            if (
                !is_numeric($latitude) ||
                $latitude < -90 ||
                $latitude > 90
            ) {

                $error =
                    "Latitude must be between -90 and 90.";

            }

        }


        if ($longitude !== '') {

            if (
                !is_numeric($longitude) ||
                $longitude < -180 ||
                $longitude > 180
            ) {

                $error =
                    "Longitude must be between -180 and 180.";

            }

        }

    }


    // =================================================
    // VALIDATE BUSINESS HOURS
    // =================================================

    if (
        $error === '' &&
        $openingTime !== '' &&
        $closingTime !== ''
    ) {

        if ($openingTime >= $closingTime) {

            $error =
                "Closing time must be later than opening time.";

        }

    }


    // =================================================
    // IMAGE UPLOAD FUNCTION
    // =================================================

    function uploadRestaurantImage(
        $file,
        $directory,
        $prefix
    ) {

        if (
            !isset($file) ||
            $file['error'] === UPLOAD_ERR_NO_FILE
        ) {

            return [
                'success' => true,
                'filename' => null
            ];

        }


        if ($file['error'] !== UPLOAD_ERR_OK) {

            return [
                'success' => false,
                'message' => 'Image upload failed.'
            ];

        }


        // Maximum 5MB

        if ($file['size'] > 5 * 1024 * 1024) {

            return [
                'success' => false,
                'message' =>
                    'Image size must not exceed 5MB.'
            ];

        }


        // Allowed extensions

        $allowedExtensions = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];


        $extension =
            strtolower(
                pathinfo(
                    $file['name'],
                    PATHINFO_EXTENSION
                )
            );


        if (
            !in_array(
                $extension,
                $allowedExtensions,
                true
            )
        ) {

            return [
                'success' => false,
                'message' =>
                    'Only JPG, JPEG, PNG and WEBP images are allowed.'
            ];

        }


        // Validate actual image

        $imageInfo =
            getimagesize($file['tmp_name']);


        if ($imageInfo === false) {

            return [
                'success' => false,
                'message' =>
                    'Uploaded file is not a valid image.'
            ];

        }


        // Generate unique filename

        $filename =
            $prefix .
            '_' .
            time() .
            '_' .
            bin2hex(random_bytes(5)) .
            '.' .
            $extension;


        $destination =
            $directory . $filename;


        if (
            !move_uploaded_file(
                $file['tmp_name'],
                $destination
            )
        ) {

            return [
                'success' => false,
                'message' =>
                    'Unable to save uploaded image.'
            ];

        }


        return [
            'success' => true,
            'filename' => $filename
        ];
    }


    // =================================================
    // UPLOAD LOGO
    // =================================================

    $newLogo = null;

    if ($error === '' && isset($_FILES['logo'])) {

        $logoResult =
            uploadRestaurantImage(
                $_FILES['logo'],
                $logoDirectory,
                'logo'
            );


        if (!$logoResult['success']) {

            $error =
                $logoResult['message'];

        } else {

            $newLogo =
                $logoResult['filename'];

        }

    }


    // =================================================
    // UPLOAD COVER IMAGE
    // =================================================

    $newCover = null;

    if ($error === '' && isset($_FILES['cover_image'])) {

        $coverResult =
            uploadRestaurantImage(
                $_FILES['cover_image'],
                $coverDirectory,
                'cover'
            );


        if (!$coverResult['success']) {

            $error =
                $coverResult['message'];

        } else {

            $newCover =
                $coverResult['filename'];

        }

    }


    // =================================================
    // UPDATE DATABASE
    // =================================================

    if ($error === '') {

        try {

            $sql = "
                UPDATE restaurants

                SET

                    name = ?,

                    description = ?,

                    phone = ?,

                    email = ?,

                    address = ?,

                    city = ?,

                    region = ?,

                    latitude = NULLIF(?, ''),

                    longitude = NULLIF(?, ''),

                    opening_time = NULLIF(?, ''),

                    closing_time = NULLIF(?, ''),

                    delivery_available = ?,

                    pickup_available = ?,

                    minimum_order_amount = ?,

                    delivery_fee = ?
            ";


            $params = [

                $name,

                $description !== ''
                    ? $description
                    : null,

                $phone,

                $email !== ''
                    ? $email
                    : null,

                $address,

                $city,

                $region !== ''
                    ? $region
                    : null,

                $latitude,

                $longitude,

                $openingTime,

                $closingTime,

                $deliveryAvailable,

                $pickupAvailable,

                $minimumOrderAmount,

                $deliveryFee

            ];


            // Add logo if uploaded

            if ($newLogo !== null) {

                $sql .= ",
                    logo = ?
                ";

                $params[] = $newLogo;

            }


            // Add cover if uploaded

            if ($newCover !== null) {

                $sql .= ",
                    cover_image = ?
                ";

                $params[] = $newCover;

            }


            $sql .= "
                WHERE id = ?
                  AND owner_id = ?
            ";


            $params[] = $restaurantId;

            $params[] = $_SESSION['user_id'];


            $update =
                $pdo->prepare($sql);


            $update->execute($params);


            $success =
                "Restaurant settings updated successfully.";


            // Reload restaurant data

            $stmt = $pdo->prepare("
                SELECT *
                FROM restaurants
                WHERE id = ?
                  AND owner_id = ?
                LIMIT 1
            ");

            $stmt->execute([
                $restaurantId,
                $_SESSION['user_id']
            ]);

            $restaurant =
                $stmt->fetch();


        } catch (PDOException $e) {

            $error =
                "Unable to update restaurant settings.";

        }

    }

}


// =====================================================
// CURRENT RESTAURANT STATUS
// =====================================================

$isOpen = false;

if (
    !empty($restaurant['opening_time']) &&
    !empty($restaurant['closing_time'])
) {

    $currentTime =
        date('H:i:s');

    $opening =
        $restaurant['opening_time'];

    $closing =
        $restaurant['closing_time'];


    if ($opening < $closing) {

        $isOpen =
            $currentTime >= $opening &&
            $currentTime <= $closing;

    } else {

        // Handles restaurants open across midnight

        $isOpen =
            $currentTime >= $opening ||
            $currentTime <= $closing;

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

    <title>
        Restaurant Settings - MloGo
    </title>


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

            background: #f6f8fb;

            color: #212529;

        }


        .settings-wrapper {

            max-width: 1200px;

            margin: auto;

        }


        .settings-card {

            border: none;

            border-radius: 18px;

            background: white;

            box-shadow:
                0 5px 25px
                rgba(0, 0, 0, .06);

        }


        .settings-header {

            border-bottom:
                1px solid #eee;

            padding: 25px;

        }


        .settings-body {

            padding: 30px;

        }


        .section-title {

            font-size: 18px;

            font-weight: 700;

            margin-bottom: 20px;

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .section-title i {

            color: #198754;

        }


        .form-label {

            font-weight: 600;

        }


        .form-control,
        .form-select {

            border-radius: 10px;

            padding: 11px 14px;

        }


        .form-control:focus,
        .form-select:focus {

            border-color: #198754;

            box-shadow:
                0 0 0 .2rem
                rgba(25, 135, 84, .12);

        }


        .image-box {

            border:
                2px dashed #dfe5e9;

            border-radius: 15px;

            padding: 20px;

            text-align: center;

            background: #fafbfc;

        }


        .logo-preview {

            width: 120px;

            height: 120px;

            object-fit: cover;

            border-radius: 15px;

            border: 1px solid #eee;

            margin-bottom: 15px;

        }


        .cover-preview {

            width: 100%;

            height: 180px;

            object-fit: cover;

            border-radius: 15px;

            border: 1px solid #eee;

            margin-bottom: 15px;

        }


        .status-box {

            border-radius: 12px;

            padding: 15px;

            background: #f8f9fa;

        }


        .save-button {

            padding:
                12px 30px;

            border-radius: 10px;

            font-weight: 600;

        }


        .back-button {

            border-radius: 10px;

        }


        .form-check-input {

            width: 2.7em;

            height: 1.35em;

        }


        .form-check-input:checked {

            background-color: #198754;

            border-color: #198754;

        }

    </style>

</head>


<body>


<div class="container-fluid py-4">

<div class="settings-wrapper">


    <!-- =================================================
         PAGE HEADER
    ================================================== -->

    <div
        class="d-flex justify-content-between align-items-center mb-4"
    >

        <div>

            <h2 class="fw-bold mb-1">

                Restaurant Settings

            </h2>

            <p class="text-muted mb-0">

                Manage your restaurant information,
                ordering options and business hours.

            </p>

        </div>


        <a
            href="dashboard.php"
            class="btn btn-outline-secondary back-button"
        >

            <i class="bi bi-arrow-left"></i>

            Back to Dashboard

        </a>

    </div>



    <!-- =================================================
         STATUS
    ================================================== -->

    <div class="settings-card mb-4">

        <div class="settings-body">

            <div class="status-box">

                <div
                    class="d-flex justify-content-between
                           align-items-center"
                >

                    <div>

                        <h6 class="fw-bold mb-1">

                            Restaurant Status

                        </h6>

                        <small class="text-muted">

                            Current status based on
                            your business hours.

                        </small>

                    </div>


                    <?php if ($isOpen): ?>

                        <span
                            class="badge bg-success
                                   rounded-pill px-3 py-2"
                        >

                            <i class="bi bi-circle-fill me-1"></i>

                            Open Now

                        </span>

                    <?php else: ?>

                        <span
                            class="badge bg-danger
                                   rounded-pill px-3 py-2"
                        >

                            <i class="bi bi-circle-fill me-1"></i>

                            Closed

                        </span>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>



    <!-- =================================================
         MESSAGES
    ================================================== -->

    <?php if ($success !== ''): ?>

        <div
            class="alert alert-success
                   alert-dismissible fade show"
        >

            <i class="bi bi-check-circle-fill me-2"></i>

            <?= clean($success) ?>


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <?php if ($error !== ''): ?>

        <div
            class="alert alert-danger
                   alert-dismissible fade show"
        >

            <i class="bi bi-exclamation-triangle-fill me-2"></i>

            <?= clean($error) ?>


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>



    <!-- =================================================
         SETTINGS FORM
    ================================================== -->

    <form
        method="POST"
        enctype="multipart/form-data"
    >


        <!-- =================================================
             BASIC INFORMATION
        ================================================== -->

        <div class="settings-card mb-4">

            <div class="settings-header">

                <div class="section-title mb-0">

                    <i class="bi bi-shop"></i>

                    Basic Restaurant Information

                </div>

            </div>


            <div class="settings-body">

                <div class="row g-4">


                    <div class="col-md-6">

                        <label class="form-label">

                            Restaurant Name

                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $restaurant['name']
                            ) ?>"
                            required
                        >

                    </div>


                    <div class="col-md-3">

                        <label class="form-label">

                            Phone

                        </label>

                        <input
                            type="text"
                            name="phone"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $restaurant['phone']
                            ) ?>"
                            required
                        >

                    </div>


                    <div class="col-md-3">

                        <label class="form-label">

                            Email

                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $restaurant['email'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <div class="col-12">

                        <label class="form-label">

                            Description

                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="4"
                            placeholder="Tell customers about your restaurant..."
                        ><?= htmlspecialchars(
                            $restaurant['description'] ?? ''
                        ) ?></textarea>

                    </div>


                </div>

            </div>

        </div>



        <!-- =================================================
             IMAGES
        ================================================== -->

        <div class="settings-card mb-4">

            <div class="settings-header">

                <div class="section-title mb-0">

                    <i class="bi bi-image"></i>

                    Restaurant Branding

                </div>

            </div>


            <div class="settings-body">

                <div class="row g-4">


                    <!-- LOGO -->

                    <div class="col-md-4">

                        <label class="form-label">

                            Restaurant Logo

                        </label>


                        <div class="image-box">


                            <?php if (
                                !empty(
                                    $restaurant['logo']
                                )
                            ): ?>

                                <img
                                    src="../uploads/restaurants/logos/<?= htmlspecialchars(
                                        $restaurant['logo']
                                    ) ?>"
                                    class="logo-preview"
                                    alt="Restaurant Logo"
                                >

                            <?php else: ?>

                                <div
                                    class="logo-preview
                                           d-flex
                                           align-items-center
                                           justify-content-center
                                           bg-light mx-auto"
                                >

                                    <i
                                        class="bi bi-shop
                                               fs-1
                                               text-muted"
                                    ></i>

                                </div>

                            <?php endif; ?>


                            <input
                                type="file"
                                name="logo"
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.webp"
                            >


                            <small class="text-muted">

                                Maximum 5MB

                            </small>

                        </div>

                    </div>



                    <!-- COVER -->

                    <div class="col-md-8">

                        <label class="form-label">

                            Cover Image

                        </label>


                        <div class="image-box">


                            <?php if (
                                !empty(
                                    $restaurant['cover_image']
                                )
                            ): ?>

                                <img
                                    src="../uploads/restaurants/covers/<?= htmlspecialchars(
                                        $restaurant['cover_image']
                                    ) ?>"
                                    class="cover-preview"
                                    alt="Restaurant Cover"
                                >

                            <?php else: ?>

                                <div
                                    class="cover-preview
                                           d-flex
                                           align-items-center
                                           justify-content-center
                                           bg-light"
                                >

                                    <i
                                        class="bi bi-image
                                               fs-1
                                               text-muted"
                                    ></i>

                                </div>

                            <?php endif; ?>


                            <input
                                type="file"
                                name="cover_image"
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.webp"
                            >


                            <small class="text-muted">

                                Maximum 5MB

                            </small>

                        </div>

                    </div>


                </div>

            </div>

        </div>



        <!-- =================================================
             LOCATION
        ================================================== -->

        <div class="settings-card mb-4">

            <div class="settings-header">

                <div class="section-title mb-0">

                    <i class="bi bi-geo-alt"></i>

                    Restaurant Location

                </div>

            </div>


            <div class="settings-body">

                <div class="row g-4">


                    <div class="col-md-6">

                        <label class="form-label">

                            Address

                        </label>

                        <input
                            type="text"
                            name="address"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $restaurant['address']
                            ) ?>"
                            required
                        >

                    </div>


                    <div class="col-md-3">

                        <label class="form-label">

                            City

                        </label>

                        <input
                            type="text"
                            name="city"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $restaurant['city']
                            ) ?>"
                            required
                        >

                    </div>


                    <div class="col-md-3">

                        <label class="form-label">

                            Region

                        </label>

                        <input
                            type="text"
                            name="region"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $restaurant['region'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">

                            Latitude

                        </label>

                        <input
                            type="number"
                            step="any"
                            name="latitude"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $restaurant['latitude'] ?? ''
                            ) ?>"
                            placeholder="-6.7924"
                        >

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">

                            Longitude

                        </label>

                        <input
                            type="number"
                            step="any"
                            name="longitude"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $restaurant['longitude'] ?? ''
                            ) ?>"
                            placeholder="39.2083"
                        >

                    </div>


                </div>

            </div>

        </div>



        <!-- =================================================
             BUSINESS HOURS
        ================================================== -->

        <div class="settings-card mb-4">

            <div class="settings-header">

                <div class="section-title mb-0">

                    <i class="bi bi-clock"></i>

                    Business Hours

                </div>

            </div>


            <div class="settings-body">

                <div class="row g-4">


                    <div class="col-md-6">

                        <label class="form-label">

                            Opening Time

                        </label>

                        <input
                            type="time"
                            name="opening_time"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $restaurant['opening_time'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">

                            Closing Time

                        </label>

                        <input
                            type="time"
                            name="closing_time"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $restaurant['closing_time'] ?? ''
                            ) ?>"
                        >

                    </div>


                </div>

            </div>

        </div>



        <!-- =================================================
             ORDER OPTIONS
        ================================================== -->

        <div class="settings-card mb-4">

            <div class="settings-header">

                <div class="section-title mb-0">

                    <i class="bi bi-bag-check"></i>

                    Ordering & Delivery

                </div>

            </div>


            <div class="settings-body">

                <div class="row g-4">


                    <!-- DELIVERY -->

                    <div class="col-md-6">

                        <div
                            class="form-check
                                   form-switch
                                   p-3
                                   border
                                   rounded-3"
                        >

                            <input
                                class="form-check-input
                                       ms-0
                                       me-3"
                                type="checkbox"
                                name="delivery_available"
                                id="deliveryAvailable"
                                <?= $restaurant['delivery_available']
                                    ? 'checked'
                                    : '' ?>
                            >

                            <label
                                class="form-check-label
                                       fw-semibold"
                                for="deliveryAvailable"
                            >

                                Delivery Available

                            </label>


                            <div
                                class="text-muted
                                       small
                                       mt-1"
                            >

                                Allow customers to request
                                food delivery.

                            </div>

                        </div>

                    </div>



                    <!-- PICKUP -->

                    <div class="col-md-6">

                        <div
                            class="form-check
                                   form-switch
                                   p-3
                                   border
                                   rounded-3"
                        >

                            <input
                                class="form-check-input
                                       ms-0
                                       me-3"
                                type="checkbox"
                                name="pickup_available"
                                id="pickupAvailable"
                                <?= $restaurant['pickup_available']
                                    ? 'checked'
                                    : '' ?>
                            >

                            <label
                                class="form-check-label
                                       fw-semibold"
                                for="pickupAvailable"
                            >

                                Pickup Available

                            </label>


                            <div
                                class="text-muted
                                       small
                                       mt-1"
                            >

                                Allow customers to pick up
                                their orders.

                            </div>

                        </div>

                    </div>



                    <!-- MINIMUM ORDER -->

                    <div class="col-md-6">

                        <label class="form-label">

                            Minimum Order Amount

                        </label>


                        <div class="input-group">

                            <span class="input-group-text">

                                TZS

                            </span>


                            <input
                                type="number"
                                name="minimum_order_amount"
                                class="form-control"
                                min="0"
                                step="100"
                                value="<?= htmlspecialchars(
                                    $restaurant[
                                        'minimum_order_amount'
                                    ]
                                ) ?>"
                            >

                        </div>

                    </div>



                    <!-- DELIVERY FEE -->

                    <div class="col-md-6">

                        <label class="form-label">

                            Delivery Fee

                        </label>


                        <div class="input-group">

                            <span class="input-group-text">

                                TZS

                            </span>


                            <input
                                type="number"
                                name="delivery_fee"
                                class="form-control"
                                min="0"
                                step="100"
                                value="<?= htmlspecialchars(
                                    $restaurant[
                                        'delivery_fee'
                                    ]
                                ) ?>"
                            >

                        </div>

                    </div>


                </div>

            </div>

        </div>



        <!-- =================================================
             SAVE BUTTON
        ================================================== -->

        <div
            class="settings-card"
        >

            <div
                class="settings-body
                       d-flex
                       justify-content-end
                       gap-2"
            >

                <a
                    href="dashboard.php"
                    class="btn btn-light save-button"
                >

                    Cancel

                </a>


                <button
                    type="submit"
                    class="btn btn-success save-button"
                >

                    <i class="bi bi-check-lg me-1"></i>

                    Save Changes

                </button>

            </div>

        </div>


    </form>


</div>

</div>



<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>