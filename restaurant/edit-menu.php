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
// GET MENU ID
// =====================================================

$menuId = (int) ($_GET['id'] ?? 0);

if ($menuId <= 0) {
    header("Location: menu.php");
    exit;
}


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
// GET MENU ITEM
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        m.*,
        c.name AS category_name
    FROM menu_items m
    LEFT JOIN categories c
        ON m.category_id = c.id
    WHERE m.id = ?
    AND m.restaurant_id = ?
    LIMIT 1
");

$stmt->execute([
    $menuId,
    $restaurantId
]);

$menuItem = $stmt->fetch();


// Make sure item belongs to this restaurant

if (!$menuItem) {
    die("Food item not found or you do not have permission to edit it.");
}


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

$name = $menuItem['name'];

$description = $menuItem['description'] ?? '';

$price = $menuItem['price'];

$preparationTime =
    $menuItem['preparation_time'] ?? 15;

$categoryId =
    $menuItem['category_id'];

$isAvailable =
    (int) $menuItem['is_available'];

$isFeatured =
    (int) $menuItem['is_featured'];

$currentImage =
    $menuItem['image'];


// =====================================================
// FORM SUBMISSION
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim(
        $_POST['name'] ?? ''
    );

    $description = trim(
        $_POST['description'] ?? ''
    );

    $price = trim(
        $_POST['price'] ?? ''
    );

    $preparationTime = (int) (
        $_POST['preparation_time'] ?? 15
    );

    $categoryId = !empty(
        $_POST['category_id']
    )
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

        $error =
            "Preparation time must be at least 1 minute.";

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

            $error =
                "Selected category is not valid.";

        }

    }


    // =================================================
    // IMAGE
    // =================================================

    $newImagePath = $currentImage;


    if (
        $error === "" &&
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        $file = $_FILES['image'];


        if (
            $file['error'] !== UPLOAD_ERR_OK
        ) {

            $error =
                "There was a problem uploading the image.";

        } else {

            $allowedTypes = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            $maxSize =
                5 * 1024 * 1024;


            if (
                $file['size'] > $maxSize
            ) {

                $error =
                    "Image size must not exceed 5MB.";

            }


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

                    $newImagePath =
                        "uploads/menu/" .
                        $fileName;


                    // Remove old image

                    if (
                        !empty($currentImage)
                    ) {

                        $oldImage =
                            "../" .
                            $currentImage;


                        if (
                            file_exists(
                                $oldImage
                            )
                        ) {

                            unlink(
                                $oldImage
                            );

                        }

                    }

                } else {

                    $error =
                        "Failed to save the uploaded image.";

                }

            }

        }

    }


    // =================================================
    // UPDATE MENU ITEM
    // =================================================

    if ($error === "") {

        // Generate slug

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

            $slug =
                'food-' . time();

        }


        // Check if another food uses the slug

        $baseSlug = $slug;

        $counter = 1;


        while (true) {

            $stmt = $pdo->prepare("
                SELECT id
                FROM menu_items
                WHERE restaurant_id = ?
                AND slug = ?
                AND id != ?
                LIMIT 1
            ");

            $stmt->execute([
                $restaurantId,
                $slug,
                $menuId
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


        // Update

        $stmt = $pdo->prepare("
            UPDATE menu_items

            SET
                category_id = ?,
                name = ?,
                slug = ?,
                description = ?,
                price = ?,
                image = ?,
                preparation_time = ?,
                is_available = ?,
                is_featured = ?,
                updated_at = CURRENT_TIMESTAMP

            WHERE id = ?
            AND restaurant_id = ?
        ");


        $stmt->execute([

            $categoryId,

            $name,

            $slug,

            $description !== ''
                ? $description
                : null,

            (float)$price,

            $newImagePath,

            $preparationTime,

            $isAvailable,

            $isFeatured,

            $menuId,

            $restaurantId

        ]);


        header(
            "Location: menu.php?success=updated"
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
        Edit Food -
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
            font-family: Arial, Helvetica, sans-serif;
        }

        .main {
            max-width: 950px;
            margin: auto;
            padding: 35px 15px 60px;
        }

        .topbar,
        .form-card {
            background: white;
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(0,0,0,.04);
        }

        .topbar {
            padding: 20px 25px;
            margin-bottom: 25px;
        }

        .form-card {
            padding: 30px;
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
            box-shadow: 0 0 0 .2rem rgba(32,201,151,.15);
        }

        .image-preview {
            width: 100%;
            height: 250px;
            border-radius: 14px;
            overflow: hidden;
            background: #f1f3f5;
            display: flex;
            align-items: center;
            justify-content: center;
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


    <!-- HEADER -->

    <div class="topbar">

        <a
            href="menu.php"
            class="text-decoration-none text-muted"
        >

            <i class="bi bi-arrow-left"></i>

            Back to Menu

        </a>


        <div class="d-flex justify-content-between align-items-center">

            <div>

                <h2 class="fw-bold mt-2 mb-1">

                    Edit Food

                </h2>

                <p class="text-muted mb-0">

                    Update
                    <?= htmlspecialchars(
                        $menuItem['name']
                    ) ?>

                </p>

            </div>


            <i
                class="bi bi-pencil-square fs-1 text-primary"
            ></i>

        </div>

    </div>


    <!-- FORM -->

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


                <!-- FOOD INFORMATION -->

                <div class="col-lg-7">


                    <h5 class="fw-bold mb-4">

                        Food Information

                    </h5>


                    <div class="mb-3">

                        <label class="form-label">

                            Food Name

                            <span class="required">*</span>

                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            value="<?= htmlspecialchars($name) ?>"
                            required
                        >

                    </div>


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


                    <div class="mb-3">

                        <label class="form-label">

                            Description

                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="5"
                        ><?= htmlspecialchars($description) ?></textarea>

                    </div>


                    <div class="row g-3">


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
                                    value="<?= htmlspecialchars($price) ?>"
                                    required
                                >

                            </div>

                        </div>


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
                                    value="<?= htmlspecialchars($preparationTime) ?>"
                                >

                                <span class="input-group-text">

                                    minutes

                                </span>

                            </div>

                        </div>


                    </div>


                    <h5 class="fw-bold mt-4 mb-3">

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

                                    Customers can order this food
                                    while it is available.

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

                                    Featured food may appear
                                    in promotional sections.

                                </div>

                            </label>

                        </div>

                    </div>


                </div>


                <!-- IMAGE -->

                <div class="col-lg-5">


                    <h5 class="fw-bold mb-4">

                        Food Image

                    </h5>


                    <div class="image-preview">


                        <?php if (
                            !empty($currentImage)
                        ): ?>

                            <img
                                id="previewImage"
                                src="../<?= htmlspecialchars(
                                    $currentImage
                                ) ?>"
                                alt="<?= htmlspecialchars(
                                    $name
                                ) ?>"
                            >

                        <?php else: ?>

                            <img
                                id="previewImage"
                                style="display:none;"
                                alt="Preview"
                            >

                            <i
                                id="previewIcon"
                                class="bi bi-image fs-1 text-muted"
                            ></i>

                        <?php endif; ?>


                    </div>


                    <div class="mt-3">

                        <label class="form-label">

                            Replace Image

                        </label>


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


                    <div class="alert alert-warning mt-4">

                        <i class="bi bi-exclamation-circle"></i>

                        If you upload a new image,
                        the old image will be removed.

                    </div>


                </div>


            </div>


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
                    class="btn btn-primary px-4"
                >

                    <i class="bi bi-check-lg"></i>

                    Save Changes

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

                if (previewIcon) {

                    previewIcon.style.display =
                        'none';

                }

            };

        reader.readAsDataURL(file);

    }
);

</script>


</body>

</html>