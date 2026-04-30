<?php
$page_title = 'Member Details';
require_once '../includes/header.php';
requireLogin();

$member_id = intval($_GET['id'] ?? 0);
if (!$member_id) {
    header('Location: index.php');
    exit();
}

// Get member data
$member = getMemberById($member_id, $pdo);
if (!$member) {
    header('Location: index.php');
    exit();
}

// Get member payments
$member_payments = getMemberPayments($member_id, $pdo);
$total_donations = getTotalDonations($member_id, $pdo);
$avg_donation = count($member_payments) > 0 ? $total_donations / count($member_payments) : 0;

// Get unique payment types for filter
$payment_types = array_unique(array_column($member_payments, 'type'));

// Handle tab selection
$active_tab = $_GET['tab'] ?? 'overview';

// For payment history tab - handle sorting and filtering
$sort_field = $_GET['sort'] ?? 'payment_date';
$sort_direction = $_GET['direction'] ?? 'desc';
$filter_type = $_GET['type'] ?? 'all';

// Filter payments by type
$filtered_payments = $filter_type === 'all' 
    ? $member_payments 
    : array_filter($member_payments, function($payment) use ($filter_type) {
        return $payment['type'] === $filter_type;
    });

// Sort payments
usort($filtered_payments, function($a, $b) use ($sort_field, $sort_direction) {
    $aValue = $a[$sort_field];
    $bValue = $b[$sort_field];
    
    if ($sort_field === 'payment_date') {
        $aValue = strtotime($aValue);
        $bValue = strtotime($bValue);
    } elseif ($sort_field === 'amount') {
        $aValue = floatval($aValue);
        $bValue = floatval($bValue);
    } else {
        $aValue = strtolower($aValue);
        $bValue = strtolower($bValue);
    }
    
    if ($sort_direction === 'asc') {
        return $aValue <=> $bValue;
    } else {
        return $bValue <=> $aValue;
    }
});

// For monthly tracker tab
$selected_month = $_GET['month'] ?? date('Y-m');

// Filter payments for selected month
$monthly_payments = array_filter($member_payments, function($payment) use ($selected_month) {
    $payment_month = date('Y-m', strtotime($payment['payment_date']));
    return $payment_month === $selected_month;
});

