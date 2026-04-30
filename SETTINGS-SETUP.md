# Settings Page Setup Instructions

## Overview
The Church Management System now has a fully functional Settings page where administrators can:
- Change the church logo
- Update church name
- Update system name
- Change admin username and password

## Database Setup

### Step 1: Create Settings Table
Run the following SQL file to create the settings table:

```bash
mysql -u your_username -p your_database < database/settings_table.sql
```

OR manually run this SQL in phpMyAdmin:

```sql
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO settings (setting_key, setting_value) VALUES
('church_name', 'Grace Community Church'),
('church_logo', ''),
('admin_username', 'admin'),
('admin_password', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('system_name', 'Church Management System')
ON DUPLICATE KEY UPDATE setting_key=setting_key;
```

**Note:** The default password hash is for the password: `password`

### Step 2: Set Permissions
Make sure the `uploads/` directory has write permissions:

```bash
chmod 755 uploads/
```

## Features

### 1. Church Logo Upload
- **Supported formats:** JPG, PNG, GIF, WEBP
- **Maximum file size:** 5MB
- **Recommended size:** 200x200 pixels
- **Storage:** Files are stored in `/uploads/` directory
- **Auto-deletion:** Old logos are automatically deleted when uploading a new one

### 2. Church Information
- **Church Name:** Displayed in the header and login page
- **System Name:** Displayed as the subtitle throughout the system
- Both update instantly across all pages

### 3. Admin Credentials
- **Username Change:** Update your admin username
- **Password Change:** Must be at least 6 characters
- **Security:** Passwords are hashed using PHP's `password_hash()` function
- **Verification:** Current password required to make changes

## Usage

### Accessing Settings
1. Log in to the system
2. Click on "⚙️ Settings" in the sidebar
3. Make your desired changes
4. Click "Save Changes"

### Changing Logo
1. Go to Settings page
2. Click "Click to upload logo" in the Church Logo section
3. Select your image file
4. Click "Save Changes"
5. To remove logo, click the × button on the logo preview

### Changing Church Name
1. Go to Settings page
2. Update the "Church Name" field
3. Click "Save Changes"
4. Name will appear in header and login page immediately

### Changing Admin Credentials
1. Go to Settings page
2. Enter your **current password** (required)
3. Enter new username (optional - leave blank to keep current)
4. Enter new password (optional - leave blank to keep current)
5. Confirm new password if changing password
6. Click "Save Changes"
7. **Important:** Remember your new credentials!

## Security Notes

### Password Security
- All passwords are hashed using `PASSWORD_DEFAULT` algorithm
- Never store plain text passwords
- Minimum password length is 6 characters
- Always require current password for credential changes

### File Upload Security
- Only image files are allowed
- File size is limited to 5MB
- Files are stored outside the web root when possible
- `.htaccess` protection prevents PHP execution in uploads folder

### Session Management
- Username is updated in session immediately after change
- Users remain logged in after credential changes
- Session expires on logout or browser close

## Troubleshooting

### Issue: Can't upload logo
**Solution:**
1. Check that `uploads/` directory exists
2. Verify write permissions: `chmod 755 uploads/`
3. Check PHP upload limits in `php.ini`:
   ```ini
   upload_max_filesize = 10M
   post_max_size = 10M
   ```

### Issue: Settings not saving
**Solution:**
1. Verify database connection in `config/database.php`
2. Check that settings table exists
3. Review error logs for database errors

### Issue: Can't login after changing password
**Solution:**
1. Access database directly (phpMyAdmin)
2. Reset password manually:
   ```sql
   UPDATE settings 
   SET setting_value = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
   WHERE setting_key = 'admin_password';
   ```
   This resets password to: `password`

### Issue: Logo not displaying
**Solution:**
1. Check file path is correct in database
2. Verify file exists in `uploads/` directory
3. Check file permissions: `chmod 644 uploads/yourlogo.png`
4. Clear browser cache

## Files Modified/Created

### New Files
- `/settings.php` - Settings page
- `/classes/Settings.php` - Settings management class
- `/database/settings_table.sql` - Database schema
- `/uploads/.htaccess` - Upload directory protection

### Modified Files
- `/index.php` - Uses dynamic credentials and church info
- `/includes/header.php` - Displays dynamic church name and logo
- `/includes/header.php` - Settings link in sidebar

## Default Credentials

**Username:** admin  
**Password:** password

**⚠️ IMPORTANT:** Change these immediately after setup for security!

## Support

If you encounter any issues:
1. Check the troubleshooting section above
2. Review PHP error logs
3. Verify database connection and table structure
4. Ensure proper file permissions

---

**Last Updated:** October 2025  
**Version:** 1.0
