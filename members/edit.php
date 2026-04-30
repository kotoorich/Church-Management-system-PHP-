<?php
$page_title = 'Edit Member';
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

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $profession = sanitizeInput($_POST['profession'] ?? '');
    $digital_address = sanitizeInput($_POST['digital_address'] ?? '');
    $house_address = sanitizeInput($_POST['house_address'] ?? '');
    $membership_date = $_POST['membership_date'] ?? '';
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Invalid email format';
    }
    if (empty($phone)) {
        $errors[] = 'Phone is required';
    } elseif (!validatePhone($phone)) {
        $errors[] = 'Invalid phone format';
    }
    if (empty($house_address)) $errors[] = 'House address is required';
    if (empty($membership_date)) $errors[] = 'Membership date is required';
    
    // Check if email already exists (excluding current member)
    if (empty($errors)) {
        $email_check = $pdo->prepare("SELECT id FROM members WHERE email = ? AND id != ?");
        $email_check->execute([$email, $member_id]);
        if ($email_check->fetch()) {
            $errors[] = 'Email already exists';
        }
    }
    
    // Handle image upload
    $image_url = $member['image_url']; // Keep existing image by default
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploaded_file = uploadImage($_FILES['image']);
        if ($uploaded_file) {
            // Delete old image if it exists
            if ($member['image_url'] && file_exists('../' . $member['image_url'])) {
                unlink('../' . $member['image_url']);
            }
            $image_url = $uploaded_file;
        } else {
            $errors[] = 'Failed to upload image';
        }
    }
    
    // Update member if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE members 
                SET name = ?, email = ?, phone = ?, profession = ?, digital_address = ?, 
                    house_address = ?, membership_date = ?, status = ?, image_url = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$name, $email, $phone, $profession, $digital_address, $house_address, $membership_date, $status, $image_url, $member_id]);
            
            $_SESSION['success_message'] = 'Member updated successfully!';
            header('Location: view.php?id=' . $member_id);
            exit();
        } catch (Exception $e) {
            $errors[] = 'Failed to update member. Please try again.';
        }
    }
}
?>

