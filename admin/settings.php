<?php

session_start();

require_once "../config/database.php";
require_once "../includes/functions.php";


// =====================================================
// SUPER ADMIN ACCESS
// =====================================================

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'super_admin'
) {
    redirect('../login.php');
    exit;
}


// =====================================================
// DEFAULT SETTINGS
// =====================================================

$defaults = [

    'platform_name' => 'MloGo',

    'platform_email' => 'support@mlogo.com',

    'platform_phone' => '+255 700 000 000',

    'platform_city' => 'Dar es Salaam',

    'platform_region' => 'Dar es Salaam',

    'currency' => 'TZS',

    'default_delivery_fee' => '2000',

    'minimum_order_amount' => '5000',

    'customer_registration' => '1',

    'restaurant_registration' => '1',

    'maintenance_mode' => '0',

    'platform_description' =>
        'MloGo is a food ordering and delivery platform connecting customers with restaurants.'

];


// =====================================================
// LOAD SETTINGS
// =====================================================

$settings = $defaults;

$stmt = $pdo->query("
    SELECT setting_key, setting_value
    FROM settings
");

$dbSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($dbSettings as $row) {

    if (array_key_exists(
        $row['setting_key'],
        $defaults
    )) {

        $settings[$row['setting_key']] =
            $row['setting_value'];
    }
}


// =====================================================
// MESSAGES
// =====================================================

$success = "";
$error = "";


// =====================================================
// UPDATE SETTINGS
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';


    // =================================================
    // SAVE SETTINGS
    // =================================================

    if ($action === 'save_settings') {

        $platformName =
            trim($_POST['platform_name'] ?? '');

        $platformEmail =
            trim($_POST['platform_email'] ?? '');

        $platformPhone =
            trim($_POST['platform_phone'] ?? '');

        $platformCity =
            trim($_POST['platform_city'] ?? '');

        $platformRegion =
            trim($_POST['platform_region'] ?? '');

        $currency =
            trim($_POST['currency'] ?? '');

        $deliveryFee =
            trim($_POST['default_delivery_fee'] ?? '');

        $minimumOrder =
            trim($_POST['minimum_order_amount'] ?? '');

        $customerRegistration =
            isset($_POST['customer_registration'])
                ? '1'
                : '0';

        $restaurantRegistration =
            isset($_POST['restaurant_registration'])
                ? '1'
                : '0';

        $maintenanceMode =
            isset($_POST['maintenance_mode'])
                ? '1'
                : '0';

        $platformDescription =
            trim($_POST['platform_description'] ?? '');


        // =============================================
        // VALIDATION
        // =============================================

        if ($platformName === '') {

            $error =
                "Platform name is required.";

        } elseif ($platformEmail !== '' &&
                  !filter_var(
                      $platformEmail,
                      FILTER_VALIDATE_EMAIL
                  )) {

            $error =
                "Please enter a valid platform email.";

        } elseif ($deliveryFee === '' ||
                  !is_numeric($deliveryFee) ||
                  $deliveryFee < 0) {

            $error =
                "Please enter a valid delivery fee.";

        } elseif ($minimumOrder === '' ||
                  !is_numeric($minimumOrder) ||
                  $minimumOrder < 0) {

            $error =
                "Please enter a valid minimum order amount.";

        }


        // =============================================
        // SAVE
        // =============================================

        if ($error === '') {

            $newSettings = [

                'platform_name' =>
                    $platformName,

                'platform_email' =>
                    $platformEmail,

                'platform_phone' =>
                    $platformPhone,

                'platform_city' =>
                    $platformCity,

                'platform_region' =>
                    $platformRegion,

                'currency' =>
                    $currency,

                'default_delivery_fee' =>
                    $deliveryFee,

                'minimum_order_amount' =>
                    $minimumOrder,

                'customer_registration' =>
                    $customerRegistration,

                'restaurant_registration' =>
                    $restaurantRegistration,

                'maintenance_mode' =>
                    $maintenanceMode,

                'platform_description' =>
                    $platformDescription

            ];


            try {

                $pdo->beginTransaction();


                $stmt = $pdo->prepare("
                    INSERT INTO settings
                    (
                        setting_key,
                        setting_value,
                        setting_type,
                        updated_at
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        NOW()
                    )

                    ON DUPLICATE KEY UPDATE

                    setting_value = VALUES(setting_value),

                    updated_at = NOW()
                ");


                foreach (
                    $newSettings as $key => $value
                ) {

                    $type = 'text';


                    if (
                        in_array(
                            $key,
                            [
                                'default_delivery_fee',
                                'minimum_order_amount'
                            ]
                        )
                    ) {

                        $type = 'number';

                    } elseif (
                        in_array(
                            $key,
                            [
                                'customer_registration',
                                'restaurant_registration',
                                'maintenance_mode'
                            ]
                        )
                    ) {

                        $type = 'boolean';

                    } elseif (
                        $key === 'platform_description'
                    ) {

                        $type = 'textarea';

                    }


                    $stmt->execute([
                        $key,
                        $value,
                        $type
                    ]);

                }


                $pdo->commit();


                // Update local values
                $settings = array_merge(
                    $settings,
                    $newSettings
                );


                $success =
                    "Platform settings updated successfully.";

            } catch (Exception $e) {

                if ($pdo->inTransaction()) {

                    $pdo->rollBack();

                }

                $error =
                    "Unable to save settings. Please try again.";

            }

        }

    }


    // =================================================
    // RESET SETTINGS
    // =================================================

    if ($action === 'reset_settings') {

        try {

            $pdo->beginTransaction();


            $stmt = $pdo->prepare("
                INSERT INTO settings
                (
                    setting_key,
                    setting_value,
                    setting_type,
                    updated_at
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    NOW()
                )

                ON DUPLICATE KEY UPDATE

                setting_value = VALUES(setting_value),

                updated_at = NOW()
            ");


            foreach (
                $defaults as $key => $value
            ) {

                $type = 'text';


                if (
                    in_array(
                        $key,
                        [
                            'default_delivery_fee',
                            'minimum_order_amount'
                        ]
                    )
                ) {

                    $type = 'number';

                } elseif (
                    in_array(
                        $key,
                        [
                            'customer_registration',
                            'restaurant_registration',
                            'maintenance_mode'
                        ]
                    )
                ) {

                    $type = 'boolean';

                } elseif (
                    $key === 'platform_description'
                ) {

                    $type = 'textarea';

                }


                $stmt->execute([
                    $key,
                    $value,
                    $type
                ]);

            }


            $pdo->commit();


            $settings = $defaults;


            $success =
                "Platform settings have been restored to their defaults.";

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {

                $pdo->rollBack();

            }

            $error =
                "Unable to reset settings.";

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

    <title>
        System Settings - MloGo
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

            background: #f5f7fb;

            color: #212529;

        }


        .page-header {

            background: white;

            border-radius: 18px;

            padding: 25px;

            margin-bottom: 25px;

            box-shadow:
                0 4px 20px rgba(0,0,0,0.05);

        }


        .settings-card {

            background: white;

            border-radius: 18px;

            border: none;

            box-shadow:
                0 4px 20px rgba(0,0,0,0.05);

            margin-bottom: 25px;

            overflow: hidden;

        }


        .settings-header {

            padding: 20px 24px;

            border-bottom: 1px solid #eee;

        }


        .settings-header h5 {

            margin: 0;

            font-weight: 700;

        }


        .settings-body {

            padding: 25px;

        }


        .form-label {

            font-weight: 600;

        }


        .form-control,
        .form-select {

            border-radius: 10px;

            padding: 11px 13px;

        }


        .form-control:focus,
        .form-select:focus {

            border-color: #198754;

            box-shadow:
                0 0 0 0.2rem rgba(
                    25,
                    135,
                    84,
                    0.12
                );

        }


        .setting-icon {

            width: 45px;

            height: 45px;

            border-radius: 12px;

            background: #e8f7ef;

            color: #198754;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 21px;

        }


        .toggle-card {

            border: 1px solid #e9ecef;

            border-radius: 12px;

            padding: 18px;

            height: 100%;

        }


        .toggle-card:hover {

            border-color: #198754;

        }


        .form-switch .form-check-input {

            width: 3em;

            height: 1.5em;

            cursor: pointer;

        }


        .form-switch .form-check-input:checked {

            background-color: #198754;

            border-color: #198754;

        }


        .danger-zone {

            border: 1px solid #f5c2c7;

            background: #fff5f5;

            border-radius: 15px;

            padding: 20px;

        }


        .btn {

            border-radius: 10px;

            padding: 10px 18px;

        }


        textarea {

            min-height: 130px;

        }

    </style>

</head>


<body>


<div class="container-fluid p-4">


    <!-- =================================================
         PAGE HEADER
    ================================================== -->

    <div class="page-header">


        <div
            class="d-flex justify-content-between align-items-center flex-wrap gap-3"
        >


            <div
                class="d-flex align-items-center gap-3"
            >

                <div class="setting-icon">

                    <i class="bi bi-gear-fill"></i>

                </div>


                <div>

                    <h2 class="fw-bold mb-1">

                        System Settings

                    </h2>


                    <p class="text-muted mb-0">

                        Manage MloGo platform-wide configuration.

                    </p>

                </div>

            </div>


            <div>


                <a
                    href="dashboard.php"
                    class="btn btn-outline-secondary"
                >

                    <i class="bi bi-arrow-left me-1"></i>

                    Back to Dashboard

                </a>


            </div>


        </div>


    </div>



    <!-- =================================================
         ALERTS
    ================================================== -->

    <?php if ($success !== ''): ?>


        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert"
        >

            <i class="bi bi-check-circle-fill me-2"></i>

            <?= htmlspecialchars($success) ?>


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>


    <?php endif; ?>


    <?php if ($error !== ''): ?>


        <div
            class="alert alert-danger alert-dismissible fade show"
            role="alert"
        >

            <i class="bi bi-exclamation-triangle-fill me-2"></i>

            <?= htmlspecialchars($error) ?>


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

    <form method="POST">


        <input
            type="hidden"
            name="action"
            value="save_settings"
        >


        <!-- =================================================
             PLATFORM INFORMATION
        ================================================== -->

        <div class="settings-card">


            <div class="settings-header">

                <h5>

                    <i class="bi bi-building text-success me-2"></i>

                    Platform Information

                </h5>

            </div>


            <div class="settings-body">


                <div class="row g-4">


                    <div class="col-md-6">


                        <label class="form-label">

                            Platform Name

                        </label>


                        <input
                            type="text"
                            name="platform_name"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $settings['platform_name']
                            ) ?>"
                            required
                        >


                    </div>


                    <div class="col-md-6">


                        <label class="form-label">

                            Currency

                        </label>


                        <input
                            type="text"
                            name="currency"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $settings['currency']
                            ) ?>"
                            maxlength="10"
                            required
                        >


                    </div>


                    <div class="col-md-6">


                        <label class="form-label">

                            Platform Email

                        </label>


                        <input
                            type="email"
                            name="platform_email"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $settings['platform_email']
                            ) ?>"
                        >


                    </div>


                    <div class="col-md-6">


                        <label class="form-label">

                            Platform Phone

                        </label>


                        <input
                            type="text"
                            name="platform_phone"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $settings['platform_phone']
                            ) ?>"
                        >


                    </div>


                    <div class="col-md-6">


                        <label class="form-label">

                            City

                        </label>


                        <input
                            type="text"
                            name="platform_city"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $settings['platform_city']
                            ) ?>"
                        >


                    </div>


                    <div class="col-md-6">


                        <label class="form-label">

                            Region

                        </label>


                        <input
                            type="text"
                            name="platform_region"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $settings['platform_region']
                            ) ?>"
                        >


                    </div>


                    <div class="col-12">


                        <label class="form-label">

                            Platform Description

                        </label>


                        <textarea
                            name="platform_description"
                            class="form-control"
                        ><?= htmlspecialchars(
                            $settings['platform_description']
                        ) ?></textarea>


                    </div>


                </div>


            </div>


        </div>



        <!-- =================================================
             ORDER SETTINGS
        ================================================== -->

        <div class="settings-card">


            <div class="settings-header">

                <h5>

                    <i class="bi bi-cart-check text-success me-2"></i>

                    Order & Delivery Settings

                </h5>

            </div>


            <div class="settings-body">


                <div class="row g-4">


                    <div class="col-md-6">


                        <label class="form-label">

                            Default Delivery Fee

                        </label>


                        <div class="input-group">


                            <span class="input-group-text">

                                <?= htmlspecialchars(
                                    $settings['currency']
                                ) ?>

                            </span>


                            <input
                                type="number"
                                name="default_delivery_fee"
                                class="form-control"
                                min="0"
                                step="0.01"
                                value="<?= htmlspecialchars(
                                    $settings['default_delivery_fee']
                                ) ?>"
                                required
                            >


                        </div>


                        <small class="text-muted">

                            Default fee used when a restaurant has no specific
                            delivery fee.

                        </small>


                    </div>



                    <div class="col-md-6">


                        <label class="form-label">

                            Minimum Order Amount

                        </label>


                        <div class="input-group">


                            <span class="input-group-text">

                                <?= htmlspecialchars(
                                    $settings['currency']
                                ) ?>

                            </span>


                            <input
                                type="number"
                                name="minimum_order_amount"
                                class="form-control"
                                min="0"
                                step="0.01"
                                value="<?= htmlspecialchars(
                                    $settings['minimum_order_amount']
                                ) ?>"
                                required
                            >


                        </div>


                        <small class="text-muted">

                            Minimum amount a customer can order.

                        </small>


                    </div>


                </div>


            </div>


        </div>



        <!-- =================================================
             REGISTRATION SETTINGS
        ================================================== -->

        <div class="settings-card">


            <div class="settings-header">

                <h5>

                    <i class="bi bi-person-plus text-success me-2"></i>

                    Registration Settings

                </h5>

            </div>


            <div class="settings-body">


                <div class="row g-4">


                    <div class="col-md-6">


                        <div class="toggle-card">


                            <div
                                class="form-check form-switch d-flex justify-content-between align-items-center"
                            >


                                <div>

                                    <label
                                        class="form-check-label fw-bold"
                                        for="customerRegistration"
                                    >

                                        Customer Registration

                                    </label>


                                    <div class="text-muted small mt-1">

                                        Allow new customers to create accounts.

                                    </div>

                                </div>


                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="customer_registration"
                                    id="customerRegistration"
                                    value="1"
                                    <?= $settings[
                                        'customer_registration'
                                    ] == '1'
                                        ? 'checked'
                                        : '' ?>
                                >


                            </div>


                        </div>


                    </div>



                    <div class="col-md-6">


                        <div class="toggle-card">


                            <div
                                class="form-check form-switch d-flex justify-content-between align-items-center"
                            >


                                <div>

                                    <label
                                        class="form-check-label fw-bold"
                                        for="restaurantRegistration"
                                    >

                                        Restaurant Registration

                                    </label>


                                    <div class="text-muted small mt-1">

                                        Allow restaurant owners to register.

                                    </div>

                                </div>


                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="restaurant_registration"
                                    id="restaurantRegistration"
                                    value="1"
                                    <?= $settings[
                                        'restaurant_registration'
                                    ] == '1'
                                        ? 'checked'
                                        : '' ?>
                                >


                            </div>


                        </div>


                    </div>


                </div>


            </div>


        </div>



        <!-- =================================================
             SYSTEM SETTINGS
        ================================================== -->

        <div class="settings-card">


            <div class="settings-header">

                <h5>

                    <i class="bi bi-sliders text-success me-2"></i>

                    System Controls

                </h5>

            </div>


            <div class="settings-body">


                <div class="toggle-card">


                    <div
                        class="form-check form-switch d-flex justify-content-between align-items-center"
                    >


                        <div>

                            <label
                                class="form-check-label fw-bold"
                                for="maintenanceMode"
                            >

                                Maintenance Mode

                            </label>


                            <div class="text-muted small mt-1">

                                Temporarily disable public access to the
                                platform while maintenance is being performed.

                            </div>

                        </div>


                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="maintenance_mode"
                            id="maintenanceMode"
                            value="1"
                            <?= $settings[
                                'maintenance_mode'
                            ] == '1'
                                ? 'checked'
                                : '' ?>
                        >


                    </div>


                </div>


            </div>


        </div>



        <!-- =================================================
             ACTIONS
        ================================================== -->

        <div class="settings-card">


            <div class="settings-body">


                <div
                    class="d-flex justify-content-between align-items-center flex-wrap gap-3"
                >


                    <div>

                        <h5 class="fw-bold mb-1">

                            Save Platform Settings

                        </h5>


                        <p class="text-muted mb-0">

                            Changes will affect the MloGo platform.

                        </p>

                    </div>


                    <div class="d-flex gap-2">


                        <button
                            type="reset"
                            class="btn btn-outline-secondary"
                        >

                            <i class="bi bi-arrow-counterclockwise me-1"></i>

                            Reset Form

                        </button>


                        <button
                            type="submit"
                            class="btn btn-success"
                        >

                            <i class="bi bi-check-lg me-1"></i>

                            Save Changes

                        </button>


                    </div>


                </div>


            </div>


        </div>


    </form>



    <!-- =================================================
         DANGER ZONE
    ================================================== -->

    <div class="settings-card">


        <div class="settings-body">


            <div class="danger-zone">


                <div
                    class="d-flex justify-content-between align-items-center flex-wrap gap-3"
                >


                    <div>


                        <h5 class="text-danger fw-bold">

                            <i class="bi bi-exclamation-triangle me-2"></i>

                            Restore Default Settings

                        </h5>


                        <p class="text-muted mb-0">

                            Restore all platform settings to their original
                            default values.

                        </p>


                    </div>


                    <form
                        method="POST"
                        onsubmit="return confirm(
                            'Are you sure you want to restore all settings to their defaults?'
                        );"
                    >


                        <input
                            type="hidden"
                            name="action"
                            value="reset_settings"
                        >


                        <button
                            type="submit"
                            class="btn btn-outline-danger"
                        >

                            <i class="bi bi-arrow-counterclockwise me-1"></i>

                            Restore Defaults

                        </button>


                    </form>


                </div>


            </div>


        </div>


    </div>


</div>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>