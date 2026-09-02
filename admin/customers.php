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
// SEARCH
// =====================================================

$search = trim($_GET['search'] ?? '');

$status = $_GET['status'] ?? 'all';


// =====================================================
// BUILD QUERY
// =====================================================

$sql = "
    SELECT
        id,
        first_name,
        last_name,
        email,
        phone,
        profile_image,
        is_active,
        email_verified_at,
        last_login_at,
        created_at
    FROM users
    WHERE role = 'customer'
";

$params = [];


// =====================================================
// SEARCH FILTER
// =====================================================

if ($search !== '') {

    $sql .= "
        AND (
            first_name LIKE ?
            OR last_name LIKE ?
            OR email LIKE ?
            OR phone LIKE ?
        )
    ";

    $searchValue = "%{$search}%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}


// =====================================================
// STATUS FILTER
// =====================================================

if ($status === 'active') {

    $sql .= "
        AND is_active = 1
    ";

} elseif ($status === 'inactive') {

    $sql .= "
        AND is_active = 0
    ";

}


// =====================================================
// ORDER
// =====================================================

$sql .= "
    ORDER BY created_at DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$customers = $stmt->fetchAll();


// =====================================================
// STATISTICS
// =====================================================

$totalCustomersStmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'customer'
");

$totalCustomers = (int)$totalCustomersStmt->fetchColumn();


$activeCustomersStmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'customer'
    AND is_active = 1
");

$activeCustomers = (int)$activeCustomersStmt->fetchColumn();


$inactiveCustomersStmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'customer'
    AND is_active = 0
");

$inactiveCustomers = (int)$inactiveCustomersStmt->fetchColumn();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Customers - MloGo Admin</title>


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

        }


        .page-header {

            background: white;

            border-radius: 15px;

            padding: 25px;

            margin-bottom: 25px;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.05);

        }


        .stat-card {

            background: white;

            border: none;

            border-radius: 15px;

            padding: 22px;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.05);

            height: 100%;

        }


        .stat-icon {

            width: 50px;

            height: 50px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;

        }


        .icon-blue {

            background: #e7f1ff;

            color: #0d6efd;

        }


        .icon-green {

            background: #e8f7ee;

            color: #198754;

        }


        .icon-red {

            background: #fdecec;

            color: #dc3545;

        }


        .customer-table-card {

            background: white;

            border-radius: 15px;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.05);

            overflow: hidden;

        }


        .customer-table-card .card-header {

            background: white;

            border-bottom: 1px solid #eee;

            padding: 20px;

        }


        .table {

            margin-bottom: 0;

        }


        .table th {

            font-size: 13px;

            text-transform: uppercase;

            color: #6c757d;

            white-space: nowrap;

        }


        .table td {

            vertical-align: middle;

        }


        .customer-avatar {

            width: 42px;

            height: 42px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #e8f7ee;

            color: #198754;

            font-weight: 700;

            overflow: hidden;

        }


        .customer-avatar img {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }


        .status-badge {

            padding: 6px 11px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

        }


        .status-active {

            background: #d1e7dd;

            color: #0f5132;

        }


        .status-inactive {

            background: #f8d7da;

            color: #842029;

        }


        .search-box {

            max-width: 400px;

        }


        .search-box input {

            border-radius: 10px 0 0 10px;

        }


        .search-box button {

            border-radius: 0 10px 10px 0;

        }


        .empty-state {

            padding: 70px 20px;

            text-align: center;

        }


        .empty-state i {

            font-size: 60px;

            color: #adb5bd;

        }

    </style>

</head>


<body>


