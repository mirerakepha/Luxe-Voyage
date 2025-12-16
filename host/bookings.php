<?php
include "../includes/auth.php";
include "../config/db.php";
requireRole('host');

$hostId = $_SESSION['user']['id'];

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$hotel_id = $_GET['hotel'] ?? '';

// Build query with filters - FIXED VERSION
$query = "SELECT 
            b.id, b.customer_id, b.hotel_id, b.check_in, b.check_out, 
            b.status, b.created_at, b.total_amount,
            h.name AS hotel_name, h.image as hotel_image, h.location,
            u.username AS customer_name, u.email AS customer_email,
            DATE(b.check_in) as check_in_date, DATE(b.check_out) as check_out_date,
            d.name as destination_name
          FROM bookings b
          JOIN hotels h ON b.hotel_id = h.id
          JOIN users u ON b.customer_id = u.id
          JOIN destinations d ON h.destination_id = d.id
          WHERE h.host_id = ?";

          
$params = [$hostId];
$types = "i";

// Apply filters
if (!empty($status) && $status !== 'all') {
    $query .= " AND b.status = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($hotel_id)) {
    $query .= " AND h.id = ?";
    $params[] = $hotel_id;
    $types .= "i";
}

if (!empty($search)) {
    $query .= " AND (h.name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR d.name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
}

// Use created_at instead of booking_date, or if you added booking_date column
if (isset($_GET['date']) && $_GET['date'] !== '') {
    $query .= " AND DATE(b.created_at) = ?";
    $params[] = $_GET['date'];
    $types .= "s";
}

// Order by check_in date (newest first) or created_at
$query .= " ORDER BY b.check_in DESC";

// Get bookings
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$bookings = $stmt->get_result();

// Get stats
$stats_query = $conn->prepare(
    "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as pending,
        COALESCE(SUM(b.total_amount), 0) as revenue
     FROM bookings b
     JOIN hotels h ON b.hotel_id = h.id
     WHERE h.host_id = ?"
);
$stats_query->bind_param("i", $hostId);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();

// Get hotels for filter
$hotels_query = $conn->prepare("SELECT id, name FROM hotels WHERE host_id = ? ORDER BY name");
$hotels_query->bind_param("i", $hostId);
$hotels_query->execute();
$hotels = $hotels_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings Management - Luxe Voyage Host</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/host_dashboard.css">
    <link rel="stylesheet" href="../assets/css/host_bookings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Quick inline styles */
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; margin: 0; }
    </style>
