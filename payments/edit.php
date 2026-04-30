<?php
$page_title = 'Edit Payment';
require_once '../includes/header.php';
requireLogin();

$payment_id = intval($_GET['id'] ?? 0);
if (!$payment_id) {
    header('Location: index.php');
    exit();
}

// Get payment data
$payment = getPaymentById($payment_id, $pdo);
if (!$payment) {
    header('Location: index.php');
    exit();
}

// Get all active members for dropdown
$members_stmt = $pdo->query("SELECT * FROM members WHERE status = 'active' ORDER BY name");
$members = $members_stmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $member_id = intval($_POST['member_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $type = sanitizeInput($_POST['type'] ?? '');
    $payment_method = sanitizeInput($_POST['payment_method'] ?? '');
    $payment_date = $_POST['payment_date'] ?? '';
    $description = sanitizeInput($_POST['description'] ?? '');
    
    // Validation
    if (!$member_id) $errors[] = 'Please select a member';
    if ($amount <= 0) $errors[] = 'Amount must be greater than 0';
    if (empty($type)) $errors[] = 'Payment type is required';
    if (empty($payment_method)) $errors[] = 'Payment method is required';
    if (empty($payment_date)) $errors[] = 'Payment date is required';
    
    // Verify member exists
    if ($member_id && !getMemberById($member_id, $pdo)) {
        $errors[] = 'Selected member not found';
    }
    
    // Update payment if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE payments 
                SET member_id = ?, amount = ?, type = ?, payment_method = ?, payment_date = ?, description = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$member_id, $amount, $type, $payment_method, $payment_date, $description, $payment_id]);
            
            $_SESSION['success_message'] = 'Payment updated successfully!';
            
            // Check if we should return to member view
            $return_url = $_GET['return'] ?? 'payments';
            if ($return_url === 'member' && isset($_GET['member_id'])) {
                header('Location: ../members/view.php?id=' . intval($_GET['member_id']) . '&tab=payments');
            } else {
                header('Location: index.php');
            }
            exit();
        } catch (Exception $e) {
            $errors[] = 'Failed to update payment. Please try again.';
        }
    }
}

// Determine return URL for cancel button
$cancel_url = 'index.php';
$return_context = $_GET['return'] ?? '';
if ($return_context === 'member' && isset($_GET['member_id'])) {
    $cancel_url = '../members/view.php?id=' . intval($_GET['member_id']) . '&tab=payments';
}
?>

