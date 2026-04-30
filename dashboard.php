<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Member.php';
require_once 'classes/Payment.php';

$memberClass = new Member($pdo);
$paymentClass = new Payment($pdo);

// Get statistics
$totalMembers = $memberClass->getCount();
$allPayments = $paymentClass->getAll();
$totalDonations = array_sum(array_column($allPayments, 'amount'));

// Get this month's payments
$thisMonthPayments = array_filter($allPayments, function($payment) {
    $paymentDate = new DateTime($payment['payment_date']);
    $now = new DateTime();
    return $paymentDate->format('Y-m') === $now->format('Y-m');
});
$thisMonthCount = count($thisMonthPayments);

// Calculate average
$avgDonation = count($allPayments) > 0 ? $totalDonations / count($allPayments) : 0;

// Get recent members and payments
$recentMembers = $memberClass->getAll('', 'all', 1, 5);
$recentPayments = array_slice($allPayments, -5);
array_reverse($recentPayments); // Most recent first

$page_title = 'Dashboard - Church Management System';
include 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/enhanced-components.css">

<div class="page-content space-y-6">
    <div>
        <h2>Church Dashboard</h2>
        <p style="color: var(--muted-foreground);">Overview of church activities and finances</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="stats-card">
            <h3 class="text-sm mb-2" style="color: var(--muted-foreground); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Total Members</h3>
            <div class="stats-value"><?php echo $totalMembers; ?></div>
            <p class="text-xs" style="color: var(--muted-foreground);">Active church members</p>
        </div>
        
        <div class="stats-card">
            <h3 class="text-sm mb-2" style="color: var(--muted-foreground); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Total Donations</h3>
            <div class="stats-value currency-display">₵<?php echo number_format($totalDonations); ?></div>
            <p class="text-xs" style="color: var(--muted-foreground);">All time donations</p>
        </div>
        
        <div class="stats-card">
            <h3 class="text-sm mb-2" style="color: var(--muted-foreground); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">This Month</h3>
            <div class="stats-value"><?php echo $thisMonthCount; ?></div>
            <p class="text-xs" style="color: var(--muted-foreground);">Payments this month</p>
        </div>
        
        <div class="stats-card">
            <h3 class="text-sm mb-2" style="color: var(--muted-foreground); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Average Donation</h3>
            <div class="stats-value currency-display">₵<?php echo number_format($avgDonation, 0); ?></div>
            <p class="text-xs" style="color: var(--muted-foreground);">Per transaction</p>
        </div>
    </div>

    <!-- Recent Members and Payments - Matches React exactly -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Members -->
        <div class="rounded-lg border shadow-sm" style="background: var(--card); border-color: var(--border);">
            <div class="p-6 border-b" style="border-color: var(--border);">
                <h3>Recent Members</h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <?php if (count($recentMembers) > 0): ?>
                        <?php foreach ($recentMembers as $member): ?>
                            <div class="flex items-center space-x-3">
                                <?php if (!empty($member['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($member['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($member['name']); ?>"
                                         class="w-8 h-8 rounded-full object-cover">
                                <?php else: ?>
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm"
                                         style="background: var(--muted); color: var(--muted-foreground);">
                                        <?php echo strtoupper(substr($member['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <p><?php echo htmlspecialchars($member['name']); ?></p>
                                    <p class="text-sm" style="color: var(--muted-foreground);"><?php echo htmlspecialchars($member['email']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--muted-foreground);">No members yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="rounded-lg border shadow-sm" style="background: var(--card); border-color: var(--border);">
            <div class="p-6 border-b" style="border-color: var(--border);">
                <h3>Recent Payments</h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <?php if (count($recentPayments) > 0): ?>
                        <?php foreach ($recentPayments as $payment): ?>
                            <?php
                            $member = $memberClass->getById($payment['member_id']);
                            $memberName = $member ? $member['name'] : 'Unknown';
                            ?>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p><?php echo htmlspecialchars($memberName); ?></p>
                                    <p class="text-sm" style="color: var(--muted-foreground);"><?php echo htmlspecialchars($payment['payment_type']); ?></p>
                                </div>
                                <div class="text-right">
                                    <p>₵<?php echo number_format($payment['amount']); ?></p>
                                    <p class="text-sm" style="color: var(--muted-foreground);">
                                        <?php echo date('M j, Y', strtotime($payment['payment_date'])); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--muted-foreground);">No payments yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>