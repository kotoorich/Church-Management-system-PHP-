<?php
$page_title = 'Payment Records';
require_once '../includes/header.php';
requireLogin();

// Get search and filter parameters
$search = sanitizeInput($_GET['search'] ?? '');
$type_filter = $_GET['type'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$sort_field = $_GET['sort'] ?? 'payment_date';
$sort_direction = $_GET['direction'] ?? 'desc';

// Build search query
$search_query = buildPaymentSearchQuery($search, $type_filter);
$where_clause = $search_query['where'];
$params = $search_query['params'];

// Count total filtered payments
$count_sql = "
    SELECT COUNT(*) as total 
    FROM payments p 
    JOIN members m ON p.member_id = m.id 
    " . $where_clause;
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_payments = $count_stmt->fetch()['total'];

// Get pagination data
$pagination = getPaginationData($total_payments, $page, PAYMENTS_PER_PAGE);

// Build sort clause
$valid_sort_fields = ['payment_date', 'amount', 'member_name', 'type'];
$sort_field = in_array($sort_field, $valid_sort_fields) ? $sort_field : 'payment_date';
$sort_direction = in_array($sort_direction, ['asc', 'desc']) ? $sort_direction : 'desc';

$sort_clause = "ORDER BY ";
switch ($sort_field) {
    case 'member_name':
        $sort_clause .= "m.name " . $sort_direction;
        break;
    case 'payment_date':
        $sort_clause .= "p.payment_date " . $sort_direction;
        break;
    case 'amount':
        $sort_clause .= "p.amount " . $sort_direction;
        break;
    case 'type':
        $sort_clause .= "p.type " . $sort_direction;
        break;
}

// Get payments with pagination and sorting
$sql = "
    SELECT p.*, m.name as member_name, m.email as member_email, m.image_url as member_image
    FROM payments p 
    JOIN members m ON p.member_id = m.id 
    " . $where_clause . " 
    " . $sort_clause . " 
    LIMIT " . $pagination['items_per_page'] . " 
    OFFSET " . $pagination['offset'];

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Get unique payment types for filter
$types_stmt = $pdo->query("SELECT DISTINCT type FROM payments ORDER BY type");
$payment_types = $types_stmt->fetchAll(PDO::FETCH_COLUMN);

// Calculate totals for filtered results
$total_sql = "
    SELECT SUM(p.amount) as total_amount 
    FROM payments p 
    JOIN members m ON p.member_id = m.id 
    " . $where_clause;
$total_stmt = $pdo->prepare($total_sql);
$total_stmt->execute($params);
$total_amount = $total_stmt->fetch()['total_amount'] ?? 0;

function getSortIcon($field, $current_field, $direction) {
    if ($field !== $current_field) return '↕️';
    return $direction === 'asc' ? '↑' : '↓';
}

function getSortUrl($field, $current_field, $direction, $params = []) {
    $new_direction = ($field === $current_field && $direction === 'desc') ? 'asc' : 'desc';
    $params['sort'] = $field;
    $params['direction'] = $new_direction;
    return '?' . http_build_query($params);
}

// Handle success/error messages
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<div class="space-y-6">
    <!-- Success/Error Messages -->
    <?php if (isset($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($error_message)): ?>
        <div class="bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded-lg">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="flex items-center justify-between">
        <div>
            <h2>Payment Records</h2>
            <p class="text-muted-foreground">Track all church donations and payments</p>
        </div>
        <a href="add.php" class="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2">
            <span>+</span> Add Payment
        </a>
    </div>

    <!-- Search and Filter Bar -->
    <div class="bg-card rounded-lg border border shadow-sm p-6">
        <form method="GET" class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
            <div class="flex-1 max-w-md">
                <div class="relative">
                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground">
                        🔍
                    </span>
                    <input
                        type="text"
                        name="search"
                        placeholder="Search by member name, type, or amount..."
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="w-full pl-10 pr-4 py-2 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                    />
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <select
                    name="type"
                    class="px-3 py-2 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                >
                    <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>All Types</option>
                    <?php foreach ($payment_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $type_filter === $type ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="px-4 py-2 bg-secondary text-secondary-foreground rounded-lg hover:bg-secondary/80 transition-colors">
                    Search
                </button>
                
                <div class="text-sm text-muted-foreground">
                    <?php echo number_format($total_payments); ?> payments
                </div>
            </div>
        </form>
    </div>

    <!-- Payments Table -->
    <div class="bg-card rounded-lg border border shadow-sm">
        <div class="p-6 border-b border">
            <div class="flex items-center justify-between">
                <h3>Payment Records (<?php echo number_format($total_payments); ?> payments)</h3>
                <div class="text-right">
                    <p class="text-lg font-bold">Total: <?php echo formatCurrency($total_amount); ?></p>
                    <p class="text-sm text-muted-foreground">
                        Showing <?php echo $pagination['offset'] + 1; ?> to <?php echo min($pagination['offset'] + $pagination['items_per_page'], $total_payments); ?> of <?php echo number_format($total_payments); ?>
                    </p>
                </div>
            </div>
        </div>

        <?php if (!empty($payments)): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="text-left p-4 border-b border font-medium">
                                <a href="<?php echo getSortUrl('member_name', $sort_field, $sort_direction, ['search' => $search, 'type' => $type_filter, 'page' => $page]); ?>" class="flex items-center gap-2 hover:text-primary transition-colors">
                                    Member <?php echo getSortIcon('member_name', $sort_field, $sort_direction); ?>
                                </a>
                            </th>
                            <th class="text-left p-4 border-b border font-medium">
                                <a href="<?php echo getSortUrl('payment_date', $sort_field, $sort_direction, ['search' => $search, 'type' => $type_filter, 'page' => $page]); ?>" class="flex items-center gap-2 hover:text-primary transition-colors">
                                    Date <?php echo getSortIcon('payment_date', $sort_field, $sort_direction); ?>
                                </a>
                            </th>
                            <th class="text-left p-4 border-b border font-medium">
                                <a href="<?php echo getSortUrl('type', $sort_field, $sort_direction, ['search' => $search, 'type' => $type_filter, 'page' => $page]); ?>" class="flex items-center gap-2 hover:text-primary transition-colors">
                                    Type <?php echo getSortIcon('type', $sort_field, $sort_direction); ?>
                                </a>
                            </th>
                            <th class="text-left p-4 border-b border font-medium">Method</th>
                            <th class="text-right p-4 border-b border font-medium">
                                <a href="<?php echo getSortUrl('amount', $sort_field, $sort_direction, ['search' => $search, 'type' => $type_filter, 'page' => $page]); ?>" class="flex items-center justify-end gap-2 hover:text-primary transition-colors w-full">
                                    Amount <?php echo getSortIcon('amount', $sort_field, $sort_direction); ?>
                                </a>
                            </th>
                            <th class="text-left p-4 border-b border font-medium">Description</th>
                            <th class="text-center p-4 border-b border font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $index => $payment): ?>
                            <tr class="hover:bg-accent/50 transition-colors <?php echo $index % 2 === 0 ? 'bg-background' : 'bg-muted/20'; ?>">
                                <td class="p-4 border-b border">
                                    <div class="flex items-center space-x-3">
                                        <?php if ($payment['member_image']): ?>
                                            <img
                                                src="../<?php echo htmlspecialchars($payment['member_image']); ?>"
                                                alt="<?php echo htmlspecialchars($payment['member_name']); ?>"
                                                class="w-8 h-8 rounded-full object-cover"
                                            />
                                        <?php else: ?>
                                            <div class="w-8 h-8 bg-gradient-to-br from-primary/20 to-primary/40 rounded-full flex items-center justify-center text-primary text-sm font-medium">
                                                <?php echo getMemberInitials($payment['member_name']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <p class="font-medium">
                                                <a href="../members/view.php?id=<?php echo $payment['member_id']; ?>" class="hover:text-primary transition-colors">
                                                    <?php echo htmlspecialchars($payment['member_name']); ?>
                                                </a>
                                            </p>
                                            <p class="text-xs text-muted-foreground"><?php echo htmlspecialchars($payment['member_email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 border-b border">
                                    <div class="font-medium">
                                        <?php echo formatDate($payment['payment_date'], 'M j, Y'); ?>
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        <?php echo date('l', strtotime($payment['payment_date'])); ?>
                                    </div>
                                </td>
                                <td class="p-4 border-b border">
                                    <span class="inline-block px-2 py-1 bg-primary/10 text-primary rounded text-sm font-medium">
                                        <?php echo htmlspecialchars($payment['type']); ?>
                                    </span>
                                </td>
                                <td class="p-4 border-b border">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-secondary/50 text-secondary-foreground rounded text-sm">
                                        <?php echo getPaymentMethodWithIcon($payment['payment_method']); ?>
                                    </span>
                                </td>
                                <td class="p-4 border-b border text-right">
                                    <span class="font-bold text-lg"><?php echo formatCurrency($payment['amount']); ?></span>
                                </td>
                                <td class="p-4 border-b border">
                                    <span class="text-muted-foreground text-sm">
                                        <?php echo htmlspecialchars($payment['description'] ?: '—'); ?>
                                    </span>
                                </td>
                                <td class="p-4 border-b border text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <a href="edit.php?id=<?php echo $payment['id']; ?>" class="p-2 text-muted-foreground hover:text-primary hover:bg-accent rounded-lg transition-colors" title="Edit Payment">
                                            ✏️
                                        </a>
                                        <a href="delete.php?id=<?php echo $payment['id']; ?>" class="p-2 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded-lg transition-colors" title="Delete Payment" onclick="return confirm('Are you sure you want to delete this payment?')">
                                            🗑
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <div class="text-6xl mb-4">💰</div>
                <h3 class="text-lg mb-2">No payments found</h3>
                <p class="text-muted-foreground mb-4">
                    <?php if ($search || $type_filter !== 'all'): ?>
                        Try adjusting your search criteria
                    <?php else: ?>
                        Add your first payment to get started
                    <?php endif; ?>
                </p>
                <?php if (!$search && $type_filter === 'all'): ?>
                    <a href="add.php" class="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors">
                        Add First Payment
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="p-6 border-t border">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-muted-foreground">
                        Showing <?php echo $pagination['offset'] + 1; ?> to <?php echo min($pagination['offset'] + $pagination['items_per_page'], $total_payments); ?> of <?php echo number_format($total_payments); ?> payments
                    </div>
                    <div class="flex items-center space-x-2">
                        <?php
                        $query_params = $_GET;
                        $max_visible_pages = 5;
                        $start_page = max(1, $page - floor($max_visible_pages / 2));
                        $end_page = min($pagination['total_pages'], $start_page + $max_visible_pages - 1);
                        
                        if ($end_page - $start_page + 1 < $max_visible_pages) {
                            $start_page = max(1, $end_page - $max_visible_pages + 1);
                        }
                        
                        // Previous button
                        if ($page > 1):
                            $query_params['page'] = $page - 1;
                        ?>
                            <a href="?<?php echo http_build_query($query_params); ?>" class="px-3 py-2 text-sm border border rounded-lg hover:bg-accent transition-colors">
                                ←
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        // Page numbers
                        for ($i = $start_page; $i <= $end_page; $i++):
                            $query_params['page'] = $i;
                        ?>
                            <a href="?<?php echo http_build_query($query_params); ?>" class="px-3 py-2 text-sm border border rounded-lg transition-colors <?php echo $page === $i ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php
                        // Next button
                        if ($page < $pagination['total_pages']):
                            $query_params['page'] = $page + 1;
                        ?>
                            <a href="?<?php echo http_build_query($query_params); ?>" class="px-3 py-2 text-sm border border rounded-lg hover:bg-accent transition-colors">
                                →
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>