<div class="space-y-6">
    <a href="view.php?id=<?php echo $member['id']; ?>" class="text-primary hover:text-primary/80 transition-colors">
        ← Back to <?php echo htmlspecialchars($member['name']); ?>
    </a>

    <div class="max-w-2xl bg-card rounded-lg border border shadow-sm">
        <div class="p-6 border-b border">
            <h3>Edit Member - <?php echo htmlspecialchars($member['name']); ?></h3>
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

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <!-- Profile Image Section -->
                <div class="flex flex-col items-center space-y-4">
                    <div class="relative">
                        <div id="imagePreview" class="w-32 h-32 rounded-full border-4 border-primary/20 overflow-hidden">
                            <?php if ($member['image_url']): ?>
                                <img src="../<?php echo htmlspecialchars($member['image_url']); ?>" alt="Member preview" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full bg-gradient-to-br from-primary/20 to-primary/40 flex items-center justify-center text-primary text-2xl font-medium">
                                    <?php echo getMemberInitials($member['name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" id="removeImage" class="<?php echo $member['image_url'] ? '' : 'hidden'; ?> absolute -top-2 -right-2 bg-destructive text-destructive-foreground rounded-full w-8 h-8 flex items-center justify-center hover:bg-destructive/90 transition-colors" onclick="removeImagePreview()">
                            ×
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <label for="image" class="inline-flex items-center px-4 py-2 bg-secondary text-secondary-foreground rounded-lg hover:bg-secondary/80 cursor-pointer transition-colors">
                            📷 <?php echo $member['image_url'] ? 'Change Photo' : 'Add Photo'; ?>
                        </label>
                        <input id="image" name="image" type="file" accept="image/*" class="hidden" onchange="previewImage(this)" />
                        <p class="text-xs text-muted-foreground mt-2">
                            JPG, PNG, or GIF (max 5MB)
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm text-muted-foreground mb-1">Full Name</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="<?php echo htmlspecialchars($_POST['name'] ?? $member['name']); ?>"
                            required
                            class="w-full px-3 py-2 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                        />
                    </div>

                    <div>
                        <label for="email" class="block text-sm text-muted-foreground mb-1">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? $member['email']); ?>"
                            required
                            class="w-full px-3 py-2 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                        />
                    </div>

                    <div>
                        <label for="phone" class="block text-sm text-muted-foreground mb-1">Phone Number</label>
                        <input
                            id="phone"
                            name="phone"
                            type="text"
                            value="<?php echo htmlspecialchars($_POST['phone'] ?? $member['phone']); ?>"
                            required
                            class="w-full px-3 py-2 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                        />
                    </div>

                    <div class="relative">
                        <label for="profession" class="block text-sm text-muted-foreground mb-1">Profession/Occupation</label>
                        <input
                            id="profession"
                            name="profession"
                            type="text"
                            value="<?php echo htmlspecialchars($_POST['profession'] ?? $member['profession']); ?>"
                            placeholder="Start typing your profession..."
                            class="w-full px-3 py-2 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                            list="professionList"
                        />
                        <datalist id="professionList">
                            <?php foreach ($common_professions as $profession): ?>
                                <option value="<?php echo htmlspecialchars($profession); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div>
                        <label for="status" class="block text-sm text-muted-foreground mb-1">Status</label>
                        <select 
                            id="status"
                            name="status"
                            class="w-full px-3 py-2 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                        >
                            <option value="active" <?php echo ($_POST['status'] ?? $member['status']) === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($_POST['status'] ?? $member['status']) === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div>
                        <label for="digital_address" class="block text-sm text-muted-foreground mb-1">Digital Address (GPS)</label>
                        <input
                            id="digital_address"
                            name="digital_address"
                            type="text"
                            value="<?php echo htmlspecialchars($_POST['digital_address'] ?? $member['digital_address']); ?>"
                            placeholder="e.g., GA-123-4567"
                            class="w-full px-3 py-2 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                        />
                    </div>

                    <div>
                        <label for="house_address" class="block text-sm text-muted-foreground mb-1">House Address</label>
                        <input
                            id="house_address"
                            name="house_address"
                            type="text"
                            value="<?php echo htmlspecialchars($_POST['house_address'] ?? $member['house_address']); ?>"
                            placeholder="e.g., House 123, Street Name"
                            required
                            class="w-full px-3 py-2 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                        />
                    </div>

                    <div>
                        <label for="membership_date" class="block text-sm text-muted-foreground mb-1">Membership Date</label>
                        <input
                            id="membership_date"
                            name="membership_date"
                            type="date"
                            value="<?php echo htmlspecialchars($_POST['membership_date'] ?? $member['membership_date']); ?>"
                            required
                            class="w-full px-3 py-2 bg-input-background border border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                        />
                    </div>
                </div>

                <div class="flex space-x-4 pt-4">
                    <button 
                        type="submit"
                        class="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:bg-primary/90 transition-colors"
                    >
                        Update Member
                    </button>
                    <a 
                        href="view.php?id=<?php echo $member['id']; ?>"
                        class="bg-secondary text-secondary-foreground px-6 py-2 rounded-lg hover:bg-secondary/80 transition-colors"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="w-full h-full object-cover">`;
            document.getElementById('removeImage').classList.remove('hidden');
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

function removeImagePreview() {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = `<div class="w-full h-full bg-gradient-to-br from-primary/20 to-primary/40 flex items-center justify-center text-primary text-2xl font-medium"><?php echo getMemberInitials($member['name']); ?></div>`;
    document.getElementById('removeImage').classList.add('hidden');
    document.getElementById('image').value = '';
}
</script>

<?php require_once '../includes/footer.php'; ?>