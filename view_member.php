<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Member.php';
require_once 'classes/Payment.php';

$memberClass = new Member($pdo);
$paymentClass = new Payment($pdo);

// Get member ID
$member_id = $_GET['id'] ?? null;
if (!$member_id) {
    header('Location: members.php');
    exit();
}

// Get member details
$member = $memberClass->getById($member_id);
if (!$member) {
    header('Location: members.php?error=' . urlencode('Member not found'));
    exit();
}

// Get active tab
$activeTab = $_GET['tab'] ?? 'overview';

// Get member payments
$memberPayments = $paymentClass->getByMemberId($member_id);
$totalDonations = array_sum(array_column($memberPayments, 'amount'));
$avgDonation = count($memberPayments) > 0 ? $totalDonations / count($memberPayments) : 0;

// Get unique payment types
$paymentTypes = array_unique(array_column($memberPayments, 'payment_type'));

// For payments tab - handle sorting and filtering
$sortField = $_GET['sort'] ?? 'date';
$sortDirection = $_GET['dir'] ?? 'desc';
$filterType = $_GET['type'] ?? 'all';

// Filter payments
$filteredPayments = $memberPayments;
if ($filterType !== 'all') {
    $filteredPayments = array_filter($memberPayments, function($p) use ($filterType) {
        return $p['payment_type'] === $filterType;
    });
}

// Sort payments
usort($filteredPayments, function($a, $b) use ($sortField, $sortDirection) {
    if ($sortField === 'date') {
        $aVal = strtotime($a['payment_date']);
        $bVal = strtotime($b['payment_date']);
    } elseif ($sortField === 'amount') {
        $aVal = $a['amount'];
        $bVal = $b['amount'];
    } else {
        $aVal = $a['payment_type'];
        $bVal = $b['payment_type'];
    }
    
    if ($sortDirection === 'asc') {
        return $aVal <=> $bVal;
    } else {
        return $bVal <=> $aVal;
    }
});

// For monthly tracker tab
$selectedMonth = $_GET['month'] ?? date('Y-m');
list($year, $month) = explode('-', $selectedMonth);
$monthlyPayments = $paymentClass->getMemberMonthlyPayments($member_id, $year, $month);
$totalMonthlyAmount = array_sum(array_column($monthlyPayments, 'amount'));

// Get 6-month trends
$trends = $paymentClass->getMemberPaymentTrends($member_id, 6);

// Payment method icons
$paymentMethods = [
    'Cash' => '💵',
    'Mobile Money' => '📱',
    'Bank Transfer' => '🏦',
    'Check' => '📄',
    'Card' => '💳',
    'Other' => '🔄'
];

