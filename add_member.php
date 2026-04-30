<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Member.php';

$memberClass = new Member($pdo);
$member = null;
$isEdit = false;

// Check if editing
if (isset($_GET['id'])) {
    $member = $memberClass->getById($_GET['id']);
    $isEdit = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => $_POST['name'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'],
        'profession' => $_POST['profession'],
        'digital_address' => $_POST['digitalAddress'],
        'house_address' => $_POST['houseAddress'],
        'membership_date' => $_POST['membershipDate'],
        'status' => $_POST['status'],
        'image_url' => $_POST['imageUrl'] ?? null
    ];
    
    if ($isEdit) {
        $memberClass->update($_GET['id'], $data);
    } else {
        $memberClass->create($data);
    }
    
    header('Location: members.php');
    exit();
}

$page_title = ($isEdit ? 'Edit' : 'Add') . ' Member - Church Management System';
include 'includes/header.php';
?>

<div class="space-y-6">
    <a href="members.php" class="transition-colors inline-block" style="color: var(--primary);" onmouseover="this.style.color='color-mix(in srgb, var(--primary) 80%, transparent)';" onmouseout="this.style.color='var(--primary)';">
        ← Back to Members
    </a>

    <div class="max-w-2xl rounded-lg border shadow-sm" style="background: var(--card); border-color: var(--border);">
        <div class="p-6 border-b" style="border-color: var(--border);">
            <h3><?php echo $isEdit ? 'Edit Member' : 'Add New Member'; ?></h3>
        </div>
        <div class="p-6">
            <form method="POST" class="space-y-6" id="memberForm">
                <input type="hidden" name="imageUrl" id="imageUrl" value="<?php echo htmlspecialchars($member['image_url'] ?? ''); ?>">
                
                <!-- Profile Image Section -->
                <div class="flex flex-col items-center space-y-4">
                    <div class="relative">
                        <div id="imagePreview" class="<?php echo !empty($member['image_url']) ? '' : 'hidden'; ?>">
                            <img src="<?php echo htmlspecialchars($member['image_url'] ?? ''); ?>" alt="Member preview" class="w-32 h-32 rounded-full object-cover" style="border: 4px solid color-mix(in srgb, var(--primary) 20%, transparent);" id="previewImg">
                            <button type="button" onclick="removeImage()" class="absolute -top-2 -right-2 rounded-full w-8 h-8 flex items-center justify-center transition-colors" style="background: var(--destructive); color: var(--destructive-foreground);" onmouseover="this.style.background='color-mix(in srgb, var(--destructive) 90%, transparent)';" onmouseout="this.style.background='var(--destructive)';">
                                ×
                            </button>
                        </div>
                        <div id="imagePlaceholder" class="w-32 h-32 rounded-full flex items-center justify-center text-2xl <?php echo !empty($member['image_url']) ? 'hidden' : ''; ?>" style="background: linear-gradient(to bottom right, color-mix(in srgb, var(--primary) 20%, transparent), color-mix(in srgb, var(--primary) 40%, transparent)); color: var(--primary); font-weight: var(--font-weight-medium); border: 4px solid color-mix(in srgb, var(--primary) 20%, transparent);">
                            <?php echo $member ? strtoupper(substr($member['name'], 0, 1)) : '👤'; ?>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <label for="memberImage" class="inline-flex items-center px-4 py-2 rounded-lg cursor-pointer transition-colors" style="background: var(--secondary); color: var(--secondary-foreground);" onmouseover="this.style.background='color-mix(in srgb, var(--secondary) 80%, transparent)';" onmouseout="this.style.background='var(--secondary)';">
                            📷 <span id="imageButtonText"><?php echo !empty($member['image_url']) ? 'Change Photo' : 'Add Photo'; ?></span>
                        </label>
                        <input id="memberImage" type="file" accept="image/*" onchange="handleImageChange(this)" class="hidden" />
                        <p class="text-xs mt-2" style="color: var(--muted-foreground);">
                            JPG, PNG, or GIF (max 5MB)
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm mb-1" style="color: var(--muted-foreground);">Full Name</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="<?php echo htmlspecialchars($member['name'] ?? ''); ?>"
                            required
                            class="w-full px-3 py-2 border rounded-lg"
                            style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                            onfocus="this.style.borderColor='var(--ring)'; this.style.outline='2px solid color-mix(in srgb, var(--ring) 50%, transparent)';"
                            onblur="this.style.borderColor='var(--border)'; this.style.outline='none';"
                        />
                    </div>

                    <div>
                        <label for="email" class="block text-sm mb-1" style="color: var(--muted-foreground);">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="<?php echo htmlspecialchars($member['email'] ?? ''); ?>"
                            required
                            class="w-full px-3 py-2 border rounded-lg"
                            style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                            onfocus="this.style.borderColor='var(--ring)'; this.style.outline='2px solid color-mix(in srgb, var(--ring) 50%, transparent)';"
                            onblur="this.style.borderColor='var(--border)'; this.style.outline='none';"
                        />
                    </div>

                    <div>
                        <label for="phone" class="block text-sm mb-1" style="color: var(--muted-foreground);">Phone Number</label>
                        <input
                            id="phone"
                            name="phone"
                            type="text"
                            value="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>"
                            required
                            class="w-full px-3 py-2 border rounded-lg"
                            style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                            onfocus="this.style.borderColor='var(--ring)'; this.style.outline='2px solid color-mix(in srgb, var(--ring) 50%, transparent)';"
                            onblur="this.style.borderColor='var(--border)'; this.style.outline='none';"
                        />
                    </div>

                    <div class="relative">
                        <label for="profession" class="block text-sm mb-1" style="color: var(--muted-foreground);">Profession/Occupation</label>
                        <input
                            id="profession"
                            name="profession"
                            type="text"
                            value="<?php echo htmlspecialchars($member['profession'] ?? ''); ?>"
                            placeholder="Start typing your profession..."
                            class="w-full px-3 py-2 border rounded-lg"
                            style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                            onfocus="this.style.borderColor='var(--ring)'; this.style.outline='2px solid color-mix(in srgb, var(--ring) 50%, transparent)'; showProfessionSuggestions();"
                            onblur="setTimeout(hideProfessionSuggestions, 200); this.style.borderColor='var(--border)'; this.style.outline='none';"
                            oninput="filterProfessions()"
                        />
                        
                        <!-- Profession Suggestions Dropdown -->
                        <div id="professionSuggestions" class="hidden absolute top-full left-0 right-0 mt-1 border rounded-lg shadow-lg z-20 max-h-48 overflow-y-auto" style="background: var(--card); border-color: var(--border);"></div>
                    </div>

                    <div>
                        <label for="status" class="block text-sm mb-1" style="color: var(--muted-foreground);">Status</label>
                        <select 
                            id="status"
                            name="status"
                            class="w-full px-3 py-2 border rounded-lg"
                            style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                            onfocus="this.style.borderColor='var(--ring)'; this.style.outline='2px solid color-mix(in srgb, var(--ring) 50%, transparent)';"
                            onblur="this.style.borderColor='var(--border)'; this.style.outline='none';"
                        >
                            <option value="active" <?php echo ($member['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($member['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div>
                        <label for="digitalAddress" class="block text-sm mb-1" style="color: var(--muted-foreground);">Digital Address (GPS)</label>
                        <input
                            id="digitalAddress"
                            name="digitalAddress"
                            type="text"
                            value="<?php echo htmlspecialchars($member['digital_address'] ?? ''); ?>"
                            placeholder="e.g., GA-123-4567"
                            class="w-full px-3 py-2 border rounded-lg"
                            style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                            onfocus="this.style.borderColor='var(--ring)'; this.style.outline='2px solid color-mix(in srgb, var(--ring) 50%, transparent)';"
                            onblur="this.style.borderColor='var(--border)'; this.style.outline='none';"
                        />
                    </div>

                    <div>
                        <label for="houseAddress" class="block text-sm mb-1" style="color: var(--muted-foreground);">House Address</label>
                        <input
                            id="houseAddress"
                            name="houseAddress"
                            type="text"
                            value="<?php echo htmlspecialchars($member['house_address'] ?? ''); ?>"
                            placeholder="e.g., House 123, Street Name"
                            required
                            class="w-full px-3 py-2 border rounded-lg"
                            style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                            onfocus="this.style.borderColor='var(--ring)'; this.style.outline='2px solid color-mix(in srgb, var(--ring) 50%, transparent)';"
                            onblur="this.style.borderColor='var(--border)'; this.style.outline='none';"
                        />
                    </div>

                    <div>
                        <label for="membershipDate" class="block text-sm mb-1" style="color: var(--muted-foreground);">Membership Date</label>
                        <input
                            id="membershipDate"
                            name="membershipDate"
                            type="date"
                            value="<?php echo isset($member['membership_date']) ? date('Y-m-d', strtotime($member['membership_date'])) : date('Y-m-d'); ?>"
                            required
                            class="w-full px-3 py-2 border rounded-lg"
                            style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                            onfocus="this.style.borderColor='var(--ring)'; this.style.outline='2px solid color-mix(in srgb, var(--ring) 50%, transparent)';"
                            onblur="this.style.borderColor='var(--border)'; this.style.outline='none';"
                        />
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
                        <?php echo $isEdit ? 'Update Member' : 'Add Member'; ?>
                    </button>
                    <a 
                        href="members.php"
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
// Common professions for autocomplete
const commonProfessions = [
    'Teacher', 'Engineer', 'Doctor', 'Nurse', 'Lawyer', 'Accountant', 'Pastor', 'Business Owner',
    'Farmer', 'Banker', 'Police Officer', 'Carpenter', 'Electrician', 'Chef', 'Driver',
    'Student', 'Retired', 'Civil Servant', 'Trader', 'Mechanic', 'Tailor', 'Hair Dresser',
    'Pharmacist', 'Architect', 'Software Developer', 'Marketing Manager', 'Sales Representative'
];

function filterProfessions() {
    const input = document.getElementById('profession');
    const value = input.value.toLowerCase();
    const suggestions = document.getElementById('professionSuggestions');
    
    if (!value) {
        suggestions.classList.add('hidden');
        return;
    }
    
    const filtered = commonProfessions.filter(p => p.toLowerCase().includes(value)).slice(0, 8);
    
    if (filtered.length === 0) {
        suggestions.classList.add('hidden');
        return;
    }
    
    suggestions.innerHTML = filtered.map(profession => `
        <button type="button" onclick="selectProfession('${profession}')" class="w-full text-left px-3 py-2 transition-colors" style="color: var(--foreground);" onmouseover="this.style.background='var(--accent)';" onmouseout="this.style.background='transparent';">
            ${profession}
        </button>
    `).join('');
    
    suggestions.classList.remove('hidden');
}

function selectProfession(profession) {
    document.getElementById('profession').value = profession;
    hideProfessionSuggestions();
}

function showProfessionSuggestions() {
    filterProfessions();
}

function hideProfessionSuggestions() {
    document.getElementById('professionSuggestions').classList.add('hidden');
}

function handleImageChange(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imageUrl').value = e.target.result;
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').classList.remove('hidden');
            document.getElementById('imagePlaceholder').classList.add('hidden');
            document.getElementById('imageButtonText').textContent = 'Change Photo';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeImage() {
    document.getElementById('imageUrl').value = '';
    document.getElementById('memberImage').value = '';
    document.getElementById('imagePreview').classList.add('hidden');
    document.getElementById('imagePlaceholder').classList.remove('hidden');
    document.getElementById('imageButtonText').textContent = 'Add Photo';
}
</script>

<?php include 'includes/footer.php'; ?>