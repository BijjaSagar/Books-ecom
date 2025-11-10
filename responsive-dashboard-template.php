<?php
/**
 * Responsive Dashboard Template
 * Example dashboard page using responsive framework
 * Demonstrates all responsive components and utilities
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Books eCommerce</title>

    <!-- Responsive Framework -->
    <link rel="stylesheet" href="/css/responsive-framework.css">
    <link rel="stylesheet" href="/css/responsive-components.css">

    <style>
        /* Page-specific styles */
        .dashboard-welcome {
            background: linear-gradient(135deg, #1a3a52 0%, #2d5a7b 100%);
            color: white;
            padding: 40px 20px;
            border-radius: 8px;
            margin-bottom: 32px;
        }

        .dashboard-welcome h1 {
            color: white;
            margin-bottom: 8px;
        }

        .dashboard-welcome p {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 0;
        }

        .sidebar {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 24px;
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .sidebar h3 {
            margin-bottom: 16px;
            color: #1a3a52;
            font-size: 18px;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 8px;
        }

        .sidebar-menu a {
            display: block;
            padding: 12px 16px;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            transition: all 300ms;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: #f9f9f9;
            color: #1a3a52;
            font-weight: 600;
        }

        .content-area {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 24px;
        }

        /* Mobile menu for sidebar */
        @media (max-width: 991px) {
            .sidebar {
                position: static;
                margin-bottom: 24px;
            }

            .sidebar-toggle {
                display: block;
                width: 100%;
                padding: 12px;
                background: #1a3a52;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                margin-bottom: 16px;
                font-weight: 600;
            }

            .sidebar-menu {
                display: none;
                max-height: 0;
                overflow: hidden;
            }

            .sidebar-menu.active {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container flex flex-between">
            <a href="/" class="navbar-brand">
                📚 Books Store
            </a>
            <button class="navbar-toggler" aria-label="Toggle navigation" aria-expanded="false">
                ☰
            </button>
            <ul class="navbar-nav">
                <li><a href="/" class="nav-link">Home</a></li>
                <li><a href="/shop" class="nav-link">Shop</a></li>
                <li><a href="/dashboard" class="nav-link active">Dashboard</a></li>
                <li><a href="/cart" class="nav-link">Cart (3)</a></li>
                <li><a href="/logout" class="nav-link">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container" style="padding-top: 32px; padding-bottom: 32px;">
        <!-- Welcome Section -->
        <div class="dashboard-welcome">
            <h1>Welcome Back, Sarah! 👋</h1>
            <p>You have 2 new orders and 1 message from our support team.</p>
        </div>

        <!-- Dashboard Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">15</div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$1,250</div>
                <div class="stat-label">Lifetime Value</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">8</div>
                <div class="stat-label">Wishlist Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">1</div>
                <div class="stat-label">Open Tickets</div>
            </div>
        </div>

        <!-- Main Content with Sidebar -->
        <div class="sidebar-layout">
            <!-- Sidebar Menu -->
            <div class="sidebar">
                <h3>Menu</h3>
                <ul class="sidebar-menu">
                    <li><a href="#orders" class="active">📦 My Orders</a></li>
                    <li><a href="#wishlist">❤️ My Wishlist</a></li>
                    <li><a href="#downloads">⬇️ Downloads</a></li>
                    <li><a href="#tickets">💬 Support Tickets</a></li>
                    <li><a href="#addresses">📍 Addresses</a></li>
                    <li><a href="#settings">⚙️ Settings</a></li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="content-area">
                <!-- Section: Orders -->
                <h2>📦 My Recent Orders</h2>

                <div class="item-list">
                    <div class="list-item">
                        <img src="/images/book1.jpg" alt="Book" class="list-item-image">
                        <div class="list-item-content">
                            <div class="list-item-title">The Great Gatsby</div>
                            <div class="list-item-meta">Order #ORD-20251108-123456 • Nov 8, 2024</div>
                            <div class="list-item-price">$12.99</div>
                            <div class="list-item-meta">Status: <strong>Shipped</strong></div>
                        </div>
                        <div class="list-item-actions">
                            <button class="btn btn-primary">Track</button>
                            <button class="btn btn-outline">Details</button>
                        </div>
                    </div>

                    <div class="list-item">
                        <img src="/images/book2.jpg" alt="Book" class="list-item-image">
                        <div class="list-item-content">
                            <div class="list-item-title">To Kill a Mockingbird</div>
                            <div class="list-item-meta">Order #ORD-20251105-123455 • Nov 5, 2024</div>
                            <div class="list-item-price">$14.99</div>
                            <div class="list-item-meta">Status: <strong>Delivered</strong></div>
                        </div>
                        <div class="list-item-actions">
                            <button class="btn btn-primary">Review</button>
                            <button class="btn btn-outline">Reorder</button>
                        </div>
                    </div>

                    <div class="list-item">
                        <img src="/images/book3.jpg" alt="Book" class="list-item-image">
                        <div class="list-item-content">
                            <div class="list-item-title">1984</div>
                            <div class="list-item-meta">Order #ORD-20251101-123454 • Nov 1, 2024</div>
                            <div class="list-item-price">$13.99</div>
                            <div class="list-item-meta">Status: <strong>Delivered</strong></div>
                        </div>
                        <div class="list-item-actions">
                            <button class="btn btn-primary">Review</button>
                            <button class="btn btn-outline">Reorder</button>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <span class="disabled">← Previous</span>
                    <a href="#" class="active">1</a>
                    <a href="#">2</a>
                    <a href="#">3</a>
                    <a href="#">Next →</a>
                </div>

                <!-- Section: Wishlist (Tab) -->
                <div style="margin-top: 48px;">
                    <h2>❤️ My Wishlist</h2>
                    <p>You have 8 items saved</p>

                    <div class="product-grid">
                        <div class="product-card">
                            <img src="/images/book4.jpg" alt="Book" class="product-image">
                            <div class="product-info">
                                <div class="product-title">The Catcher in the Rye</div>
                                <div class="product-price">$10.99</div>
                                <div class="product-rating">★★★★★ (428)</div>
                                <div class="product-actions">
                                    <button class="btn btn-primary">Add to Cart</button>
                                    <button class="btn btn-outline">Remove</button>
                                </div>
                            </div>
                        </div>

                        <div class="product-card">
                            <img src="/images/book5.jpg" alt="Book" class="product-image">
                            <div class="product-info">
                                <div class="product-title">Pride and Prejudice</div>
                                <div class="product-price">$9.99</div>
                                <div class="product-rating">★★★★★ (512)</div>
                                <div class="product-actions">
                                    <button class="btn btn-primary">Add to Cart</button>
                                    <button class="btn btn-outline">Remove</button>
                                </div>
                            </div>
                        </div>

                        <div class="product-card">
                            <img src="/images/book6.jpg" alt="Book" class="product-image">
                            <div class="product-info">
                                <div class="product-title">Wuthering Heights</div>
                                <div class="product-price">$8.99</div>
                                <div class="product-rating">★★★★☆ (356)</div>
                                <div class="product-actions">
                                    <button class="btn btn-primary">Add to Cart</button>
                                    <button class="btn btn-outline">Remove</button>
                                </div>
                            </div>
                        </div>

                        <div class="product-card">
                            <img src="/images/book7.jpg" alt="Book" class="product-image">
                            <div class="product-info">
                                <div class="product-title">Jane Eyre</div>
                                <div class="product-price">$11.99</div>
                                <div class="product-rating">★★★★★ (489)</div>
                                <div class="product-actions">
                                    <button class="btn btn-primary">Add to Cart</button>
                                    <button class="btn btn-outline">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerts Section -->
                <div style="margin-top: 48px;">
                    <h2>Notifications</h2>

                    <div class="alert alert-success">
                        ✓ Your order ORD-20251108-123456 has been shipped! <a href="#">Track it here</a>
                    </div>

                    <div class="alert alert-info">
                        ℹ️ You have a response from our support team to your ticket TKT-20251110-ABC123
                    </div>

                    <div class="alert alert-warning">
                        ⚠️ Your wishlist item "The Hobbit" is now 20% off!
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h4>About Us</h4>
                    <a href="#">About Books Store</a>
                    <a href="#">Careers</a>
                    <a href="#">Press</a>
                    <a href="#">Blog</a>
                </div>
                <div class="footer-column">
                    <h4>Customer Service</h4>
                    <a href="#">Contact Us</a>
                    <a href="#">FAQ</a>
                    <a href="#">Returns</a>
                    <a href="#">Shipping Info</a>
                </div>
                <div class="footer-column">
                    <h4>Account</h4>
                    <a href="#">Login</a>
                    <a href="#">Register</a>
                    <a href="#">My Account</a>
                    <a href="#">Wishlist</a>
                </div>
                <div class="footer-column">
                    <h4>Legal</h4>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                    <a href="#">Accessibility</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Books eCommerce. All rights reserved. | Responsive Design by Team</p>
            </div>
        </div>
    </footer>

    <!-- Mobile Menu Script -->
    <script src="/js/mobile-menu.js"></script>
</body>
</html>