<div class="container-fluid p-4">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="page-header">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

            <div>

                <h3 class="fw-bold mb-1">

                    <i class="bi bi-people me-2 text-success"></i>

                    Customer Management

                </h3>

                <p class="text-muted mb-0">

                    Manage MloGo customer accounts.

                </p>

            </div>

        </div>

         <div class="d-flex gap-2">
                    <!-- Back to Admin Dashboard -->

        <a
            href="dashboard.php"
            class="btn btn-outline-secondary"
        >

            <i class="bi bi-arrow-left me-1"></i>

            Admin Dashboard

        </a>

    </div>

    </div>



    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="row g-4 mb-4">


        <div class="col-md-4">

            <div class="stat-card">

                <div class="d-flex align-items-center gap-3">

                    <div class="stat-icon icon-blue">

                        <i class="bi bi-people"></i>

                    </div>

                    <div>

                        <small class="text-muted">

                            Total Customers

                        </small>

                        <h3 class="fw-bold mb-0">

                            <?= number_format($totalCustomers) ?>

                        </h3>

                    </div>

                </div>

            </div>

        </div>



        <div class="col-md-4">

            <div class="stat-card">

                <div class="d-flex align-items-center gap-3">

                    <div class="stat-icon icon-green">

                        <i class="bi bi-person-check"></i>

                    </div>

                    <div>

                        <small class="text-muted">

                            Active Customers

                        </small>

                        <h3 class="fw-bold mb-0">

                            <?= number_format($activeCustomers) ?>

                        </h3>

                    </div>

                </div>

            </div>

        </div>



        <div class="col-md-4">

            <div class="stat-card">

                <div class="d-flex align-items-center gap-3">

                    <div class="stat-icon icon-red">

                        <i class="bi bi-person-x"></i>

                    </div>

                    <div>

                        <small class="text-muted">

                            Inactive Customers

                        </small>

                        <h3 class="fw-bold mb-0">

                            <?= number_format($inactiveCustomers) ?>

                        </h3>

                    </div>

                </div>

            </div>

        </div>


    </div>



    <!-- =================================================
         CUSTOMER TABLE
    ================================================== -->

    <div class="customer-table-card">


        <div class="card-header">


            <div class="row align-items-center g-3">


                <div class="col-lg-6">

                    <h5 class="mb-0 fw-bold">

                        All Customers

                    </h5>

                </div>


                <div class="col-lg-6">


                    <form method="GET">


                        <div class="row g-2">


                            <div class="col-md-7">

                                <input
                                    type="text"
                                    name="search"
                                    class="form-control"
                                    placeholder="Search customer..."
                                    value="<?= htmlspecialchars($search) ?>"
                                >

                            </div>


                            <div class="col-md-3">

                                <select
                                    name="status"
                                    class="form-select"
                                >

                                    <option
                                        value="all"
                                        <?= $status === 'all' ? 'selected' : '' ?>
                                    >
                                        All
                                    </option>

                                    <option
                                        value="active"
                                        <?= $status === 'active' ? 'selected' : '' ?>
                                    >
                                        Active
                                    </option>

                                    <option
                                        value="inactive"
                                        <?= $status === 'inactive' ? 'selected' : '' ?>
                                    >
                                        Inactive
                                    </option>

                                </select>

                            </div>


                            <div class="col-md-2">

                                <button
                                    type="submit"
                                    class="btn btn-success w-100"
                                >

                                    <i class="bi bi-search"></i>

                                </button>

                            </div>


                        </div>

                    </form>

                </div>


            </div>


        </div>



        <!-- TABLE -->

        <div class="table-responsive">


            <?php if (count($customers) > 0): ?>


                <table class="table table-hover align-middle">


                    <thead class="table-light">

                        <tr>

                            <th>Customer</th>

                            <th>Phone</th>

                            <th>Status</th>

                            <th>Email Verification</th>

                            <th>Last Login</th>

                            <th>Registered</th>

                            <th class="text-end">Actions</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach ($customers as $customer): ?>


                        <tr>


                            <!-- CUSTOMER -->

                            <td>


                                <div class="d-flex align-items-center gap-3">


                                    <div class="customer-avatar">


                                        <?php if (!empty($customer['profile_image'])): ?>

                                            <img
                                                src="../uploads/profiles/<?= htmlspecialchars($customer['profile_image']) ?>"
                                                alt="Profile"
                                            >

                                        <?php else: ?>

                                            <?= strtoupper(
                                                substr(
                                                    $customer['first_name'],
                                                    0,
                                                    1
                                                )
                                            ) ?>

                                        <?php endif; ?>


                                    </div>


                                    <div>

                                        <div class="fw-semibold">

                                            <?= htmlspecialchars(
                                                $customer['first_name']
                                            ) ?>

                                            <?= htmlspecialchars(
                                                $customer['last_name']
                                            ) ?>

                                        </div>


                                        <small class="text-muted">

                                            <?= htmlspecialchars(
                                                $customer['email']
                                            ) ?>

                                        </small>

                                    </div>


                                </div>


                            </td>



                            <!-- PHONE -->

                            <td>

                                <?= !empty($customer['phone'])
                                    ? htmlspecialchars($customer['phone'])
                                    : '<span class="text-muted">Not provided</span>'
                                ?>

                            </td>



                            <!-- STATUS -->

                            <td>

                                <?php if ((int)$customer['is_active'] === 1): ?>

                                    <span class="status-badge status-active">

                                        <i class="bi bi-check-circle me-1"></i>

                                        Active

                                    </span>

                                <?php else: ?>

                                    <span class="status-badge status-inactive">

                                        <i class="bi bi-x-circle me-1"></i>

                                        Inactive

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- EMAIL -->

                            <td>

                                <?php if (!empty($customer['email_verified_at'])): ?>

                                    <span class="text-success">

                                        <i class="bi bi-patch-check-fill"></i>

                                        Verified

                                    </span>

                                <?php else: ?>

                                    <span class="text-muted">

                                        Not verified

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- LAST LOGIN -->

                            <td>

                                <?php if (!empty($customer['last_login_at'])): ?>

                                    <?= date(
                                        'd M Y, H:i',
                                        strtotime(
                                            $customer['last_login_at']
                                        )
                                    ) ?>

                                <?php else: ?>

                                    <span class="text-muted">

                                        Never

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- REGISTERED -->

                            <td>

                                <?= date(
                                    'd M Y',
                                    strtotime(
                                        $customer['created_at']
                                    )
                                ) ?>

                            </td>



                            <!-- ACTIONS -->

                            <td class="text-end">


                                <div class="btn-group">


                                    <a
                                        href="customer-view.php?id=<?= (int)$customer['id'] ?>"
                                        class="btn btn-sm btn-outline-primary"
                                        title="View Customer"
                                    >

                                        <i class="bi bi-eye"></i>

                                    </a>


                                    <?php if ((int)$customer['is_active'] === 1): ?>

                                        <a
                                            href="customer-status.php?id=<?= (int)$customer['id'] ?>&action=deactivate"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Deactivate"
                                            onclick="return confirm('Are you sure you want to deactivate this customer?');"
                                        >

                                            <i class="bi bi-person-x"></i>

                                        </a>

                                    <?php else: ?>

                                        <a
                                            href="customer-status.php?id=<?= (int)$customer['id'] ?>&action=activate"
                                            class="btn btn-sm btn-outline-success"
                                            title="Activate"
                                            onclick="return confirm('Are you sure you want to activate this customer?');"
                                        >

                                            <i class="bi bi-person-check"></i>

                                        </a>

                                    <?php endif; ?>


                                </div>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                </table>


            <?php else: ?>


                <div class="empty-state">


                    <i class="bi bi-people"></i>


                    <h4 class="mt-3">

                        No Customers Found

                    </h4>


                    <p class="text-muted">

                        No customer accounts match your search.

                    </p>


                </div>


            <?php endif; ?>


        </div>


    </div>


</div>


</body>

</html>