<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Member.php';
require_once 'classes/Payment.php';

$memberClass = new Member($pdo);
$paymentClass = new Payment($pdo);

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? 'all';
$currentPage = isset($_GET['pg']) ? (int)$_GET['pg'] : 1;
$itemsPerPage = 6;

// Build filter conditions
$conditions = [];
$params = [];

if ($search) {
    $conditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($statusFilter !== 'all') {
    $conditions[] = "status = ?";
    $params[] = $statusFilter;
}

// Get filtered members with pagination
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$offset = ($currentPage - 1) * $itemsPerPage;

$sql = "SELECT * FROM members $where ORDER BY created_at DESC LIMIT $itemsPerPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM members $where";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalMembers = $countStmt->fetchColumn();
$totalPages = ceil($totalMembers / $itemsPerPage);

// Get total member count (for display)
$totalMembersAll = $memberClass->getCount();

$page_title = 'Members - Church Management System';
include 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/enhanced-components.css">

<div class="page-content space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2>Church Members</h2>
            <p style="color: var(--muted-foreground);">Manage your church community</p>
        </div>
        <a href="add_member.php" class="px-4 py-2 rounded-lg transition-colors flex items-center gap-2" style="background: var(--primary); color: var(--primary-foreground);" onmouseover="this.style.background='color-mix(in srgb, var(--primary) 90%, transparent)';" onmouseout="this.style.background='var(--primary)';">
            <span>+</span> Add Member
        </a>
    </div>

    <!-- Search and Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="members.php" class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between" id="searchForm">
            <div class="flex-1 max-w-md search-container">
                <span class="search-icon">🔍</span>
                <input
                    type="text"
                    name="search"
                    placeholder="Search members by name, email, or phone..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    class="search-input"
                    onkeyup="clearTimeout(window.searchTimeout); window.searchTimeout = setTimeout(() => { document.getElementById('searchForm').submit(); }, 500);"
                />
            </div>
            
            <div class="flex items-center gap-4">
                <select
                    name="status"
                    onchange="this.form.submit()"
                    class="select-enhanced"
                >
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>✓ Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>○ Inactive</option>
                </select>
                
                <div class="results-counter">
                    <?php echo $totalMembers; ?> of <?php echo $totalMembersAll; ?> members
                </div>
            </div>
        </form>
    </div>

    <!-- Members Grid -->
    <div class="rounded-lg border shadow-sm" style="background: var(--card); border-color: var(--border);">
        <div class="p-6 border-b" style="border-color: var(--border);">
            <h3>Member Directory</h3>
        </div>
        <div class="p-6">
            <?php if (count($members) > 0): ?>
                <div class="grid gap-4">
                    <?php foreach ($members as $member): ?>
                        <?php
                        $memberPayments = $paymentClass->getByMemberId($member['id']);
                        $totalDonations = array_sum(array_column($memberPayments, 'amount'));
                        ?>
                        <div class="card-hover flex items-center justify-between p-4 border rounded-lg" 
                             style="border-color: var(--border); background: var(--card);"
                             onclick="window.location.href='view_member.php?id=<?php echo $member['id']; ?>'">
                            <div class="flex items-center space-x-4">
                                <?php if (!empty($member['image_url'])): ?>
                                    <img
                                        src="<?php echo htmlspecialchars($member['image_url']); ?>"
                                        alt="<?php echo htmlspecialchars($member['name']); ?>"
                                        class="w-12 h-12 rounded-full object-cover"
                                        style="border: 2px solid color-mix(in srgb, var(--primary) 20%, transparent);"
                                    />
                                <?php else: ?>
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center"
                                         style="background: linear-gradient(to bottom right, color-mix(in srgb, var(--primary) 20%, transparent), color-mix(in srgb, var(--primary) 40%, transparent)); color: var(--primary); font-weight: var(--font-weight-medium);">
                                        <?php echo strtoupper(substr($member['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h4 class="transition-colors" onmouseover="this.style.color='var(--primary)';" onmouseout="this.style.color='var(--foreground)';">
                                        <?php echo htmlspecialchars($member['name']); ?>
                                    </h4>
                                    <p class="text-sm" style="color: var(--muted-foreground);"><?php echo htmlspecialchars($member['email']); ?></p>
                                    <p class="text-sm" style="color: var(--muted-foreground);"><?php echo htmlspecialchars($member['phone']); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-6">
                                <span class="status-badge <?php echo $member['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $member['status']; ?>
                                </span>
                                <div class="text-right text-sm">
                                    <p style="font-weight: var(--font-weight-medium);"><?php echo count($memberPayments); ?> payments</p>
                                    <p style="color: var(--muted-foreground);">₵<?php echo number_format($totalDonations); ?></p>
                                </div>
                                <div class="flex space-x-1" onclick="event.stopPropagation();">
                                    <a href="view_member.php?id=<?php echo $member['id']; ?>" class="p-2 rounded-lg transition-colors" style="color: var(--muted-foreground);" onmouseover="this.style.color='var(--primary)'; this.style.background='var(--accent)';" onmouseout="this.style.color='var(--muted-foreground)'; this.style.background='transparent';" title="View Details">
                                        👁
                                    </a>
                                    <a href="add_member.php?id=<?php echo $member['id']; ?>" class="p-2 rounded-lg transition-colors" style="color: var(--muted-foreground);" onmouseover="this.style.color='var(--primary)'; this.style.background='var(--accent)';" onmouseout="this.style.color='var(--muted-foreground)'; this.style.background='transparent';" title="Edit Member">
                                        ✏️
                                    </a>
                                    <button onclick="if(confirm('Are you sure you want to delete this member?')) { window.location.href='members.php?delete=<?php echo $member['id']; ?>'; }" class="p-2 rounded-lg transition-colors" style="color: var(--muted-foreground);" onmouseover="this.style.color='var(--destructive)'; this.style.background='color-mix(in srgb, var(--destructive) 10%, transparent)';" onmouseout="this.style.color='var(--muted-foreground)'; this.style.background='transparent';" title="Delete Member">
                                        🗑
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">👥</div>
                    <h3 class="empty-state-title">No members found</h3>
                    <p class="empty-state-description">
                        <?php echo ($search || $statusFilter !== 'all') ? 'Try adjusting your search criteria' : 'Add your first member to get started'; ?>
                    </p>
                    <?php if (!$search && $statusFilter === 'all'): ?>
                        <a href="add_member.php" class="btn-animated px-6 py-3 rounded-lg inline-block" style="background: var(--primary); color: var(--primary-foreground); text-decoration: none; font-weight: 600;">
                            + Add First Member
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="p-6 border-t" style="border-color: var(--border);">
                <div class="flex items-center justify-between">
                    <div class="text-sm" style="color: var(--muted-foreground);">
                        Showing <?php echo (($currentPage - 1) * $itemsPerPage) + 1; ?> to <?php echo min($currentPage * $itemsPerPage, $totalMembers); ?> of <?php echo $totalMembers; ?> members
                    </div>
                    <div class="flex items-center space-x-2">
                        <?php if ($currentPage > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['pg' => $currentPage - 1])); ?>" class="pagination-btn">
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
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['pg' => $i])); ?>" class="pagination-btn <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['pg' => $currentPage + 1])); ?>" class="pagination-btn">
                                →
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Handle delete
if (isset($_GET['delete'])) {
    $memberClass->delete($_GET['delete']);
    header('Location: members.php');
    exit();
}

include 'includes/footer.php';
?>