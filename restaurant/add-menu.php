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

$userId = (int) $_SESSION['user_id'];


// =====================================================
// FIND RESTAURANT
// =====================================================

$stmt = $pdo->prepare("
    SELECT *
    FROM restaurants
    WHERE owner_id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$restaurant = $stmt->fetch();

if (!$restaurant) {
    die("No restaurant has been assigned to your account.");
}

$restaurantId = (int) $restaurant['id'];


// =====================================================
// GET CATEGORIES
// =====================================================

$stmt = $pdo->query("
    SELECT
        id,
        name
    FROM categories
    WHERE is_active = 1
    ORDER BY name ASC
");

$categories = $stmt->fetchAll();


// =====================================================
// VARIABLES
// =====================================================

$error = "";

$name = "";
$description = "";
$price = "";
$preparationTime = 15;
$categoryId = "";
$isAvailable = 1;
$isFeatured = 0;


// =====================================================
// FORM SUBMISSION
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');

    $description = trim(
        $_POST['description'] ?? ''
    );

    $price = trim(
        $_POST['price'] ?? ''
    );

    $preparationTime = (int) (
        $_POST['preparation_time'] ?? 15
    );

    $categoryId = !empty($_POST['category_id'])
        ? (int) $_POST['category_id']
        : null;

    $isAvailable = isset(
        $_POST['is_available']
    ) ? 1 : 0;

    $isFeatured = isset(
        $_POST['is_featured']
    ) ? 1 : 0;


    // =================================================
    // VALIDATION
    // =================================================

    if ($name === '') {

        $error = "Food name is required.";

    } elseif (
        $price === '' ||
        !is_numeric($price) ||
        (float)$price <= 0
    ) {

        $error = "Please enter a valid food price.";

    } elseif (
        $preparationTime < 1
    ) {

        $error = "Preparation time must be at least 1 minute.";

    }


    // =================================================
    // VALIDATE CATEGORY
    // =================================================

    if (
        $error === "" &&
        $categoryId !== null
    ) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM categories
            WHERE id = ?
            AND is_active = 1
            LIMIT 1
        ");

        $stmt->execute([
            $categoryId
        ]);

        if (!$stmt->fetch()) {

            $error = "Selected category is not valid.";

        }

    }


    // =================================================
    // IMAGE UPLOAD
    // =================================================

    $imagePath = null;


    if (
        $error === "" &&
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        $file = $_FILES['image'];

        if (
            $file['error'] !== UPLOAD_ERR_OK
        ) {

            $error = "There was a problem uploading the image.";

        } else {

            $allowedTypes = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            $maxSize = 5 * 1024 * 1024;


            // Check file size

            if (
                $file['size'] > $maxSize
            ) {

                $error =
                    "Image size must not exceed 5MB.";

            }


            // Check MIME type

            $mimeType = mime_content_type(
                $file['tmp_name']
            );


            if (
                $error === "" &&
                !in_array(
                    $mimeType,
                    $allowedTypes,
                    true
                )
            ) {

                $error =
                    "Only JPG, PNG and WEBP images are allowed.";

            }


            // Upload image

            if ($error === "") {

                $uploadDirectory =
                    "../uploads/menu/";

                if (
                    !is_dir(
                        $uploadDirectory
                    )
                ) {

                    mkdir(
                        $uploadDirectory,
                        0777,
                        true
                    );

                }


                $extension = match ($mimeType) {

                    'image/jpeg' => 'jpg',

                    'image/png' => 'png',

                    'image/webp' => 'webp',

                    default => 'jpg'

                };


                $fileName =
                    "food_" .
                    $restaurantId .
                    "_" .
                    time() .
                    "_" .
                    bin2hex(
                        random_bytes(4)
                    ) .
                    "." .
                    $extension;


                $destination =
                    $uploadDirectory .
                    $fileName;


                if (
                    move_uploaded_file(
                        $file['tmp_name'],
                        $destination
                    )
                ) {

                    $imagePath =
                        "uploads/menu/" .
                        $fileName;

                } else {

                    $error =
                        "Failed to save the uploaded image.";

                }

            }

        }

    }


    // =================================================
    // CREATE SLUG
    // =================================================

    if ($error === "") {

        $slug = strtolower(
            trim(
                preg_replace(
                    '/[^A-Za-z0-9-]+/',
                    '-',
                    $name
                ),
                '-'
            )
        );


        if ($slug === '') {

            $slug = 'food-' . time();

        }


        // Make slug unique

        $baseSlug = $slug;

        $counter = 1;


        while (true) {

            $stmt = $pdo->prepare("
                SELECT id
                FROM menu_items
                WHERE restaurant_id = ?
                AND slug = ?
                LIMIT 1
            ");

            $stmt->execute([
                $restaurantId,
                $slug
            ]);


            if (!$stmt->fetch()) {

                break;

            }


            $counter++;

            $slug =
                $baseSlug .
                '-' .
                $counter;

        }


        // =================================================
        // INSERT FOOD
        // =================================================

        $stmt = $pdo->prepare("
            INSERT INTO menu_items (
                restaurant_id,
                category_id,
                name,
                slug,
                description,
                price,
                image,
                preparation_time,
                is_available,
                is_featured
            )

            VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");


        $stmt->execute([

            $restaurantId,

            $categoryId,

            $name,

            $slug,

            $description !== ''
                ? $description
                : null,

            (float)$price,

            $imagePath,

            $preparationTime,

            $isAvailable,

            $isFeatured

        ]);


        // =================================================
        // REDIRECT
        // =================================================

        header(
            "Location: menu.php?success=added"
        );

        exit;

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

        Add Food -
        <?= htmlspecialchars(
            $restaurant['name']
        ) ?>

    </title>


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

            background: #f6f8fa;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

        }


        .main {

            max-width: 950px;

            margin: auto;

            padding: 35px 15px 60px;

        }


        .topbar {

            background: white;

            border-radius: 16px;

            padding: 20px 25px;

            margin-bottom: 25px;

            box-shadow:
                0 4px 18px
                rgba(0,0,0,.04);

        }


        .form-card {

            background: white;

            border-radius: 18px;

            padding: 30px;

            box-shadow:
                0 4px 18px
                rgba(0,0,0,.04);

        }


        .form-label {

            font-weight: 600;

        }


        .form-control,
        .form-select {

            padding: 11px 13px;

            border-radius: 10px;

        }


        .form-control:focus,
        .form-select:focus {

            border-color: #20c997;

            box-shadow:
                0 0 0 .2rem
                rgba(32,201,151,.15);

        }


        .image-preview {

            width: 100%;

            height: 230px;

            border-radius: 14px;

            background: #f1f3f5;

            display: flex;

            align-items: center;

            justify-content: center;

            overflow: hidden;

            color: #adb5bd;

        }


        .image-preview img {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }


        .switch-box {

            background: #f8f9fa;

            border-radius: 12px;

            padding: 15px 18px;

        }


        .required {

            color: #dc3545;

        }

    </style>

</head>


<body>


<div class="main">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="topbar">


        <div class="d-flex justify-content-between align-items-center">


            <div>


                <a
                    href="menu.php"
                    class="text-decoration-none text-muted"
                >

                    <i class="bi bi-arrow-left"></i>

                    Back to Menu

                </a>


                <h2 class="fw-bold mt-2 mb-1">

                    Add New Food

                </h2>


                <p class="text-muted mb-0">

                    Add a new item to
                    <?= htmlspecialchars(
                        $restaurant['name']
                    ) ?>

                </p>


            </div>


            <i
                class="bi bi-basket3 fs-1 text-success"
            ></i>


        </div>


    </div>



    <!-- =================================================
         FORM
    ================================================== -->

    <div class="form-card">


        <?php if ($error !== ""): ?>


            <div class="alert alert-danger">

                <i class="bi bi-exclamation-triangle"></i>

                <?= htmlspecialchars($error) ?>

            </div>


        <?php endif; ?>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <div class="row g-4">


                <!-- =====================================
                     FOOD INFORMATION
                ====================================== -->

                <div class="col-lg-7">


                    <h5 class="fw-bold mb-4">

                        Food Information

                    </h5>


                    <!-- NAME -->

                    <div class="mb-3">


                        <label class="form-label">

                            Food Name

                            <span class="required">*</span>

                        </label>


                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            placeholder="e.g. Chicken Pilau"
                            value="<?= htmlspecialchars($name) ?>"
                            required
                        >

                    </div>



                    <!-- CATEGORY -->

                    <div class="mb-3">


                        <label class="form-label">

                            Category

                        </label>


                        <select
                            name="category_id"
                            class="form-select"
                        >

                            <option value="">

                                Select category

                            </option>


                            <?php foreach (
                                $categories
                                as $category
                            ): ?>


                                <option
                                    value="<?= (int)$category['id'] ?>"
                                    <?= (
                                        (string)$categoryId ===
                                        (string)$category['id']
                                    )
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= htmlspecialchars(
                                        $category['name']
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                    </div>



                    <!-- DESCRIPTION -->

                    <div class="mb-3">


                        <label class="form-label">

                            Description

                        </label>


                        <textarea
                            name="description"
                            class="form-control"
                            rows="5"
                            placeholder="Describe the food, ingredients, portion size, etc."
                        ><?= htmlspecialchars($description) ?></textarea>


                    </div>



                    <div class="row g-3">


                        <!-- PRICE -->

                        <div class="col-md-6">


                            <label class="form-label">

                                Price (TZS)

                                <span class="required">*</span>

                            </label>


                            <div class="input-group">


                                <span class="input-group-text">

                                    TZS

                                </span>


                                <input
                                    type="number"
                                    name="price"
                                    class="form-control"
                                    min="1"
                                    step="0.01"
                                    placeholder="8000"
                                    value="<?= htmlspecialchars($price) ?>"
                                    required
                                >


                            </div>


                        </div>



                        <!-- PREPARATION -->

                        <div class="col-md-6">


                            <label class="form-label">

                                Preparation Time

                            </label>


                            <div class="input-group">


                                <input
                                    type="number"
                                    name="preparation_time"
                                    class="form-control"
                                    min="1"
                                    value="<?= htmlspecialchars(
                                        $preparationTime
                                    ) ?>"
                                >


                                <span class="input-group-text">

                                    minutes

                                </span>


                            </div>


                        </div>


                    </div>


                    <div class="mt-4">


                        <h5 class="fw-bold mb-3">

                            Food Settings

                        </h5>


                        <div class="switch-box mb-3">


                            <div class="form-check form-switch">


                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="is_available"
                                    id="is_available"
                                    <?= $isAvailable
                                        ? 'checked'
                                        : '' ?>
                                >


                                <label
                                    class="form-check-label"
                                    for="is_available"
                                >

                                    <strong>
                                        Available for ordering
                                    </strong>


                                    <div class="text-muted small">

                                        Customers can order this
                                        food when enabled.

                                    </div>

                                </label>


                            </div>


                        </div>



                        <div class="switch-box">


                            <div class="form-check form-switch">


                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="is_featured"
                                    id="is_featured"
                                    <?= $isFeatured
                                        ? 'checked'
                                        : '' ?>
                                >


                                <label
                                    class="form-check-label"
                                    for="is_featured"
                                >

                                    <strong>
                                        Featured food
                                    </strong>


                                    <div class="text-muted small">

                                        Featured foods can appear
                                        in promotional sections.

                                    </div>

                                </label>


                            </div>


                        </div>


                    </div>


                </div>



                <!-- =====================================
                     IMAGE
                ====================================== -->

                <div class="col-lg-5">


                    <h5 class="fw-bold mb-4">

                        Food Image

                    </h5>


                    <div
                        class="image-preview"
                        id="imagePreview"
                    >

                        <i
                            class="bi bi-image fs-1"
                            id="previewIcon"
                        ></i>


                        <img
                            id="previewImage"
                            style="display:none;"
                            alt="Preview"
                        >

                    </div>


                    <div class="mt-3">


                        <input
                            type="file"
                            name="image"
                            id="image"
                            class="form-control"
                            accept="image/jpeg,image/png,image/webp"
                        >


                        <div class="form-text">

                            JPG, PNG or WEBP.

                            Maximum size: 5MB.

                        </div>


                    </div>


                    <div class="alert alert-light mt-4">

                        <i class="bi bi-lightbulb text-warning"></i>

                        <strong>Tip:</strong>

                        Use a clear, attractive image of the
                        actual food. Good food photography helps
                        customers decide what to order.

                    </div>


                </div>


            </div>



            <!-- =========================================
                 BUTTONS
            ========================================== -->

            <hr class="my-4">


            <div class="d-flex justify-content-end gap-2">


                <a
                    href="menu.php"
                    class="btn btn-outline-secondary"
                >

                    Cancel

                </a>


                <button
                    type="submit"
                    class="btn btn-success px-4"
                >

                    <i class="bi bi-plus-lg"></i>

                    Add Food

                </button>


            </div>


        </form>


    </div>


</div>



<script>

const imageInput =
    document.getElementById('image');

const previewImage =
    document.getElementById('previewImage');

const previewIcon =
    document.getElementById('previewIcon');


imageInput.addEventListener(
    'change',
    function () {

        const file = this.files[0];

        if (!file) {

            previewImage.style.display =
                'none';

            previewIcon.style.display =
                'block';

            return;

        }


        const reader =
            new FileReader();


        reader.onload =
            function (event) {

                previewImage.src =
                    event.target.result;

                previewImage.style.display =
                    'block';

                previewIcon.style.display =
                    'none';

            };


        reader.readAsDataURL(file);

    }
);

</script>


</body>

</html>