<div class="space-y-6">
    <a href="<?php echo $cancel_url; ?>" class="text-primary hover:text-primary/80 transition-colors">
        ← Back
    </a>

    <div class="max-w-2xl bg-card rounded-lg border border shadow-sm">
        <div class="p-6 border-b border">
            <h3>Edit Payment - <?php echo formatCurrency($payment['amount']); ?> by <?php echo htmlspecialchars($payment['member_name']); ?></h3>
        </div>
        <div class="p-6">
            <?php if (!empty($errors)): ?>
                <div class="bg-destructive/10 border border-destructive/20 text-destructive p-4 rounded-lg mb-6">
                    <ul class="list-disc list-inside space-y-1">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <!-- Member Selection Section -->
                <div class="bg-muted/30 rounded-lg p-4">
                    <label for="member_id" class="block text-sm text-muted-foreground mb-3">Select Member</label>
                    
                    <div class="space-y-3">
                        <select 
                            id="member_id"
                            name="member_id"
                            required
                            class="w-full px-3 py-3 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                            onchange="showMemberInfo(this.value)"
                        >
                            <option value="">Choose a member...</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?php echo $member['id']; ?>" <?php echo (($_POST['member_id'] ?? $payment['member_id']) == $member['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($member['name'] . ' - ' . $member['email']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <!-- Member info display -->
                        <div id="member-info" class="<?php echo $payment['member_id'] ? '' : 'hidden'; ?> p-3 bg-card rounded-lg border border">
                            <div class="flex items-center space-x-3">
                                <div id="member-avatar" class="w-12 h-12 bg-gradient-to-br from-primary/20 to-primary/40 rounded-full flex items-center justify-center text-primary font-medium">
                                    <?php echo getMemberInitials($payment['member_name']); ?>
                                </div>
                                <div class="flex-1">
                                    <p id="member-name" class="font-medium"><?php echo htmlspecialchars($payment['member_name']); ?></p>
                                    <p id="member-email" class="text-sm text-muted-foreground"><?php echo htmlspecialchars($payment['member_email']); ?></p>
                                    <p id="member-phone" class="text-sm text-muted-foreground"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="amount" class="block text-sm text-muted-foreground mb-1">Amount (₵)</label>
                        <input
                            id="amount"
                            name="amount"
                            type="number"
                            step="0.01"
                            min="0"
                            value="<?php echo htmlspecialchars($_POST['amount'] ?? $payment['amount']); ?>"
                            required
                            class="w-full px-3 py-2 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                        />
                    </div>

                    <div>
                        <label for="type" class="block text-sm text-muted-foreground mb-1">Payment Type</label>
                        <select 
                            id="type"
                            name="type"
                            required
                            class="w-full px-3 py-2 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                        >
                            <option value="">Select type...</option>
                            <?php foreach ($payment_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (($_POST['type'] ?? $payment['type']) === $type) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="payment_method" class="block text-sm text-muted-foreground mb-1">Payment Method</label>
                        <select 
                            id="payment_method"
                            name="payment_method"
                            required
                            class="w-full px-3 py-2 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                        >
                            <option value="">Select method...</option>
                            <?php foreach ($payment_methods as $method => $display): ?>
                                <option value="<?php echo htmlspecialchars($method); ?>" <?php echo (($_POST['payment_method'] ?? $payment['payment_method']) === $method) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($display); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="payment_date" class="block text-sm text-muted-foreground mb-1">Date</label>
                        <input
                            id="payment_date"
                            name="payment_date"
                            type="date"
                            value="<?php echo htmlspecialchars($_POST['payment_date'] ?? $payment['payment_date']); ?>"
                            required
                            class="w-full px-3 py-2 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                        />
                    </div>

                    <div class="md:col-span-3">
                        <label for="description" class="block text-sm text-muted-foreground mb-1">Description (Optional)</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            placeholder="Add any notes about this payment..."
                            class="w-full px-3 py-2 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                        ><?php echo htmlspecialchars($_POST['description'] ?? $payment['description']); ?></textarea>
                    </div>
                </div>

                <div class="flex space-x-4 pt-4">
                    <button 
                        type="submit"
                        class="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:bg-primary/90 transition-colors"
                    >
                        Update Payment
                    </button>
                    <a href="<?php echo $cancel_url; ?>" class="bg-secondary text-secondary-foreground px-6 py-2 rounded-lg hover:bg-secondary/80 transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Member data for JavaScript
const membersData = <?php echo json_encode($members); ?>;

function showMemberInfo(memberId) {
    const memberInfo = document.getElementById('member-info');
    
    if (!memberId) {
        memberInfo.classList.add('hidden');
        return;
    }
    
    const member = membersData.find(m => m.id == memberId);
    if (!member) return;
    
    // Generate initials
    const initials = member.name.split(' ').map(n => n[0]).join('').substring(0, 2);
    
    // Update member info display
    document.getElementById('member-avatar').textContent = initials;
    document.getElementById('member-name').textContent = member.name;
    document.getElementById('member-email').textContent = member.email;
    document.getElementById('member-phone').textContent = member.phone;
    
    memberInfo.classList.remove('hidden');
}

// Initialize with current selection
document.addEventListener('DOMContentLoaded', function() {
    const currentMemberId = document.getElementById('member_id').value;
    if (currentMemberId) {
        showMemberInfo(currentMemberId);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>