// Get member's payment trends (last 6 months)
$member_trends = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_payments = array_filter($member_payments, function($payment) use ($month) {
        return date('Y-m', strtotime($payment['payment_date'])) === $month;
    });
    
    $member_trends[] = [
        'month' => $month,
        'month_name' => date('M y', strtotime($month . '-01')),
        'count' => count($month_payments),
        'total' => array_sum(array_column($month_payments, 'amount'))
    ];
}

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
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="index.php" class="flex items-center gap-2 text-primary hover:text-primary/80 transition-colors px-3 py-2 rounded-lg hover:bg-accent">
            ← Back to Members
        </a>
        
        <div class="flex items-center gap-3">
            <a href="edit.php?id=<?php echo $member['id']; ?>" class="bg-secondary text-secondary-foreground px-4 py-2 rounded-lg hover:bg-secondary/80 transition-colors flex items-center gap-2">
                ✏️ Edit Member
            </a>
            <a href="../payments/add.php?member_id=<?php echo $member['id']; ?>" class="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2">
                <span>+</span> Add Payment
            </a>
        </div>
    </div>
    
    <!-- Navigation Tabs -->
    <div class="bg-card rounded-lg border border shadow-sm">
        <div class="flex border-b border">
            <a href="?id=<?php echo $member['id']; ?>&tab=overview" class="px-6 py-3 font-medium transition-colors <?php echo $active_tab === 'overview' ? 'text-primary border-b-2 border-primary' : 'text-muted-foreground hover:text-foreground'; ?>">
                📋 Overview
            </a>
            <a href="?id=<?php echo $member['id']; ?>&tab=payments" class="px-6 py-3 font-medium transition-colors <?php echo $active_tab === 'payments' ? 'text-primary border-b-2 border-primary' : 'text-muted-foreground hover:text-foreground'; ?>">
                💰 Payment History
            </a>
            <a href="?id=<?php echo $member['id']; ?>&tab=monthly" class="px-6 py-3 font-medium transition-colors <?php echo $active_tab === 'monthly' ? 'text-primary border-b-2 border-primary' : 'text-muted-foreground hover:text-foreground'; ?>">
                📅 Monthly Tracker
            </a>
        </div>
    </div>

    <?php if ($active_tab === 'overview'): ?>
        <!-- Member Profile Header -->
        <div class="bg-gradient-to-r from-primary/5 to-primary/10 rounded-lg border border p-6">
            <div class="flex items-center space-x-6">
                <?php if ($member['image_url']): ?>
                    <img
                        src="../<?php echo htmlspecialchars($member['image_url']); ?>"
                        alt="<?php echo htmlspecialchars($member['name']); ?>"
                        class="w-20 h-20 rounded-full object-cover border-4 border-primary/20"
                    />
                <?php else: ?>
                    <div class="w-20 h-20 bg-gradient-to-br from-primary/20 to-primary/40 rounded-full flex items-center justify-center text-primary text-2xl font-medium">
                        <?php echo getMemberInitials($member['name']); ?>
                    </div>
                <?php endif; ?>
                <div class="flex-1">
                    <h2 class="text-2xl mb-2"><?php echo htmlspecialchars($member['name']); ?></h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                        <div>
                            <label class="text-muted-foreground">Email</label>
                            <p><?php echo htmlspecialchars($member['email']); ?></p>
                        </div>
                        <div>
                            <label class="text-muted-foreground">Phone</label>
                            <p><?php echo htmlspecialchars($member['phone']); ?></p>
                        </div>
                        <div>
                            <label class="text-muted-foreground">Profession</label>
                            <p><?php echo htmlspecialchars($member['profession'] ?: 'Not specified'); ?></p>
                        </div>
                        <div>
                            <label class="text-muted-foreground">Status</label>
                            <?php echo getMemberStatusBadge($member['status']); ?>
                        </div>
                        <div>
                            <label class="text-muted-foreground">Member Since</label>
                            <p><?php echo formatDate($member['membership_date']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t border">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-primary"><?php echo count($member_payments); ?></p>
                        <p class="text-sm text-muted-foreground">Total Payments</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-primary"><?php echo formatCurrency($total_donations); ?></p>
                        <p class="text-sm text-muted-foreground">Total Donated</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-primary"><?php echo formatCurrency($avg_donation); ?></p>
                        <p class="text-sm text-muted-foreground">Average Payment</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-primary"><?php echo count($payment_types); ?></p>
                        <p class="text-sm text-muted-foreground">Payment Types</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Address Card -->
        <div class="bg-card rounded-lg border border shadow-sm p-6">
            <h3 class="mb-3">Address Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-muted-foreground text-sm">Digital Address (GPS)</label>
                    <p class="font-medium"><?php echo htmlspecialchars($member['digital_address'] ?: 'Not provided'); ?></p>
                </div>
                <div>
                    <label class="text-muted-foreground text-sm">House Address</label>
                    <p class="font-medium"><?php echo htmlspecialchars($member['house_address']); ?></p>
                </div>
            </div>
        </div>

    <?php elseif ($active_tab === 'payments'): ?>
        <!-- Payment History -->
        <div class="bg-card rounded-lg border border shadow-sm">
            <div class="p-6 border-b border">
                <div class="flex items-center justify-between mb-4">
                    <h3>Payment History (<?php echo count($filtered_payments); ?> payments)</h3>
                    
                    <div class="flex items-center gap-4">
                        <form method="GET" class="flex items-center gap-2">
                            <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                            <input type="hidden" name="tab" value="payments">
                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_field); ?>">
                            <input type="hidden" name="direction" value="<?php echo htmlspecialchars($sort_direction); ?>">
                            <select name="type" onchange="this.form.submit()" class="px-3 py-2 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring text-sm">
                                <option value="all">All Types</option>
                                <?php foreach ($payment_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filter_type === $type ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>
            </div>

            <?php if (!empty($filtered_payments)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="text-left p-4 border-b border font-medium">
                                    <a href="<?php echo getSortUrl('payment_date', $sort_field, $sort_direction, ['id' => $member['id'], 'tab' => 'payments', 'type' => $filter_type]); ?>" class="flex items-center gap-2 hover:text-primary transition-colors">
                                        Date <?php echo getSortIcon('payment_date', $sort_field, $sort_direction); ?>
                                    </a>
                                </th>
                                <th class="text-left p-4 border-b border font-medium">
                                    <a href="<?php echo getSortUrl('type', $sort_field, $sort_direction, ['id' => $member['id'], 'tab' => 'payments', 'type' => $filter_type]); ?>" class="flex items-center gap-2 hover:text-primary transition-colors">
                                        Type <?php echo getSortIcon('type', $sort_field, $sort_direction); ?>
                                    </a>
                                </th>
                                <th class="text-left p-4 border-b border font-medium">Method</th>
                                <th class="text-right p-4 border-b border font-medium">
                                    <a href="<?php echo getSortUrl('amount', $sort_field, $sort_direction, ['id' => $member['id'], 'tab' => 'payments', 'type' => $filter_type]); ?>" class="flex items-center justify-end gap-2 hover:text-primary transition-colors w-full">
                                        Amount <?php echo getSortIcon('amount', $sort_field, $sort_direction); ?>
                                    </a>
                                </th>
                                <th class="text-left p-4 border-b border font-medium">Description</th>
                                <th class="text-center p-4 border-b border font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filtered_payments as $index => $payment): ?>
                                <tr class="hover:bg-accent/50 transition-colors <?php echo $index % 2 === 0 ? 'bg-background' : 'bg-muted/20'; ?>">
                                    <td class="p-4 border-b border">
                                        <div class="font-medium">
                                            <?php echo formatDate($payment['payment_date'], 'M j, Y'); ?>
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            <?php echo formatDateWithDay($payment['payment_date']); ?>
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
                                        <span class="text-muted-foreground">
                                            <?php echo htmlspecialchars($payment['description'] ?: '—'); ?>
                                        </span>
                                    </td>
                                    <td class="p-4 border-b border text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <a href="../payments/edit.php?id=<?php echo $payment['id']; ?>" class="p-2 text-muted-foreground hover:text-primary hover:bg-accent rounded-lg transition-colors" title="Edit Payment">
                                                ✏️
                                            </a>
                                            <a href="../payments/delete.php?id=<?php echo $payment['id']; ?>&return=member&member_id=<?php echo $member['id']; ?>" class="p-2 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded-lg transition-colors" title="Delete Payment" onclick="return confirm('Are you sure you want to delete this payment?')">
                                                🗑
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-muted/30">
                            <tr>
                                <td colspan="3" class="p-4 border-t border font-medium">
                                    Total (<?php echo count($filtered_payments); ?> payments)
                                </td>
                                <td class="p-4 border-t border text-right font-bold text-lg">
                                    <?php echo formatCurrency(array_sum(array_column($filtered_payments, 'amount'))); ?>
                                </td>
                                <td colspan="2" class="p-4 border-t border"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-12 text-center">
                    <div class="text-6xl mb-4">💰</div>
                    <h3 class="text-lg mb-2">No payments found</h3>
                    <p class="text-muted-foreground mb-4">
                        <?php echo $filter_type !== 'all' ? 'No payments match the selected filter' : 'This member has no payment records yet'; ?>
                    </p>
                    <a href="../payments/add.php?member_id=<?php echo $member['id']; ?>" class="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors">
                        Add First Payment
                    </a>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($active_tab === 'monthly'): ?>
        <!-- Monthly Tracker -->
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h3><?php echo htmlspecialchars($member['name']); ?>'s Monthly Payment Tracker</h3>
                    <p class="text-muted-foreground">Track monthly payment patterns and trends</p>
                </div>
                
                <div class="flex items-center gap-2">
                    <form method="GET" class="flex items-center gap-2">
                        <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                        <input type="hidden" name="tab" value="monthly">
                        <label for="month" class="text-sm text-muted-foreground">Month:</label>
                        <input
                            id="month"
                            name="month"
                            type="month"
                            value="<?php echo htmlspecialchars($selected_month); ?>"
                            onchange="this.form.submit()"
                            class="px-3 py-2 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                        />
                    </form>
                </div>
            </div>

            <!-- Payment Trends -->
            <?php if (count(array_filter($member_trends, function($t) { return $t['count'] > 0; })) > 1): ?>
                <div class="bg-card rounded-lg border border shadow-sm p-6">
                    <h4 class="mb-4">6-Month Payment Trend</h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <?php foreach ($member_trends as $trend): ?>
                            <a href="?id=<?php echo $member['id']; ?>&tab=monthly&month=<?php echo $trend['month']; ?>" class="p-4 rounded-lg border border text-center transition-all cursor-pointer hover:shadow-md <?php echo $trend['month'] === $selected_month ? 'bg-primary/10 border-primary shadow-md' : 'bg-muted/20 hover:bg-muted/30'; ?>">
                                <p class="text-sm font-medium"><?php echo $trend['month_name']; ?></p>
                                <p class="text-xl font-bold"><?php echo $trend['total'] > 0 ? formatCurrency($trend['total']) : '₵0'; ?></p>
                                <p class="text-xs text-muted-foreground"><?php echo $trend['count']; ?> payments</p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Monthly Summary Cards -->
            <?php 
            $total_monthly_amount = array_sum(array_column($monthly_payments, 'amount'));
            $month_name = date('F Y', strtotime($selected_month . '-01'));
            ?>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-card rounded-lg border border p-6 shadow-sm">
                    <h4 class="text-sm text-muted-foreground mb-2">Payments in <?php echo $month_name; ?></h4>
                    <div class="text-2xl mb-1"><?php echo count($monthly_payments); ?></div>
                    <p class="text-xs text-muted-foreground">Transactions</p>
                </div>
                <div class="bg-card rounded-lg border border p-6 shadow-sm">
                    <h4 class="text-sm text-muted-foreground mb-2">Total Amount</h4>
                    <div class="text-2xl mb-1"><?php echo formatCurrency($total_monthly_amount); ?></div>
                    <p class="text-xs text-muted-foreground">Total contributions</p>
                </div>
                <div class="bg-card rounded-lg border border p-6 shadow-sm">
                    <h4 class="text-sm text-muted-foreground mb-2">Average Payment</h4>
                    <div class="text-2xl mb-1">
                        <?php echo count($monthly_payments) > 0 ? formatCurrency($total_monthly_amount / count($monthly_payments)) : '₵0'; ?>
                    </div>
                    <p class="text-xs text-muted-foreground">Per transaction</p>
                </div>
                <div class="bg-card rounded-lg border border p-6 shadow-sm">
                    <h4 class="text-sm text-muted-foreground mb-2">Payment Types</h4>
                    <div class="text-2xl mb-1"><?php echo count(array_unique(array_column($monthly_payments, 'type'))); ?></div>
                    <p class="text-xs text-muted-foreground">Different types</p>
                </div>
            </div>

            <!-- Payment Details Table -->
            <?php if (!empty($monthly_payments)): ?>
                <div class="bg-card rounded-lg border border shadow-sm">
                    <div class="p-6 border-b border">
                        <h4>Payments in <?php echo $month_name; ?></h4>
                        <p class="text-sm text-muted-foreground">
                            <?php echo count($monthly_payments); ?> payments totaling <?php echo formatCurrency($total_monthly_amount); ?>
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="text-left p-4 border-b border font-medium">Date</th>
                                    <th class="text-left p-4 border-b border font-medium">Type</th>
                                    <th class="text-left p-4 border-b border font-medium">Method</th>
                                    <th class="text-right p-4 border-b border font-medium">Amount</th>
                                    <th class="text-left p-4 border-b border font-medium">Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                usort($monthly_payments, function($a, $b) {
                                    return strtotime($b['payment_date']) - strtotime($a['payment_date']);
                                });
                                foreach ($monthly_payments as $index => $payment): ?>
                                    <tr class="hover:bg-accent/50 transition-colors <?php echo $index % 2 === 0 ? 'bg-background' : 'bg-muted/20'; ?>">
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
                                            <span class="inline-flex items-center gap-1 text-sm">
                                                <?php echo getPaymentMethodWithIcon($payment['payment_method']); ?>
                                            </span>
                                        </td>
                                        <td class="p-4 border-b border text-right">
                                            <span class="font-bold text-lg"><?php echo formatCurrency($payment['amount']); ?></span>
                                        </td>
                                        <td class="p-4 border-b border">
                                            <span class="text-sm text-muted-foreground">
                                                <?php echo htmlspecialchars($payment['description'] ?: '—'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-muted/30">
                                <tr>
                                    <td colspan="3" class="p-4 border-t border font-medium">
                                        Total (<?php echo count($monthly_payments); ?> payments)
                                    </td>
                                    <td class="p-4 border-t border text-right font-bold text-lg">
                                        <?php echo formatCurrency($total_monthly_amount); ?>
                                    </td>
                                    <td class="p-4 border-t border"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-card rounded-lg border border shadow-sm p-12 text-center">
                    <div class="text-6xl mb-4">📅</div>
                    <h4 class="text-lg mb-2">No Payments in <?php echo $month_name; ?></h4>
                    <p class="text-muted-foreground">
                        <?php echo htmlspecialchars($member['name']); ?> made no payments during <?php echo $month_name; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>