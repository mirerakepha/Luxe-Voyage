<?php
include "../includes/auth.php";
include "../config/db.php";
requireRole('admin');

$admin_name = $_SESSION['user']['username'];
$admin_email = $_SESSION['user']['email'];
$admin_initials = strtoupper(substr($admin_name, 0, 2));

// Get basic counts
$destinations = $conn->query("SELECT COUNT(*) AS total FROM destinations")->fetch_assoc();
$users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc();
$hotels = $conn->query("SELECT COUNT(*) AS total FROM hotels")->fetch_assoc();
$bookings = $conn->query("SELECT COUNT(*) AS total FROM bookings")->fetch_assoc();

// Get recent bookings
$recent_bookings = $conn->query(
    "SELECT b.*, u.username, h.name as hotel_name
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     JOIN hotels h ON b.hotel_id = h.id
     ORDER BY b.booking_date DESC
     LIMIT 5"
);

// Get recent users
$recent_users = $conn->query(
    "SELECT * FROM users 
     ORDER BY created_at DESC 
     LIMIT 5"
);

// Get today's bookings
$today_bookings = $conn->query(
    "SELECT COUNT(*) as total, SUM(total_amount) as revenue 
     FROM bookings 
     WHERE DATE(booking_date) = CURDATE()"
)->fetch_assoc();

// Get pending bookings
$pending_bookings = $conn->query(
    "SELECT COUNT(*) as total 
     FROM bookings 
     WHERE status = 'pending'"
)->fetch_assoc();

// Get user growth this month
$month_growth = $conn->query(
    "SELECT COUNT(*) as growth 
     FROM users 
     WHERE MONTH(created_at) = MONTH(CURDATE()) 
     AND YEAR(created_at) = YEAR(CURDATE())"
)->fetch_assoc();

// Get booking growth this month
$booking_growth = $conn->query(
    "SELECT COUNT(*) as growth 
     FROM bookings 
     WHERE MONTH(booking_date) = MONTH(CURDATE()) 
     AND YEAR(booking_date) = YEAR(CURDATE())"
)->fetch_assoc();

// Get revenue this month
$month_revenue = $conn->query(
    "SELECT SUM(total_amount) as revenue 
     FROM bookings 
     WHERE MONTH(booking_date) = MONTH(CURDATE()) 
     AND YEAR(booking_date) = YEAR(CURDATE())
     AND status = 'confirmed'"
)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Luxe Voyage</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Additional inline styles */
        body { font-family: 'Poppins', sans-serif; margin: 0; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 1rem; }
    </style>
