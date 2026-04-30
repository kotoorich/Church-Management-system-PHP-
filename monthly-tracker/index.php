<?php
$page_title = 'Monthly Payment Tracker';
require_once '../includes/header.php';
requireLogin();

// Get all active members for dropdown
$members_stmt = $pdo->query("SELECT * FROM members WHERE status = 'active' ORDER BY name");
$members = $members_stmt->fetchAll();

// Get selected member and month
$selected_member_id = intval($_GET['member_id'] ?? 0);
$selected_month = $_GET['month'] ?? date('Y-m');

$selected_member = null;
if ($selected_member_id) {
    $selected_member = getMemberById($selected_member_id, $pdo);
    if (!$selected_member) {
        $selected_member_id = 0;
    }
}

// Get member's payment history for trends (if member selected)
$member_trends = [];
$member_all_payments = [];
if ($selected_member_id) {
    $member_all_payments = getMemberPayments($selected_member_id, $pdo);
    
    // Generate 6-month trend data
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $month_payments = array_filter($member_all_payments, function($payment) use ($month) {
            return date('Y-m', strtotime($payment['payment_date'])) === $month;
        });
        
        $member_trends[] = [
            'month' => $month,
            'month_name' => date('M y', strtotime($month . '-01')),
            'count' => count($month_payments),
            'total' => array_sum(array_column($month_payments, 'amount'))
        ];
    }
}

// Filter payments for selected month and member
$monthly_payments = [];
if ($selected_member_id) {
    $monthly_payments = array_filter($member_all_payments, function($payment) use ($selected_month) {
        return date('Y-m', strtotime($payment['payment_date'])) === $selected_month;
    });
    
    // Sort by date descending
    usort($monthly_payments, function($a, $b) {
        return strtotime($b['payment_date']) - strtotime($a['payment_date']);
    });
}

