<?php

session_start();

require_once "../config/database.php";
require_once "../includes/functions.php";


// =====================================================
// CHECK SUPER ADMIN
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
// GET DATA
// =====================================================

$customerId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

$action = $_GET['action'] ?? '';


// =====================================================
// VALIDATE
// =====================================================

if (!$customerId || !in_array($action, ['activate', 'deactivate'], true)) {

    redirect('customers.php');

    exit;
}


// =====================================================
// DETERMINE STATUS
// =====================================================

$newStatus = ($action === 'activate') ? 1 : 0;


// =====================================================
// UPDATE CUSTOMER
// =====================================================

$stmt = $pdo->prepare("
    UPDATE users
    SET
        is_active = ?,
        updated_at = NOW()
    WHERE id = ?
      AND role = 'customer'
");

$stmt->execute([
    $newStatus,
    $customerId
]);


// =====================================================
// RETURN
// =====================================================

redirect('customers.php');

exit;