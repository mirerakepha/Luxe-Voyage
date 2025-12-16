<?php
include "includes/auth.php";
include "config/db.php";
requireLogin();

$user = $_SESSION['user'];

$success_message = '';
$error_message = '';

// Get current user data from database
$stmt = $conn->prepare("SELECT username, email, profile_pic, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$current_user = $result->fetch_assoc();

if (isset($_POST['save'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate username
    if (empty($username)) {
        $error_message = "Username cannot be empty.";
    } elseif (strlen($username) < 3) {
        $error_message = "Username must be at least 3 characters.";
    }
    
    // Validate email
    if (empty($error_message) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    }
    
    // Check if username or email already exists (excluding current user)
    if (empty($error_message)) {
        $check_stmt = $conn->prepare(
            "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?"
        );
        $check_stmt->bind_param("ssi", $username, $email, $user['id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "Username or email already exists.";
        }
    }
    
    // Handle password change if provided
    $password_changed = false;
    if (empty($error_message) && !empty($current_password)) {
        // Verify current password
        $verify_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $verify_stmt->bind_param("i", $user['id']);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        $db_user = $verify_result->fetch_assoc();
        
        if (!password_verify($current_password, $db_user['password'])) {
            $error_message = "Current password is incorrect.";
        } elseif (empty($new_password)) {
            $error_message = "New password cannot be empty.";
        } elseif (strlen($new_password) < 8) {
            $error_message = "New password must be at least 8 characters.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } else {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $password_changed = true;
        }
    }
    
    // Handle profile picture upload
    $profile_pic = $current_user['profile_pic'];
    if (empty($error_message) && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['profile_pic']['type'], $allowed_types)) {
            $error_message = "Only JPG, PNG, GIF, and WebP images are allowed.";
        } elseif ($_FILES['profile_pic']['size'] > $max_size) {
            $error_message = "Image size must be less than 2MB.";
        } else {
            $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $profile_pic = uniqid() . '_' . time() . '.' . $ext;
            $upload_path = "assets/images/profiles/" . $profile_pic;
            
            // Create directory if it doesn't exist
            if (!file_exists('assets/images/profiles')) {
                mkdir('assets/images/profiles', 0777, true);
            }
            
            if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
                $error_message = "Failed to upload profile picture.";
            } else {
                // Delete old profile picture if it's not the default
                if ($current_user['profile_pic'] !== 'default.png' && 
                    file_exists("assets/images/profiles/" . $current_user['profile_pic'])) {
                    @unlink("assets/images/profiles/" . $current_user['profile_pic']);
                }
            }
        }
    }
    
    // Update database if no errors
    if (empty($error_message)) {
        if ($password_changed) {
            $update_stmt = $conn->prepare(
                "UPDATE users SET username = ?, email = ?, profile_pic = ?, password = ? WHERE id = ?"
            );
            $update_stmt->bind_param("ssssi", $username, $email, $profile_pic, $password_hash, $user['id']);
        } else {
            $update_stmt = $conn->prepare(
                "UPDATE users SET username = ?, email = ?, profile_pic = ? WHERE id = ?"
            );
            $update_stmt->bind_param("sssi", $username, $email, $profile_pic, $user['id']);
        }
        
        if ($update_stmt->execute()) {
            // Update session
            $_SESSION['user']['username'] = $username;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['profile_pic'] = $profile_pic;
            
            $success_message = "Profile updated successfully!";
            
            // Refresh current user data
            $current_user['username'] = $username;
            $current_user['email'] = $email;
            $current_user['profile_pic'] = $profile_pic;
        } else {
            $error_message = "Failed to update profile: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Luxe Voyage</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/edit_profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Additional inline styles */
        body { font-family: 'Poppins', sans-serif; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
    </style>
</head>
<body>
    <div class="edit-profile-page">
        <!-- Header -->
        <header class="edit-profile-header">
            <div class="container">
                <div class="header-container">
                    <a href="profile.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Profile
                    </a>
                    
                    <div class="header-title">
                        <h1><i class="fas fa-user-edit"></i> Edit Profile</h1>
                        <p>Update your personal information and profile settings</p>
                    </div>
                    
                    <div style="width: 150px;"></div> <!-- Spacer for alignment -->
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="edit-profile-container">
            <div class="edit-profile-card">
                <?php if ($success_message): ?>
                    <!-- Success Message -->
                    <div class="success-message">
                        <div class="success-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <h3>Profile Updated Successfully!</h3>
                        <p>Your profile information has been saved. Changes will be reflected immediately.</p>
                        <div class="form-actions">
                            <a href="profile.php" class="submit-btn">
                                <i class="fas fa-user-circle"></i> View Profile
                            </a>
                            <button type="button" class="cancel-btn" onclick="window.location.reload()">
                                <i class="fas fa-edit"></i> Edit More
                            </button>
                        </div>
                    </div>
                
                <?php else: ?>
                    <!-- Card Header -->
                    <div class="card-header">
                        <h2><i class="fas fa-cog"></i> Account Settings</h2>
                        <p>Keep your profile information up to date and manage your account preferences</p>
                    </div>
                    
                    <!-- Form Container -->
                    <form method="POST" enctype="multipart/form-data" class="form-container" id="editProfileForm">
                        <?php if ($error_message): ?>
                            <div class="form-error full-width" style="margin-bottom: 1.5rem; padding: 1rem; background: rgba(239, 71, 111, 0.1); border-radius: 10px; border-left: 4px solid #EF476F;">
                                <i class="fas fa-exclamation-circle"></i>
                                <strong>Error:</strong> <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Profile Picture Section -->
                        <div class="profile-picture-section">
                            <div class="current-avatar">
                                <img src="assets/images/profiles/<?php echo htmlspecialchars($current_user['profile_pic']); ?>" 
                                     alt="Profile Picture" 
                                     class="avatar-image"
                                     id="currentAvatar"
                                     onerror="this.src='assets/images/profiles/default.png'">
                                <div class="avatar-change-btn" onclick="document.getElementById('profilePicInput').click()">
                                    <i class="fas fa-camera"></i>
                                </div>
                            </div>
                            
                            <div class="file-upload-container">
                                <label class="file-upload-label" id="fileUploadLabel">
                                    <div class="file-upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <div class="file-upload-info">
                                        <h4>Change Profile Picture</h4>
                                        <p>Click to upload or drag and drop</p>
                                        <p>JPG, PNG, GIF, WebP (Max 2MB)</p>
                                    </div>
                                    <input type="file" id="profilePicInput" name="profile_pic" class="file-input" accept="image/*">
                                </label>
                                <div class="file-preview" id="filePreview">
                                    <img src="" alt="Preview" class="preview-image" id="previewImage">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Personal Information -->
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Username <span class="required">*</span></label>
                                <input type="text" name="username" class="form-control" 
                                       value="<?php echo htmlspecialchars($current_user['username']); ?>"
                                       placeholder="Enter your username" required>
                                <div class="form-help">
                                    <i class="fas fa-info-circle"></i> This is how you'll appear on the platform
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email Address <span class="required">*</span></label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($current_user['email']); ?>"
                                       placeholder="your.email@example.com" required>
                                <div class="form-help">
                                    <i class="fas fa-info-circle"></i> We'll send important notifications to this email
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Account Role</label>
                                <input type="text" class="form-control readonly-field" 
                                       value="<?php echo ucfirst($current_user['role']); ?>" 
                                       readonly>
                                <div class="form-help">
                                    <i class="fas fa-info-circle"></i> Role changes require administrator approval
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Member Since</label>
                                <input type="text" class="form-control readonly-field" 
                                       value="<?php echo date('F j, Y', strtotime($current_user['created_at'])); ?>" 
                                       readonly>
                                <div class="form-help">
                                    <i class="fas fa-info-circle"></i> Account creation date
                                </div>
                            </div>
                        </div>
                        
                        <!-- Password Change Section -->
                        <div class="password-toggle">
                            <button type="button" class="toggle-btn" id="passwordToggle">
                                <i class="fas fa-lock"></i>
                                <span>Change Password</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                        
                        <div class="password-fields" id="passwordFields">
                            <div class="form-group">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" 
                                       placeholder="Enter current password">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" 
                                       placeholder="Enter new password" 
                                       id="newPassword">
                                <div class="password-strength">
                                    <div class="strength-bar">
                                        <div class="strength-fill" id="strengthFill"></div>
                                    </div>
                                    <div class="strength-text" id="strengthText">Password strength</div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" 
                                       placeholder="Confirm new password">
                            </div>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="submit" name="save" class="submit-btn">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="profile.php" class="cancel-btn">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Profile picture preview
            const profilePicInput = document.getElementById('profilePicInput');
            const previewImage = document.getElementById('previewImage');
            const filePreview = document.getElementById('filePreview');
            const fileUploadLabel = document.getElementById('fileUploadLabel');
            const currentAvatar = document.getElementById('currentAvatar');
            
            profilePicInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        filePreview.classList.add('show');
                        // Also update the current avatar preview
                        currentAvatar.src = e.target.result;
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                } else {
                    previewImage.src = '';
                    filePreview.classList.remove('show');
                }
            });
            
            // Drag and drop functionality
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                fileUploadLabel.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                fileUploadLabel.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                fileUploadLabel.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight() {
                fileUploadLabel.classList.add('drag-over');
            }
            
            function unhighlight() {
                fileUploadLabel.classList.remove('drag-over');
            }
            
            fileUploadLabel.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                profilePicInput.files = files;
                
                // Trigger change event
                const event = new Event('change');
                profilePicInput.dispatchEvent(event);
            });
            
            // Password toggle
            const passwordToggle = document.getElementById('passwordToggle');
            const passwordFields = document.getElementById('passwordFields');
            const chevron = passwordToggle.querySelector('.fa-chevron-down');
            
            passwordToggle.addEventListener('click', function() {
                passwordFields.classList.toggle('show');
                if (passwordFields.classList.contains('show')) {
                    chevron.style.transform = 'rotate(180deg)';
                    this.querySelector('span').textContent = 'Hide Password Change';
                } else {
                    chevron.style.transform = 'rotate(0deg)';
                    this.querySelector('span').textContent = 'Change Password';
                }
                chevron.style.transition = 'transform 0.3s ease';
            });
            
            // Password strength meter
            const newPasswordInput = document.getElementById('newPassword');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            newPasswordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let text = 'Password strength';
                
                if (password.length >= 8) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;
                
                strengthFill.className = 'strength-fill';
                if (password.length === 0) {
                    strengthFill.style.width = '0%';
                    text = 'Password strength';
                } else if (strength <= 1) {
                    strengthFill.classList.add('weak');
                    text = 'Weak password';
                } else if (strength <= 3) {
                    strengthFill.classList.add('fair');
                    text = 'Fair password';
                } else {
                    strengthFill.classList.add('strong');
                    text = 'Strong password';
                }
                
                strengthText.textContent = text;
            });
            
            // Form validation
            const form = document.getElementById('editProfileForm');
            form.addEventListener('submit', function(e) {
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');
                const errorMessages = [];
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.style.borderColor = '#EF476F';
                        errorMessages.push(`${field.previousElementSibling.textContent} is required`);
                    } else {
                        field.style.borderColor = '';
                    }
                });
                
                // Check if changing password
                const currentPassword = form.querySelector('input[name="current_password"]');
                const newPassword = form.querySelector('input[name="new_password"]');
                const confirmPassword = form.querySelector('input[name="confirm_password"]');
                
                if (currentPassword.value || newPassword.value || confirmPassword.value) {
                    // All password fields must be filled
                    if (!currentPassword.value || !newPassword.value || !confirmPassword.value) {
                        isValid = false;
                        errorMessages.push('All password fields must be filled to change password');
                    } else if (newPassword.value !== confirmPassword.value) {
                        isValid = false;
                        errorMessages.push('New passwords do not match');
                        newPassword.style.borderColor = '#EF476F';
                        confirmPassword.style.borderColor = '#EF476F';
                    }
                }
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fix the following errors:\n\n' + errorMessages.join('\n'));
                } else {
                    // Show loading state
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving Changes...';
                    submitBtn.disabled = true;
                    
                    // Re-enable button if form submission fails
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 5000);
                }
            });
            
            // Avatar error handling
            currentAvatar.addEventListener('error', function() {
                this.src = 'assets/images/profiles/default.png';
            });
        });
    </script>
</body>
</html>