</head>
<body>
    <div class="bookings-page">
        <!-- Header -->
        <header class="bookings-header">
            <div class="container">
                <div class="header-content">
                    <div class="header-left">
                        <h1><i class="fas fa-calendar-alt"></i> Bookings Management</h1>
                        <p>Manage and track all bookings for your hotels</p>
                    </div>
                    <div class="header-actions">
                        <a href="dashboard.php" class="action-btn">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                        <a href="add_hotel.php" class="action-btn primary">
                            <i class="fas fa-plus"></i> Add Hotel
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Stats Overview -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total'] ?? 0; ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
            
            <div class="stat-card confirmed">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['confirmed'] ?? 0; ?></h3>
                    <p>Confirmed</p>
                </div>
            </div>
            
            <div class="stat-card pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['pending'] ?? 0; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            
            <div class="stat-card cancelled">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['cancelled'] ?? 0; ?></h3>
                    <p>Cancelled</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <div class="filters-header">
                <h3><i class="fas fa-filter"></i> Filter Bookings</h3>
                <a href="bookings.php" class="reset-filters">
                    <i class="fas fa-redo"></i> Reset Filters
                </a>
            </div>
            <form method="GET" class="filters-grid">
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="filter-select" onchange="this.form.submit()">
                        <option value="all" <?php echo empty($status) || $status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="hotel">Hotel</label>
                    <select id="hotel" name="hotel" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Hotels</option>
                        <?php while($hotel = $hotels->fetch_assoc()): ?>
                            <option value="<?php echo $hotel['id']; ?>" 
                                <?php echo $hotel_id == $hotel['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($hotel['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="date_from">Date From</label>
                    <input type="date" id="date_from" name="date_from" class="filter-select">
                </div>
                
                <div class="filter-group">
                    <label for="date_to">Date To</label>
                    <input type="date" id="date_to" name="date_to" class="filter-select">
                </div>
            </form>
        </div>

        <!-- Bookings Table -->
        <div class="bookings-table-section">
            <div class="table-header">
                <h3><i class="fas fa-list"></i> Bookings List</h3>
                <div class="table-controls">
                    <div class="search-box">
                        <input type="text" placeholder="Search bookings..." value="<?php echo htmlspecialchars($search); ?>">
                        <i class="fas fa-search"></i>
                    </div>
                    <a href="#" class="export-btn">
                        <i class="fas fa-file-export"></i> Export
                    </a>
                </div>
            </div>

            <?php if ($bookings->num_rows > 0): ?>
                <div class="bookings-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Hotel</th>
                                <th>Check-in / Check-out</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($booking = $bookings->fetch_assoc()): 
                                $initials = strtoupper(substr($booking['customer_name'], 0, 2));
                                $status_class = 'status-' . $booking['status'];
                            ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                        <div class="date-sub">
                                            <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="booking-details">
                                            <div class="customer-avatar">
                                                <?php echo $initials; ?>
                                            </div>
                                            <div class="customer-info">
                                                <span class="customer-name"><?php echo htmlspecialchars($booking['customer_name']); ?></span>
                                                <span class="customer-email"><?php echo htmlspecialchars($booking['customer_email']); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="hotel-info">
                                            <div class="hotel-icon">
                                                <i class="fas fa-hotel"></i>
                                            </div>
                                            <div class="hotel-details">
                                                <span class="hotel-name"><?php echo htmlspecialchars($booking['hotel_name']); ?></span>
                                                <span class="hotel-location">
                                                    <i class="fas fa-map-marker-alt"></i> 
                                                    <?php echo htmlspecialchars($booking['location']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="date-cell">
                                            <span class="date-main"><?php echo date('M d, Y', strtotime($booking['check_in'])); ?></span>
                                            <span class="date-sub">to <?php echo date('M d, Y', strtotime($booking['check_out'])); ?></span>
                                        </div>
                                    </td>
                                    <td class="amount-cell">
                                        $<?php echo number_format($booking['total_amount'], 2); ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <i class="fas fa-circle"></i>
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-view" onclick="viewBooking(<?php echo $booking['id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <?php if ($booking['status'] === 'pending'): ?>
                                                <button class="btn-confirm" onclick="confirmBooking(<?php echo $booking['id']; ?>)">
                                                    <i class="fas fa-check"></i> Confirm
                                                </button>
                                                <button class="btn-cancel" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <a href="#" class="disabled">&laquo; Previous</a>
                    <a href="#" class="active">1</a>
                    <a href="#">2</a>
                    <a href="#">3</a>
                    <a href="#">Next &raquo;</a>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h4>No Bookings Found</h4>
                    <p>You don't have any bookings matching your filters. Try adjusting your search criteria.</p>
                    <a href="bookings.php" class="action-btn primary">
                        <i class="fas fa-redo"></i> Reset Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <footer class="bookings-footer">
            <div class="container">
                <p>&copy; 2024 Luxe Voyage Host Dashboard. Showing <?php echo $bookings->num_rows; ?> bookings</p>
            </div>
        </footer>
    </div>

    <!-- Booking Details Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> Booking Details</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Modal content will be loaded via AJAX -->
                <div id="modalContent">
                    <div style="text-align: center; padding: 2rem;">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>Loading booking details...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-confirm" onclick="confirmBooking(selectedBookingId)">
                    <i class="fas fa-check"></i> Confirm Booking
                </button>
                <button class="btn-cancel" onclick="cancelBooking(selectedBookingId)">
                    <i class="fas fa-times"></i> Cancel Booking
                </button>
                <button class="cancel-btn" onclick="closeModal()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        let selectedBookingId = null;
        const modal = document.getElementById('bookingModal');
        const modalContent = document.getElementById('modalContent');

        function viewBooking(bookingId) {
            selectedBookingId = bookingId;
            
            // Show loading state
            modalContent.innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Loading booking details...</p>
                </div>
            `;
            
            // Show modal
            modal.style.display = 'flex';
            
            // Simulate loading booking details
            setTimeout(() => {
                modalContent.innerHTML = `
                    <div class="booking-details-modal">
                        <div class="detail-group">
                            <h4><i class="fas fa-id-card"></i> Booking Information</h4>
                            <p><strong>Booking ID:</strong> #${String(bookingId).padStart(6, '0')}</p>
                            <p><strong>Booking Date:</strong> ${new Date().toLocaleDateString()}</p>
                        </div>
                        
                        <div class="detail-group">
                            <h4><i class="fas fa-user"></i> Customer Details</h4>
                            <p><strong>Name:</strong> Customer Name</p>
                            <p><strong>Email:</strong> customer@example.com</p>
                            <p><strong>Phone:</strong> +1 (555) 123-4567</p>
                        </div>
                        
                        <div class="detail-group">
                            <h4><i class="fas fa-hotel"></i> Hotel Information</h4>
                            <p><strong>Hotel:</strong> Sample Hotel</p>
                            <p><strong>Location:</strong> Sample City</p>
                            <p><strong>Room Type:</strong> Deluxe Suite</p>
                        </div>
                        
                        <div class="detail-group">
                            <h4><i class="fas fa-calendar"></i> Stay Details</h4>
                            <p><strong>Check-in:</strong> ${new Date(Date.now() + 86400000 * 7).toLocaleDateString()}</p>
                            <p><strong>Check-out:</strong> ${new Date(Date.now() + 86400000 * 14).toLocaleDateString()}</p>
                            <p><strong>Nights:</strong> 7</p>
                            <p><strong>Guests:</strong> 2 Adults, 1 Child</p>
                        </div>
                        
                        <div class="detail-group">
                            <h4><i class="fas fa-money-bill-wave"></i> Payment Details</h4>
                            <p><strong>Total Amount:</strong> $1,299.99</p>
                            <p><strong>Payment Method:</strong> Credit Card</p>
                            <p><strong>Payment Status:</strong> Paid</p>
                        </div>
                    </div>
                `;
            }, 500);
        }

        function confirmBooking(bookingId) {
            if (confirm('Are you sure you want to confirm this booking?')) {
                // Send AJAX request to confirm booking
                fetch(`confirm_booking.php?id=${bookingId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Booking confirmed successfully!');
                        location.reload();
                    } else {
                        alert('Error confirming booking: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error confirming booking. Please try again.');
                });
            }
        }

        function cancelBooking(bookingId) {
            if (confirm('Are you sure you want to cancel this booking?')) {
                // Send AJAX request to cancel booking
                fetch(`cancel_booking.php?id=${bookingId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Booking cancelled successfully!');
                        location.reload();
                    } else {
                        alert('Error cancelling booking: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error cancelling booking. Please try again.');
                });
            }
        }

        function closeModal() {
            modal.style.display = 'none';
            selectedBookingId = null;
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        // Search functionality
        document.querySelector('.search-box input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                if (searchTerm) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('search', searchTerm);
                    window.location.href = url.toString();
                }
            }
        });

        // Update stats every 30 seconds
        setInterval(() => {
            // Could implement AJAX refresh here
        }, 30000);
    </script>
</body>
</html>