<?php
session_start();
include "../config/db.php";
include "../includes/auth.php";

// Simple admin check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_name = $_SESSION['user']['username'];
$admin_initials = strtoupper(substr($admin_name, 0, 2));

$success_message = '';
$error_message = '';

// Create uploads directory
$upload_dir = "../uploads/destinations/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// FORM PROCESSING
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $best_season = trim($_POST['best_season'] ?? '');
    $popular_attractions = trim($_POST['popular_attractions'] ?? '');
    
    // Validate
    if (empty($name) || empty($desc) || empty($country)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Check if file was uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            // Validate file type and size
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                $error_message = "Only JPG, PNG, GIF, and WebP images are allowed.";
            } elseif ($_FILES['image']['size'] > $max_size) {
                $error_message = "Image size must be less than 5MB.";
            } else {
                // Generate unique filename
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $img = uniqid() . '_' . time() . '.' . $ext;
                $upload_path = $upload_dir . $img;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    // Insert into database
                    $stmt = $conn->prepare(
                        "INSERT INTO destinations (name, description, image, country, best_season, popular_attractions)
                         VALUES (?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param("ssssss", $name, $desc, $img, $country, $best_season, $popular_attractions);
                    
                    if ($stmt->execute()) {
                        $success_message = "Destination added successfully!";
                        // Clear form on success
                        $_POST = array();
                        $_FILES = array();
                    } else {
                        $error_message = "Database error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error_message = "Failed to upload image. Please check directory permissions.";
                }
            }
        } else {
            $error_message = "Please select an image.";
        }
    }
}

