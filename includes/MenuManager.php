<?php
// Create this file as includes/MenuManager.php

class MenuManager {
    private $conn;
    private $cache_enabled;
    private $cache_duration;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
        $this->cache_enabled = $this->getSetting('enable_menu_cache', 1);
        $this->cache_duration = $this->getSetting('menu_cache_duration', 3600);
    }
    
    /**
     * Get a complete menu by location or ID
     */
    public function getMenu($identifier, $type = 'location') {
        $menu_id = $this->getMenuId($identifier, $type);
        if (!$menu_id) {
            return [];
        }
        
        // Check cache first
        if ($this->cache_enabled) {
            $cache_key = "menu_{$menu_id}_" . $this->getUserVisibility();
            $cached = $this->getFromCache($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }
        
        $menu_items = $this->buildMenuTree($menu_id);
        
        // Cache the result
        if ($this->cache_enabled && !empty($menu_items)) {
            $this->saveToCache($cache_key, $menu_items);
        }
        
        return $menu_items;
    }
    
    /**
     * Build hierarchical menu tree
     */
    private function buildMenuTree($menu_id, $parent_id = null, $depth = 0) {
        $max_depth = $this->getSetting('max_menu_depth', 3);
        if ($depth >= $max_depth) {
            return [];
        }
        
        $visibility = $this->getUserVisibility();
        
        $sql = "SELECT mi.*, c.name as category_name, c.slug as category_slug 
                FROM menu_items mi
                LEFT JOIN categories c ON mi.category_id = c.id AND mi.is_category_link = 1
                WHERE mi.menu_id = ? 
                AND mi.status = 'active'
                AND (mi.visibility = 'public' OR mi.visibility = ?)";
        
        if ($parent_id === null) {
            $sql .= " AND mi.parent_id IS NULL";
        } else {
            $sql .= " AND mi.parent_id = ?";
        }
        
        $sql .= " ORDER BY mi.sort_order ASC, mi.title ASC";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($parent_id === null) {
            $stmt->bind_param("is", $menu_id, $visibility);
        } else {
            $stmt->bind_param("isi", $menu_id, $visibility, $parent_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            // Process URL for category links
            if ($row['is_category_link'] && $row['category_id']) {
                $row['url'] = "/bookshelf/shop.php?category=" . $row['category_id'];
                if ($row['category_slug']) {
                    $row['url'] = "/bookshelf/category/" . $row['category_slug'];
                }
            }
            
            // Get children recursively
            $children = $this->buildMenuTree($menu_id, $row['id'], $depth + 1);
            if (!empty($children)) {
                $row['children'] = $children;
                $row['has_children'] = true;
            } else {
                $row['children'] = [];
                $row['has_children'] = false;
            }
            
            // Add CSS classes
            $row['css_classes'] = $this->generateMenuItemClasses($row, $depth);
            
            // Check if current page
            $row['is_current'] = $this->isCurrentPage($row['url']);
            $row['is_parent_current'] = $this->isParentOfCurrentPage($row);
            
            $items[] = $row;
        }
        
        return $items;
    }
    
    /**
     * Render menu as HTML
     */
    public function renderMenu($identifier, $options = []) {
        $defaults = [
            'container_class' => 'nav',
            'item_class' => 'nav-item',
            'link_class' => 'nav-link',
            'dropdown_class' => 'dropdown',
            'dropdown_toggle_class' => 'dropdown-toggle',
            'dropdown_menu_class' => 'dropdown-menu',
            'depth' => 0,
            'max_depth' => 2,
            'show_icons' => true,
            'show_descriptions' => false
        ];
        
        $options = array_merge($defaults, $options);
        $menu_items = $this->getMenu($identifier);
        
        if (empty($menu_items)) {
            return '';
        }
        
        return $this->renderMenuItems($menu_items, $options);
    }
    
    /**
     * Render menu items recursively
     */
    private function renderMenuItems($items, $options, $depth = 0) {
        if ($depth >= $options['max_depth']) {
            return '';
        }
        
        $html = '';
        $container_class = $depth === 0 ? $options['container_class'] : $options['dropdown_menu_class'];
        
        if ($depth === 0) {
            $html .= "<ul class=\"{$container_class}\">";
        } else {
            $html .= "<ul class=\"{$container_class}\">";
        }
        
        foreach ($items as $item) {
            $html .= $this->renderMenuItem($item, $options, $depth);
        }
        
        $html .= "</ul>";
        
        return $html;
    }
    
    /**
     * Render single menu item
     */
    private function renderMenuItem($item, $options, $depth = 0) {
        $item_classes = [$options['item_class']];
        $link_classes = [$options['link_class']];
        
        // Add dropdown classes if has children
        if ($item['has_children']) {
            $item_classes[] = $options['dropdown_class'];
            $link_classes[] = $options['dropdown_toggle_class'];
        }
        
        // Add active classes
        if ($item['is_current']) {
            $link_classes[] = 'active';
        }
        
        if ($item['is_parent_current']) {
            $item_classes[] = 'active';
        }
        
        // Add custom CSS classes
        if (!empty($item['css_class'])) {
            $item_classes[] = $item['css_class'];
        }
        
        $item_class_str = implode(' ', array_unique($item_classes));
        $link_class_str = implode(' ', array_unique($link_classes));
        
        $html = "<li class=\"{$item_class_str}\">";
        
        // Determine URL
        $url = $item['url'] ?: '#';
        $target = $item['target'] ?: '_self';
        
        // Build data attributes for dropdowns
        $data_attrs = '';
        if ($item['has_children']) {
            $data_attrs = 'data-bs-toggle="dropdown" aria-expanded="false"';
        }
        
        // Start link
        $html .= "<a href=\"{$url}\" class=\"{$link_class_str}\" target=\"{$target}\" {$data_attrs}>";
        
        // Add icon
        if ($options['show_icons'] && !empty($item['icon_class'])) {
            $html .= "<i class=\"{$item['icon_class']} me-2\"></i>";
        }
        
        // Add title
        $html .= htmlspecialchars($item['title']);
        
        // Add dropdown arrow
        if ($item['has_children']) {
            $html .= " <i class=\"bi bi-chevron-down ms-1\"></i>";
        }
        
        $html .= "</a>";
        
        // Add description if enabled
        if ($options['show_descriptions'] && !empty($item['description'])) {
            $html .= "<small class=\"text-muted d-block\">" . htmlspecialchars($item['description']) . "</small>";
        }
        
        // Render children
        if ($item['has_children'] && !empty($item['children'])) {
            $html .= $this->renderMenuItems($item['children'], $options, $depth + 1);
        }
        
        $html .= "</li>";
        
        return $html;
    }
    
    /**
     * Render categories dropdown for header
     */
    public function renderCategoriesDropdown($options = []) {
        $defaults = [
            'show_product_count' => $this->getSetting('show_category_counts', 1),
            'max_items' => 12,
            'show_view_all' => true
        ];
        
        $options = array_merge($defaults, $options);
        
        $sql = "SELECT c.*, COUNT(p.id) as product_count 
                FROM categories c 
                LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
                WHERE c.status = 'active' 
                GROUP BY c.id 
                ORDER BY c.sort_order, c.name 
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $options['max_items']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $html = '<ul class="dropdown-menu category-megamenu">';
        
        while ($category = $result->fetch_assoc()) {
            $url = "/bookshelf/shop.php?category=" . $category['id'];
            $count_text = $options['show_product_count'] ? " ({$category['product_count']})" : '';
            
            $html .= '<li>';
            $html .= "<a class=\"dropdown-item d-flex justify-content-between align-items-center\" href=\"{$url}\">";
            $html .= '<span><i class="bi bi-book me-2"></i>' . htmlspecialchars($category['name']) . '</span>';
            if ($options['show_product_count']) {
                $html .= '<small class="text-muted">' . $category['product_count'] . '</small>';
            }
            $html .= '</a>';
            $html .= '</li>';
        }
        
        if ($options['show_view_all']) {
            $html .= '<li><hr class="dropdown-divider"></li>';
            $html .= '<li><a class="dropdown-item fw-bold" href="/bookshelf/shop.php">';
            $html .= '<i class="bi bi-grid me-2"></i>View All Categories</a></li>';
        }
        
        $html .= '</ul>';
        
        return $html;
    }
    
    /**
     * Get menu ID by identifier
     */
    private function getMenuId($identifier, $type = 'location') {
        if ($type === 'id') {
            return (int)$identifier;
        }
        
        $stmt = $this->conn->prepare("SELECT id FROM navigation_menus WHERE location = ? AND status = 'active' LIMIT 1");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0 ? $result->fetch_assoc()['id'] : null;
    }
    
    /**
     * Determine user visibility level
     */
    private function getUserVisibility() {
        if (isset($_SESSION['user_id'])) {
            return isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'admin' : 'logged_in';
        }
        return 'logged_out';
    }
    
    /**
     * Generate CSS classes for menu item
     */
    private function generateMenuItemClasses($item, $depth) {
        $classes = [];
        
        if ($depth === 0) {
            $classes[] = 'main-menu-item';
        } else {
            $classes[] = 'sub-menu-item';
            $classes[] = "depth-{$depth}";
        }
        
        if ($item['has_children']) {
            $classes[] = 'has-children';
        }
        
        if ($item['is_category_link']) {
            $classes[] = 'category-link';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Check if URL is current page
     */
    private function isCurrentPage($url) {
        if (empty($url) || $url === '#') {
            return false;
        }
        
        $current_url = $_SERVER['REQUEST_URI'];
        $parsed_url = parse_url($url);
        $path = $parsed_url['path'] ?? '';
        
        // Exact match
        if ($current_url === $url || $current_url === $path) {
            return true;
        }
        
        // Check query parameters for category pages
        if (strpos($url, 'category=') !== false && strpos($current_url, 'category=') !== false) {
            parse_str(parse_url($url, PHP_URL_QUERY), $url_params);
            parse_str(parse_url($current_url, PHP_URL_QUERY), $current_params);
            
            return isset($url_params['category']) && 
                   isset($current_params['category']) && 
                   $url_params['category'] === $current_params['category'];
        }
        
        return false;
    }
    
    /**
     * Check if item is parent of current page
     */
    private function isParentOfCurrentPage($item) {
        if (!$item['has_children']) {
            return false;
        }
        
        foreach ($item['children'] as $child) {
            if ($child['is_current'] || $this->isParentOfCurrentPage($child)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Cache management
     */
    private function getFromCache($cache_key) {
        $stmt = $this->conn->prepare("SELECT cached_html FROM menu_cache WHERE cache_key = ? AND expires_at > NOW()");
        $stmt->bind_param("s", $cache_key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return json_decode($row['cached_html'], true);
        }
        
        return false;
    }
    
    private function saveToCache($cache_key, $data) {
        $cached_html = json_encode($data);
        $expires_at = date('Y-m-d H:i:s', time() + $this->cache_duration);
        
        $stmt = $this->conn->prepare("INSERT INTO menu_cache (cache_key, cached_html, expires_at, menu_id) VALUES (?, ?, ?, 0) ON DUPLICATE KEY UPDATE cached_html = VALUES(cached_html), expires_at = VALUES(expires_at)");
        $stmt->bind_param("sss", $cache_key, $cached_html, $expires_at);
        $stmt->execute();
    }
    
    /**
     * Clear all menu cache
     */
    public function clearCache($menu_id = null) {
        if ($menu_id) {
            $stmt = $this->conn->prepare("DELETE FROM menu_cache WHERE menu_id = ?");
            $stmt->bind_param("i", $menu_id);
        } else {
            $stmt = $this->conn->prepare("DELETE FROM menu_cache");
        }
        $stmt->execute();
    }
    
    /**
     * Get site setting
     */
    private function getSetting($key, $default = null) {
        static $settings_cache = [];
        
        if (!isset($settings_cache[$key])) {
            $stmt = $this->conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
            $stmt->bind_param("s", $key);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $settings_cache[$key] = $row['setting_value'];
            } else {
                $settings_cache[$key] = $default;
            }
        }
        
        return $settings_cache[$key];
    }
    
    /**
     * Rebuild category menus
     */
    public function rebuildCategoryMenus() {
        try {
            $stmt = $this->conn->prepare("CALL RebuildCategoryMenus()");
            $stmt->execute();
            $this->clearCache();
            return true;
        } catch (Exception $e) {
            error_log("Error rebuilding category menus: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add new menu item
     */
    public function addMenuItem($menu_id, $data) {
        $sql = "INSERT INTO menu_items (menu_id, parent_id, title, url, target, icon_class, css_class, description, is_category_link, category_id, sort_order, status, visibility) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iissssssiisss", 
            $menu_id,
            $data['parent_id'],
            $data['title'],
            $data['url'],
            $data['target'] ?? '_self',
            $data['icon_class'],
            $data['css_class'],
            $data['description'],
            $data['is_category_link'] ?? 0,
            $data['category_id'],
            $data['sort_order'] ?? 0,
            $data['status'] ?? 'active',
            $data['visibility'] ?? 'public'
        );
        
        $success = $stmt->execute();
        if ($success) {
            $this->clearCache($menu_id);
        }
        
        return $success;
    }
    
    /**
     * Update menu item
     */
    public function updateMenuItem($item_id, $data) {
        $sql = "UPDATE menu_items SET title = ?, url = ?, target = ?, icon_class = ?, css_class = ?, description = ?, sort_order = ?, status = ?, visibility = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssssssi",
            $data['title'],
            $data['url'],
            $data['target'] ?? '_self',
            $data['icon_class'],
            $data['css_class'],
            $data['description'],
            $data['sort_order'] ?? 0,
            $data['status'] ?? 'active',
            $data['visibility'] ?? 'public',
            $item_id
        );
        
        $success = $stmt->execute();
        if ($success) {
            // Get menu_id to clear cache
            $stmt2 = $this->conn->prepare("SELECT menu_id FROM menu_items WHERE id = ?");
            $stmt2->bind_param("i", $item_id);
            $stmt2->execute();
            $result = $stmt2->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $this->clearCache($row['menu_id']);
            }
        }
        
        return $success;
    }
    
    /**
     * Delete menu item
     */
    public function deleteMenuItem($item_id) {
        // Get menu_id first
        $stmt = $this->conn->prepare("SELECT menu_id FROM menu_items WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $row = $result->fetch_assoc();
        $menu_id = $row['menu_id'];
        
        // Delete the item (will cascade to children due to foreign key)
        $stmt2 = $this->conn->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt2->bind_param("i", $item_id);
        $success = $stmt2->execute();
        
        if ($success) {
            $this->clearCache($menu_id);
        }
        
        return $success;
    }
}
?>