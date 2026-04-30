<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Member.php';
require_once 'classes/Payment.php';

$memberClass = new Member($pdo);
$paymentClass = new Payment($pdo);

// Get selected month and member
$selectedMonth = $_GET['month'] ?? date('Y-m');
$selectedMemberId = $_GET['member'] ?? '';

list($year, $month) = explode('-', $selectedMonth);

// Get all members for selection
$allMembers = $memberClass->getAll();
$selectedMember = null;

if ($selectedMemberId) {
    $selectedMember = $memberClass->getById($selectedMemberId);
}

// Get monthly payments for selected member
$monthlyPayments = [];
$totalMonthlyAmount = 0;
$trends = [];

if ($selectedMember) {
    $monthlyPayments = $paymentClass->getMemberMonthlyPayments($selectedMemberId, $year, $month);
    $totalMonthlyAmount = array_sum(array_column($monthlyPayments, 'amount'));
    $trends = $paymentClass->getMemberPaymentTrends($selectedMemberId, 6);
}

// Payment method icons
$paymentMethods = [
    'Cash' => '💵',
    'Mobile Money' => '📱',
    'Bank Transfer' => '🏦',
    'Check' => '📄',
    'Card' => '💳',
    'Other' => '🔄'
];

$page_title = 'Monthly Payment Tracker - Church Management System';
include 'includes/header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2>Member Monthly Payment Tracker</h2>
            <p style="color: var(--muted-foreground);">Track individual member payments by month</p>
        </div>
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
            <div class="flex items-center gap-2">
                <label for="monthSelect" class="text-sm" style="color: var(--muted-foreground);">Month:</label>
                <input id="monthSelect" type="month" value="<?php echo $selectedMonth; ?>" onchange="window.location.href='?month='+this.value+'&member=<?php echo $selectedMemberId; ?>'" class="px-3 py-2 border rounded-lg" style="background: var(--input-background); border-color: var(--border); color: var(--foreground);" onfocus="this.style.borderColor='var(--ring)'; this.style.outline='2px solid color-mix(in srgb, var(--ring) 50%, transparent)';" onblur="this.style.borderColor='var(--border)'; this.style.outline='none';" />
            </div>
        </div>
    </div>

    <!-- Member Selection -->
    <div class="rounded-lg border shadow-sm p-6" style="background: var(--card); border-color: var(--border);">
        <h3 class="mb-4">Select Member to Track</h3>
        <div class="space-y-3">
            <div class="relative" id="memberSearchContainer">
                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 z-10" style="color: var(--muted-foreground);">
                    🔍
                </span>
                <input
                    type="text"
                    id="memberSearch"
                    placeholder="Search members by name, email, or phone..."
                    class="w-full pl-10 pr-4 py-3 border rounded-lg"
                    style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                    onfocus="this.style.borderColor='var(--ring)'; this.style.outline='2px solid color-mix(in srgb, var(--ring) 50%, transparent)'; showMemberList();"
                    onblur="setTimeout(hideMemberList, 200); this.style.borderColor='var(--border)'; this.style.outline='none';"
                    oninput="filterMembers()"
                />
                
                <!-- Member List Dropdown -->
                <div id="memberList" class="hidden absolute top-full left-0 right-0 mt-1 border rounded-lg shadow-lg z-20 max-h-60 overflow-y-auto" style="background: var(--card); border-color: var(--border);"></div>
            </div>

            <!-- Selected Member Display -->
            <?php if ($selectedMember): ?>
                <div class="flex items-center space-x-3 p-4 rounded-lg" style="background: color-mix(in srgb, var(--muted) 30%, transparent);">
                    <?php if (!empty($selectedMember['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($selectedMember['image_url']); ?>" alt="<?php echo htmlspecialchars($selectedMember['name']); ?>" class="w-12 h-12 rounded-full object-cover" />
                    <?php else: ?>
                        <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: linear-gradient(to bottom right, color-mix(in srgb, var(--primary) 20%, transparent), color-mix(in srgb, var(--primary) 40%, transparent)); color: var(--primary); font-weight: var(--font-weight-medium);">
                            <?php echo strtoupper(substr($selectedMember['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <p style="font-weight: var(--font-weight-medium);"><?php echo htmlspecialchars($selectedMember['name']); ?></p>
                        <p class="text-sm" style="color: var(--muted-foreground);"><?php echo htmlspecialchars($selectedMember['profession']); ?> • <?php echo htmlspecialchars($selectedMember['email']); ?></p>
                        <p class="text-sm" style="color: var(--muted-foreground);">Member since <?php echo date('F j, Y', strtotime($selectedMember['membership_date'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($selectedMember): ?>
        <!-- Member Payment Trends -->
        <?php if (count($trends) > 1): ?>
            <div class="rounded-lg border shadow-sm p-6" style="background: var(--card); border-color: var(--border);">
                <h3 class="mb-4">Payment History Trend for <?php echo htmlspecialchars($selectedMember['name']); ?></h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <?php foreach ($trends as $trend): ?>
                        <?php 
                        $trendMonth = sprintf('%04d-%02d', $trend['year'], $trend['month']);
                        $monthLabel = date('M \'y', strtotime($trendMonth . '-01'));
                        $isSelected = $trendMonth === $selectedMonth;
                        ?>
                        <a href="?month=<?php echo $trendMonth; ?>&member=<?php echo $selectedMemberId; ?>" class="p-4 rounded-lg border text-center transition-all" style="<?php echo $isSelected ? 'background: color-mix(in srgb, var(--primary) 10%, transparent); border-color: var(--primary);' : 'background: color-mix(in srgb, var(--muted) 20%, transparent); border-color: var(--border);'; ?>" onmouseover="<?php echo !$isSelected ? 'this.style.background=\'color-mix(in srgb, var(--muted) 30%, transparent)\';' : ''; ?>" onmouseout="<?php echo !$isSelected ? 'this.style.background=\'color-mix(in srgb, var(--muted) 20%, transparent)\';' : ''; ?>">
                            <p class="text-sm" style="font-weight: var(--font-weight-medium);"><?php echo $monthLabel; ?></p>
                            <p class="text-lg" style="font-weight: var(--font-weight-medium);">₵<?php echo number_format($trend['total_amount']); ?></p>
                            <p class="text-xs" style="color: var(--muted-foreground);"><?php echo $trend['payment_count']; ?> payments</p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Monthly Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="rounded-lg border p-6 shadow-sm" style="background: var(--card); border-color: var(--border);">
                <h3 class="text-sm mb-2" style="color: var(--muted-foreground);">Payments in <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?></h3>
                <div class="text-2xl mb-1"><?php echo count($monthlyPayments); ?></div>
                <p class="text-xs" style="color: var(--muted-foreground);">Transactions by <?php echo htmlspecialchars($selectedMember['name']); ?></p>
            </div>
            <div class="rounded-lg border p-6 shadow-sm" style="background: var(--card); border-color: var(--border);">
                <h3 class="text-sm mb-2" style="color: var(--muted-foreground);">Total Amount</h3>
                <div class="text-2xl mb-1">₵<?php echo number_format($totalMonthlyAmount); ?></div>
                <p class="text-xs" style="color: var(--muted-foreground);">Paid in <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?></p>
            </div>
            <div class="rounded-lg border p-6 shadow-sm" style="background: var(--card); border-color: var(--border);">
                <h3 class="text-sm mb-2" style="color: var(--muted-foreground);">Average Payment</h3>
                <div class="text-2xl mb-1">
                    ₵<?php echo count($monthlyPayments) > 0 ? number_format($totalMonthlyAmount / count($monthlyPayments), 0) : '0'; ?>
                </div>
                <p class="text-xs" style="color: var(--muted-foreground);">Per transaction</p>
            </div>
            <div class="rounded-lg border p-6 shadow-sm" style="background: var(--card); border-color: var(--border);">
                <h3 class="text-sm mb-2" style="color: var(--muted-foreground);">Payment Types</h3>
                <div class="text-2xl mb-1"><?php echo count(array_unique(array_column($monthlyPayments, 'payment_type'))); ?></div>
                <p class="text-xs" style="color: var(--muted-foreground);">Different types used</p>
            </div>
        </div>

        <!-- Payment Details -->
        <?php if (count($monthlyPayments) > 0): ?>
            <div class="rounded-lg border shadow-sm" style="background: var(--card); border-color: var(--border);">
                <div class="p-6 border-b" style="border-color: var(--border);">
                    <h3><?php echo htmlspecialchars($selectedMember['name']); ?>'s Payments in <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?></h3>
                    <p class="text-sm" style="color: var(--muted-foreground);">
                        <?php echo count($monthlyPayments); ?> payments totaling ₵<?php echo number_format($totalMonthlyAmount); ?>
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead style="background: color-mix(in srgb, var(--muted) 50%, transparent);">
                            <tr>
                                <th class="text-left p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">Date</th>
                                <th class="text-left p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">Payment Type</th>
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
                <h3 class="text-lg mb-2">No Payments in <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?></h3>
                <p style="color: var(--muted-foreground);">
                    <?php echo htmlspecialchars($selectedMember['name']); ?> made no payments during <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?>
                </p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="rounded-lg border shadow-sm p-12 text-center" style="background: var(--card); border-color: var(--border);">
            <div class="text-6xl mb-4">👤</div>
            <h3 class="text-lg mb-2">Select a Member</h3>
            <p style="color: var(--muted-foreground);">
                Choose a member from the search above to view their monthly payment tracking
            </p>
        </div>
    <?php endif; ?>
</div>

<script>
const members = <?php echo json_encode($allMembers); ?>;

function filterMembers() {
    const search = document.getElementById('memberSearch').value.toLowerCase();
    
    // Filter members by search term
    let filtered = members.filter(m => m.status === 'active');
    
    if (search) {
        filtered = filtered.filter(m => 
            m.name.toLowerCase().includes(search) ||
            m.email.toLowerCase().includes(search) ||
            m.phone.includes(search)
        );
    }
    
    filtered = filtered.sort((a, b) => a.name.localeCompare(b.name));
    
    const list = document.getElementById('memberList');
    
    if (filtered.length > 0) {
        list.innerHTML = filtered.slice(0, 10).map(m => `
            <button type="button" onclick='selectMember(${m.id})' class="w-full flex items-center space-x-3 p-3 transition-colors" style="color: var(--foreground);" onmouseover="this.style.background='var(--accent)';" onmouseout="this.style.background='transparent';">
                ${m.image_url ? 
                    `<img src="${m.image_url}" alt="${m.name}" class="w-10 h-10 rounded-full object-cover" />` :
                    `<div class="w-10 h-10 rounded-full flex items-center justify-center text-sm" style="background: linear-gradient(to bottom right, color-mix(in srgb, var(--primary) 20%, transparent), color-mix(in srgb, var(--primary) 40%, transparent)); color: var(--primary); font-weight: var(--font-weight-medium);">
                        ${m.name.charAt(0).toUpperCase()}
                    </div>`
                }
                <div class="flex-1 text-left">
                    <p style="font-weight: var(--font-weight-medium);">${m.name}</p>
                    <p class="text-sm" style="color: var(--muted-foreground);">${m.email}</p>
                    <p class="text-sm" style="color: var(--muted-foreground);">${m.phone}</p>
                </div>
            </button>
        `).join('');
        list.classList.remove('hidden');
    } else {
        list.innerHTML = '<div class="p-4 text-center" style="color: var(--muted-foreground);">No members found</div>';
        list.classList.remove('hidden');
    }
}

function selectMember(memberId) {
    window.location.href = '?month=<?php echo $selectedMonth; ?>&member=' + memberId;
}

function showMemberList() {
    filterMembers();
}

function hideMemberList() {
    document.getElementById('memberList').classList.add('hidden');
}
</script>

<?php include 'includes/footer.php'; ?>