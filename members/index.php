<?php
$page_title = 'Members';
require_once '../includes/header.php';
requireLogin();

// Get search and filter parameters
$search = sanitizeInput($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));

// Build search query
$search_query = buildMemberSearchQuery($search, $status_filter);
$where_clause = $search_query['where'];
$params = $search_query['params'];

// Count total filtered members
$count_sql = "SELECT COUNT(*) as total FROM members " . $where_clause;
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_members = $count_stmt->fetch()['total'];

// Get pagination data
$pagination = getPaginationData($total_members, $page, MEMBERS_PER_PAGE);

// Get members with pagination
$sql = "SELECT * FROM members " . $where_clause . " ORDER BY created_at DESC LIMIT " . $pagination['items_per_page'] . " OFFSET " . $pagination['offset'];
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

// Get payment counts and totals for each member
$member_stats = [];
if (!empty($members)) {
    $member_ids = array_column($members, 'id');
    $placeholders = str_repeat('?,', count($member_ids) - 1) . '?';
    
    $stats_stmt = $pdo->prepare("
        SELECT member_id, COUNT(*) as payment_count, SUM(amount) as total_amount 
        FROM payments 
        WHERE member_id IN ($placeholders) 
        GROUP BY member_id
    ");
    $stats_stmt->execute($member_ids);
    
    while ($row = $stats_stmt->fetch()) {
        $member_stats[$row['member_id']] = [
            'payment_count' => $row['payment_count'],
            'total_amount' => $row['total_amount']
        ];
    }
}
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2>Church Members</h2>
            <p class="text-muted-foreground">Manage your church community</p>
        </div>
        <a href="add.php" class="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2">
            <span>+</span> Add Member
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
                        placeholder="Search members by name, email, or phone..."
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="w-full pl-10 pr-4 py-2 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                    />
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <select
                    name="status"
                    class="px-3 py-2 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                >
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
                
                <button type="submit" class="px-4 py-2 bg-secondary text-secondary-foreground rounded-lg hover:bg-secondary/80 transition-colors">
                    Search
                </button>
                
                <div class="text-sm text-muted-foreground">
                    <?php echo number_format($total_members); ?> of <?php echo number_format($pagination['total_items']); ?> members
                </div>
            </div>
        </form>
    </div>

    <!-- Members Grid -->
    <div class="bg-card rounded-lg border border shadow-sm">
        <div class="p-6 border-b border">
            <h3>Member Directory</h3>
        </div>
        <div class="p-6">
            <?php if (!empty($members)): ?>
                <div class="grid gap-4">
                    <?php foreach ($members as $member): ?>
                        <?php 
                        $stats = $member_stats[$member['id']] ?? ['payment_count' => 0, 'total_amount' => 0];
                        ?>
                        <div class="flex items-center justify-between p-4 border border rounded-lg hover:shadow-md transition-shadow cursor-pointer" onclick="location.href='view.php?id=<?php echo $member['id']; ?>'">
                            <div class="flex items-center space-x-4">
                                <?php if ($member['image_url']): ?>
                                    <img
                                        src="../<?php echo htmlspecialchars($member['image_url']); ?>"
                                        alt="<?php echo htmlspecialchars($member['name']); ?>"
                                        class="w-12 h-12 rounded-full object-cover border-2 border-primary/20"
                                    />
                                <?php else: ?>
                                    <div class="w-12 h-12 bg-gradient-to-br from-primary/20 to-primary/40 rounded-full flex items-center justify-center text-primary font-medium">
                                        <?php echo getMemberInitials($member['name']); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h4 class="font-medium hover:text-primary transition-colors"><?php echo htmlspecialchars($member['name']); ?></h4>
                                    <p class="text-sm text-muted-foreground"><?php echo htmlspecialchars($member['email']); ?></p>
                                    <p class="text-sm text-muted-foreground"><?php echo htmlspecialchars($member['phone']); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-6">
                                <?php echo getMemberStatusBadge($member['status']); ?>
                                <div class="text-right text-sm">
                                    <p class="font-medium"><?php echo number_format($stats['payment_count']); ?> payments</p>
                                    <p class="text-muted-foreground"><?php echo formatCurrency($stats['total_amount']); ?></p>
                                </div>
                                <div class="flex space-x-1" onclick="event.stopPropagation()">
                                    <a href="view.php?id=<?php echo $member['id']; ?>" class="p-2 text-muted-foreground hover:text-primary hover:bg-accent rounded-lg transition-colors" title="View Details">
                                        👁
                                    </a>
                                    <a href="edit.php?id=<?php echo $member['id']; ?>" class="p-2 text-muted-foreground hover:text-primary hover:bg-accent rounded-lg transition-colors" title="Edit Member">
                                        ✏️
                                    </a>
                                    <a href="delete.php?id=<?php echo $member['id']; ?>" class="p-2 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded-lg transition-colors" title="Delete Member" onclick="return confirm('Are you sure you want to delete this member?')">
                                        🗑
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">👥</div>
                    <h3 class="text-lg mb-2">No members found</h3>
                    <p class="text-muted-foreground mb-4">
                        <?php if ($search || $status_filter !== 'all'): ?>
                            Try adjusting your search criteria
                        <?php else: ?>
                            Add your first member to get started
                        <?php endif; ?>
                    </p>
                    <?php if (!$search && $status_filter === 'all'): ?>
                        <a href="add.php" class="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors">
                            Add First Member
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="p-6 border-t border">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-muted-foreground">
                        Showing <?php echo $pagination['offset'] + 1; ?> to <?php echo min($pagination['offset'] + $pagination['items_per_page'], $total_members); ?> of <?php echo number_format($total_members); ?> members
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