</head>
<body class="admin-dashboard">
    <!-- Header -->
    <header class="admin-header">
        <div class="container">
            <div class="header-content">
                <div class="brand">
                    <div class="brand-logo">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="brand-text">
                        <h1>Luxe Voyage Admin</h1>
                        <p>Premium Travel Management System</p>
                    </div>
                </div>
                
                <div class="admin-controls">
                    <div class="admin-profile">
                        <div class="avatar">
                            <?php echo $admin_initials; ?>
                        </div>
                        <div class="profile-info">
                            <span class="profile-name"><?php echo htmlspecialchars($admin_name); ?></span>
                            <span class="profile-role">Administrator</span>
                        </div>
                    </div>
                    
                    <a href="../logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="dashboard-content">
        <div class="container">
            <!-- Welcome Section -->
            <section class="welcome-section">
                <div class="welcome-content">
                    <h2>Welcome back, <?php echo htmlspecialchars(explode(' ', $admin_name)[0]); ?>! 👋</h2>
                    <p class="welcome-text">
                        Here's what's happening with your platform today. Monitor activities, manage content, 
                        and ensure everything runs smoothly for premium travelers worldwide.
                    </p>
                    <div class="current-time">
                        <i class="fas fa-clock"></i>
                        <span id="currentDateTime"><?php echo date('l, F j, Y - g:i A'); ?></span>
                    </div>
                </div>
            </section>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card users">
                    <div class="stat-header">
                        <div class="stat-title">Total Users</div>
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $users['total']; ?></div>
                    <div class="stat-change change-up">
                        <i class="fas fa-arrow-up"></i>
                        <span><?php echo $month_growth['growth'] ?? 0; ?> this month</span>
                    </div>
                    <div class="stat-period">Updated just now</div>
                </div>
                
                <div class="stat-card destinations">
                    <div class="stat-header">
                        <div class="stat-title">Destinations</div>
                        <div class="stat-icon">
                            <i class="fas fa-globe-americas"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $destinations['total']; ?></div>
                    <div class="stat-change change-up">
                        <i class="fas fa-plus"></i>
                        <span>Ready to add more</span>
                    </div>
                    <div class="stat-period">Across <?php echo $destinations['total'] > 0 ? $destinations['total'] : 0; ?> countries</div>
                </div>
                
                <div class="stat-card hotels">
                    <div class="stat-header">
                        <div class="stat-title">Hotels Listed</div>
                        <div class="stat-icon">
                            <i class="fas fa-hotel"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $hotels['total']; ?></div>
                    <div class="stat-change change-up">
                        <i class="fas fa-chart-line"></i>
                        <span><?php echo round($hotels['total'] / max($destinations['total'], 1), 1); ?> per destination</span>
                    </div>
                    <div class="stat-period">Premium properties worldwide</div>
                </div>
                
                <div class="stat-card bookings">
                    <div class="stat-header">
                        <div class="stat-title">Total Bookings</div>
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $bookings['total']; ?></div>
                    <div class="stat-change change-up">
                        <i class="fas fa-arrow-up"></i>
                        <span><?php echo $booking_growth['growth'] ?? 0; ?> this month</span>
                    </div>
                    <div class="stat-period"><?php echo $today_bookings['total'] ?? 0; ?> today</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <section class="actions-section">
                <div class="section-header">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <a href="#" class="view-all">
                        View All Tools <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="actions-grid">
                    <a href="add_destination.php" class="action-card primary">
                        <div class="action-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h4 class="action-title">Add Destination</h4>
                        <p class="action-desc">Add new travel destinations to the platform with images and descriptions.</p>
                        <div class="action-arrow">
                            <span>Get Started</span>
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </a>
                    
                    <a href="#" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <h4 class="action-title">Manage Users</h4>
                        <p class="action-desc">View and manage user accounts, roles, and permissions.</p>
                        <div class="action-arrow">
                            <span>Manage</span>
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </a>
                    
                    <a href="#" class="action-card secondary">
                        <div class="action-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h4 class="action-title">View Analytics</h4>
                        <p class="action-desc">Access detailed reports and analytics on bookings and revenue.</p>
                        <div class="action-arrow">
                            <span>View Reports</span>
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </a>
                    
                    <a href="#" class="action-card warning">
                        <div class="action-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h4 class="action-title">System Settings</h4>
                        <p class="action-desc">Configure platform settings, payment gateways, and email templates.</p>
                        <div class="action-arrow">
                            <span>Configure</span>
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </a>
                </div>
            </section>

            <div class="dashboard-row">
                <div class="dashboard-col">
                    <!-- Recent Activity -->
                    <section class="activity-section">
                        <div class="section-header">
                            <h3><i class="fas fa-history"></i> Recent Activity</h3>
                            <a href="#" class="view-all">
                                View Full Log <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        
                        <div class="activity-list">
                            <!-- Recent Bookings -->
                            <?php while($booking = $recent_bookings->fetch_assoc()): ?>
                                <div class="activity-item">
                                    <div class="activity-icon success">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">New Booking</div>
                                        <p class="activity-desc">
                                            <?php echo htmlspecialchars($booking['username']); ?> booked 
                                            <?php echo htmlspecialchars($booking['hotel_name']); ?>
                                        </p>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('H:i', strtotime($booking['booking_date'])); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            
                            <!-- Recent Users -->
                            <?php while($user = $recent_users->fetch_assoc()): ?>
                                <div class="activity-item">
                                    <div class="activity-icon info">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">New User Registered</div>
                                        <p class="activity-desc">
                                            <?php echo htmlspecialchars($user['username']); ?> 
                                            (<?php echo htmlspecialchars($user['email']); ?>)
                                        </p>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('H:i', strtotime($user['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            
                            <!-- System Activities -->
                            <div class="activity-item">
                                <div class="activity-icon warning">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">System Maintenance</div>
                                    <p class="activity-desc">Monthly database optimization completed</p>
                                </div>
                                <div class="activity-time">15:30</div>
                            </div>
                            
                            <div class="activity-item">
                                <div class="activity-icon success">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">Revenue Update</div>
                                    <p class="activity-desc">
                                        $<?php echo number_format($month_revenue['revenue'] ?? 0, 2); ?> 
                                        generated this month
                                    </p>
                                </div>
                                <div class="activity-time">Yesterday</div>
                            </div>
                        </div>
                    </section>
                </div>
                
                <div class="dashboard-col">
                    <!-- System Status -->
                    <section class="system-status">
                        <div class="section-header">
                            <h3><i class="fas fa-server"></i> System Status</h3>
                        </div>
                        
                        <div class="status-grid">
                            <div class="status-item">
                                <div class="status-indicator online"></div>
                                <div class="status-info">
                                    <h4>Web Server</h4>
                                    <p>Apache running normally</p>
                                </div>
                            </div>
                            
                            <div class="status-item">
                                <div class="status-indicator online"></div>
                                <div class="status-info">
                                    <h4>Database</h4>
                                    <p>MySQL connected (<?php echo $conn->ping() ? 'Online' : 'Offline'; ?>)</p>
                                </div>
                            </div>
                            
                            <div class="status-item">
                                <div class="status-indicator online"></div>
                                <div class="status-info">
                                    <h4>Storage</h4>
                                    <p><?php 
                                        $free = disk_free_space("/");
                                        $total = disk_total_space("/");
                                        $used_percent = round((($total - $free) / $total) * 100, 1);
                                        echo $used_percent; ?>% used
                                    </p>
                                </div>
                            </div>
                            
                            <div class="status-item">
                                <div class="status-indicator <?php echo ($pending_bookings['total'] ?? 0) > 0 ? 'warning' : 'online'; ?>"></div>
                                <div class="status-info">
                                    <h4>Pending Actions</h4>
                                    <p><?php echo $pending_bookings['total'] ?? 0; ?> bookings pending review</p>
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Revenue Summary -->
                    <section class="activity-section" style="margin-top: 1.5rem;">
                        <div class="section-header">
                            <h3><i class="fas fa-chart-line"></i> Revenue Summary</h3>
                        </div>
                        
                        <div class="activity-list">
                            <div class="activity-item">
                                <div class="activity-content">
                                    <div class="activity-title">Today's Revenue</div>
                                    <p class="activity-desc">
                                        $<?php echo number_format($today_bookings['revenue'] ?? 0, 2); ?>
                                    </p>
                                </div>
                                <div class="activity-time"><?php echo $today_bookings['total'] ?? 0; ?> bookings</div>
                            </div>
                            
                            <div class="activity-item">
                                <div class="activity-content">
                                    <div class="activity-title">This Month</div>
                                    <p class="activity-desc">
                                        $<?php echo number_format($month_revenue['revenue'] ?? 0, 2); ?>
                                    </p>
                                </div>
                                <div class="activity-time"><?php echo $booking_growth['growth'] ?? 0; ?> bookings</div>
                            </div>
                            
                            <div class="activity-item">
                                <div class="activity-content">
                                    <div class="activity-title">Average Booking Value</div>
                                    <p class="activity-desc">
                                        $<?php 
                                            $avg_value = $bookings['total'] > 0 ? 
                                                (($month_revenue['revenue'] ?? 0) / max($booking_growth['growth'] ?? 1, 1)) : 0;
                                            echo number_format($avg_value, 2); 
                                        ?>
                                    </p>
                                </div>
                                <div class="activity-time">Per booking</div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="admin-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Luxe Voyage Admin Dashboard. All rights reserved.</p>
            <div class="footer-links">
                <a href="../index.php">Visit Website</a>
                <a href="#">Documentation</a>
                <a href="#">Support Center</a>
                <a href="#">API Documentation</a>
                <a href="#">Privacy Policy</a>
            </div>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update current time
            function updateDateTime() {
                const now = new Date();
                const options = { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                };
                document.getElementById('currentDateTime').textContent = 
                    now.toLocaleDateString('en-US', options);
            }
            
            updateDateTime();
            setInterval(updateDateTime, 1000);
            
            // Animate stat cards on scroll
            const observerOptions = {
                threshold: 0.2,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);
            
            // Observe stat cards
            document.querySelectorAll('.stat-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(card);
            });
            
            // Add CSS for dashboard columns
            const style = document.createElement('style');
            style.textContent = `
                .dashboard-row {
                    display: grid;
                    grid-template-columns: 1fr;
                    gap: 1.5rem;
                }
                
                @media (min-width: 1024px) {
                    .dashboard-row {
                        grid-template-columns: 2fr 1fr;
                    }
                }
                
                .stat-card:nth-child(1) { transition-delay: 0.1s; }
                .stat-card:nth-child(2) { transition-delay: 0.2s; }
                .stat-card:nth-child(3) { transition-delay: 0.3s; }
                .stat-card:nth-child(4) { transition-delay: 0.4s; }
            `;
            document.head.appendChild(style);
            
            // Auto-refresh data every 2 minutes
            setInterval(() => {
                // You could implement AJAX refresh here
                console.log('Dashboard data refresh check...');
            }, 120000);
            
            // Add click effect to action cards
            document.querySelectorAll('.action-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    if (this.getAttribute('href') === '#') {
                        e.preventDefault();
                        // Show coming soon message for demo links
                        alert('This feature is coming soon!');
                    }
                });
            });
        });
    </script>
</body>
</html>