$total_monthly_amount = array_sum(array_column($monthly_payments, 'amount'));
$month_name = date('F Y', strtotime($selected_month . '-01'));
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2>Member Monthly Payment Tracker</h2>
            <p class="text-muted-foreground">Track individual member payments by month</p>
        </div>
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
            <div class="flex items-center gap-2">
                <form method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="member_id" value="<?php echo $selected_member_id; ?>">
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
    </div>

    <!-- Member Selection -->
    <div class="bg-card rounded-lg border border shadow-sm p-6">
        <h3 class="mb-4">Select Member to Track</h3>
        
        <form method="GET" class="space-y-3">
            <input type="hidden" name="month" value="<?php echo htmlspecialchars($selected_month); ?>">
            
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <select 
                        name="member_id"
                        onchange="this.form.submit()"
                        class="w-full px-3 py-3 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                    >
                        <option value="">Choose a member to track...</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?php echo $member['id']; ?>" <?php echo $selected_member_id == $member['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($member['name'] . ' - ' . $member['profession'] . ' - ' . $member['email']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if ($selected_member_id): ?>
                    <a href="?" class="px-4 py-2 bg-secondary text-secondary-foreground rounded-lg hover:bg-secondary/80 transition-colors flex items-center">
                        Clear Selection
                    </a>
                <?php endif; ?>
            </div>
        </form>
        
        <?php if ($selected_member): ?>
            <div class="mt-4 flex items-center space-x-3 p-4 bg-muted/30 rounded-lg">
                <?php if ($selected_member['image_url']): ?>
                    <img
                        src="../<?php echo htmlspecialchars($selected_member['image_url']); ?>"
                        alt="<?php echo htmlspecialchars($selected_member['name']); ?>"
                        class="w-12 h-12 rounded-full object-cover"
                    />
                <?php else: ?>
                    <div class="w-12 h-12 bg-gradient-to-br from-primary/20 to-primary/40 rounded-full flex items-center justify-center text-primary font-medium">
                        <?php echo getMemberInitials($selected_member['name']); ?>
                    </div>
                <?php endif; ?>
                <div class="flex-1">
                    <p class="font-medium"><?php echo htmlspecialchars($selected_member['name']); ?></p>
                    <p class="text-sm text-muted-foreground"><?php echo htmlspecialchars($selected_member['profession']); ?> • <?php echo htmlspecialchars($selected_member['email']); ?></p>
                    <p class="text-sm text-muted-foreground">Member since <?php echo formatDate($selected_member['membership_date']); ?></p>
                </div>
                <div class="text-right">
                    <a href="../members/view.php?id=<?php echo $selected_member['id']; ?>" class="text-primary hover:text-primary/80 transition-colors text-sm">
                        View Full Profile →
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($selected_member): ?>
        <!-- Member Payment Trends -->
        <?php if (count(array_filter($member_trends, function($t) { return $t['count'] > 0; })) > 1): ?>
            <div class="bg-card rounded-lg border border shadow-sm p-6">
                <h3 class="mb-4">Payment History Trend for <?php echo htmlspecialchars($selected_member['name']); ?></h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <?php foreach ($member_trends as $trend): ?>
                        <a href="?member_id=<?php echo $selected_member_id; ?>&month=<?php echo $trend['month']; ?>" class="p-4 rounded-lg border border text-center transition-all hover:shadow-md <?php echo $trend['month'] === $selected_month ? 'bg-primary/10 border-primary' : 'bg-muted/20 hover:bg-muted/30'; ?>">
                            <p class="text-sm font-medium"><?php echo $trend['month_name']; ?></p>
                            <p class="text-lg font-bold"><?php echo formatCurrency($trend['total']); ?></p>
                            <p class="text-xs text-muted-foreground"><?php echo $trend['count']; ?> payments</p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Monthly Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-card rounded-lg border border p-6 shadow-sm">
                <h3 class="text-sm text-muted-foreground mb-2">Payments in <?php echo $month_name; ?></h3>
                <div class="text-2xl mb-1"><?php echo count($monthly_payments); ?></div>
                <p class="text-xs text-muted-foreground">Transactions by <?php echo htmlspecialchars($selected_member['name']); ?></p>
            </div>
            <div class="bg-card rounded-lg border border p-6 shadow-sm">
                <h3 class="text-sm text-muted-foreground mb-2">Total Amount</h3>
                <div class="text-2xl mb-1"><?php echo formatCurrency($total_monthly_amount); ?></div>
                <p class="text-xs text-muted-foreground">Paid in <?php echo $month_name; ?></p>
            </div>
            <div class="bg-card rounded-lg border border p-6 shadow-sm">
                <h3 class="text-sm text-muted-foreground mb-2">Average Payment</h3>
                <div class="text-2xl mb-1">
                    <?php echo count($monthly_payments) > 0 ? formatCurrency($total_monthly_amount / count($monthly_payments)) : '₵0'; ?>
                </div>
                <p class="text-xs text-muted-foreground">Per transaction</p>
            </div>
            <div class="bg-card rounded-lg border border p-6 shadow-sm">
                <h3 class="text-sm text-muted-foreground mb-2">Payment Types</h3>
                <div class="text-2xl mb-1"><?php echo count(array_unique(array_column($monthly_payments, 'type'))); ?></div>
                <p class="text-xs text-muted-foreground">Different types used</p>
            </div>
        </div>

        <!-- Payment Details -->
        <?php if (!empty($monthly_payments)): ?>
            <div class="bg-card rounded-lg border border shadow-sm">
                <div class="p-6 border-b border">
                    <h3><?php echo htmlspecialchars($selected_member['name']); ?>'s Payments in <?php echo $month_name; ?></h3>
                    <p class="text-sm text-muted-foreground">
                        <?php echo count($monthly_payments); ?> payments totaling <?php echo formatCurrency($total_monthly_amount); ?>
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="text-left p-4 border-b border font-medium">Date</th>
                                <th class="text-left p-4 border-b border font-medium">Payment Type</th>
                                <th class="text-left p-4 border-b border font-medium">Method</th>
                                <th class="text-right p-4 border-b border font-medium">Amount</th>
                                <th class="text-left p-4 border-b border font-medium">Description</th>
                                <th class="text-center p-4 border-b border font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_payments as $index => $payment): ?>
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
                                    <td class="p-4 border-b border text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <a href="../payments/edit.php?id=<?php echo $payment['id']; ?>&return=tracker&member_id=<?php echo $selected_member_id; ?>&month=<?php echo $selected_month; ?>" class="p-2 text-muted-foreground hover:text-primary hover:bg-accent rounded-lg transition-colors" title="Edit Payment">
                                                ✏️
                                            </a>
                                            <a href="../payments/delete.php?id=<?php echo $payment['id']; ?>&return=tracker&member_id=<?php echo $selected_member_id; ?>&month=<?php echo $selected_month; ?>" class="p-2 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded-lg transition-colors" title="Delete Payment" onclick="return confirm('Are you sure you want to delete this payment?')">
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
                                    Total (<?php echo count($monthly_payments); ?> payments)
                                </td>
                                <td class="p-4 border-t border text-right font-bold text-lg">
                                    <?php echo formatCurrency($total_monthly_amount); ?>
                                </td>
                                <td colspan="2" class="p-4 border-t border"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-card rounded-lg border border shadow-sm p-12 text-center">
                <div class="text-6xl mb-4">📅</div>
                <h3 class="text-lg mb-2">No Payments in <?php echo $month_name; ?></h3>
                <p class="text-muted-foreground mb-4">
                    <?php echo htmlspecialchars($selected_member['name']); ?> made no payments during <?php echo $month_name; ?>
                </p>
                <a href="../payments/add.php?member_id=<?php echo $selected_member_id; ?>" class="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors">
                    Add Payment for <?php echo htmlspecialchars($selected_member['name']); ?>
                </a>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- No member selected state -->
        <div class="bg-card rounded-lg border border shadow-sm p-12 text-center">
            <div class="text-6xl mb-4">👤</div>
            <h3 class="text-lg mb-2">Select a Member</h3>
            <p class="text-muted-foreground">
                Choose a member from the dropdown above to view their monthly payment tracking and trends
            </p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>