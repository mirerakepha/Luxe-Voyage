<?php
include "../includes/auth.php";
include "../config/db.php";
requireRole('host');

$hostId = $_SESSION['user']['id'];

// Get hotel count
$hotelCount = $conn->query(
    "SELECT COUNT(*) AS total FROM hotels WHERE host_id=$hostId"
)->fetch_assoc();

// Get booking count
$bookingCount = $conn->query(
    "SELECT COUNT(*) AS total
     FROM bookings b
     JOIN hotels h ON b.hotel_id = h.id
     WHERE h.host_id=$hostId"
)->fetch_assoc();

// Get recent bookings 
$recentBookings = $conn->query(
    "SELECT b.*, h.name as hotel_name, u.username as customer_name, u.email as customer_email
     FROM bookings b
     JOIN hotels h ON b.hotel_id = h.id
     JOIN users u ON b.customer_id = u.id
     WHERE h.host_id=$hostId
     ORDER BY b.check_in DESC
     LIMIT 5"
);

// Get recent hotels
$recentHotels = $conn->query(
    "SELECT * FROM hotels 
     WHERE host_id=$hostId 
     ORDER BY id DESC  -- Using id instead of created_at
     LIMIT 3"
);


// Get total revenue
$revenue = $conn->query(
    "SELECT SUM(
        DATEDIFF(b.check_out, b.check_in) * h.price
     ) as total_revenue
     FROM bookings b
     JOIN hotels h ON b.hotel_id = h.id
     WHERE h.host_id=$hostId AND b.status='confirmed'"
)->fetch_assoc();

