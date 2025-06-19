<?php
include '../includes/config.php';
include '../includes/auth.php';
redirectIfNotAdmin();

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        // Update order status for all items in the order
        $client_id = intval($_POST['client_id']);
        $new_status = trim($_POST['new_status']);
        $order_date = $_POST['order_date'];

        $valid_statuses = ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Delivered', 'Cancelled'];

        if (in_array($new_status, $valid_statuses)) {
            try {
                // Update all orders for this client around the same time (within 1 hour)
                $order_time = strtotime($order_date);
                $start_time = date('Y-m-d H:i:s', $order_time - 1800); // 30 minutes before
                $end_time = date('Y-m-d H:i:s', $order_time + 1800);   // 30 minutes after

                $stmt = $pdo->prepare("
                    UPDATE `ORDER`
                    SET ORDER_SITUATION = ?
                    WHERE ID_CLIENTS = ?
                    AND DATE_ORDER BETWEEN ? AND ?
                ");
                $stmt->execute([$new_status, $client_id, $start_time, $end_time]);
                $success = "Order status updated successfully for all items!";
            } catch (PDOException $e) {
                $error = "Error updating order status: " . $e->getMessage();
            }
        } else {
            $error = "Invalid order status!";
        }
    }

    elseif (isset($_POST['update_payment'])) {
        // Update payment status for all items in the order
        $client_id = intval($_POST['client_id']);
        $payment_status = intval($_POST['payment_status']);
        $order_date = $_POST['order_date'];

        if (in_array($payment_status, [0, 1, 2])) { // 0=Pending, 1=Paid, 2=Refunded
            try {
                // Update all orders for this client around the same time (within 1 hour)
                $order_time = strtotime($order_date);
                $start_time = date('Y-m-d H:i:s', $order_time - 1800); // 30 minutes before
                $end_time = date('Y-m-d H:i:s', $order_time + 1800);   // 30 minutes after

                $stmt = $pdo->prepare("
                    UPDATE `ORDER`
                    SET PAYMENT_SITUATION = ?
                    WHERE ID_CLIENTS = ?
                    AND DATE_ORDER BETWEEN ? AND ?
                ");
                $stmt->execute([$payment_status, $client_id, $start_time, $end_time]);
                $success = "Payment status updated successfully for all items!";
            } catch (PDOException $e) {
                $error = "Error updating payment status: " . $e->getMessage();
            }
        } else {
            $error = "Invalid payment status!";
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$payment_filter = $_GET['payment'] ?? '';
$date_filter = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "o.ORDER_SITUATION = ?";
    $params[] = $status_filter;
}

if (!empty($payment_filter)) {
    $where_conditions[] = "o.PAYMENT_SITUATION = ?";
    $params[] = $payment_filter;
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(o.DATE_ORDER) = ?";
    $params[] = $date_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(c.FULLNAME LIKE ? OR m.NAME LIKE ? OR c.EMAIL LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get orders with detailed information
$orders_query = "
    SELECT
        o.*,
        c.FULLNAME as client_name,
        c.EMAIL as client_email,
        c.PHONE_NUMBER as client_phone,
        c.LOCATION as client_location,
        m.NAME as meal_name,
        m.DESCRIPTION as meal_description,
        m.PRICE as meal_price,
        m.IMAGE_URL as meal_image,
        cat.NAME as category_name,
        CASE
            WHEN o.PAYMENT_SITUATION = 0 THEN 'Pending'
            WHEN o.PAYMENT_SITUATION = 1 THEN 'Paid'
            WHEN o.PAYMENT_SITUATION = 2 THEN 'Refunded'
            ELSE 'Unknown'
        END as payment_status_text
    FROM `ORDER` o
    JOIN CLIENTS c ON o.ID_CLIENTS = c.ID_CLIENTS
    JOIN MEALS m ON o.ID_MEALS = m.ID_MEALS
    JOIN CATEGORIES cat ON m.ID_CATEGORIES = cat.ID_CATEGORIES
    $where_clause
    ORDER BY o.DATE_ORDER DESC, o.ID_CLIENTS
";

$stmt = $pdo->prepare($orders_query);
$stmt->execute($params);
$orders_raw = $stmt->fetchAll();

// Group orders by client and order date (within 1 hour window)
$grouped_orders = [];
foreach ($orders_raw as $order) {
    // Create a unique key for grouping orders by client and approximate time
    $order_time = strtotime($order['DATE_ORDER']);
    $hour_key = floor($order_time / 3600); // Group by hour
    $group_key = $order['ID_CLIENTS'] . '_' . $hour_key . '_' . $order['ORDER_SITUATION'] . '_' . $order['PAYMENT_SITUATION'];

    if (!isset($grouped_orders[$group_key])) {
        $grouped_orders[$group_key] = [
            'client_info' => [
                'ID_CLIENTS' => $order['ID_CLIENTS'],
                'client_name' => $order['client_name'],
                'client_email' => $order['client_email'],
                'client_phone' => $order['client_phone'],
                'client_location' => $order['client_location']
            ],
            'order_info' => [
                'ORDER_SITUATION' => $order['ORDER_SITUATION'],
                'PAYMENT_SITUATION' => $order['PAYMENT_SITUATION'],
                'payment_status_text' => $order['payment_status_text'],
                'DATE_ORDER' => $order['DATE_ORDER']
            ],
            'meals' => [],
            'total_price' => 0,
            'meal_count' => 0
        ];
    }

    // Add meal to the group
    $grouped_orders[$group_key]['meals'][] = [
        'ID_MEALS' => $order['ID_MEALS'],
        'meal_name' => $order['meal_name'],
        'meal_description' => $order['meal_description'],
        'meal_price' => $order['meal_price'],
        'meal_image' => $order['meal_image'],
        'category_name' => $order['category_name']
    ];

    $grouped_orders[$group_key]['total_price'] += $order['meal_price'];
    $grouped_orders[$group_key]['meal_count']++;
}

// Convert to indexed array and sort by date
$orders = array_values($grouped_orders);
usort($orders, function($a, $b) {
    return strtotime($b['order_info']['DATE_ORDER']) - strtotime($a['order_info']['DATE_ORDER']);
});

// Get statistics
$total_orders = $pdo->query("SELECT COUNT(*) FROM `ORDER`")->fetchColumn();
$pending_orders = $pdo->query("SELECT COUNT(*) FROM `ORDER` WHERE ORDER_SITUATION = 'Pending'")->fetchColumn();
$ready_orders = $pdo->query("SELECT COUNT(*) FROM `ORDER` WHERE ORDER_SITUATION = 'Ready'")->fetchColumn();
$delivered_orders = $pdo->query("SELECT COUNT(*) FROM `ORDER` WHERE ORDER_SITUATION = 'Delivered'")->fetchColumn();
$total_revenue = $pdo->query("
    SELECT COALESCE(SUM(m.PRICE), 0) 
    FROM `ORDER` o 
    JOIN MEALS m ON o.ID_MEALS = m.ID_MEALS 
    WHERE o.PAYMENT_SITUATION = 1
")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Sushi Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .order-card {
            transition: transform 0.2s;
            border-left: 4px solid #dee2e6;
        }
        .order-card:hover {
            transform: translateY(-2px);
        }
        .order-card.status-pending { border-left-color: #ffc107; }
        .order-card.status-confirmed { border-left-color: #17a2b8; }
        .order-card.status-preparing { border-left-color: #fd7e14; }
        .order-card.status-ready { border-left-color: #28a745; }
        .order-card.status-delivered { border-left-color: #6c757d; }
        .order-card.status-cancelled { border-left-color: #dc3545; }
        
        .meal-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .filter-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        @media print {
            .no-print { display: none !important; }
            .order-card { break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark no-print">
        <div class="container">
            <a class="navbar-brand" href="dashboard_enhanced.php">
                <i class="fas fa-utensils me-2"></i>Sushi Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard_enhanced.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                
                <?php if (isSuperAdmin()): ?>
                <a class="nav-link" href="meals_enhanced.php">
                    <i class="fas fa-fish me-1"></i>Meals
                </a>
                <a class="nav-link" href="admin_management.php">
                    <i class="fas fa-users-cog me-1"></i>Admins
                </a>
                <?php endif; ?>
                
                <a class="nav-link active" href="orders_management.php">
                    <i class="fas fa-shopping-cart me-1"></i>Orders
                </a>
                <a class="nav-link" href="reservations.php">
                    <i class="fas fa-calendar me-1"></i>Reservations
                </a>
                
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row no-print">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-shopping-cart me-2"></i>Orders Management</h2>
                        <p class="text-muted mb-0">Manage and track all customer orders</p>
                    </div>
                    <div>
                        <button onclick="window.print()" class="btn btn-outline-primary">
                            <i class="fas fa-print me-1"></i>Print Orders
                        </button>
                        <button onclick="exportToCSV()" class="btn btn-outline-success">
                            <i class="fas fa-download me-1"></i>Export CSV
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show no-print">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show no-print">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4 no-print">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                        <h3><?php echo $total_orders; ?></h3>
                        <p class="mb-0">Total Orders</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h3><?php echo $pending_orders; ?></h3>
                        <p class="mb-0">Pending Orders</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h3><?php echo $ready_orders; ?></h3>
                        <p class="mb-0">Ready Orders</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                        <h3>$<?php echo number_format($total_revenue, 2); ?></h3>
                        <p class="mb-0">Total Revenue</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4 no-print">
            <div class="col-12">
                <div class="card filter-card text-white">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Orders</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Order Status</label>
                                <select name="status" class="form-control">
                                    <option value="">All Statuses</option>
                                    <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Confirmed" <?php echo $status_filter == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="Preparing" <?php echo $status_filter == 'Preparing' ? 'selected' : ''; ?>>Preparing</option>
                                    <option value="Ready" <?php echo $status_filter == 'Ready' ? 'selected' : ''; ?>>Ready</option>
                                    <option value="Delivered" <?php echo $status_filter == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="Cancelled" <?php echo $status_filter == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Payment Status</label>
                                <select name="payment" class="form-control">
                                    <option value="">All Payments</option>
                                    <option value="0" <?php echo $payment_filter === '0' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="1" <?php echo $payment_filter === '1' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="2" <?php echo $payment_filter === '2' ? 'selected' : ''; ?>>Refunded</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Client name, meal, email..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit" class="btn btn-light">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-light me-2">
                                    <i class="fas fa-filter me-1"></i>Apply Filters
                                </button>
                                <a href="orders_management.php" class="btn btn-outline-light">
                                    <i class="fas fa-times me-1"></i>Clear Filters
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Display -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Orders List
                            <span class="badge bg-primary"><?php echo count($orders); ?> orders</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($orders)): ?>

                        <!-- Cards View -->
                        <div id="cards-view">
                            <div class="row">
                                <?php foreach ($orders as $order): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card order-card status-<?php echo strtolower($order['order_info']['ORDER_SITUATION']); ?>">
                                        <div class="card-body">
                                            <!-- Customer Info Header -->
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h6 class="card-title mb-1"><?php echo htmlspecialchars($order['client_info']['client_name']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($order['client_info']['client_email']); ?></small>
                                                    <br><small class="text-muted"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($order['client_info']['client_phone']); ?></small>
                                                </div>
                                                <span class="badge bg-<?php
                                                    echo $order['order_info']['ORDER_SITUATION'] == 'Pending' ? 'warning' :
                                                        ($order['order_info']['ORDER_SITUATION'] == 'Ready' ? 'success' :
                                                        ($order['order_info']['ORDER_SITUATION'] == 'Delivered' ? 'secondary' :
                                                        ($order['order_info']['ORDER_SITUATION'] == 'Cancelled' ? 'danger' : 'info')));
                                                ?> status-badge">
                                                    <?php echo htmlspecialchars($order['order_info']['ORDER_SITUATION']); ?>
                                                </span>
                                            </div>

                                            <!-- Order Summary -->
                                            <div class="mb-3 p-2 bg-light rounded">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span><i class="fas fa-shopping-bag me-1"></i><strong><?php echo $order['meal_count']; ?> item(s)</strong></span>
                                                    <span class="text-success fw-bold">$<?php echo number_format($order['total_price'], 2); ?></span>
                                                </div>
                                            </div>

                                            <!-- Meals List -->
                                            <div class="mb-3">
                                                <h6 class="text-muted mb-2">Order Items:</h6>
                                                <?php foreach ($order['meals'] as $index => $meal): ?>
                                                    <div class="d-flex align-items-center mb-2 <?php echo $index > 0 ? 'border-top pt-2' : ''; ?>">
                                                        <div class="me-2" style="width: 40px; height: 40px; background: #f8f9fa; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                                                            <i class="fas fa-utensils text-muted"></i>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <div class="fw-bold"><?php echo htmlspecialchars($meal['meal_name']); ?></div>
                                                            <small class="text-muted"><?php echo htmlspecialchars($meal['category_name']); ?></small>
                                                        </div>
                                                        <div class="text-end">
                                                            <span class="text-success fw-bold">$<?php echo number_format($meal['meal_price'], 2); ?></span>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                            <!-- Order Info -->
                                            <div class="mb-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('M d, Y H:i', strtotime($order['order_info']['DATE_ORDER'])); ?>
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-credit-card me-1"></i>
                                                    Payment: <span class="badge bg-<?php echo $order['order_info']['PAYMENT_SITUATION'] == 1 ? 'success' : ($order['order_info']['PAYMENT_SITUATION'] == 2 ? 'warning' : 'secondary'); ?>">
                                                        <?php echo $order['order_info']['payment_status_text']; ?>
                                                    </span>
                                                </small>
                                                <?php if (!empty($order['client_info']['client_location'])): ?>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?php echo htmlspecialchars($order['client_info']['client_location']); ?>
                                                </small>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Action Buttons -->
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['client_info']['ID_CLIENTS'].'_'.strtotime($order['order_info']['DATE_ORDER']); ?>">
                                                    <i class="fas fa-eye"></i> View Details
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $order['client_info']['ID_CLIENTS'].'_'.strtotime($order['order_info']['DATE_ORDER']); ?>">
                                                    <i class="fas fa-edit"></i> Update Status
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No orders found</h5>
                            <p class="text-muted">No orders match your current filters.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Modals -->
    <?php foreach ($orders as $order): ?>
        <!-- View Order Modal -->
        <div class="modal fade" id="orderModal<?php echo $order['client_info']['ID_CLIENTS'].'_'.strtotime($order['order_info']['DATE_ORDER']); ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-eye me-2"></i>Order Details - <?php echo htmlspecialchars($order['client_info']['client_name']); ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Customer Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6><i class="fas fa-user me-2"></i>Customer Information</h6>
                                <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order['client_info']['client_name']); ?></p>
                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['client_info']['client_email']); ?></p>
                                <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order['client_info']['client_phone']); ?></p>
                                <?php if (!empty($order['client_info']['client_location'])): ?>
                                <p class="mb-1"><strong>Location:</strong> <?php echo htmlspecialchars($order['client_info']['client_location']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-info-circle me-2"></i>Order Information</h6>
                                <p class="mb-1"><strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['order_info']['DATE_ORDER'])); ?></p>
                                <p class="mb-1"><strong>Status:</strong>
                                    <span class="badge bg-<?php
                                        echo $order['order_info']['ORDER_SITUATION'] == 'Pending' ? 'warning' :
                                            ($order['order_info']['ORDER_SITUATION'] == 'Ready' ? 'success' :
                                            ($order['order_info']['ORDER_SITUATION'] == 'Delivered' ? 'secondary' :
                                            ($order['order_info']['ORDER_SITUATION'] == 'Cancelled' ? 'danger' : 'info')));
                                    ?>">
                                        <?php echo htmlspecialchars($order['order_info']['ORDER_SITUATION']); ?>
                                    </span>
                                </p>
                                <p class="mb-1"><strong>Payment:</strong>
                                    <span class="badge bg-<?php echo $order['order_info']['PAYMENT_SITUATION'] == 1 ? 'success' : ($order['order_info']['PAYMENT_SITUATION'] == 2 ? 'warning' : 'secondary'); ?>">
                                        <?php echo $order['order_info']['payment_status_text']; ?>
                                    </span>
                                </p>
                                <p class="mb-1"><strong>Total Items:</strong> <?php echo $order['meal_count']; ?></p>
                                <p class="mb-1"><strong>Total Amount:</strong> <span class="text-success fw-bold">$<?php echo number_format($order['total_price'], 2); ?></span></p>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <h6><i class="fas fa-utensils me-2"></i>Order Items</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order['meals'] as $meal): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($meal['meal_name']); ?></td>
                                        <td><?php echo htmlspecialchars($meal['category_name']); ?></td>
                                        <td>$<?php echo number_format($meal['meal_price'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-success">
                                        <th colspan="2">Total</th>
                                        <th>$<?php echo number_format($order['total_price'], 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $order['client_info']['ID_CLIENTS'].'_'.strtotime($order['order_info']['DATE_ORDER']); ?>" data-bs-dismiss="modal">
                            <i class="fas fa-edit me-1"></i>Update Status
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Status Modal -->
        <div class="modal fade" id="statusModal<?php echo $order['client_info']['ID_CLIENTS'].'_'.strtotime($order['order_info']['DATE_ORDER']); ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Update Order Status
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="client_id" value="<?php echo $order['client_info']['ID_CLIENTS']; ?>">
                            <input type="hidden" name="order_date" value="<?php echo $order['order_info']['DATE_ORDER']; ?>">

                            <div class="mb-3">
                                <label class="form-label">Customer: <?php echo htmlspecialchars($order['client_info']['client_name']); ?></label>
                                <p class="text-muted small"><?php echo $order['meal_count']; ?> items - $<?php echo number_format($order['total_price'], 2); ?></p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Order Status</label>
                                <select name="new_status" class="form-control" required>
                                    <option value="Pending" <?php echo $order['order_info']['ORDER_SITUATION'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Confirmed" <?php echo $order['order_info']['ORDER_SITUATION'] == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="Preparing" <?php echo $order['order_info']['ORDER_SITUATION'] == 'Preparing' ? 'selected' : ''; ?>>Preparing</option>
                                    <option value="Ready" <?php echo $order['order_info']['ORDER_SITUATION'] == 'Ready' ? 'selected' : ''; ?>>Ready</option>
                                    <option value="Delivered" <?php echo $order['order_info']['ORDER_SITUATION'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="Cancelled" <?php echo $order['order_info']['ORDER_SITUATION'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Payment Status</label>
                                <select name="payment_status" class="form-control">
                                    <option value="0" <?php echo $order['order_info']['PAYMENT_SITUATION'] == 0 ? 'selected' : ''; ?>>Pending</option>
                                    <option value="1" <?php echo $order['order_info']['PAYMENT_SITUATION'] == 1 ? 'selected' : ''; ?>>Paid</option>
                                    <option value="2" <?php echo $order['order_info']['PAYMENT_SITUATION'] == 2 ? 'selected' : ''; ?>>Refunded</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_status" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Update Order Status
                            </button>
                            <button type="submit" name="update_payment" class="btn btn-success">
                                <i class="fas fa-credit-card me-1"></i>Update Payment Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportToCSV() {
            // Export orders to CSV
            window.location.href = 'export_orders.php?' + window.location.search.substring(1);
        }

        function printOrder(orderId) {
            // Print individual order
            window.open('print_order.php?order=' + orderId, '_blank', 'width=800,height=600');
        }
    </script>
</body>
</html>