// Get stats for preview
$stats_query = $conn->query("
    SELECT 
        COUNT(*) as total_destinations,
        (SELECT COUNT(DISTINCT IFNULL(country, 'Unknown')) FROM destinations) as countries_covered,
        (SELECT COUNT(*) FROM hotels) as total_hotels,
        (SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = CURDATE()) as today_bookings
    FROM destinations
");
$stats = $stats_query->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Destination - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/add_destination.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
        
        /* Form styling */
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            background: #fff;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2d3748;
        }
        
        .required {
            color: #e53e3e;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 14px 32px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.2);
        }
        
        .cancel-btn {
            background: #fff;
            color: #4a5568;
            padding: 14px 32px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: 1rem;
        }
        
        .cancel-btn:hover {
            background: #f7fafc;
            border-color: #cbd5e0;
        }
        
        .success-state {
            background: #f0fdf4;
            border: 2px solid #86efac;
            border-radius: 12px;
            padding: 3rem;
            text-align: center;
            margin: 2rem 0;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: #86efac;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: #166534;
        }
        
        .error-message {
            background: #fef2f2;
            border: 2px solid #fca5a5;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #991b1b;
        }
        
        .file-upload-container {
            border: 3px dashed #e2e8f0;
            border-radius: 12px;
            padding: 3rem;
            text-align: center;
            transition: all 0.3s;
            background: #f8fafc;
            cursor: pointer;
        }
        
        .file-upload-container:hover {
            border-color: #4f46e5;
            background: #f1f5f9;
        }
        
        .file-input {
            display: none;
        }
        
        .form-tips {
            background: #f0f9ff;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }
        
        .stats-preview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .stat-preview {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        
        .admin-header {
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .form-actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
        }
    </style>
</head>
<body>
    <div class="admin-destination-page">
        <!-- Header -->
        <header class="admin-header">
            <div class="container">
                <div class="header-container">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <h1 style="margin: 0; font-size: 1.5rem;">
                            <i class="fas fa-globe-americas" style="color: #4f46e5;"></i> Luxe Voyage Admin
                        </h1>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="admin-avatar">
                                <?php echo $admin_initials; ?>
                            </div>
                            <div>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($admin_name); ?></div>
                                <div style="font-size: 0.875rem; color: #718096;">Administrator</div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <a href="dashboard.php" style="padding: 10px 20px; background: #f1f5f9; border-radius: 8px; text-decoration: none; color: #4a5568; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a href="../logout.php" style="padding: 10px 20px; background: #fef2f2; border-radius: 8px; text-decoration: none; color: #dc2626; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="container">
            <!-- Breadcrumb -->
            <div style="margin-bottom: 2rem;">
                <a href="dashboard.php" style="color: #4f46e5; text-decoration: none;">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <span style="margin: 0 8px; color: #cbd5e0;">
                    <i class="fas fa-chevron-right"></i>
                </span>
                <span style="color: #4a5568; font-weight: 500;">Add Destination</span>
            </div>
            
            <div style="background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);">
                <?php if ($success_message): ?>
                    <!-- Success State -->
                    <div class="success-state">
                        <div class="success-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <h2 style="margin: 0 0 1rem; color: #166534;">Destination Added Successfully!</h2>
                        <p style="color: #4a5568; max-width: 500px; margin: 0 auto 2rem;">
                            The destination has been added to the system and is now available for hotel listings and bookings.
                        </p>
                        
                        <div style="display: flex; gap: 1rem; justify-content: center;">
                            <a href="add_destination.php" class="submit-btn">
                                <i class="fas fa-plus"></i> Add Another Destination
                            </a>
                            <a href="dashboard.php" class="cancel-btn">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                
                <?php else: ?>
                    <!-- Form Header -->
                    <div style="margin-bottom: 2rem;">
                        <h2 style="margin: 0 0 0.5rem; display: flex; align-items: center; gap: 12px; color: #2d3748;">
                            <i class="fas fa-map-marker-alt" style="color: #4f46e5;"></i> Add New Destination
                        </h2>
                        <p style="color: #718096; margin: 0;">
                            Add a new travel destination to the Luxe Voyage platform
                        </p>
                    </div>
                    
                    <?php if ($error_message): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <div>
                                <strong>Error!</strong> <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" id="addDestinationForm">
                        <!-- Basic Information -->
                        <div class="form-group">
                            <label for="name">Destination Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                   placeholder="Enter destination name (e.g., Paris, Maldives, Tokyo)" required>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="form-group">
                                <label for="country">Country <span class="required">*</span></label>
                                <input type="text" id="country" name="country" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['country'] ?? ''); ?>"
                                       placeholder="Country name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="best_season">Best Season to Visit</label>
                                <select id="best_season" name="best_season" class="form-control">
                                    <option value="">Select Season</option>
                                    <option value="Spring" <?php echo ($_POST['best_season'] ?? '') === 'Spring' ? 'selected' : ''; ?>>Spring</option>
                                    <option value="Summer" <?php echo ($_POST['best_season'] ?? '') === 'Summer' ? 'selected' : ''; ?>>Summer</option>
                                    <option value="Fall/Autumn" <?php echo ($_POST['best_season'] ?? '') === 'Fall/Autumn' ? 'selected' : ''; ?>>Fall/Autumn</option>
                                    <option value="Winter" <?php echo ($_POST['best_season'] ?? '') === 'Winter' ? 'selected' : ''; ?>>Winter</option>
                                    <option value="Year-round" <?php echo ($_POST['best_season'] ?? '') === 'Year-round' ? 'selected' : ''; ?>>Year-round</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Description -->
                        <div class="form-group">
                            <label for="description">Destination Description <span class="required">*</span></label>
                            <textarea id="description" name="description" class="form-control" 
                                      placeholder="Describe the destination, its attractions, culture, and unique features..."
                                      rows="5" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Popular Attractions -->
                        <div class="form-group">
                            <label for="popular_attractions">Popular Attractions</label>
                            <textarea id="popular_attractions" name="popular_attractions" class="form-control" 
                                      placeholder="List popular attractions, separated by commas (e.g., Eiffel Tower, Louvre Museum, Champs-Élysées)"
                                      rows="3"><?php echo htmlspecialchars($_POST['popular_attractions'] ?? ''); ?></textarea>
                            <small style="color: #718096; display: block; margin-top: 0.5rem;">
                                <i class="fas fa-info-circle"></i> Separate attractions with commas
                            </small>
                        </div>
                        
                        <!-- Image Upload -->
                        <div class="form-group">
                            <label>Destination Image <span class="required">*</span></label>
                            <div class="file-upload-container" onclick="document.getElementById('image').click()">
                                <div style="text-align: center;">
                                    <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #a0aec0; margin-bottom: 1rem;"></i>
                                    <div style="color: #4a5568;">
                                        <h4 style="margin: 0 0 0.5rem;">Click to upload or drag and drop</h4>
                                        <p style="margin: 0; color: #718096;">PNG, JPG, GIF, WebP (Max 5MB)</p>
                                        <p style="margin: 0.5rem 0 0; color: #a0aec0; font-size: 0.875rem;">
                                            Recommended: 1200×800 pixels or larger
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <input type="file" id="image" name="image" class="file-input" accept="image/*" required>
                            <div id="filePreview" style="margin-top: 1rem; display: none;">
                                <img id="previewImage" alt="Preview" style="max-width: 300px; border-radius: 8px;">
                            </div>
                        </div>
                        
                        <!-- Form Tips -->
                        <div class="form-tips">
                            <h4 style="margin: 0 0 1rem; display: flex; align-items: center; gap: 8px; color: #2d3748;">
                                <i class="fas fa-lightbulb" style="color: #f59e0b;"></i> Tips for Best Results
                            </h4>
                            <ul style="margin: 0; padding-left: 1.5rem; color: #4a5568;">
                                <li>Use high-quality, vibrant images that showcase the destination</li>
                                <li>Provide detailed descriptions to attract travelers</li>
                                <li>Include popular attractions and activities</li>
                                <li>Mention the best time to visit for optimal experience</li>
                                <li>Destinations will appear in search results immediately</li>
                            </ul>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="submit" name="add" class="submit-btn">
                                <i class="fas fa-plus-circle"></i> Add Destination
                            </button>
                            <a href="dashboard.php" class="cancel-btn">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                    
                    <!-- Stats Preview -->
                    <div class="stats-preview">
                        <div class="stat-preview">
                            <i class="fas fa-globe" style="font-size: 2rem; color: #4f46e5; margin-bottom: 1rem;"></i>
                            <h3 style="margin: 0; font-size: 2rem; color: #2d3748;"><?php echo $stats['total_destinations'] ?? 0; ?></h3>
                            <p style="margin: 0.5rem 0 0; color: #718096;">Total Destinations</p>
                        </div>
                        
                        <div class="stat-preview">
                            <i class="fas fa-flag" style="font-size: 2rem; color: #10b981; margin-bottom: 1rem;"></i>
                            <h3 style="margin: 0; font-size: 2rem; color: #2d3748;"><?php echo $stats['countries_covered'] ?? 0; ?></h3>
                            <p style="margin: 0.5rem 0 0; color: #718096;">Countries Covered</p>
                        </div>
                        
                        <div class="stat-preview">
                            <i class="fas fa-hotel" style="font-size: 2rem; color: #f59e0b; margin-bottom: 1rem;"></i>
                            <h3 style="margin: 0; font-size: 2rem; color: #2d3748;"><?php echo $stats['total_hotels'] ?? 0; ?></h3>
                            <p style="margin: 0.5rem 0 0; color: #718096;">Total Hotels</p>
                        </div>
                        
                        <div class="stat-preview">
                            <i class="fas fa-calendar-check" style="font-size: 2rem; color: #ef4444; margin-bottom: 1rem;"></i>
                            <h3 style="margin: 0; font-size: 2rem; color: #2d3748;"><?php echo $stats['today_bookings'] ?? 0; ?></h3>
                            <p style="margin: 0.5rem 0 0; color: #718096;">Today's Bookings</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Image preview
            const imageInput = document.getElementById('image');
            const previewImage = document.getElementById('previewImage');
            const filePreview = document.getElementById('filePreview');
            
            imageInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        filePreview.style.display = 'block';
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                } else {
                    previewImage.src = '';
                    filePreview.style.display = 'none';
                }
            });
            
            // Character counter for description
            const descriptionInput = document.getElementById('description');
            const descriptionCounter = document.createElement('div');
            descriptionCounter.className = 'char-counter';
            descriptionCounter.style.cssText = 'text-align: right; color: #a0aec0; font-size: 0.85rem; margin-top: 0.5rem;';
            descriptionInput.parentNode.appendChild(descriptionCounter);
            
            function updateCounter() {
                const length = descriptionInput.value.length;
                descriptionCounter.textContent = `${length} characters`;
                
                if (length < 100) {
                    descriptionCounter.style.color = '#EF476F';
                } else if (length < 300) {
                    descriptionCounter.style.color = '#FFB347';
                } else {
                    descriptionCounter.style.color = '#06D6A0';
                }
            }
            
            descriptionInput.addEventListener('input', updateCounter);
            updateCounter();
            
            // Form validation
            const form = document.getElementById('addDestinationForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    let isValid = true;
                    const requiredFields = form.querySelectorAll('[required]');
                    
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            isValid = false;
                            field.style.borderColor = '#ef4444';
                        } else {
                            field.style.borderColor = '';
                        }
                    });
                    
                    // Validate description length
                    if (descriptionInput.value.length < 50) {
                        isValid = false;
                        descriptionInput.style.borderColor = '#ef4444';
                        alert('Description should be at least 50 characters long.');
                    }
                    
                    if (!isValid) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>