// Get pending bookings count
$pendingBookings = $conn->query(
    "SELECT COUNT(*) as pending
     FROM bookings b
     JOIN hotels h ON b.hotel_id = h.id
     WHERE h.host_id=$hostId AND b.status='pending'"
)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Host Dashboard - Luxe Voyage</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/host_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Quick inline styles in case CSS doesn't load */
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; margin: 0; }
        .dashboard-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="dashboard-header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <h1><i class="fas fa-hotel"></i> Host Dashboard</h1>
                    <p class="welcome-message">Welcome back, <?php echo htmlspecialchars($_SESSION['user']['username']); ?>!</p>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <span class="user-email"><?php echo htmlspecialchars($_SESSION['user']['email']); ?></span>
                        <a href="../profile.php" class="profile-link">
                            <i class="fas fa-user-circle"></i> Profile
                        </a>
                        <a href="../logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Quick Stats -->
    <section class="quick-stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hotel"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $hotelCount['total']; ?></h3>
                        <p>Total Hotels</p>
                    </div>
                    <a href="#hotels" class="stat-link">View All <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $bookingCount['total']; ?></h3>
                        <p>Total Bookings</p>
                    </div>
                    <a href="bookings.php" class="stat-link">Manage <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $pendingBookings['pending']; ?></h3>
                        <p>Pending Bookings</p>
                    </div>
                    <a href="bookings.php?filter=pending" class="stat-link">Review <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($revenue['total_revenue'] ?? 0, 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                    <a href="bookings.php" class="stat-link">Details <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="dashboard-main">
        <div class="container">
            <div class="dashboard-grid">
                <!-- Left Column -->
                <div class="dashboard-left">
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <div class="action-buttons">
                                <a href="add_hotel.php" class="action-btn primary">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Add New Hotel</span>
                                </a>
                                <a href="bookings.php" class="action-btn secondary">
                                    <i class="fas fa-list-alt"></i>
                                    <span>View All Bookings</span>
                                </a>
                                <a href="../edit_profile.php" class="action-btn secondary">
                                    <i class="fas fa-user-edit"></i>
                                    <span>Edit Profile</span>
                                </a>
                                <a href="#analytics" class="action-btn secondary">
                                    <i class="fas fa-chart-line"></i>
                                    <span>View Analytics</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Hotels -->
                    <div class="card" id="hotels">
                        <div class="card-header">
                            <h3><i class="fas fa-building"></i> Recent Hotels</h3>
                            <a href="add_hotel.php" class="header-link">Add New <i class="fas fa-plus"></i></a>
                        </div>
                        <div class="card-body">
                            <?php if($recentHotels->num_rows > 0): ?>
                                <div class="hotels-list">
                                    <?php while($hotel = $recentHotels->fetch_assoc()): ?>
                                        <div class="hotel-item">
                                            <div class="hotel-info">
                                                <h4><?php echo htmlspecialchars($hotel['name']); ?></h4>
                                                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($hotel['location']); ?></p>
                                                <div class="hotel-meta">
                                                    <span class="price">$<?php echo number_format($hotel['price_per_night'], 2); ?>/night</span>
                                                    <span class="rating"><i class="fas fa-star"></i> <?php echo $hotel['rating'] ?? 'New'; ?></span>
                                                </div>
                                            </div>
                                            <div class="hotel-actions">
                                                <a href="bookings.php?hotel=<?php echo $hotel['id']; ?>" class="btn-small">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-hotel"></i>
                                    <h4>No Hotels Yet</h4>
                                    <p>Start by adding your first hotel property</p>
                                    <a href="add_hotel.php" class="btn-primary">
                                        <i class="fas fa-plus"></i> Add First Hotel
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="dashboard-right">
                    <!-- Recent Bookings -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-calendar-alt"></i> Recent Bookings</h3>
                            <a href="bookings.php" class="header-link">View All <i class="fas fa-arrow-right"></i></a>
                        </div>
                        <div class="card-body">
                            <?php if($recentBookings->num_rows > 0): ?>
                                <div class="bookings-table">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Customer</th>
                                                <th>Hotel</th>
                                                <th>Check-in</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($booking = $recentBookings->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <div class="customer-info">
                                                            <strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong>
                                                            <small><?php echo htmlspecialchars($booking['customer_email']); ?></small>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($booking['hotel_name']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                                    <td class="amount">$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                            <?php echo ucfirst($booking['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <h4>No Bookings Yet</h4>
                                    <p>You'll see bookings here once customers start booking your hotels</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Performance Stats -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-line"></i> Performance Overview</h3>
                        </div>
                        <div class="card-body">
                            <div class="performance-stats">
                                <div class="performance-item">
                                    <div class="perf-icon success">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="perf-content">
                                        <h4>95%</h4>
                                        <p>Booking Completion Rate</p>
                                    </div>
                                </div>
                                <div class="performance-item">
                                    <div class="perf-icon warning">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="perf-content">
                                        <h4><?php echo $pendingBookings['pending']; ?> pending</h4>
                                        <p>Require Action</p>
                                    </div>
                                </div>
                                <div class="performance-item">
                                    <div class="perf-icon info">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div class="perf-content">
                                        <h4>4.8</h4>
                                        <p>Average Rating</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Support Section -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-headset"></i> Need Help?</h3>
                        </div>
                        <div class="card-body">
                            <div class="support-info">
                                <p>Our support team is here to help you with any questions or issues.</p>
                                <div class="support-actions">
                                    <a href="mailto:support@luxevoyage.com" class="support-link">
                                        <i class="fas fa-envelope"></i> Email Support
                                    </a>
                                    <a href="#" class="support-link">
                                        <i class="fas fa-file-alt"></i> View Documentation
                                    </a>
                                    <a href="#" class="support-link">
                                        <i class="fas fa-video"></i> Tutorial Videos
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="dashboard-footer">
        <div class="container">
            <p>&copy; 2024 Luxe Voyage Host Dashboard. All rights reserved.</p>
            <div class="footer-links">
                <a href="../index.php">Home</a>
                <a href="../profile.php">Profile</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            </div>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
    <script>
        // Dashboard-specific JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Update welcome message based on time
            const hour = new Date().getHours();
            let greeting = 'Good ';
            if (hour < 12) greeting = 'Good morning';
            else if (hour < 18) greeting = 'Good afternoon';
            else greeting = 'Good evening';
            
            document.querySelector('.welcome-message').textContent = 
                `${greeting}, <?php echo htmlspecialchars($_SESSION['user']['username']); ?>!`;

            // Add click effects to stat cards
            document.querySelectorAll('.stat-card').forEach(card => {
                card.addEventListener('click', function() {
                    const link = this.querySelector('.stat-link');
                    if (link) {
                        window.location.href = link.href;
                    }
                });
            });

            // Auto-refresh dashboard every 5 minutes
            setTimeout(() => {
                window.location.reload();
            }, 300000); // 5 minutes
        });
    </script>
</body>
</html>