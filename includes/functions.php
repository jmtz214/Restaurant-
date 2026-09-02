<?php

function redirect($url)
{
    header("Location: $url");
    exit;
}


function isPost()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}


function clean($value)
{
    return htmlspecialchars(
        trim($value),
        ENT_QUOTES,
        'UTF-8'
    );
}


function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}


function getUserRole()
{
    return $_SESSION['user_role'] ?? null;
}


function requireRestaurantApproval($pdo)
{
    if (
        empty($_SESSION['user_id']) ||
        ($_SESSION['user_role'] ?? '') !== 'restaurant_admin'
    ) {
        redirect('../login.php');
    }


    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            status
        FROM restaurants
        WHERE owner_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $_SESSION['user_id']
    ]);

    $restaurant = $stmt->fetch();


    if (!$restaurant) {

        redirect(
            '../restaurant-registration-status.php'
        );
    }


    if ($restaurant['status'] !== 'approved') {

        redirect(
            '../restaurant-registration-status.php'
        );
    }


    $_SESSION['restaurant_id'] =
        $restaurant['id'];

    $_SESSION['restaurant_name'] =
        $restaurant['name'];


    return $restaurant;
}

/**
 * Get a system setting from the database.
 */
function getSetting($key, $default = null)
{
    global $pdo;

    try {

        $stmt = $pdo->prepare("
            SELECT setting_value
            FROM settings
            WHERE setting_key = ?
            LIMIT 1
        ");

        $stmt->execute([$key]);

        $value = $stmt->fetchColumn();

        if ($value === false) {
            return $default;
        }

        return $value;

    } catch (PDOException $e) {

        return $default;
    }
}