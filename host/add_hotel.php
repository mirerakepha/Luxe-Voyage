<?php
session_start();
include "../includes/auth.php";
include "../config/db.php";
requireRole('host');

$host_id = $_SESSION['user']['id'];

$success_msg = '';
$error_msg = '';

if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $destination = intval($_POST['destination']);
    $location = trim($_POST['location']);
    $amenities = isset($_POST['amenities']) ? implode(',', $_POST['amenities']) : '';

    // Validate inputs
    if (empty($name) || empty($price) || empty($destination)) {
        $error_msg = "Please fill in all required fields.";
    } elseif ($price <= 0) {
        $error_msg = "Price must be greater than 0.";
    } else {
        // Handle image upload
        $img = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                $error_msg = "Only JPG, PNG, GIF, and WebP images are allowed.";
            } elseif ($_FILES['image']['size'] > $max_size) {
                $error_msg = "Image size must be less than 5MB.";
            } else {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $img = uniqid() . '_' . time() . '.' . $ext;
                $upload_path = "../uploads/hotels/" . $img;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $error_msg = "Failed to upload image.";
                }
            }
        } else {
            $img = 'default_hotel.jpg'; // Default image
        }
        
        if (empty($error_msg)) {
            $stmt = $conn->prepare(
                "INSERT INTO hotels (host_id, destination_id, name, description, price, image, location, amenities)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("iissdsss", $host_id, $destination, $name, $desc, $price, $img, $location, $amenities);
            
            if ($stmt->execute()) {
                $success_msg = "Hotel added successfully!";
                // Clear form fields
                $_POST = array();
            } else {
                $error_msg = "Failed to add hotel: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Get destinations for dropdown
$destinations = $conn->query("SELECT * FROM destinations ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Hotel - Luxe Voyage Host</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/host_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Add Hotel Specific Styles */
        .add-hotel-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .add-hotel-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .back-link {
            margin-bottom: 1.5rem;
        }
        
        .back-link a {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.8rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .back-link a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-5px);
        }
        
        .add-hotel-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .form-header h1 {
            color: #2d3748;
            margin: 0 0 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-size: 2rem;
        }
        
        .form-header h1 i {
            color: #667eea;
        }
        
        .form-header p {
            color: #718096;
            margin: 0;
        }
        
        /* Messages */
        .message {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .success-message {
            background: rgba(6, 214, 160, 0.1);
            color: #06D6A0;
            border-left: 4px solid #06D6A0;
        }
        
        .error-message {
            background: rgba(239, 71, 111, 0.1);
            color: #EF476F;
            border-left: 4px solid #EF476F;
        }
        
        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        
        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-full-width {
                grid-column: 1 / -1;
            }
        }
        
        /* Form Groups */
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4a5568;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .form-group label .required {
            color: #EF476F;
        }
        
        .form-control {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
            line-height: 1.5;
        }
        
        .form-control::placeholder {
            color: #a0aec0;
        }
        
        /* File Upload */
        .file-upload-container {
            position: relative;
        }
        
        .file-upload-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 1rem;
            border: 2px dashed #cbd5e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-upload-label:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
        
        .file-upload-label.drag-over {
            border-color: #06D6A0;
            background: rgba(6, 214, 160, 0.05);
        }
        
        .file-upload-label i {
            font-size: 1.5rem;
            color: #667eea;
        }
        
        .file-info {
            flex: 1;
        }
        
        .file-info h4 {
            margin: 0 0 0.3rem;
            color: #4a5568;
            font-size: 0.95rem;
        }
        
        .file-info p {
            margin: 0;
            color: #a0aec0;
            font-size: 0.85rem;
        }
        
        .file-input {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-preview {
            margin-top: 1rem;
            display: none;
        }
        
        .file-preview.show {
            display: block;
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
        }
        
        /* Amenities */
        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 0.8rem;
            margin-top: 0.5rem;
        }
        
        .amenity-checkbox {
            display: none;
        }
        
        .amenity-label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0.8rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .amenity-label:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
        
        .amenity-checkbox:checked + .amenity-label {
            background: rgba(102, 126, 234, 0.1);
            border-color: #667eea;
            color: #667eea;
        }
        
        .amenity-label i {
            font-size: 1rem;
        }
        
        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .submit-btn {
            flex: 1;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .cancel-btn {
            padding: 1rem 1.5rem;
            background: #f8fafc;
            color: #4a5568;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .cancel-btn:hover {
            background: #e2e8f0;
        }
        
        /* Form Tips */
        .form-tips {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 2rem;
        }
        
        .form-tips h4 {
            margin: 0 0 1rem;
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-tips ul {
            margin: 0;
            padding-left: 1.2rem;
            color: #718096;
        }
        
        .form-tips li {
            margin-bottom: 0.5rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .add-hotel-card {
                padding: 1.5rem;
            }
            
            .form-header h1 {
                font-size: 1.5rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .amenities-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="add-hotel-page">
        <div class="add-hotel-container">
            <div class="back-link">
                <a href="dashboard.php">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            
            <div class="add-hotel-card">
                <div class="form-header">
                    <h1><i class="fas fa-plus-circle"></i> Add New Hotel</h1>
                    <p>Fill in the details below to list your hotel on Luxe Voyage</p>
                </div>
                
                <?php if ($success_msg): ?>
                    <div class="message success-message">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Success!</strong> <?php echo $success_msg; ?>
                            <p style="margin-top: 0.3rem; font-size: 0.9rem;">
                                <a href="dashboard.php" style="color: inherit; text-decoration: underline;">Go to Dashboard</a> 
                                or 
                                <a href="#" onclick="document.getElementById('addHotelForm').reset(); document.querySelector('.file-preview').classList.remove('show'); return false;" style="color: inherit; text-decoration: underline;">Add Another Hotel</a>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_msg): ?>
                    <div class="message error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>
                            <strong>Error!</strong> <?php echo $error_msg; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="addHotelForm" class="form-grid">
                    <!-- Basic Information -->
                    <div class="form-group form-full-width">
                        <label for="name">Hotel Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                               placeholder="Enter hotel name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="destination">Destination <span class="required">*</span></label>
                        <select id="destination" name="destination" class="form-control" required>
                            <option value="">Select Destination</option>
                            <?php while ($row = $destinations->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>" 
                                    <?php echo (isset($_POST['destination']) && $_POST['destination'] == $row['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location/City <span class="required">*</span></label>
                        <input type="text" id="location" name="location" class="form-control"
                               value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>"
                               placeholder="e.g., Paris, France" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price per Night ($) <span class="required">*</span></label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #a0aec0;">$</span>
                            <input type="number" id="price" name="price" class="form-control" 
                                   style="padding-left: 30px;"
                                   value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>"
                                   placeholder="0.00" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div class="form-group form-full-width">
                        <label for="description">Hotel Description</label>
                        <textarea id="description" name="description" class="form-control" 
                                  placeholder="Describe your hotel's features, rooms, services, and unique selling points..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        <small style="color: #a0aec0; display: block; margin-top: 0.5rem;">
                            <i class="fas fa-info-circle"></i> A detailed description helps attract more guests
                        </small>
                    </div>
                    
                    <!-- Image Upload -->
                    <div class="form-group form-full-width">
                        <label>Hotel Image <span class="required">*</span></label>
                        <div class="file-upload-container" id="fileUploadContainer">
                            <label for="image" class="file-upload-label" id="fileUploadLabel">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <div class="file-info">
                                    <h4>Click to upload or drag and drop</h4>
                                    <p>PNG, JPG, GIF, WebP (Max 5MB)</p>
                                </div>
                            </label>
                            <input type="file" id="image" name="image" class="file-input" accept="image/*">
                        </div>
                        <div class="file-preview" id="filePreview">
                            <img src="" alt="Preview" class="preview-image" id="previewImage">
                        </div>
                    </div>
                    
                    <!-- Amenities -->
                    <div class="form-group form-full-width">
                        <label>Amenities (Select all that apply)</label>
                        <div class="amenities-grid">
                            <input type="checkbox" id="wifi" name="amenities[]" value="wifi" class="amenity-checkbox"
                                   <?php echo (isset($_POST['amenities']) && in_array('wifi', $_POST['amenities'])) ? 'checked' : ''; ?>>
                            <label for="wifi" class="amenity-label">
                                <i class="fas fa-wifi"></i> Free WiFi
                            </label>
                            
                            <input type="checkbox" id="pool" name="amenities[]" value="pool" class="amenity-checkbox"
                                   <?php echo (isset($_POST['amenities']) && in_array('pool', $_POST['amenities'])) ? 'checked' : ''; ?>>
                            <label for="pool" class="amenity-label">
                                <i class="fas fa-swimming-pool"></i> Swimming Pool
                            </label>
                            
                            <input type="checkbox" id="parking" name="amenities[]" value="parking" class="amenity-checkbox"
                                   <?php echo (isset($_POST['amenities']) && in_array('parking', $_POST['amenities'])) ? 'checked' : ''; ?>>
                            <label for="parking" class="amenity-label">
                                <i class="fas fa-parking"></i> Free Parking
                            </label>
                            
                            <input type="checkbox" id="spa" name="amenities[]" value="spa" class="amenity-checkbox"
                                   <?php echo (isset($_POST['amenities']) && in_array('spa', $_POST['amenities'])) ? 'checked' : ''; ?>>
                            <label for="spa" class="amenity-label">
                                <i class="fas fa-spa"></i> Spa
                            </label>
                            
                            <input type="checkbox" id="gym" name="amenities[]" value="gym" class="amenity-checkbox"
                                   <?php echo (isset($_POST['amenities']) && in_array('gym', $_POST['amenities'])) ? 'checked' : ''; ?>>
                            <label for="gym" class="amenity-label">
                                <i class="fas fa-dumbbell"></i> Gym
                            </label>
                            
                            <input type="checkbox" id="restaurant" name="amenities[]" value="restaurant" class="amenity-checkbox"
                                   <?php echo (isset($_POST['amenities']) && in_array('restaurant', $_POST['amenities'])) ? 'checked' : ''; ?>>
                            <label for="restaurant" class="amenity-label">
                                <i class="fas fa-utensils"></i> Restaurant
                            </label>
                            
                            <input type="checkbox" id="breakfast" name="amenities[]" value="breakfast" class="amenity-checkbox"
                                   <?php echo (isset($_POST['amenities']) && in_array('breakfast', $_POST['amenities'])) ? 'checked' : ''; ?>>
                            <label for="breakfast" class="amenity-label">
                                <i class="fas fa-coffee"></i> Breakfast
                            </label>
                            
                            <input type="checkbox" id="bar" name="amenities[]" value="bar" class="amenity-checkbox"
                                   <?php echo (isset($_POST['amenities']) && in_array('bar', $_POST['amenities'])) ? 'checked' : ''; ?>>
                            <label for="bar" class="amenity-label">
                                <i class="fas fa-cocktail"></i> Bar
                            </label>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" name="add" class="submit-btn">
                            <i class="fas fa-plus-circle"></i> Add Hotel
                        </button>
                        <a href="dashboard.php" class="cancel-btn">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
                
                <div class="form-tips">
                    <h4><i class="fas fa-lightbulb"></i> Tips for Best Results</h4>
                    <ul>
                        <li>Use high-quality, well-lit photos of your hotel</li>
                        <li>Be specific about amenities and services</li>
                        <li>Set competitive prices based on your location and facilities</li>
                        <li>Provide accurate location information</li>
                        <li>Hotel will appear in search results after admin approval</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

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
            
            // Price formatting
            const priceInput = document.getElementById('price');
            priceInput.addEventListener('blur', function() {
                if (this.value) {
                    this.value = parseFloat(this.value).toFixed(2);
                }
            });
            
            // Form validation
            const form = document.getElementById('addHotelForm');
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
                
                // Validate price
                if (priceInput.value && parseFloat(priceInput.value) <= 0) {
                    isValid = false;
                    priceInput.style.borderColor = '#EF476F';
                    alert('Price must be greater than 0.');
                }
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields correctly.');
                }
            });
        });
    </script>
</body>
</html>