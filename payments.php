<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Member.php';
require_once 'classes/Payment.php';

$memberClass = new Member($pdo);
$paymentClass = new Payment($pdo);

// Check if showing add/edit form
$showForm = isset($_GET['add']) || isset($_GET['edit']);
$editingPayment = null;
$preSelectedMemberId = $_GET['member'] ?? null;

if (isset($_GET['edit'])) {
    $editingPayment = $paymentClass->getById($_GET['edit']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $data = [
        'member_id' => $_POST['memberId'],
        'amount' => $_POST['amount'],
        'payment_type' => $_POST['type'],
        'payment_method' => $_POST['paymentMethod'],
        'payment_date' => $_POST['date'],
        'description' => $_POST['description'] ?? ''
    ];
    
    if ($_POST['action'] === 'edit') {
        $paymentClass->update($_POST['paymentId'], $data);
    } else {
        $paymentClass->create($data);
    }
    
    header('Location: payments.php');
    exit();
}

// For list view
$search = $_GET['search'] ?? '';
$typeFilter = $_GET['type'] ?? 'all';
$sortField = $_GET['sort'] ?? 'date';
$sortDirection = $_GET['dir'] ?? 'desc';
$currentPage = isset($_GET['pg']) ? (int)$_GET['pg'] : 1;
$itemsPerPage = 8;

// Get all payments with filters
$payments = $paymentClass->getFiltered($search, $typeFilter, $sortField, $sortDirection, $currentPage, $itemsPerPage);
$totalPayments = $paymentClass->getCount($search, $typeFilter);
$totalAmount = $paymentClass->getTotalAmount($search, $typeFilter);
$totalPages = ceil($totalPayments / $itemsPerPage);

// Get all members for the form
$allMembers = $memberClass->getAll();

// Get unique payment types
$paymentTypes = $paymentClass->getUsedTypes();

// Payment method icons
$paymentMethods = [
    'Cash' => '💵',
    'Mobile Money' => '📱',
    'Bank Transfer' => '🏦',
    'Check' => '📄',
    'Card' => '💳',
    'Other' => '🔄'
];

$page_title = ($showForm ? ($editingPayment ? 'Edit' : 'Add') . ' Payment' : 'Payments') . ' - Church Management System';
include 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/enhanced-components.css">

<?php if ($showForm): ?>
    <!-- Payment Form -->
    <div class="space-y-6">
        <a href="payments.php" class="transition-colors inline-block" style="color: var(--primary);" onmouseover="this.style.color='color-mix(in srgb, var(--primary) 80%, transparent)';" onmouseout="this.style.color='var(--primary)';">
            ← Back
        </a>

        <div class="max-w-2xl rounded-lg border shadow-sm" style="background: var(--card); border-color: var(--border);">
            <div class="p-6 border-b" style="border-color: var(--border);">
                <h3>
                    <?php echo $editingPayment ? 'Edit Payment' : 'Add New Payment'; ?>
                    <?php if ($preSelectedMemberId): ?>
                        <?php $selectedMember = $memberClass->getById($preSelectedMemberId); ?>
                        <?php if ($selectedMember): ?>
                            <span class="text-base" style="font-weight: var(--font-weight-normal); color: var(--muted-foreground);"> for <?php echo htmlspecialchars($selectedMember['name']); ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </h3>
            </div>
            <div class="p-6">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="<?php echo $editingPayment ? 'edit' : 'add'; ?>">
                    <?php if ($editingPayment): ?>
                        <input type="hidden" name="paymentId" value="<?php echo $editingPayment['id']; ?>">
                    <?php endif; ?>
                    
                    <!-- Member Selection Section -->
                    <div class="rounded-lg p-4" style="background: color-mix(in srgb, var(--muted) 30%, transparent);">
                        <label for="memberId" class="block text-sm mb-3" style="color: var(--muted-foreground);">Select Member</label>
                        <div class="space-y-3">
                            <!-- Member Search -->
                            <div class="relative">
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
                                <input type="hidden" name="memberId" id="selectedMemberId" value="<?php echo $editingPayment['member_id'] ?? ($preSelectedMemberId ?? ''); ?>" required />
                            </div>

                            <!-- Member List Dropdown -->
                            <div id="memberList" class="hidden absolute top-full left-0 right-0 mt-1 border rounded-lg shadow-lg z-20 max-h-60 overflow-y-auto" style="background: var(--card); border-color: var(--border);"></div>

                            <!-- Selected Member Display -->
                            <div id="selectedMemberDisplay" class="<?php echo ($editingPayment || $preSelectedMemberId) ? '' : 'hidden'; ?> flex items-center space-x-3 p-3 rounded-lg border" style="background: var(--card); border-color: var(--border);"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="amount" class="block text-sm mb-1" style="color: var(--muted-foreground);">Amount (₵)</label>
                            <input
                                id="amount"
                                name="amount"
                                type="number"
                                step="0.01"
                                min="0"
                                value="<?php echo $editingPayment['amount'] ?? ''; ?>"
                                required
                                class="w-full px-3 py-2 border rounded-lg"
                                style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                                onfocus="this.style.borderColor='var(--ring)'; this.style.outline='2px solid color-mix(in srgb, var(--ring) 50%, transparent)';"
                                onblur="this.style.borderColor='var(--border)'; this.style.outline='none';"
                            />
                        </div>

                        <div>
                            <label for="type" class="block text-sm mb-1" style="color: var(--muted-foreground);">Payment Type</label>
                            <select 
                                id="type"
                                name="type"
                                class="w-full px-3 py-2 border rounded-lg"
                                style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                                onfocus="this.style.borderColor='var(--ring)'; this.style.outline='2px solid color-mix(in srgb, var(--ring) 50%, transparent)';"
                                onblur="this.style.borderColor='var(--border)'; this.style.outline='none';"
                            >
                                <option value="Tithe" <?php echo ($editingPayment['payment_type'] ?? '') === 'Tithe' ? 'selected' : ''; ?>>Tithe</option>
                                <option value="Offering" <?php echo ($editingPayment['payment_type'] ?? '') === 'Offering' ? 'selected' : ''; ?>>Offering</option>
                                <option value="Dues" <?php echo ($editingPayment['payment_type'] ?? '') === 'Dues' ? 'selected' : ''; ?>>Dues</option>
                                <option value="Building Fund" <?php echo ($editingPayment['payment_type'] ?? '') === 'Building Fund' ? 'selected' : ''; ?>>Building Fund</option>
                                <option value="Mission" <?php echo ($editingPayment['payment_type'] ?? '') === 'Mission' ? 'selected' : ''; ?>>Mission</option>
                                <option value="Special Offering" <?php echo ($editingPayment['payment_type'] ?? '') === 'Special Offering' ? 'selected' : ''; ?>>Special Offering</option>
                                <option value="Youth Ministry" <?php echo ($editingPayment['payment_type'] ?? '') === 'Youth Ministry' ? 'selected' : ''; ?>>Youth Ministry</option>
                                <option value="Music Ministry" <?php echo ($editingPayment['payment_type'] ?? '') === 'Music Ministry' ? 'selected' : ''; ?>>Music Ministry</option>
                                <option value="Other" <?php echo ($editingPayment['payment_type'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>

                        <div>
                            <label for="paymentMethod" class="block text-sm mb-1" style="color: var(--muted-foreground);">Payment Method</label>
                            <select 
                                id="paymentMethod"
                                name="paymentMethod"
                                class="w-full px-3 py-2 border rounded-lg"
                                style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                                onfocus="this.style.borderColor='var(--ring)'; this.style.outline='2px solid color-mix(in srgb, var(--ring) 50%, transparent)';"
                                onblur="this.style.borderColor='var(--border)'; this.style.outline='none';"
                            >
                                <option value="Cash" <?php echo ($editingPayment['payment_method'] ?? '') === 'Cash' ? 'selected' : ''; ?>>💵 Cash</option>
                                <option value="Mobile Money" <?php echo ($editingPayment['payment_method'] ?? '') === 'Mobile Money' ? 'selected' : ''; ?>>📱 Mobile Money</option>
                                <option value="Bank Transfer" <?php echo ($editingPayment['payment_method'] ?? '') === 'Bank Transfer' ? 'selected' : ''; ?>>🏦 Bank Transfer</option>
                                <option value="Check" <?php echo ($editingPayment['payment_method'] ?? '') === 'Check' ? 'selected' : ''; ?>>📄 Check</option>
                                <option value="Card" <?php echo ($editingPayment['payment_method'] ?? '') === 'Card' ? 'selected' : ''; ?>>💳 Card</option>
                                <option value="Other" <?php echo ($editingPayment['payment_method'] ?? '') === 'Other' ? 'selected' : ''; ?>>🔄 Other</option>
                            </select>
                        </div>

                        <div>
                            <label for="date" class="block text-sm mb-1" style="color: var(--muted-foreground);">Date</label>
                            <input
                                id="date"
                                name="date"
                                type="date"
                                value="<?php echo isset($editingPayment['payment_date']) ? date('Y-m-d', strtotime($editingPayment['payment_date'])) : date('Y-m-d'); ?>"
                                required
                                class="w-full px-3 py-2 border rounded-lg"
                                style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                                onfocus="this.style.borderColor='var(--ring)'; this.style.outline='2px solid color-mix(in srgb, var(--ring) 50%, transparent)';"
                                onblur="this.style.borderColor='var(--border)'; this.style.outline='none';"
                            />
                        </div>

                        <div class="md:col-span-3">
                            <label for="description" class="block text-sm mb-1" style="color: var(--muted-foreground);">Description (Optional)</label>
                            <textarea
                                id="description"
                                name="description"
                                rows="3"
                                placeholder="Add any notes about this payment..."
                                class="w-full px-3 py-2 border rounded-lg"
                                style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                                onfocus="this.style.borderColor='var(--ring)'; this.style.outline='2px solid color-mix(in srgb, var(--ring) 50%, transparent)';"
                                onblur="this.style.borderColor='var(--border)'; this.style.outline='none';"
                            ><?php echo htmlspecialchars($editingPayment['description'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="flex space-x-4 pt-4">
                        <button 
                            type="submit"
                            class="px-6 py-2 rounded-lg transition-colors"
                            style="background: var(--primary); color: var(--primary-foreground);"
                            onmouseover="this.style.background='color-mix(in srgb, var(--primary) 90%, transparent)';"
                            onmouseout="this.style.background='var(--primary)';"
                        >
                            <?php echo $editingPayment ? 'Update Payment' : 'Add Payment'; ?>
                        </button>
                        <a 
                            href="payments.php"
                            class="px-6 py-2 rounded-lg transition-colors inline-block"
                            style="background: var(--secondary); color: var(--secondary-foreground);"
                            onmouseover="this.style.background='color-mix(in srgb, var(--secondary) 80%, transparent)';"
                            onmouseout="this.style.background='var(--secondary)';"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    const members = <?php echo json_encode($allMembers); ?>;
    let selectedMember = null;

    <?php if ($editingPayment || $preSelectedMemberId): ?>
        // Set initial member
        const initialMemberId = '<?php echo $editingPayment['member_id'] ?? ($preSelectedMemberId ?? ''); ?>';
        selectedMember = members.find(m => m.id == initialMemberId);
        if (selectedMember) {
            displaySelectedMember(selectedMember);
        }
    <?php endif; ?>

    function filterMembers() {
        const search = document.getElementById('memberSearch').value.toLowerCase();
        const filtered = members.filter(m => 
            m.status === 'active' && (
                m.name.toLowerCase().includes(search) ||
                m.email.toLowerCase().includes(search) ||
                m.phone.includes(search)
            )
        );
        
        const list = document.getElementById('memberList');
        if (filtered.length > 0 && search) {
            list.innerHTML = filtered.slice(0, 10).map(m => `
                <button type="button" onclick='selectMember(${JSON.stringify(m)})' class="w-full flex items-center space-x-3 p-3 transition-colors" style="color: var(--foreground);" onmouseover="this.style.background='var(--accent)';" onmouseout="this.style.background='transparent';">
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
            list.classList.add('hidden');
        }
    }

    function selectMember(member) {
        selectedMember = member;
        document.getElementById('selectedMemberId').value = member.id;
        document.getElementById('memberSearch').value = '';
        displaySelectedMember(member);
        hideMemberList();
    }

    function displaySelectedMember(member) {
        const display = document.getElementById('selectedMemberDisplay');
        display.innerHTML = `
            ${member.image_url ? 
                `<img src="${member.image_url}" alt="${member.name}" class="w-12 h-12 rounded-full object-cover" />` :
                `<div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: linear-gradient(to bottom right, color-mix(in srgb, var(--primary) 20%, transparent), color-mix(in srgb, var(--primary) 40%, transparent)); color: var(--primary); font-weight: var(--font-weight-medium);">
                    ${member.name.charAt(0).toUpperCase()}
                </div>`
            }
            <div class="flex-1">
                <p style="font-weight: var(--font-weight-medium);">${member.name}</p>
                <p class="text-sm" style="color: var(--muted-foreground);">${member.email}</p>
                <p class="text-sm" style="color: var(--muted-foreground);">${member.phone}</p>
            </div>
            <button type="button" onclick="removeMember()" class="p-2 rounded-lg transition-colors" style="color: var(--muted-foreground);" onmouseover="this.style.color='var(--destructive)'; this.style.background='color-mix(in srgb, var(--destructive) 10%, transparent)';" onmouseout="this.style.color='var(--muted-foreground)'; this.style.background='transparent';" title="Remove member">
                ×
            </button>
        `;
        display.classList.remove('hidden');
    }

    function removeMember() {
        selectedMember = null;
        document.getElementById('selectedMemberId').value = '';
        document.getElementById('selectedMemberDisplay').classList.add('hidden');
        document.getElementById('memberSearch').value = '';
    }

    function showMemberList() {
        if (!selectedMember) {
            filterMembers();
        }
    }

    function hideMemberList() {
        document.getElementById('memberList').classList.add('hidden');
    }
    </script>

<?php else: ?>
    <!-- Payments List -->
    <div class="page-content space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2>Payment Records</h2>
                <p style="color: var(--muted-foreground);">Track all church donations and payments</p>
            </div>
            <a href="payments.php?add=1" class="px-4 py-2 rounded-lg transition-colors flex items-center gap-2" style="background: var(--primary); color: var(--primary-foreground);" onmouseover="this.style.background='color-mix(in srgb, var(--primary) 90%, transparent)';" onmouseout="this.style.background='var(--primary)';">
                <span>+</span> Add Payment
            </a>
        </div>

        <!-- Search and Filter Bar -->
        <div class="filter-bar">
            <form method="GET" action="payments.php" class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                <div class="flex-1 max-w-md search-container">
                    <span class="search-icon">🔍</span>
                    <input
                        type="text"
                        name="search"
                        placeholder="Search by member name, type, or amount..."
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="search-input"
                    />
                </div>
                
                <div class="flex items-center gap-4">
                    <select
                        name="type"
                        onchange="this.form.submit()"
                        class="select-enhanced"
                    >
                        <option value="all" <?php echo $typeFilter === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <?php foreach ($paymentTypes as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $typeFilter === $type ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div class="text-sm" style="color: var(--muted-foreground);">
                        <?php echo $totalPayments; ?> of <?php echo $paymentClass->getCount(); ?> payments
                    </div>
                </div>
            </form>
        </div>

        <!-- Payments Table -->
        <div class="rounded-lg border shadow-sm" style="background: var(--card); border-color: var(--border);">
            <div class="p-6 border-b" style="border-color: var(--border);">
                <div class="flex items-center justify-between">
                    <h3>
                        Payment Records (<?php echo $totalPayments; ?> payments)
                    </h3>
                    <div class="text-right">
                        <p class="text-lg" style="font-weight: var(--font-weight-medium);">Total: ₵<?php echo number_format($totalAmount); ?></p>
                        <p class="text-sm" style="color: var(--muted-foreground);">
                            Showing <?php echo (($currentPage - 1) * $itemsPerPage) + 1; ?> to <?php echo min($currentPage * $itemsPerPage, $totalPayments); ?> of <?php echo $totalPayments; ?>
                        </p>
                    </div>
                </div>
            </div>

            <?php if (count($payments) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead style="background: color-mix(in srgb, var(--muted) 50%, transparent);">
                            <tr>
                                <th class="text-left p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'member', 'dir' => ($sortField === 'member' && $sortDirection === 'asc') ? 'desc' : 'asc'])); ?>" class="flex items-center gap-2 transition-colors" style="color: var(--foreground);" onmouseover="this.style.color='var(--primary)';" onmouseout="this.style.color='var(--foreground)';">
                                        Member <?php echo $sortField === 'member' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕️'; ?>
                                    </a>
                                </th>
                                <th class="text-left p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'date', 'dir' => ($sortField === 'date' && $sortDirection === 'asc') ? 'desc' : 'asc'])); ?>" class="flex items-center gap-2 transition-colors" style="color: var(--foreground);" onmouseover="this.style.color='var(--primary)';" onmouseout="this.style.color='var(--foreground)';">
                                        Date <?php echo $sortField === 'date' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕️'; ?>
                                    </a>
                                </th>
                                <th class="text-left p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">Type</th>
                                <th class="text-left p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">Method</th>
                                <th class="text-right p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'amount', 'dir' => ($sortField === 'amount' && $sortDirection === 'asc') ? 'desc' : 'asc'])); ?>" class="flex items-center justify-end gap-2 transition-colors" style="color: var(--foreground);" onmouseover="this.style.color='var(--primary)';" onmouseout="this.style.color='var(--foreground)';">
                                        Amount <?php echo $sortField === 'amount' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕️'; ?>
                                    </a>
                                </th>
                                <th class="text-left p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">Description</th>
                                <th class="text-center p-4 border-b" style="border-color: var(--border); font-weight: var(--font-weight-medium);">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $index => $payment): ?>
                                <tr class="transition-colors" style="<?php echo $index % 2 === 0 ? 'background: var(--background);' : 'background: color-mix(in srgb, var(--muted) 20%, transparent);'; ?>" onmouseover="this.style.background='color-mix(in srgb, var(--accent) 50%, transparent)';" onmouseout="this.style.background='<?php echo $index % 2 === 0 ? 'var(--background)' : 'color-mix(in srgb, var(--muted) 20%, transparent)'; ?>';">
                                    <td class="p-4 border-b" style="border-color: var(--border);">
                                        <div class="flex items-center space-x-3">
                                            <?php if (!empty($payment['image_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($payment['image_url']); ?>" alt="<?php echo htmlspecialchars($payment['member_name']); ?>" class="w-8 h-8 rounded-full object-cover" />
                                            <?php else: ?>
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm" style="background: linear-gradient(to bottom right, color-mix(in srgb, var(--primary) 20%, transparent), color-mix(in srgb, var(--primary) 40%, transparent)); color: var(--primary); font-weight: var(--font-weight-medium);">
                                                    <?php echo strtoupper(substr($payment['member_name'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <p style="font-weight: var(--font-weight-medium);"><?php echo htmlspecialchars($payment['member_name']); ?></p>
                                                <?php if (!empty($payment['member_email'])): ?>
                                                    <p class="text-xs" style="color: var(--muted-foreground);"><?php echo htmlspecialchars($payment['member_email']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
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
                                        <span class="text-sm" style="color: var(--muted-foreground);">
                                            <?php echo htmlspecialchars($payment['description']) ?: '—'; ?>
                                        </span>
                                    </td>
                                    <td class="p-4 border-b text-center" style="border-color: var(--border);">
                                        <div class="flex items-center justify-center space-x-2">
                                            <a href="payments.php?edit=<?php echo $payment['id']; ?>" class="p-2 rounded-lg transition-colors" style="color: var(--muted-foreground);" onmouseover="this.style.color='var(--primary)'; this.style.background='var(--accent)';" onmouseout="this.style.color='var(--muted-foreground)'; this.style.background='transparent';" title="Edit Payment">
                                                ✏️
                                            </a>
                                            <button onclick="if(confirm('Are you sure you want to delete this payment?')) { window.location.href='?delete=<?php echo $payment['id']; ?>'; }" class="p-2 rounded-lg transition-colors" style="color: var(--muted-foreground);" onmouseover="this.style.color='var(--destructive)'; this.style.background='color-mix(in srgb, var(--destructive) 10%, transparent)';" onmouseout="this.style.color='var(--muted-foreground)'; this.style.background='transparent';" title="Delete Payment">
                                                🗑
                                            </button>
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
                    <p style="color: var(--muted-foreground);" class="mb-4">
                        <?php echo ($search || $typeFilter !== 'all') ? 'Try adjusting your search criteria' : 'Add your first payment to get started'; ?>
                    </p>
                    <?php if (!$search && $typeFilter === 'all'): ?>
                        <a href="payments.php?add=1" class="px-4 py-2 rounded-lg transition-colors inline-block" style="background: var(--primary); color: var(--primary-foreground);" onmouseover="this.style.background='color-mix(in srgb, var(--primary) 90%, transparent)';" onmouseout="this.style.background='var(--primary)';">
                            Add First Payment
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="p-6 border-t" style="border-color: var(--border);">
                    <div class="flex items-center justify-between">
                        <div class="text-sm" style="color: var(--muted-foreground);">
                            Showing <?php echo (($currentPage - 1) * $itemsPerPage) + 1; ?> to <?php echo min($currentPage * $itemsPerPage, $totalPayments); ?> of <?php echo $totalPayments; ?> payments
                        </div>
                        <div class="flex items-center space-x-2">
                            <?php if ($currentPage > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['pg' => $currentPage - 1])); ?>" class="px-3 py-2 text-sm border rounded-lg transition-colors" style="border-color: var(--border);" onmouseover="this.style.background='var(--accent)';" onmouseout="this.style.background='transparent';">
                                    ←
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $maxVisiblePages = 5;
                            $startPage = max(1, $currentPage - floor($maxVisiblePages / 2));
                            $endPage = min($totalPages, $startPage + $maxVisiblePages - 1);
                            
                            if ($endPage - $startPage + 1 < $maxVisiblePages) {
                                $startPage = max(1, $endPage - $maxVisiblePages + 1);
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['pg' => $i])); ?>" class="px-3 py-2 text-sm border rounded-lg transition-colors" style="<?php echo $i === $currentPage ? 'background: var(--primary); color: var(--primary-foreground); border-color: var(--primary);' : 'border-color: var(--border);'; ?>" <?php if ($i !== $currentPage): ?>onmouseover="this.style.background='var(--accent)';" onmouseout="this.style.background='transparent';"<?php endif; ?>>
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['pg' => $currentPage + 1])); ?>" class="px-3 py-2 text-sm border rounded-lg transition-colors" style="border-color: var(--border);" onmouseover="this.style.background='var(--accent)';" onmouseout="this.style.background='transparent';">
                                    →
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php
// Handle delete
if (isset($_GET['delete'])) {
    $paymentClass->delete($_GET['delete']);
    header('Location: payments.php');
    exit();
}

include 'includes/footer.php';
?>