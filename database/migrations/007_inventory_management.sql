-- Phase 2, Task 1: Inventory Management System
-- Created: November 10, 2025
-- Purpose: Complete inventory tracking, stock management, and forecasting

-- ============================================
-- TABLE: product_inventory
-- Purpose: Track current stock levels and SKU information
-- ============================================
CREATE TABLE IF NOT EXISTS product_inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL UNIQUE,
    sku VARCHAR(100) UNIQUE,
    current_stock INT DEFAULT 0,
    reserved_stock INT DEFAULT 0, -- Stock allocated to unpaid orders
    available_stock INT GENERATED ALWAYS AS (current_stock - reserved_stock) STORED,
    minimum_stock INT DEFAULT 10,
    maximum_stock INT DEFAULT 1000,
    reorder_point INT DEFAULT 50,
    reorder_quantity INT DEFAULT 100,
    cost_price DECIMAL(10, 2),
    selling_price DECIMAL(10, 2),
    last_counted_at TIMESTAMP NULL,
    last_restock_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,

    INDEX idx_sku (sku),
    INDEX idx_available_stock (available_stock),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: inventory_transactions
-- Purpose: Audit trail of all inventory changes
-- ============================================
CREATE TABLE IF NOT EXISTS inventory_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    transaction_type ENUM('purchase', 'sale', 'adjustment', 'return', 'damage', 'restock', 'count') DEFAULT 'adjustment',
    quantity_change INT,
    reason VARCHAR(255),
    reference_id INT, -- Order ID, Return ID, etc
    reference_type VARCHAR(50), -- 'order', 'return', 'manual', etc
    notes LONGTEXT,
    performed_by INT,
    stock_before INT,
    stock_after INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_product_id (product_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at),
    INDEX idx_reference_id (reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: warehouses
-- Purpose: Track inventory by warehouse location
-- ============================================
CREATE TABLE IF NOT EXISTS warehouses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    warehouse_name VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    address VARCHAR(500),
    city VARCHAR(100),
    state VARCHAR(100),
    zip_code VARCHAR(20),
    country VARCHAR(100),
    capacity INT, -- Maximum items the warehouse can hold
    manager_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: warehouse_inventory
-- Purpose: Track stock levels by warehouse
-- ============================================
CREATE TABLE IF NOT EXISTS warehouse_inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    warehouse_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity_on_hand INT DEFAULT 0,
    quantity_reserved INT DEFAULT 0,
    quantity_damaged INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,

    UNIQUE KEY unique_warehouse_product (warehouse_id, product_id),
    INDEX idx_warehouse_id (warehouse_id),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: stock_alerts
-- Purpose: Alert when stock reaches critical levels
-- ============================================
CREATE TABLE IF NOT EXISTS stock_alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    alert_type ENUM('low_stock', 'out_of_stock', 'overstock') DEFAULT 'low_stock',
    current_stock INT,
    threshold INT,
    is_active BOOLEAN DEFAULT TRUE,
    acknowledged_by INT,
    acknowledged_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (acknowledged_by) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_product_id (product_id),
    INDEX idx_alert_type (alert_type),
    INDEX idx_is_active (is_active),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: reorder_points
-- Purpose: Manage automatic reorder settings
-- ============================================
CREATE TABLE IF NOT EXISTS reorder_points (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL UNIQUE,
    reorder_point INT DEFAULT 50,
    reorder_quantity INT DEFAULT 100,
    lead_time_days INT DEFAULT 7,
    supplier_id INT,
    auto_reorder_enabled BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,

    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: stock_forecasts
-- Purpose: Predict future stock needs
-- ============================================
CREATE TABLE IF NOT EXISTS stock_forecasts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    forecast_date DATE,
    predicted_demand INT,
    predicted_stock INT,
    confidence_level DECIMAL(5, 2), -- Percentage
    forecast_type ENUM('simple', 'weighted', 'exponential') DEFAULT 'simple',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,

    INDEX idx_product_id (product_id),
    INDEX idx_forecast_date (forecast_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: inventory_cost_tracking
-- Purpose: Track inventory value and cost metrics
-- ============================================
CREATE TABLE IF NOT EXISTS inventory_cost_tracking (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    cost_price DECIMAL(10, 2),
    selling_price DECIMAL(10, 2),
    markup_percentage DECIMAL(5, 2),
    total_cost DECIMAL(12, 2), -- cost_price * current_stock
    total_value DECIMAL(12, 2), -- selling_price * current_stock
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,

    UNIQUE KEY unique_product (product_id),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VIEWS for Inventory Management
-- ============================================

-- Low stock items
CREATE OR REPLACE VIEW vw_low_stock_items AS
SELECT
    p.id,
    p.title,
    pi.sku,
    pi.current_stock,
    pi.minimum_stock,
    pi.reorder_point,
    (pi.minimum_stock - pi.current_stock) as shortage,
    pi.cost_price,
    (pi.cost_price * pi.current_stock) as total_cost
FROM product_inventory pi
JOIN products p ON pi.product_id = p.id
WHERE pi.current_stock <= pi.minimum_stock
AND pi.current_stock > 0
ORDER BY shortage DESC;

-- Out of stock items
CREATE OR REPLACE VIEW vw_out_of_stock_items AS
SELECT
    p.id,
    p.title,
    pi.sku,
    pi.current_stock,
    pi.minimum_stock,
    COUNT(DISTINCT od.order_id) as pending_orders
FROM product_inventory pi
JOIN products p ON pi.product_id = p.id
LEFT JOIN order_items od ON p.id = od.product_id
WHERE pi.current_stock = 0
GROUP BY p.id;

-- Inventory value
CREATE OR REPLACE VIEW vw_inventory_value AS
SELECT
    SUM(pi.current_stock * ict.cost_price) as total_inventory_cost,
    SUM(pi.current_stock * ict.selling_price) as total_inventory_value,
    COUNT(DISTINCT pi.product_id) as total_products,
    AVG(ict.markup_percentage) as avg_markup
FROM product_inventory pi
LEFT JOIN inventory_cost_tracking ict ON pi.product_id = ict.product_id;

-- Warehouse inventory summary
CREATE OR REPLACE VIEW vw_warehouse_summary AS
SELECT
    w.id,
    w.warehouse_name,
    SUM(wi.quantity_on_hand) as total_stock,
    SUM(wi.quantity_reserved) as total_reserved,
    SUM(wi.quantity_damaged) as total_damaged,
    COUNT(DISTINCT wi.product_id) as product_count
FROM warehouses w
LEFT JOIN warehouse_inventory wi ON w.id = wi.warehouse_id
GROUP BY w.id;
