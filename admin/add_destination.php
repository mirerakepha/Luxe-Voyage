<?php
session_start();
include "../config/db.php";
include "../includes/auth.php";

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_name = $_SESSION['user']['username'];
$admin_initials = strtoupper(substr($admin_name, 0, 2));

$success_message = '';
$error_message = '';

// Create uploads directory if it doesn't exist
$upload_dir = "../uploads/destinations/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $country = trim($_POST['country'] ?? '');
    $best_season = trim($_POST['best_season'] ?? '');
    $popular_attractions = trim($_POST['popular_attractions'] ?? '');

    // Validate inputs
    if (empty($name) || empty($desc)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Handle image upload
        $img = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                $error_message = "Only JPG, PNG, GIF, and WebP images are allowed.";
            } elseif ($_FILES['image']['size'] > $max_size) {
                $error_message = "Image size must be less than 5MB.";
            } else {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $img = uniqid() . '_' . time() . '.' . $ext;
                $upload_path = $upload_dir . $img;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $error_message = "Failed to upload image.";
                }
            }
        } else {
            $error_message = "Please select an image for the destination.";
        }
        
        if (empty($error_message)) {
            $stmt = $conn->prepare(
                "INSERT INTO destinations (name, description, image, country, best_season, popular_attractions)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("ssssss", $name, $desc, $img, $country, $best_season, $popular_attractions);
            
            if ($stmt->execute()) {
                $success_message = "Destination added successfully!";
                // Clear form fields
                $_POST = array();
                $_FILES = array();
            } else {
                $error_message = "Failed to add destination: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Get destination stats for preview
$stats_query = $conn->query("
    SELECT 
        COUNT(*) as total_destinations,
        COUNT(DISTINCT country) as countries_covered,
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
        /* Additional inline styles */
        body { font-family: 'Poppins', sans-serif; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
    </style>
</head>
<body>
    <div class="admin-destination-page">
        <!-- Header -->
        <header class="destination-header">
            <div class="container">
                <div class="header-container">
                    <div class="header-left">
                        <h1><i class="fas fa-globe-americas"></i> Admin Dashboard</h1>
                        
                        <div class="admin-info">
                            <div class="admin-avatar">
                                <?php echo $admin_initials; ?>
                            </div>
                            <div class="admin-details">
                                <span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
                                <span class="admin-role">Administrator</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="header-actions">
                        <a href="dashboard.php" class="admin-btn secondary">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a href="../logout.php" class="admin-btn secondary">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="container">
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <span>Add Destination</span>
            </div>
            
            <div class="destination-container">
                <div class="destination-card">
                    <?php if ($success_message): ?>
                        <!-- Success State -->
                        <div class="success-state">
                            <div class="success-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <h3>Destination Added Successfully!</h3>
                            <p>The destination has been added to the system and is now available for hotel listings and bookings.</p>
                            
                            <div class="success-actions">
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
                        <div class="form-header">
                            <h2><i class="fas fa-map-marker-alt"></i> Add New Destination</h2>
                            <p>Add a new travel destination to the Luxe Voyage platform</p>
                        </div>
                        
                        <?php if ($error_message): ?>
                            <div class="message error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <div>
                                    <strong>Error!</strong> <?php echo $error_message; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data" id="addDestinationForm" class="form-grid">
                            <!-- Basic Information -->
                            <div class="form-group form-full-width">
                                <label for="name">Destination Name <span class="required">*</span></label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                       placeholder="Enter destination name (e.g., Paris, Maldives, Tokyo)" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="country">Country <span class="required">*</span></label>
                                <input type="text" id="country" name="country" class="form-control"
                                       value="<?php echo isset($_POST['country']) ? htmlspecialchars($_POST['country']) : ''; ?>"
                                       placeholder="Country name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="best_season">Best Season to Visit</label>
                                <select id="best_season" name="best_season" class="form-control">
                                    <option value="">Select Season</option>
                                    <option value="Spring" <?php echo (isset($_POST['best_season']) && $_POST['best_season'] === 'Spring') ? 'selected' : ''; ?>>Spring</option>
                                    <option value="Summer" <?php echo (isset($_POST['best_season']) && $_POST['best_season'] === 'Summer') ? 'selected' : ''; ?>>Summer</option>
                                    <option value="Fall/Autumn" <?php echo (isset($_POST['best_season']) && $_POST['best_season'] === 'Fall/Autumn') ? 'selected' : ''; ?>>Fall/Autumn</option>
                                    <option value="Winter" <?php echo (isset($_POST['best_season']) && $_POST['best_season'] === 'Winter') ? 'selected' : ''; ?>>Winter</option>
                                    <option value="Year-round" <?php echo (isset($_POST['best_season']) && $_POST['best_season'] === 'Year-round') ? 'selected' : ''; ?>>Year-round</option>
                                </select>
                            </div>
                            
                            <!-- Description -->
                            <div class="form-group form-full-width">
                                <label for="description">Destination Description <span class="required">*</span></label>
                                <textarea id="description" name="description" class="form-control" 
                                          placeholder="Describe the destination, its attractions, culture, and unique features..."
                                          rows="4" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>
                            
                            <!-- Popular Attractions -->
                            <div class="form-group form-full-width">
                                <label for="popular_attractions">Popular Attractions</label>
                                <textarea id="popular_attractions" name="popular_attractions" class="form-control" 
                                          placeholder="List popular attractions, separated by commas (e.g., Eiffel Tower, Louvre Museum, Champs-Élysées)"
                                          rows="3"><?php echo isset($_POST['popular_attractions']) ? htmlspecialchars($_POST['popular_attractions']) : ''; ?></textarea>
                                <small style="color: #a0aec0; display: block; margin-top: 0.5rem;">
                                    <i class="fas fa-info-circle"></i> Separate attractions with commas
                                </small>
                            </div>
                            
                            <!-- Image Upload -->
                            <div class="form-group form-full-width">
                                <label>Destination Image <span class="required">*</span></label>
                                <div class="file-upload-container" id="fileUploadContainer">
                                    <label for="image" class="file-upload-label" id="fileUploadLabel">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <div class="file-info">
                                            <h4>Click to upload or drag and drop</h4>
                                            <p>PNG, JPG, GIF, WebP (Max 5MB)</p>
                                            <p>Recommended: 1200×800 pixels or larger</p>
                                        </div>
                                    </label>
                                    <input type="file" id="image" name="image" class="file-input" accept="image/*" required>
                                </div>
                                <div class="file-preview" id="filePreview">
                                    <img src="" alt="Preview" class="preview-image" id="previewImage">
                                </div>
                            </div>
                            
                            <!-- Form Tips -->
                            <div class="form-tips">
                                <h4><i class="fas fa-lightbulb"></i> Tips for Best Results</h4>
                                <ul>
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
                                <i class="fas fa-globe"></i>
                                <h4><?php echo $stats['total_destinations'] ?? 0; ?></h4>
                                <p>Total Destinations</p>
                            </div>
                            
                            <div class="stat-preview">
                                <i class="fas fa-flag"></i>
                                <h4><?php echo $stats['countries_covered'] ?? 0; ?></h4>
                                <p>Countries Covered</p>
                            </div>
                            
                            <div class="stat-preview">
                                <i class="fas fa-hotel"></i>
                                <h4><?php echo $stats['total_hotels'] ?? 0; ?></h4>
                                <p>Total Hotels</p>
                            </div>
                            
                            <div class="stat-preview">
                                <i class="fas fa-calendar-check"></i>
                                <h4><?php echo $stats['today_bookings'] ?? 0; ?></h4>
                                <p>Today's Bookings</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Image preview
            const imageInput = document.getElementById('image');
            const previewImage = document.getElementById('previewImage');
            const filePreview = document.getElementById('filePreview');
            const fileUploadLabel = document.getElementById('fileUploadLabel');
            const fileUploadContainer = document.getElementById('fileUploadContainer');
            
            imageInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        filePreview.classList.add('show');
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                } else {
                    previewImage.src = '';
                    filePreview.classList.remove('show');
                }
            });
            
            // Drag and drop functionality
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                fileUploadContainer.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                fileUploadContainer.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                fileUploadContainer.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight() {
                fileUploadLabel.classList.add('drag-over');
            }
            
            function unhighlight() {
                fileUploadLabel.classList.remove('drag-over');
            }
            
            fileUploadContainer.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                imageInput.files = files;
                
                // Trigger change event
                const event = new Event('change');
                imageInput.dispatchEvent(event);
            });
            
            // Character counter for description
            const descriptionInput = document.getElementById('description');
            const descriptionCounter = document.createElement('div');
            descriptionCounter.className = 'char-counter';
            descriptionCounter.style.cssText = 'text-align: right; color: #a0aec0; font-size: 0.85rem; margin-top: 0.5rem;';
            descriptionInput.parentNode.appendChild(descriptionCounter);
            
            function updateCounter() {
                const length = descriptionInput.value.length;
                descriptionCounter.textContent = `${length} characters (minimum 100 recommended)`;
                
                if (length < 100) {
                    descriptionCounter.style.color = '#EF476F';
                } else if (length < 300) {
                    descriptionCounter.style.color = '#FFB347';
                } else {
                    descriptionCounter.style.color = '#06D6A0';
                }
            }
            
            descriptionInput.addEventListener('input', updateCounter);
            updateCounter(); // Initial count
            
            // Form validation
            const form = document.getElementById('addDestinationForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    let isValid = true;
                    const requiredFields = form.querySelectorAll('[required]');
                    
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            isValid = false;
                            field.style.borderColor = '#EF476F';
                        } else {
                            field.style.borderColor = '';
                        }
                    });
                    
                    // Validate description length
                    if (descriptionInput.value.length < 50) {
                        isValid = false;
                        descriptionInput.style.borderColor = '#EF476F';
                        alert('Description should be at least 50 characters long.');
                    } else {
                        descriptionInput.style.borderColor = '';
                    }
                    
                    // Validate image
                    if (!imageInput.files || imageInput.files.length === 0) {
                        isValid = false;
                        alert('Please select an image for the destination.');
                    } else {
                        const file = imageInput.files[0];
                        const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
                        const maxSize = 5 * 1024 * 1024; // 5MB
                        
                        if (!validTypes.includes(file.type)) {
                            isValid = false;
                            alert('Only JPG, PNG, GIF, and WebP images are allowed.');
                        }
                        
                        if (file.size > maxSize) {
                            isValid = false;
                            alert('Image size must be less than 5MB.');
                        }
                    }
                    
                    if (!isValid) {
                        e.preventDefault();
                        alert('Please fill in all required fields correctly.');
                    } else {
                        // Show loading state
                        const submitBtn = form.querySelector('button[type="submit"]');
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding Destination...';
                        submitBtn.disabled = true;
                        
                        // Re-enable button if form submission fails
                        setTimeout(() => {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }, 5000);
                    }
                });
            }
            
            // Auto-suggest countries
            const countryInput = document.getElementById('country');
            const countries = [
                'United States', 'United Kingdom', 'France', 'Italy', 'Spain', 'Germany', 
                'Japan', 'China', 'Australia', 'Canada', 'Brazil', 'Mexico', 'Thailand',
                'Greece', 'Turkey', 'Portugal', 'Netherlands', 'Switzerland', 'Austria',
                'United Arab Emirates', 'Singapore', 'Malaysia', 'India', 'South Korea',
                'New Zealand', 'South Africa', 'Egypt', 'Morocco', 'Argentina', 'Chile'
            ];
            
            let countrySuggestions = null;
            
            countryInput.addEventListener('input', function() {
                const value = this.value.toLowerCase();
                
                // Remove existing suggestions
                if (countrySuggestions) {
                    countrySuggestions.remove();
                }
                
                if (value.length > 1) {
                    const filtered = countries.filter(country => 
                        country.toLowerCase().includes(value)
                    );
                    
                    if (filtered.length > 0) {
                        countrySuggestions = document.createElement('div');
                        countrySuggestions.className = 'suggestions';
                        countrySuggestions.style.cssText = `
                            position: absolute;
                            background: white;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            max-height: 200px;
                            overflow-y: auto;
                            z-index: 1000;
                            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                            width: ${countryInput.offsetWidth}px;
                        `;
                        
                        filtered.forEach(country => {
                            const suggestion = document.createElement('div');
                            suggestion.textContent = country;
                            suggestion.style.cssText = `
                                padding: 0.8rem 1rem;
                                cursor: pointer;
                                transition: background 0.2s;
                            `;
                            suggestion.addEventListener('mouseenter', function() {
                                this.style.background = '#f8fafc';
                            });
                            suggestion.addEventListener('mouseleave', function() {
                                this.style.background = 'white';
                            });
                            suggestion.addEventListener('click', function() {
                                countryInput.value = country;
                                countrySuggestions.remove();
                                countrySuggestions = null;
                            });
                            countrySuggestions.appendChild(suggestion);
                        });
                        
                        countryInput.parentNode.appendChild(countrySuggestions);
                    }
                }
            });
            
            // Remove suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (countrySuggestions && !countryInput.contains(e.target) && !countrySuggestions.contains(e.target)) {
                    countrySuggestions.remove();
                    countrySuggestions = null;
                }
            });
        });
    </script>
</body>
</html>