<?php

session_start();

require_once "../config/database.php";
require_once "../includes/functions.php";


// =====================================================
// AUTHENTICATION
// =====================================================

if (
    empty($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'super_admin'
) {
    header("Location: ../login.php");
    exit;
}


// =====================================================
// GET DATA
// =====================================================

$restaurantId = filter_input(
    INPUT_POST,
    'restaurant_id',
    FILTER_VALIDATE_INT
);

$action = $_POST['action'] ?? '';


// =====================================================
// VALIDATE
// =====================================================

if (!$restaurantId || $action === '') {

    header(
        "Location: restaurants.php"
    );

    exit;
}


// =====================================================
// ALLOWED ACTIONS
// =====================================================

$allowedActions = [
    'approve',
    'reject',
    'suspend',
    'activate'
];


if (!in_array(
    $action,
    $allowedActions,
    true
)) {

    header(
        "Location: restaurant-view.php?id="
        . $restaurantId
    );

    exit;
}


// =====================================================
// GET RESTAURANT
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        status

    FROM restaurants

    WHERE id = ?

    LIMIT 1
");

$stmt->execute([
    $restaurantId
]);

$restaurant = $stmt->fetch();


if (!$restaurant) {

    header(
        "Location: restaurants.php"
    );

    exit;
}


// =====================================================
// DETERMINE NEW STATUS
// =====================================================

$newStatus = null;


switch ($action) {


    case 'approve':

        // Only pending restaurants
        // should normally be approved.

        if (
            $restaurant['status'] !== 'pending'
        ) {

            header(
                "Location: restaurant-view.php?id="
                . $restaurantId
            );

            exit;
        }

        $newStatus = 'approved';

        break;



    case 'reject':

        // Pending → Rejected

        if (
            $restaurant['status'] !== 'pending'
        ) {

            header(
                "Location: restaurant-view.php?id="
                . $restaurantId
            );

            exit;
        }

        $newStatus = 'rejected';

        break;



    case 'suspend':

        // Approved → Suspended

        if (
            $restaurant['status'] !== 'approved'
        ) {

            header(
                "Location: restaurant-view.php?id="
                . $restaurantId
            );

            exit;
        }

        $newStatus = 'suspended';

        break;



    case 'activate':

        // Suspended → Approved

        if (
            $restaurant['status'] !== 'suspended'
        ) {

            header(
                "Location: restaurant-view.php?id="
                . $restaurantId
            );

            exit;
        }

        $newStatus = 'approved';

        break;

}


// =====================================================
// UPDATE
// =====================================================

if ($newStatus !== null) {

    $stmt = $pdo->prepare("
        UPDATE restaurants

        SET status = ?

        WHERE id = ?

        LIMIT 1
    ");

    $stmt->execute([
        $newStatus,
        $restaurantId
    ]);

}


// =====================================================
// REDIRECT
// =====================================================

header(
    "Location: restaurant-view.php?id="
    . $restaurantId
);

exit;