$page_title = $member['name'] . ' - Church Management System';
include 'includes/header.php';
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="members.php" class="flex items-center gap-2 px-3 py-2 rounded-lg transition-colors" style="color: var(--primary);" onmouseover="this.style.background='var(--accent)';" onmouseout="this.style.background='transparent';">
            ← Back to Members
        </a>
        
        <div class="flex items-center gap-3">
            <a href="payments.php?add=1&member=<?php echo $member['id']; ?>" class="px-4 py-2 rounded-lg transition-colors flex items-center gap-2" style="background: var(--primary); color: var(--primary-foreground);" onmouseover="this.style.background='color-mix(in srgb, var(--primary) 90%, transparent)';" onmouseout="this.style.background='var(--primary)';">
                <span>+</span> Add Payment
            </a>
        </div>
    </div>
    
    <!-- Navigation Tabs -->
    <div class="rounded-lg border shadow-sm" style="background: var(--card); border-color: var(--border);">
        <div class="flex border-b" style="border-color: var(--border);">
            <a href="?id=<?php echo $member['id']; ?>&tab=overview" class="px-6 py-3 transition-colors" style="font-weight: var(--font-weight-medium); <?php echo $activeTab === 'overview' ? 'color: var(--primary); border-bottom: 2px solid var(--primary);' : 'color: var(--muted-foreground);'; ?>" onmouseover="<?php echo $activeTab !== 'overview' ? 'this.style.color=\'var(--foreground)\';' : ''; ?>" onmouseout="<?php echo $activeTab !== 'overview' ? 'this.style.color=\'var(--muted-foreground)\';' : ''; ?>">
                📋 Overview
            </a>
            <a href="?id=<?php echo $member['id']; ?>&tab=payments" class="px-6 py-3 transition-colors" style="font-weight: var(--font-weight-medium); <?php echo $activeTab === 'payments' ? 'color: var(--primary); border-bottom: 2px solid var(--primary);' : 'color: var(--muted-foreground);'; ?>" onmouseover="<?php echo $activeTab !== 'payments' ? 'this.style.color=\'var(--foreground)\';' : ''; ?>" onmouseout="<?php echo $activeTab !== 'payments' ? 'this.style.color=\'var(--muted-foreground)\';' : ''; ?>">
                💰 Payment History
            </a>
            <a href="?id=<?php echo $member['id']; ?>&tab=monthly" class="px-6 py-3 transition-colors" style="font-weight: var(--font-weight-medium); <?php echo $activeTab === 'monthly' ? 'color: var(--primary); border-bottom: 2px solid var(--primary);' : 'color: var(--muted-foreground);'; ?>" onmouseover="<?php echo $activeTab !== 'monthly' ? 'this.style.color=\'var(--foreground)\';' : ''; ?>" onmouseout="<?php echo $activeTab !== 'monthly' ? 'this.style.color=\'var(--muted-foreground)\';' : ''; ?>">
                📅 Monthly Tracker
            </a>
        </div>
    </div>

    <?php if ($activeTab === 'overview'): ?>
        <!-- Member Profile Header -->
        <div class="rounded-lg border p-6" style="background: linear-gradient(to right, color-mix(in srgb, var(--primary) 5%, transparent), color-mix(in srgb, var(--primary) 10%, transparent)); border-color: var(--border);">
            <div class="flex items-center space-x-6">
                <?php if (!empty($member['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($member['image_url']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>" class="w-20 h-20 rounded-full object-cover" style="border: 4px solid color-mix(in srgb, var(--primary) 20%, transparent);" />
                <?php else: ?>
                    <div class="w-20 h-20 rounded-full flex items-center justify-center text-2xl" style="background: linear-gradient(to bottom right, color-mix(in srgb, var(--primary) 20%, transparent), color-mix(in srgb, var(--primary) 40%, transparent)); color: var(--primary); font-weight: var(--font-weight-medium);">
                        <?php echo strtoupper(substr($member['name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <div class="flex-1">
                    <h2 class="text-2xl mb-2"><?php echo htmlspecialchars($member['name']); ?></h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                        <div>
                            <label style="color: var(--muted-foreground);">Email</label>
                            <p><?php echo htmlspecialchars($member['email']); ?></p>
                        </div>
                        <div>
                            <label style="color: var(--muted-foreground);">Phone</label>
                            <p><?php echo htmlspecialchars($member['phone']); ?></p>
                        </div>
                        <div>
                            <label style="color: var(--muted-foreground);">Profession</label>
                            <p><?php echo htmlspecialchars($member['profession']); ?></p>
                        </div>
                        <div>
                            <label style="color: var(--muted-foreground);">Status</label>
                            <span class="inline-block px-3 py-1 text-xs rounded-full" style="font-weight: var(--font-weight-medium); <?php echo $member['status'] === 'active' ? 'background: #dcfce7; color: #166534;' : 'background: var(--muted); color: var(--muted-foreground);'; ?>">
                                <?php echo $member['status']; ?>
                            </span>
                        </div>
                        <div>
                            <label style="color: var(--muted-foreground);">Member Since</label>
                            <p><?php echo date('F j, Y', strtotime($member['membership_date'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t" style="border-color: var(--border);">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="text-center">
                        <p class="text-2xl" style="color: var(--primary); font-weight: var(--font-weight-medium);"><?php echo count($memberPayments); ?></p>
                        <p class="text-sm" style="color: var(--muted-foreground);">Total Payments</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl" style="color: var(--primary); font-weight: var(--font-weight-medium);">₵<?php echo number_format($totalDonations); ?></p>
                        <p class="text-sm" style="color: var(--muted-foreground);">Total Donated</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl" style="color: var(--primary); font-weight: var(--font-weight-medium);">₵<?php echo number_format($avgDonation, 0); ?></p>
                        <p class="text-sm" style="color: var(--muted-foreground);">Average Payment</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl" style="color: var(--primary); font-weight: var(--font-weight-medium);"><?php echo count($paymentTypes); ?></p>
                        <p class="text-sm" style="color: var(--muted-foreground);">Payment Types</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Address Card -->
        <div class="rounded-lg border shadow-sm p-6" style="background: var(--card); border-color: var(--border);">
            <h3 class="mb-3">Address Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm" style="color: var(--muted-foreground);">Digital Address (GPS)</label>
                    <p style="font-weight: var(--font-weight-medium);"><?php echo htmlspecialchars($member['digital_address']) ?: 'Not provided'; ?></p>
                </div>
                <div>
                    <label class="text-sm" style="color: var(--muted-foreground);">House Address</label>
                    <p style="font-weight: var(--font-weight-medium);"><?php echo htmlspecialchars($member['house_address']); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($activeTab === 'payments'): ?>
        <!-- Payment History - Excel Style Table -->
        <div class="rounded-lg border shadow-sm" style="background: var(--card); border-color: var(--border);">
            <div class="p-6 border-b" style="border-color: var(--border);">
                <div class="flex items-center justify-between mb-4">
                    <h3>Payment History (<?php echo count($filteredPayments); ?> payments)</h3>
                    
                    <div class="flex items-center gap-4">
                        <select onchange="window.location.href='?id=<?php echo $member['id']; ?>&tab=payments&type='+this.value+'&sort=<?php echo $sortField; ?>&dir=<?php echo $sortDirection; ?>'" class="px-3 py-2 border rounded-lg text-sm" style="background: var(--input-background); border-color: var(--border); color: var(--foreground);" onfocus="this.style.borderColor='var(--ring)'; this.style.outline='2px solid color-mix(in srgb, var(--ring) 50%, transparent)';" onblur="this.style.borderColor='var(--border)'; this.style.outline='none';">
                            <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <?php foreach (array_unique(array_column($memberPayments, 'payment_type')) as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filterType === $type ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <?php if (count($filteredPayments) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead style="background: color-mix(in srgb, var(--muted) 50%, transparent);">
                            <tr>
                                <th class="text-left p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">
                                    <a href="?id=<?php echo $member['id']; ?>&tab=payments&type=<?php echo $filterType; ?>&sort=date&dir=<?php echo ($sortField === 'date' && $sortDirection === 'asc') ? 'desc' : 'asc'; ?>" class="flex items-center gap-2 transition-colors" style="color: var(--foreground);" onmouseover="this.style.color='var(--primary)';" onmouseout="this.style.color='var(--foreground)';">
                                        Date <?php echo $sortField === 'date' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕️'; ?>
                                    </a>
                                </th>
                                <th class="text-left p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">
                                    <a href="?id=<?php echo $member['id']; ?>&tab=payments&type=<?php echo $filterType; ?>&sort=type&dir=<?php echo ($sortField === 'type' && $sortDirection === 'asc') ? 'desc' : 'asc'; ?>" class="flex items-center gap-2 transition-colors" style="color: var(--foreground);" onmouseover="this.style.color='var(--primary)';" onmouseout="this.style.color='var(--foreground)';">
                                        Type <?php echo $sortField === 'type' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕️'; ?>
                                    </a>
                                </th>
                                <th class="text-left p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">Method</th>
                                <th class="text-right p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">
                                    <a href="?id=<?php echo $member['id']; ?>&tab=payments&type=<?php echo $filterType; ?>&sort=amount&dir=<?php echo ($sortField === 'amount' && $sortDirection === 'asc') ? 'desc' : 'asc'; ?>" class="flex items-center justify-end gap-2 transition-colors" style="color: var(--foreground);" onmouseover="this.style.color='var(--primary)';" onmouseout="this.style.color='var(--foreground)';">
                                        Amount <?php echo $sortField === 'amount' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕️'; ?>
                                    </a>
                                </th>
                                <th class="text-left p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">Description</th>
                                <th class="text-center p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filteredPayments as $index => $payment): ?>
                                <tr class="transition-colors" style="<?php echo $index % 2 === 0 ? 'background: var(--background);' : 'background: color-mix(in srgb, var(--muted) 20%, transparent);'; ?>" onmouseover="this.style.background='color-mix(in srgb, var(--accent) 50%, transparent)';" onmouseout="this.style.background='<?php echo $index % 2 === 0 ? 'var(--background)' : 'color-mix(in srgb, var(--muted) 20%, transparent)'; ?>';">
                                    <td class="p-4 border-b" style="border-color: var(--border);">
                                        <div style="font-weight: var(--font-weight-medium);">
                                            <?php echo date('M j, Y', strtotime($payment['payment_date'])); ?>
                                        </div>
                                        <div class="text-xs" style="color: var(--muted-foreground);">
                                            <?php echo date('l', strtotime($payment['payment_date'])); ?>
                                        </div>
                                    </td>
                                    <td class="p-4 border-b" style="border-color: var(--border);">
                                        <span class="inline-block px-2 py-1 rounded text-sm" style="background: color-mix(in srgb, var(--primary) 10%, transparent); color: var(--primary); font-weight: var(--font-weight-medium);">
                                            <?php echo htmlspecialchars($payment['payment_type']); ?>
                                        </span>
                                    </td>
                                    <td class="p-4 border-b" style="border-color: var(--border);">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-sm" style="background: color-mix(in srgb, var(--secondary) 50%, transparent); color: var(--secondary-foreground);">
                                            <?php echo $paymentMethods[$payment['payment_method']] ?? '🔄'; ?>
                                            <?php echo htmlspecialchars($payment['payment_method']); ?>
                                        </span>
                                    </td>
                                    <td class="p-4 border-b text-right" style="border-color: var(--border);">
                                        <span class="text-lg" style="font-weight: var(--font-weight-medium);">₵<?php echo number_format($payment['amount']); ?></span>
                                    </td>
                                    <td class="p-4 border-b" style="border-color: var(--border);">
                                        <span style="color: var(--muted-foreground);">
                                            <?php echo htmlspecialchars($payment['description']) ?: '—'; ?>
                                        </span>
                                    </td>
                                    <td class="p-4 border-b text-center" style="border-color: var(--border);">
                                        <div class="flex items-center justify-center space-x-2">
                                            <a href="payments.php?edit=<?php echo $payment['id']; ?>" class="p-2 rounded-lg transition-colors" style="color: var(--muted-foreground);" onmouseover="this.style.color='var(--primary)'; this.style.background='var(--accent)';" onmouseout="this.style.color='var(--muted-foreground)'; this.style.background='transparent';" title="Edit Payment">
                                                ✏️
                                            </a>
                                            <button onclick="if(confirm('Are you sure you want to delete this payment?')) { window.location.href='?id=<?php echo $member['id']; ?>&tab=payments&delete=<?php echo $payment['id']; ?>'; }" class="p-2 rounded-lg transition-colors" style="color: var(--muted-foreground);" onmouseover="this.style.color='var(--destructive)'; this.style.background='color-mix(in srgb, var(--destructive) 10%, transparent)';" onmouseout="this.style.color='var(--muted-foreground)'; this.style.background='transparent';" title="Delete Payment">
                                                🗑
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot style="background: color-mix(in srgb, var(--muted) 30%, transparent);">
                            <tr>
                                <td colspan="3" class="p-4 border-t" style="border-color: var(--border); font-weight: var(--font-weight-medium);">
                                    Total (<?php echo count($filteredPayments); ?> payments)
                                </td>
                                <td class="p-4 border-t text-right text-lg" style="border-color: var(--border); font-weight: var(--font-weight-medium);">
                                    ₵<?php echo number_format(array_sum(array_column($filteredPayments, 'amount'))); ?>
                                </td>
                                <td colspan="2" class="p-4 border-t" style="border-color: var(--border);"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-12 text-center">
                    <div class="text-6xl mb-4">💰</div>
                    <h3 class="text-lg mb-2">No payments found</h3>
                    <p style="color: var(--muted-foreground);" class="mb-4">
                        <?php echo $filterType !== 'all' ? 'No payments match the selected filter' : 'This member has no payment records yet'; ?>
                    </p>
                    <a href="payments.php?add=1&member=<?php echo $member['id']; ?>" class="px-4 py-2 rounded-lg transition-colors inline-block" style="background: var(--primary); color: var(--primary-foreground);" onmouseover="this.style.background='color-mix(in srgb, var(--primary) 90%, transparent)';" onmouseout="this.style.background='var(--primary)';">
                        Add First Payment
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($activeTab === 'monthly'): ?>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h3><?php echo htmlspecialchars($member['name']); ?>'s Monthly Payment Tracker</h3>
                    <p style="color: var(--muted-foreground);">Track monthly payment patterns and trends</p>
                </div>
                
                <div class="flex items-center gap-2">
                    <label for="monthSelect" class="text-sm" style="color: var(--muted-foreground);">Month:</label>
                    <input id="monthSelect" type="month" value="<?php echo $selectedMonth; ?>" onchange="window.location.href='?id=<?php echo $member['id']; ?>&tab=monthly&month='+this.value" class="px-3 py-2 border rounded-lg" style="background: var(--input-background); border-color: var(--border); color: var(--foreground);" onfocus="this.style.borderColor='var(--ring)'; this.style.outline='2px solid color-mix(in srgb, var(--ring) 50%, transparent)';" onblur="this.style.borderColor='var(--border)'; this.style.outline='none';" />
                </div>
            </div>

            <!-- Payment Trends -->
            <?php if (count($trends) > 1): ?>
                <div class="rounded-lg border shadow-sm p-6" style="background: var(--card); border-color: var(--border);">
                    <h4 class="mb-4">6-Month Payment Trend</h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <?php foreach ($trends as $trend): ?>
                            <?php 
                            $trendMonth = sprintf('%04d-%02d', $trend['year'], $trend['month']);
                            $monthLabel = date('M \'y', strtotime($trendMonth . '-01'));
                            $isSelected = $trendMonth === $selectedMonth;
                            ?>
                            <a href="?id=<?php echo $member['id']; ?>&tab=monthly&month=<?php echo $trendMonth; ?>" class="p-4 rounded-lg border text-center transition-all cursor-pointer" style="<?php echo $isSelected ? 'background: color-mix(in srgb, var(--primary) 10%, transparent); border-color: var(--primary); box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);' : 'background: color-mix(in srgb, var(--muted) 20%, transparent); border-color: var(--border);'; ?>" onmouseover="<?php echo !$isSelected ? 'this.style.background=\'color-mix(in srgb, var(--muted) 30%, transparent)\';' : ''; ?>" onmouseout="<?php echo !$isSelected ? 'this.style.background=\'color-mix(in srgb, var(--muted) 20%, transparent)\';' : ''; ?>">
                                <p class="text-sm" style="font-weight: var(--font-weight-medium);"><?php echo $monthLabel; ?></p>
                                <p class="text-lg" style="font-weight: var(--font-weight-medium);"><?php echo $trend['total_amount'] > 0 ? '₵' . number_format($trend['total_amount']) : '₵0'; ?></p>
                                <p class="text-xs" style="color: var(--muted-foreground);"><?php echo $trend['payment_count']; ?> payments</p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Monthly Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="rounded-lg border p-6 shadow-sm" style="background: var(--card); border-color: var(--border);">
                    <h4 class="text-sm mb-2" style="color: var(--muted-foreground);">Payments in <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?></h4>
                    <div class="text-2xl mb-1"><?php echo count($monthlyPayments); ?></div>
                    <p class="text-xs" style="color: var(--muted-foreground);">Transactions</p>
                </div>
                <div class="rounded-lg border p-6 shadow-sm" style="background: var(--card); border-color: var(--border);">
                    <h4 class="text-sm mb-2" style="color: var(--muted-foreground);">Total Amount</h4>
                    <div class="text-2xl mb-1">₵<?php echo number_format($totalMonthlyAmount); ?></div>
                    <p class="text-xs" style="color: var(--muted-foreground);">Total contributions</p>
                </div>
                <div class="rounded-lg border p-6 shadow-sm" style="background: var(--card); border-color: var(--border);">
                    <h4 class="text-sm mb-2" style="color: var(--muted-foreground);">Average Payment</h4>
                    <div class="text-2xl mb-1">
                        ₵<?php echo count($monthlyPayments) > 0 ? number_format($totalMonthlyAmount / count($monthlyPayments), 0) : '0'; ?>
                    </div>
                    <p class="text-xs" style="color: var(--muted-foreground);">Per transaction</p>
                </div>
                <div class="rounded-lg border p-6 shadow-sm" style="background: var(--card); border-color: var(--border);">
                    <h4 class="text-sm mb-2" style="color: var(--muted-foreground);">Payment Types</h4>
                    <div class="text-2xl mb-1"><?php echo count(array_unique(array_column($monthlyPayments, 'payment_type'))); ?></div>
                    <p class="text-xs" style="color: var(--muted-foreground);">Different types</p>
                </div>
            </div>

            <!-- Payment Details Table -->
            <?php if (count($monthlyPayments) > 0): ?>
                <div class="rounded-lg border shadow-sm" style="background: var(--card); border-color: var(--border);">
                    <div class="p-6 border-b" style="border-color: var(--border);">
                        <h4>Payments in <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?></h4>
                        <p class="text-sm" style="color: var(--muted-foreground);">
                            <?php echo count($monthlyPayments); ?> payments totaling ₵<?php echo number_format($totalMonthlyAmount); ?>
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead style="background: color-mix(in srgb, var(--muted) 50%, transparent);">
                                <tr>
                                    <th class="text-left p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">Date</th>
                                    <th class="text-left p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">Type</th>
                                    <th class="text-left p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">Method</th>
                                    <th class="text-right p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">Amount</th>
                                    <th class="text-left p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthlyPayments as $index => $payment): ?>
                                    <tr class="transition-colors" style="<?php echo $index % 2 === 0 ? 'background: var(--background);' : 'background: color-mix(in srgb, var(--muted) 20%, transparent);'; ?>" onmouseover="this.style.background='color-mix(in srgb, var(--accent) 50%, transparent)';" onmouseout="this.style.background='<?php echo $index % 2 === 0 ? 'var(--background)' : 'color-mix(in srgb, var(--muted) 20%, transparent)'; ?>';">
                                        <td class="p-4 border-b" style="border-color: var(--border);">
                                            <div style="font-weight: var(--font-weight-medium);">
                                                <?php echo date('M j, Y', strtotime($payment['payment_date'])); ?>
                                            </div>
                                            <div class="text-xs" style="color: var(--muted-foreground);">
                                                <?php echo date('l', strtotime($payment['payment_date'])); ?>
                                            </div>
                                        </td>
                                        <td class="p-4 border-b" style="border-color: var(--border);">
                                            <span class="inline-block px-2 py-1 rounded text-sm" style="background: color-mix(in srgb, var(--primary) 10%, transparent); color: var(--primary); font-weight: var(--font-weight-medium);">
                                                <?php echo htmlspecialchars($payment['payment_type']); ?>
                                            </span>
                                        </td>
                                        <td class="p-4 border-b" style="border-color: var(--border);">
                                            <span class="inline-flex items-center gap-1 text-sm">
                                                <?php echo $paymentMethods[$payment['payment_method']] ?? '🔄'; ?>
                                                <?php echo htmlspecialchars($payment['payment_method']); ?>
                                            </span>
                                        </td>
                                        <td class="p-4 border-b text-right" style="border-color: var(--border);">
                                            <span class="text-lg" style="font-weight: var(--font-weight-medium);">₵<?php echo number_format($payment['amount']); ?></span>
                                        </td>
                                        <td class="p-4 border-b" style="border-color: var(--border);">
                                            <span class="text-sm" style="color: var(--muted-foreground);">
                                                <?php echo htmlspecialchars($payment['description']) ?: '—'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot style="background: color-mix(in srgb, var(--muted) 30%, transparent);">
                                <tr>
                                    <td colspan="3" class="p-4 border-t" style="border-color: var(--border); font-weight: var(--font-weight-medium);">
                                        Total (<?php echo count($monthlyPayments); ?> payments)
                                    </td>
                                    <td class="p-4 border-t text-right text-lg" style="border-color: var(--border); font-weight: var(--font-weight-medium);">
                                        ₵<?php echo number_format($totalMonthlyAmount); ?>
                                    </td>
                                    <td class="p-4 border-t" style="border-color: var(--border);"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="rounded-lg border shadow-sm p-12 text-center" style="background: var(--card); border-color: var(--border);">
                    <div class="text-6xl mb-4">📅</div>
                    <h4 class="text-lg mb-2">No Payments in <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?></h4>
                    <p style="color: var(--muted-foreground);">
                        <?php echo htmlspecialchars($member['name']); ?> made no payments during <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Handle delete payment
if (isset($_GET['delete'])) {
    $paymentClass->delete($_GET['delete']);
    header('Location: view_member.php?id=' . $member_id . '&tab=payments');
    exit();
}

include 'includes/footer.php';
?>