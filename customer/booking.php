<?php
session_start();
include "../includes/auth.php";
include "../config/db.php";
requireRole('customer');

$user_id = $_SESSION['user']['id'];
$success_message = '';
$error_message = '';

// Get hotel details if hotel_id is provided
$hotel = null;
if (isset($_GET['hotel_id']) || isset($_POST['hotel_id'])) {
    $hotel_id = $_GET['hotel_id'] ?? $_POST['hotel_id'];
    $hotel_query = $conn->prepare(
        "SELECT h.*, d.name as destination_name 
         FROM hotels h 
         JOIN destinations d ON h.destination_id = d.id 
         WHERE h.id = ?"
    );
    $hotel_query->bind_param("i", $hotel_id);
    $hotel_query->execute();
    $hotel_result = $hotel_query->get_result();
    $hotel = $hotel_result->fetch_assoc();
}

if (isset($_POST['book']) && $hotel) {
    $hotel_id = $_POST['hotel_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $guests = $_POST['guests'] ?? 1;
    $special_requests = $_POST['special_requests'] ?? '';
    
    // Validate dates
    $today = date('Y-m-d');
    if ($check_in < $today) {
        $error_message = "Check-in date cannot be in the past.";
    } elseif ($check_out <= $check_in) {
        $error_message = "Check-out date must be after check-in date.";
    } else {
        // Calculate total price
        $nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
        $total_amount = $nights * $hotel['price'] * $guests;
        
        $stmt = $conn->prepare(
            "INSERT INTO bookings (user_id, hotel_id, check_in_date, check_out_date, guests, total_amount, special_requests, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
        );
        $stmt->bind_param("iissids", $user_id, $hotel_id, $check_in, $check_out, $guests, $total_amount, $special_requests);
        
        if ($stmt->execute()) {
            $success_message = "Booking request submitted successfully!";
            $booking_id = $conn->insert_id;
            
            // Clear form
            $_POST = array();
            $hotel = null;
        } else {
            $error_message = "Error creating booking: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Hotel - Luxe Voyage</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/customer_booking.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Additional inline styles */
        body { font-family: 'Poppins', sans-serif; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
    </style>
</head>
<body>
    <div class="booking-page">
        <!-- Header -->
        <header class="booking-header">
            <div class="container">
                <div class="booking-header-content">
                    <div class="booking-title">
                        <i class="fas fa-calendar-check"></i>
                        <div>
                            <h1>Complete Your Booking</h1>
                            <p class="booking-subtitle">Secure your luxury stay in just a few steps</p>
                        </div>
                    </div>
                    
                    <a href="dashboard.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
                
                <!-- Booking Steps -->
                <div class="process-steps">
                    <div class="step active">
                        <div class="step-icon">
                            <i class="fas fa-bed"></i>
                        </div>
                        <span class="step-label">Select Hotel</span>
                    </div>
                    
                    <div class="step <?php echo $hotel ? 'active' : ''; ?>">
                        <div class="step-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <span class="step-label">Choose Dates</span>
                    </div>
                    
                    <div class="step <?php echo isset($_POST['book']) ? 'active' : ''; ?>">
                        <div class="step-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <span class="step-label">Payment</span>
                    </div>
                    
                    <div class="step">
                        <div class="step-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <span class="step-label">Confirmation</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="booking-process">
            <div class="container">
                <?php if ($success_message): ?>
                    <div class="booking-card">
                        <div class="success-message">
                            <div class="success-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <h2>Booking Request Submitted!</h2>
                            <p>Your booking request has been received. The hotel will review and confirm your reservation within 24 hours. You'll receive an email confirmation once approved.</p>
                            
                            <div class="success-actions">
                                <a href="dashboard.php" class="btn-primary">
                                    <i class="fas fa-home"></i> Back to Dashboard
                                </a>
                                <a href="booking.php?view=mybookings" class="btn-secondary">
                                    <i class="fas fa-list"></i> View My Bookings
                                </a>
                            </div>
                        </div>
                    </div>
                
                <?php elseif (!$hotel && !isset($_GET['hotel_id'])): ?>
                    <!-- Hotel Selection -->
                    <div class="booking-card">
                        <div class="hotel-preview-section">
                            <div class="section-title">
                                <i class="fas fa-hotel"></i>
                                <h2>Select a Hotel</h2>
                            </div>
                            
                            <div style="text-align: center; padding: 3rem;">
                                <i class="fas fa-hotel fa-3x" style="color: #cbd5e0; margin-bottom: 1rem;"></i>
                                <h3 style="color: #4a5568; margin-bottom: 0.5rem;">No Hotel Selected</h3>
                                <p style="color: #718096; margin-bottom: 1.5rem;">Please select a hotel from the dashboard to book</p>
                                <a href="dashboard.php" class="btn-primary">
                                    <i class="fas fa-search"></i> Browse Hotels
                                </a>
                            </div>
                        </div>
                    </div>
                
                <?php elseif ($hotel): ?>
                    <!-- Booking Form -->
                    <form method="POST" action="" class="booking-card">
                        <input type="hidden" name="hotel_id" value="<?php echo $hotel['id']; ?>">
                        
                        <!-- Hotel Preview -->
                        <div class="hotel-preview-section">
                            <div class="section-title">
                                <i class="fas fa-hotel"></i>
                                <h2>Your Selected Hotel</h2>
                            </div>
                            
                            <div class="hotel-preview">
                                <div class="hotel-image">
                                    <img src="../uploads/hotels/<?php echo htmlspecialchars($hotel['image'] ?? 'default_hotel.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($hotel['name']); ?>">
                                </div>
                                
                                <div class="hotel-details">
                                    <h2 class="hotel-name"><?php echo htmlspecialchars($hotel['name']); ?></h2>
                                    
                                    <div class="hotel-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($hotel['location']); ?>, 
                                        <?php echo htmlspecialchars($hotel['destination_name']); ?>
                                    </div>
                                    
                                    <div class="hotel-rating">
                                        <i class="fas fa-star"></i>
                                        <span><?php echo $hotel['rating'] ?? '4.5'; ?> (<?php echo rand(50, 500); ?> reviews)</span>
                                    </div>
                                    
                                    <div class="hotel-price">
                                        $<?php echo number_format($hotel['price'], 2); ?>
                                        <span class="price-period">per night</span>
                                    </div>
                                    
                                    <div class="hotel-amenities">
                                        <?php
                                        $amenities = explode(',', $hotel['amenities'] ?? '');
                                        $amenity_icons = [
                                            'wifi' => 'fa-wifi',
                                            'pool' => 'fa-swimming-pool',
                                            'parking' => 'fa-parking',
                                            'spa' => 'fa-spa',
                                            'gym' => 'fa-dumbbell',
                                            'restaurant' => 'fa-utensils',
                                            'breakfast' => 'fa-coffee',
                                            'bar' => 'fa-cocktail'
                                        ];
                                        
                                        foreach ($amenities as $amenity):
                                            if (isset($amenity_icons[$amenity])):
                                        ?>
                                            <span class="amenity-tag">
                                                <i class="fas <?php echo $amenity_icons[$amenity]; ?>"></i>
                                                <?php echo ucfirst($amenity); ?>
                                            </span>
                                        <?php endif; endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($error_message): ?>
                            <div style="background: rgba(239, 71, 111, 0.1); color: #EF476F; padding: 1rem; margin: 0 2rem; border-radius: 8px; border-left: 4px solid #EF476F;">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Booking Details -->
                        <div class="booking-form-section">
                            <div class="section-title">
                                <i class="fas fa-calendar-alt"></i>
                                <h2>Booking Details</h2>
                            </div>
                            
                            <div class="booking-form">
                                <div class="form-group">
                                    <label class="form-label">Check-in Date <span class="required">*</span></label>
                                    <input type="date" name="check_in" class="form-control" 
                                           value="<?php echo $_POST['check_in'] ?? date('Y-m-d', strtotime('+7 days')); ?>"
                                           min="<?php echo date('Y-m-d'); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Check-out Date <span class="required">*</span></label>
                                    <input type="date" name="check_out" class="form-control" 
                                           value="<?php echo $_POST['check_out'] ?? date('Y-m-d', strtotime('+14 days')); ?>"
                                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Number of Guests <span class="required">*</span></label>
                                    <div class="guests-selector">
                                        <div class="guest-counter">
                                            <button type="button" class="counter-btn" onclick="updateGuests(-1)">-</button>
                                            <span class="counter-value" id="guestCount"><?php echo $_POST['guests'] ?? 2; ?></span>
                                            <button type="button" class="counter-btn" onclick="updateGuests(1)">+</button>
                                        </div>
                                        <input type="hidden" name="guests" id="guestsInput" value="<?php echo $_POST['guests'] ?? 2; ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label class="form-label">Special Requests (Optional)</label>
                                    <textarea name="special_requests" class="form-control" 
                                              placeholder="Any special requests or requirements for your stay..."><?php echo $_POST['special_requests'] ?? ''; ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Price Summary -->
                            <div class="price-summary">
                                <h3 class="summary-title">
                                    <i class="fas fa-receipt"></i> Price Summary
                                </h3>
                                
                                <div class="price-details">
                                    <div class="price-row">
                                        <span class="price-label">Room Rate (per night)</span>
                                        <span class="price-value" id="roomRate">$<?php echo number_format($hotel['price'], 2); ?></span>
                                    </div>
                                    
                                    <div class="price-row">
                                        <span class="price-label">Number of Nights</span>
                                        <span class="price-value" id="nightsCount">0</span>
                                    </div>
                                    
                                    <div class="price-row">
                                        <span class="price-label">Number of Guests</span>
                                        <span class="price-value" id="guestsCount">2</span>
                                    </div>
                                    
                                    <div class="price-row">
                                        <span class="price-label">Service Fee</span>
                                        <span class="price-value">$49.99</span>
                                    </div>
                                    
                                    <div class="price-row" style="border-bottom: none; padding-top: 1rem;">
                                        <span class="price-label price-total">Total Amount</span>
                                        <span class="price-value total-price" id="totalPrice">$0.00</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payment Method -->
                            <div class="payment-methods">
                                <h3 class="summary-title">
                                    <i class="fas fa-credit-card"></i> Payment Method
                                </h3>
                                
                                <div class="payment-options">
                                    <div class="payment-option">
                                        <input type="radio" id="creditCard" name="payment_method" value="credit_card" class="payment-radio" checked>
                                        <label for="creditCard" class="payment-label">
                                            <div class="payment-icon">
                                                <i class="fas fa-credit-card"></i>
                                            </div>
                                            <div class="payment-info">
                                                <h4>Credit Card</h4>
                                                <p>Pay with Visa, MasterCard, or Amex</p>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div class="payment-option">
                                        <input type="radio" id="paypal" name="payment_method" value="paypal" class="payment-radio">
                                        <label for="paypal" class="payment-label">
                                            <div class="payment-icon">
                                                <i class="fab fa-paypal"></i>
                                            </div>
                                            <div class="payment-info">
                                                <h4>PayPal</h4>
                                                <p>Fast and secure online payment</p>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div class="payment-option">
                                        <input type="radio" id="bankTransfer" name="payment_method" value="bank_transfer" class="payment-radio">
                                        <label for="bankTransfer" class="payment-label">
                                            <div class="payment-icon">
                                                <i class="fas fa-university"></i>
                                            </div>
                                            <div class="payment-info">
                                                <h4>Bank Transfer</h4>
                                                <p>Direct bank transfer payment</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Footer Actions -->
                        <div class="booking-actions-footer">
                            <div class="secure-notice">
                                <i class="fas fa-shield-alt"></i>
                                <span>Your payment is secured with 256-bit SSL encryption</span>
                            </div>
                            
                            <div class="action-buttons">
                                <a href="dashboard.php" class="btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" name="book" class="btn-primary">
                                    <i class="fas fa-lock"></i> Complete Booking
                                </button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkInInput = document.querySelector('input[name="check_in"]');
            const checkOutInput = document.querySelector('input[name="check_out"]');
            const roomRate = <?php echo $hotel['price'] ?? 0; ?>;
            
            // Update date validation
            if (checkInInput && checkOutInput) {
                checkInInput.addEventListener('change', function() {
                    if (this.value) {
                        const nextDay = new Date(this.value);
                        nextDay.setDate(nextDay.getDate() + 1);
                        const nextDayStr = nextDay.toISOString().split('T')[0];
                        
                        checkOutInput.min = nextDayStr;
                        if (checkOutInput.value && checkOutInput.value < nextDayStr) {
                            checkOutInput.value = nextDayStr;
                        }
                        
                        updatePriceSummary();
                    }
                });
                
                checkOutInput.addEventListener('change', updatePriceSummary);
            }
            
            // Update price summary
            function updatePriceSummary() {
                if (!checkInInput || !checkOutInput || !checkInInput.value || !checkOutInput.value) {
                    return;
                }
                
                const checkIn = new Date(checkInInput.value);
                const checkOut = new Date(checkOutInput.value);
                const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
                const guests = parseInt(document.getElementById('guestsInput').value);
                
                if (nights > 0) {
                    const basePrice = roomRate * nights * guests;
                    const serviceFee = 49.99;
                    const total = basePrice + serviceFee;
                    
                    document.getElementById('nightsCount').textContent = nights;
                    document.getElementById('guestsCount').textContent = guests;
                    document.getElementById('totalPrice').textContent = '$' + total.toFixed(2);
                }
            }
            
            // Initialize price summary
            updatePriceSummary();
            
            // Guest counter functionality
            window.updateGuests = function(change) {
                const guestCount = document.getElementById('guestCount');
                const guestsInput = document.getElementById('guestsInput');
                let current = parseInt(guestCount.textContent);
                
                current += change;
                if (current < 1) current = 1;
                if (current > 10) current = 10;
                
                guestCount.textContent = current;
                guestsInput.value = current;
                
                updatePriceSummary();
            };
            
            // Form validation
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!checkInInput.value || !checkOutInput.value) {
                        e.preventDefault();
                        alert('Please select both check-in and check-out dates.');
                        return;
                    }
                    
                    const checkIn = new Date(checkInInput.value);
                    const checkOut = new Date(checkOutInput.value);
                    
                    if (checkOut <= checkIn) {
                        e.preventDefault();
                        alert('Check-out date must be after check-in date.');
                        return;
                    }
                    
                    if (!confirm('Are you ready to complete your booking?')) {
                        e.preventDefault();
                    }
                });
            }
            
            // Initialize date inputs
            const today = new Date().toISOString().split('T')[0];
            const defaultCheckIn = new Date(Date.now() + 7 * 86400000).toISOString().split('T')[0];
            const defaultCheckOut = new Date(Date.now() + 14 * 86400000).toISOString().split('T')[0];
            
            if (checkInInput && !checkInInput.value) {
                checkInInput.value = defaultCheckIn;
                checkInInput.min = today;
            }
            
            if (checkOutInput && !checkOutInput.value) {
                checkOutInput.value = defaultCheckOut;
                checkOutInput.min = defaultCheckIn;
            }
        });
    </script>
</body>
</html>