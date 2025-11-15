<?php
/**
 * Responsive Shop Template
 * Product browsing and filtering on all devices
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Books eCommerce</title>

    <link rel="stylesheet" href="/css/responsive-framework.css">
    <link rel="stylesheet" href="/css/responsive-components.css">

    <style>
        .shop-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 32px;
            padding: 32px 0;
        }

        .shop-filters {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 24px;
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .filter-group {
            margin-bottom: 24px;
        }

        .filter-group h3 {
            font-size: 16px;
            margin-bottom: 12px;
            color: #1a3a52;
        }

        .filter-option {
            margin-bottom: 12px;
        }

        .filter-option label {
            display: flex;
            align-items: center;
            margin-bottom: 0;
            cursor: pointer;
            font-weight: normal;
        }

        .filter-option input {
            margin-right: 8px;
            cursor: pointer;
        }

        .shop-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .sort-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        @media (max-width: 991px) {
            .shop-container {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .shop-filters {
                position: static;
                margin-bottom: 32px;
            }

            .filter-toggle {
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

            .filter-group {
                display: none;
            }

            .filter-group.active {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container flex flex-between">
            <a href="/" class="navbar-brand">📚 Books Store</a>
            <button class="navbar-toggler">☰</button>
            <ul class="navbar-nav">
                <li><a href="/">Home</a></li>
                <li><a href="/shop" class="active">Shop</a></li>
                <li><a href="/dashboard">Dashboard</a></li>
                <li><a href="/cart">Cart (3)</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="/">Home</a>
            <span class="breadcrumb-separator">/</span>
            <span class="active">Books</span>
        </div>

        <!-- Shop Header -->
        <div class="shop-header">
            <h1>Browse Our Books</h1>
            <div>
                <label for="sort">Sort by:</label>
                <select id="sort" class="sort-select">
                    <option>Newest</option>
                    <option>Best Sellers</option>
                    <option>Price: Low to High</option>
                    <option>Price: High to Low</option>
                    <option>Highest Rated</option>
                </select>
            </div>
        </div>

        <!-- Shop Layout -->
        <div class="shop-container">
            <!-- Filters Sidebar -->
            <aside class="shop-filters">
                <h2>Filters</h2>

                <!-- Category Filter -->
                <div class="filter-group">
                    <h3>Category</h3>
                    <div class="filter-option">
                        <label>
                            <input type="checkbox"> Fiction
                        </label>
                    </div>
                    <div class="filter-option">
                        <label>
                            <input type="checkbox"> Non-Fiction
                        </label>
                    </div>
                    <div class="filter-option">
                        <label>
                            <input type="checkbox"> Mystery
                        </label>
                    </div>
                    <div class="filter-option">
                        <label>
                            <input type="checkbox"> Romance
                        </label>
                    </div>
                </div>

                <!-- Price Filter -->
                <div class="filter-group">
                    <h3>Price Range</h3>
                    <div class="filter-option">
                        <label>
                            <input type="checkbox"> Under $10
                        </label>
                    </div>
                    <div class="filter-option">
                        <label>
                            <input type="checkbox"> $10 - $20
                        </label>
                    </div>
                    <div class="filter-option">
                        <label>
                            <input type="checkbox"> $20 - $50
                        </label>
                    </div>
                    <div class="filter-option">
                        <label>
                            <input type="checkbox"> Over $50
                        </label>
                    </div>
                </div>

                <!-- Rating Filter -->
                <div class="filter-group">
                    <h3>Rating</h3>
                    <div class="filter-option">
                        <label>
                            <input type="checkbox"> ★★★★★ (4.5+)
                        </label>
                    </div>
                    <div class="filter-option">
                        <label>
                            <input type="checkbox"> ★★★★☆ (4.0+)
                        </label>
                    </div>
                    <div class="filter-option">
                        <label>
                            <input type="checkbox"> ★★★☆☆ (3.0+)
                        </label>
                    </div>
                </div>

                <!-- Author Filter -->
                <div class="filter-group">
                    <h3>Popular Authors</h3>
                    <div class="filter-option">
                        <label>
                            <input type="checkbox"> Jane Austen
                        </label>
                    </div>
                    <div class="filter-option">
                        <label>
                            <input type="checkbox"> George Orwell
                        </label>
                    </div>
                    <div class="filter-option">
                        <label>
                            <input type="checkbox"> Harper Lee
                        </label>
                    </div>
                    <div class="filter-option">
                        <label>
                            <input type="checkbox"> F. Scott Fitzgerald
                        </label>
                    </div>
                </div>

                <button class="btn btn-primary btn-block">Apply Filters</button>
            </aside>

            <!-- Products Grid -->
            <section>
                <div class="product-grid">
                    <!-- Product Card 1 -->
                    <div class="product-card">
                        <img src="/images/book1.jpg" alt="The Great Gatsby" class="product-image">
                        <div class="product-info">
                            <div class="product-title">The Great Gatsby</div>
                            <div class="product-price">$12.99</div>
                            <div class="product-rating">★★★★★ (428)</div>
                            <div class="product-actions">
                                <button class="btn btn-primary">Add to Cart</button>
                                <button class="btn btn-outline">❤️</button>
                            </div>
                        </div>
                    </div>

                    <!-- Product Card 2 -->
                    <div class="product-card">
                        <img src="/images/book2.jpg" alt="To Kill a Mockingbird" class="product-image">
                        <div class="product-info">
                            <div class="product-title">To Kill a Mockingbird</div>
                            <div class="product-price">$14.99</div>
                            <div class="product-rating">★★★★★ (512)</div>
                            <div class="product-actions">
                                <button class="btn btn-primary">Add to Cart</button>
                                <button class="btn btn-outline">❤️</button>
                            </div>
                        </div>
                    </div>

                    <!-- Product Card 3 -->
                    <div class="product-card">
                        <img src="/images/book3.jpg" alt="1984" class="product-image">
                        <div class="product-info">
                            <div class="product-title">1984</div>
                            <div class="product-price">$13.99</div>
                            <div class="product-rating">★★★★☆ (487)</div>
                            <div class="product-actions">
                                <button class="btn btn-primary">Add to Cart</button>
                                <button class="btn btn-outline">❤️</button>
                            </div>
                        </div>
                    </div>

                    <!-- Product Card 4 -->
                    <div class="product-card">
                        <img src="/images/book4.jpg" alt="Pride and Prejudice" class="product-image">
                        <div class="product-info">
                            <div class="product-title">Pride and Prejudice</div>
                            <div class="product-price">$9.99</div>
                            <div class="product-rating">★★★★★ (562)</div>
                            <div class="product-actions">
                                <button class="btn btn-primary">Add to Cart</button>
                                <button class="btn btn-outline">❤️</button>
                            </div>
                        </div>
                    </div>

                    <!-- Product Card 5 -->
                    <div class="product-card">
                        <img src="/images/book5.jpg" alt="Wuthering Heights" class="product-image">
                        <div class="product-info">
                            <div class="product-title">Wuthering Heights</div>
                            <div class="product-price">$8.99</div>
                            <div class="product-rating">★★★★☆ (356)</div>
                            <div class="product-actions">
                                <button class="btn btn-primary">Add to Cart</button>
                                <button class="btn btn-outline">❤️</button>
                            </div>
                        </div>
                    </div>

                    <!-- Product Card 6 -->
                    <div class="product-card">
                        <img src="/images/book6.jpg" alt="Jane Eyre" class="product-image">
                        <div class="product-info">
                            <div class="product-title">Jane Eyre</div>
                            <div class="product-price">$11.99</div>
                            <div class="product-rating">★★★★★ (489)</div>
                            <div class="product-actions">
                                <button class="btn btn-primary">Add to Cart</button>
                                <button class="btn btn-outline">❤️</button>
                            </div>
                        </div>
                    </div>

                    <!-- Product Card 7 -->
                    <div class="product-card">
                        <img src="/images/book7.jpg" alt="The Catcher in the Rye" class="product-image">
                        <div class="product-info">
                            <div class="product-title">The Catcher in the Rye</div>
                            <div class="product-price">$10.99</div>
                            <div class="product-rating">★★★★☆ (445)</div>
                            <div class="product-actions">
                                <button class="btn btn-primary">Add to Cart</button>
                                <button class="btn btn-outline">❤️</button>
                            </div>
                        </div>
                    </div>

                    <!-- Product Card 8 -->
                    <div class="product-card">
                        <img src="/images/book8.jpg" alt="Moby Dick" class="product-image">
                        <div class="product-info">
                            <div class="product-title">Moby Dick</div>
                            <div class="product-price">$15.99</div>
                            <div class="product-rating">★★★☆☆ (289)</div>
                            <div class="product-actions">
                                <button class="btn btn-primary">Add to Cart</button>
                                <button class="btn btn-outline">❤️</button>
                            </div>
                        </div>
                    </div>

                    <!-- Product Card 9 -->
                    <div class="product-card">
                        <img src="/images/book9.jpg" alt="The Hobbit" class="product-image">
                        <div class="product-info">
                            <div class="product-title">The Hobbit</div>
                            <div class="product-price">$16.99</div>
                            <div class="product-rating">★★★★★ (598)</div>
                            <div class="product-actions">
                                <button class="btn btn-primary">Add to Cart</button>
                                <button class="btn btn-outline">❤️</button>
                            </div>
                        </div>
                    </div>

                    <!-- Product Card 10 -->
                    <div class="product-card">
                        <img src="/images/book10.jpg" alt="The Lord of the Rings" class="product-image">
                        <div class="product-info">
                            <div class="product-title">The Lord of the Rings</div>
                            <div class="product-price">$24.99</div>
                            <div class="product-rating">★★★★★ (723)</div>
                            <div class="product-actions">
                                <button class="btn btn-primary">Add to Cart</button>
                                <button class="btn btn-outline">❤️</button>
                            </div>
                        </div>
                    </div>

                    <!-- Product Card 11 -->
                    <div class="product-card">
                        <img src="/images/book11.jpg" alt="The Odyssey" class="product-image">
                        <div class="product-info">
                            <div class="product-title">The Odyssey</div>
                            <div class="product-price">$18.99</div>
                            <div class="product-rating">★★★★☆ (412)</div>
                            <div class="product-actions">
                                <button class="btn btn-primary">Add to Cart</button>
                                <button class="btn btn-outline">❤️</button>
                            </div>
                        </div>
                    </div>

                    <!-- Product Card 12 -->
                    <div class="product-card">
                        <img src="/images/book12.jpg" alt="War and Peace" class="product-image">
                        <div class="product-info">
                            <div class="product-title">War and Peace</div>
                            <div class="product-price">$22.99</div>
                            <div class="product-rating">★★★★☆ (356)</div>
                            <div class="product-actions">
                                <button class="btn btn-primary">Add to Cart</button>
                                <button class="btn btn-outline">❤️</button>
                            </div>
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
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h4>About</h4>
                    <a href="#">About Us</a>
                    <a href="#">Careers</a>
                    <a href="#">Blog</a>
                </div>
                <div class="footer-column">
                    <h4>Support</h4>
                    <a href="#">Contact</a>
                    <a href="#">FAQ</a>
                    <a href="#">Returns</a>
                </div>
                <div class="footer-column">
                    <h4>Legal</h4>
                    <a href="#">Privacy</a>
                    <a href="#">Terms</a>
                </div>
                <div class="footer-column">
                    <h4>Follow</h4>
                    <a href="#">Facebook</a>
                    <a href="#">Instagram</a>
                    <a href="#">Twitter</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Books eCommerce. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="/js/mobile-menu.js"></script>
</body>
</html>
