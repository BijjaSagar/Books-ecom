<?php
/**
 * Dashboard Manager Class
 * Manages all customer dashboard functionality
 * - Order history and tracking
 * - Wishlist management
 * - Address management
 * - Download history
 * - Support tickets
 * - Account settings
 *
 * @version 1.0
 * @author Books eCommerce Platform
 */

class DashboardManager {
    private $conn;
    private $table_wishlist = 'wishlist';
    private $table_orders = 'orders';
    private $table_tickets = 'support_tickets';
    private $table_reviews = 'customer_reviews';
    private $table_account_settings = 'customer_account_settings';
    private $table_notification_prefs = 'notification_preferences';
    private $table_addresses = 'customer_addresses';
    private $table_downloads = 'digital_downloads';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get customer's complete dashboard summary
     */
    public function getCustomerSummary($customer_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    u.id,
                    u.name,
                    u.email,
                    COALESCE(cs.phone_number, '') as phone,
                    COALESCE(cs.date_of_birth, '') as date_of_birth,
                    COALESCE(cs.gender, '') as gender,
                    COUNT(DISTINCT o.id) as total_orders,
                    COUNT(DISTINCT w.id) as wishlist_count,
                    COUNT(DISTINCT st.id) as open_tickets,
                    COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.total ELSE 0 END), 0) as lifetime_value,
                    MAX(o.created_at) as last_order_date,
                    u.created_at as member_since,
                    COALESCE(cs.last_login, u.created_at) as last_login
                FROM users u
                LEFT JOIN orders o ON u.id = o.customer_id
                LEFT JOIN $this->table_wishlist w ON u.id = w.customer_id
                LEFT JOIN support_tickets st ON u.id = st.customer_id AND st.status IN ('open', 'in_progress')
                LEFT JOIN customer_account_settings cs ON u.id = cs.customer_id
                WHERE u.id = ? AND u.role = 'customer'
                GROUP BY u.id
            ");

            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $data = $result->fetch_assoc();

            if (!$data) {
                return ['success' => false, 'error' => 'Customer not found'];
            }

            return [
                'success' => true,
                'customer' => [
                    'id' => intval($data['id']),
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'date_of_birth' => $data['date_of_birth'],
                    'gender' => $data['gender'],
                    'stats' => [
                        'total_orders' => intval($data['total_orders']),
                        'wishlist_items' => intval($data['wishlist_count']),
                        'open_tickets' => intval($data['open_tickets']),
                        'lifetime_value' => floatval($data['lifetime_value']),
                        'last_order_date' => $data['last_order_date'],
                        'member_since' => $data['member_since'],
                        'last_login' => $data['last_login']
                    ]
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get order history for customer
     */
    public function getOrderHistory($customer_id, $limit = 10, $offset = 0) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    o.id,
                    o.order_number,
                    o.total,
                    o.currency,
                    o.status,
                    o.payment_status,
                    o.created_at,
                    o.updated_at,
                    COUNT(oi.id) as item_count
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.customer_id = ?
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT ? OFFSET ?
            ");

            $stmt->bind_param("iii", $customer_id, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();

            $orders = [];
            while ($row = $result->fetch_assoc()) {
                $orders[] = [
                    'id' => intval($row['id']),
                    'order_number' => $row['order_number'],
                    'total' => floatval($row['total']),
                    'currency' => $row['currency'],
                    'status' => $row['status'],
                    'payment_status' => $row['payment_status'],
                    'item_count' => intval($row['item_count']),
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                ];
            }

            // Get total count
            $count_stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM orders WHERE customer_id = ?");
            $count_stmt->bind_param("i", $customer_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result()->fetch_assoc();

            return [
                'success' => true,
                'orders' => $orders,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'total' => intval($count_result['total'])
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get order details including items
     */
    public function getOrderDetails($customer_id, $order_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    o.id,
                    o.order_number,
                    o.total,
                    o.subtotal,
                    o.tax_amount,
                    o.shipping_cost,
                    o.currency,
                    o.status,
                    o.payment_status,
                    o.payment_method,
                    o.created_at,
                    o.updated_at,
                    ca.first_name,
                    ca.last_name,
                    ca.email,
                    ca.phone,
                    ca.address,
                    ca.address2,
                    ca.city,
                    ca.state,
                    ca.zip,
                    ca.country
                FROM orders o
                LEFT JOIN customer_addresses ca ON o.shipping_address_id = ca.id
                WHERE o.id = ? AND o.customer_id = ?
            ");

            $stmt->bind_param("ii", $order_id, $customer_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if (!$order) {
                return ['success' => false, 'error' => 'Order not found'];
            }

            // Get order items
            $items_stmt = $this->conn->prepare("
                SELECT
                    oi.id,
                    oi.product_id,
                    p.title,
                    p.sku,
                    oi.quantity,
                    oi.price,
                    (oi.quantity * oi.price) as line_total
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");

            $items_stmt->bind_param("i", $order_id);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();

            $items = [];
            while ($item = $items_result->fetch_assoc()) {
                $items[] = [
                    'id' => intval($item['id']),
                    'product_id' => intval($item['product_id']),
                    'title' => $item['title'],
                    'sku' => $item['sku'],
                    'quantity' => intval($item['quantity']),
                    'price' => floatval($item['price']),
                    'line_total' => floatval($item['line_total'])
                ];
            }

            return [
                'success' => true,
                'order' => [
                    'id' => intval($order['id']),
                    'order_number' => $order['order_number'],
                    'status' => $order['status'],
                    'payment_status' => $order['payment_status'],
                    'payment_method' => $order['payment_method'],
                    'currency' => $order['currency'],
                    'subtotal' => floatval($order['subtotal']),
                    'tax' => floatval($order['tax_amount']),
                    'shipping' => floatval($order['shipping_cost']),
                    'total' => floatval($order['total']),
                    'created_at' => $order['created_at'],
                    'updated_at' => $order['updated_at'],
                    'shipping_address' => [
                        'name' => ($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''),
                        'email' => $order['email'],
                        'phone' => $order['phone'],
                        'street' => $order['address'],
                        'street2' => $order['address2'],
                        'city' => $order['city'],
                        'state' => $order['state'],
                        'zip' => $order['zip'],
                        'country' => $order['country']
                    ],
                    'items' => $items
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get customer's wishlist
     */
    public function getWishlist($customer_id, $limit = 20, $offset = 0) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    w.id,
                    p.id as product_id,
                    p.title,
                    p.sku,
                    p.price,
                    p.cover_image,
                    p.in_stock,
                    w.added_at
                FROM $this->table_wishlist w
                JOIN products p ON w.product_id = p.id
                WHERE w.customer_id = ?
                ORDER BY w.added_at DESC
                LIMIT ? OFFSET ?
            ");

            $stmt->bind_param("iii", $customer_id, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();

            $items = [];
            while ($row = $result->fetch_assoc()) {
                $items[] = [
                    'wishlist_id' => intval($row['id']),
                    'product_id' => intval($row['product_id']),
                    'title' => $row['title'],
                    'sku' => $row['sku'],
                    'price' => floatval($row['price']),
                    'cover_image' => $row['cover_image'],
                    'in_stock' => boolval($row['in_stock']),
                    'added_at' => $row['added_at']
                ];
            }

            // Get count
            $count_stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM $this->table_wishlist WHERE customer_id = ?");
            $count_stmt->bind_param("i", $customer_id);
            $count_stmt->execute();
            $count = $count_stmt->get_result()->fetch_assoc();

            return [
                'success' => true,
                'items' => $items,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'total' => intval($count['total'])
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add product to wishlist
     */
    public function addToWishlist($customer_id, $product_id) {
        try {
            // Check if already in wishlist
            $check_stmt = $this->conn->prepare("SELECT id FROM $this->table_wishlist WHERE customer_id = ? AND product_id = ?");
            $check_stmt->bind_param("ii", $customer_id, $product_id);
            $check_stmt->execute();

            if ($check_stmt->get_result()->fetch_assoc()) {
                return ['success' => false, 'error' => 'Product already in wishlist'];
            }

            $stmt = $this->conn->prepare("INSERT INTO $this->table_wishlist (customer_id, product_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $customer_id, $product_id);

            if (!$stmt->execute()) {
                return ['success' => false, 'error' => 'Failed to add to wishlist'];
            }

            return ['success' => true, 'message' => 'Added to wishlist'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Remove from wishlist
     */
    public function removeFromWishlist($customer_id, $wishlist_id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM $this->table_wishlist WHERE id = ? AND customer_id = ?");
            $stmt->bind_param("ii", $wishlist_id, $customer_id);

            if (!$stmt->execute()) {
                return ['success' => false, 'error' => 'Failed to remove from wishlist'];
            }

            return ['success' => true, 'message' => 'Removed from wishlist'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get customer's downloads
     */
    public function getDownloads($customer_id, $limit = 20, $offset = 0) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    dd.id,
                    dd.product_id,
                    dd.order_id,
                    p.title as product_title,
                    o.order_number,
                    dd.download_count,
                    dd.created_at,
                    dd.expires_at,
                    CASE WHEN dd.expires_at IS NULL OR dd.expires_at > NOW() THEN 'active' ELSE 'expired' END as status
                FROM digital_downloads dd
                JOIN products p ON dd.product_id = p.id
                JOIN orders o ON dd.order_id = o.id
                WHERE o.customer_id = ?
                ORDER BY dd.created_at DESC
                LIMIT ? OFFSET ?
            ");

            $stmt->bind_param("iii", $customer_id, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();

            $downloads = [];
            while ($row = $result->fetch_assoc()) {
                $downloads[] = [
                    'id' => intval($row['id']),
                    'product_id' => intval($row['product_id']),
                    'order_id' => intval($row['order_id']),
                    'product_title' => $row['product_title'],
                    'order_number' => $row['order_number'],
                    'download_count' => intval($row['download_count']),
                    'status' => $row['status'],
                    'downloaded_at' => $row['created_at'],
                    'expires_at' => $row['expires_at'],
                    'download_url' => '/api/download-product.php?download_id=' . $row['id']
                ];
            }

            // Get count
            $count_stmt = $this->conn->prepare("
                SELECT COUNT(*) as total FROM digital_downloads dd
                JOIN orders o ON dd.order_id = o.id
                WHERE o.customer_id = ?
            ");
            $count_stmt->bind_param("i", $customer_id);
            $count_stmt->execute();
            $count = $count_stmt->get_result()->fetch_assoc();

            return [
                'success' => true,
                'downloads' => $downloads,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'total' => intval($count['total'])
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get customer's support tickets
     */
    public function getSupportTickets($customer_id, $limit = 10, $offset = 0, $status = null) {
        try {
            $query = "
                SELECT
                    st.id,
                    st.ticket_number,
                    st.subject,
                    st.priority,
                    st.status,
                    st.category,
                    st.order_id,
                    o.order_number,
                    COUNT(DISTINCT tr.id) as reply_count,
                    st.created_at,
                    st.updated_at,
                    DATEDIFF(NOW(), st.created_at) as days_open
                FROM support_tickets st
                LEFT JOIN orders o ON st.order_id = o.id
                LEFT JOIN ticket_replies tr ON st.id = tr.ticket_id
                WHERE st.customer_id = ?
            ";

            if ($status) {
                $query .= " AND st.status = ?";
            }

            $query .= "
                GROUP BY st.id
                ORDER BY
                    CASE st.priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END,
                    st.updated_at DESC
                LIMIT ? OFFSET ?
            ";

            if ($status) {
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("isii", $customer_id, $status, $limit, $offset);
            } else {
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("iii", $customer_id, $limit, $offset);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $tickets = [];
            while ($row = $result->fetch_assoc()) {
                $tickets[] = [
                    'id' => intval($row['id']),
                    'ticket_number' => $row['ticket_number'],
                    'subject' => $row['subject'],
                    'priority' => $row['priority'],
                    'status' => $row['status'],
                    'category' => $row['category'],
                    'order_id' => $row['order_id'] ? intval($row['order_id']) : null,
                    'order_number' => $row['order_number'],
                    'reply_count' => intval($row['reply_count']),
                    'days_open' => intval($row['days_open']),
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                ];
            }

            // Get count
            $count_query = "SELECT COUNT(*) as total FROM support_tickets WHERE customer_id = ?";
            if ($status) {
                $count_query .= " AND status = ?";
            }

            if ($status) {
                $count_stmt = $this->conn->prepare($count_query);
                $count_stmt->bind_param("is", $customer_id, $status);
            } else {
                $count_stmt = $this->conn->prepare($count_query);
                $count_stmt->bind_param("i", $customer_id);
            }

            $count_stmt->execute();
            $count = $count_stmt->get_result()->fetch_assoc();

            return [
                'success' => true,
                'tickets' => $tickets,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'total' => intval($count['total'])
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get ticket details with replies
     */
    public function getTicketDetails($customer_id, $ticket_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    st.id,
                    st.ticket_number,
                    st.subject,
                    st.description,
                    st.priority,
                    st.status,
                    st.category,
                    st.order_id,
                    st.created_at,
                    st.updated_at
                FROM support_tickets st
                WHERE st.id = ? AND st.customer_id = ?
            ");

            $stmt->bind_param("ii", $ticket_id, $customer_id);
            $stmt->execute();
            $ticket = $stmt->get_result()->fetch_assoc();

            if (!$ticket) {
                return ['success' => false, 'error' => 'Ticket not found'];
            }

            // Get replies
            $replies_stmt = $this->conn->prepare("
                SELECT
                    tr.id,
                    tr.user_id,
                    COALESCE(u.name, 'Support') as user_name,
                    tr.reply_text,
                    tr.is_admin,
                    tr.created_at
                FROM ticket_replies tr
                LEFT JOIN users u ON tr.user_id = u.id
                WHERE tr.ticket_id = ?
                ORDER BY tr.created_at ASC
            ");

            $replies_stmt->bind_param("i", $ticket_id);
            $replies_stmt->execute();
            $replies_result = $replies_stmt->get_result();

            $replies = [];
            while ($reply = $replies_result->fetch_assoc()) {
                $replies[] = [
                    'id' => intval($reply['id']),
                    'user_name' => $reply['user_name'],
                    'is_admin' => boolval($reply['is_admin']),
                    'message' => $reply['reply_text'],
                    'created_at' => $reply['created_at']
                ];
            }

            return [
                'success' => true,
                'ticket' => [
                    'id' => intval($ticket['id']),
                    'ticket_number' => $ticket['ticket_number'],
                    'subject' => $ticket['subject'],
                    'description' => $ticket['description'],
                    'priority' => $ticket['priority'],
                    'status' => $ticket['status'],
                    'category' => $ticket['category'],
                    'order_id' => $ticket['order_id'],
                    'created_at' => $ticket['created_at'],
                    'updated_at' => $ticket['updated_at'],
                    'replies' => $replies
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create new support ticket
     */
    public function createTicket($customer_id, $data) {
        try {
            $ticket_number = 'TKT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            $stmt = $this->conn->prepare("
                INSERT INTO support_tickets (
                    customer_id, order_id, ticket_number, subject,
                    description, priority, category
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $order_id = $data['order_id'] ?? null;
            $subject = trim($data['subject'] ?? '');
            $description = trim($data['description'] ?? '');
            $priority = strtolower($data['priority'] ?? 'normal');
            $category = trim($data['category'] ?? '');

            $stmt->bind_param(
                "iisssss",
                $customer_id,
                $order_id,
                $ticket_number,
                $subject,
                $description,
                $priority,
                $category
            );

            if (!$stmt->execute()) {
                return ['success' => false, 'error' => 'Failed to create ticket'];
            }

            return [
                'success' => true,
                'ticket_id' => $stmt->insert_id,
                'ticket_number' => $ticket_number,
                'message' => 'Support ticket created successfully'
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add reply to ticket
     */
    public function addTicketReply($customer_id, $ticket_id, $reply_text) {
        try {
            // Verify ticket belongs to customer
            $verify_stmt = $this->conn->prepare("SELECT id FROM support_tickets WHERE id = ? AND customer_id = ?");
            $verify_stmt->bind_param("ii", $ticket_id, $customer_id);
            $verify_stmt->execute();

            if (!$verify_stmt->get_result()->fetch_assoc()) {
                return ['success' => false, 'error' => 'Ticket not found'];
            }

            $stmt = $this->conn->prepare("
                INSERT INTO ticket_replies (ticket_id, user_id, reply_text, is_admin)
                VALUES (?, ?, ?, FALSE)
            ");

            $stmt->bind_param("iss", $ticket_id, $customer_id, $reply_text);

            if (!$stmt->execute()) {
                return ['success' => false, 'error' => 'Failed to add reply'];
            }

            // Update ticket updated_at
            $update_stmt = $this->conn->prepare("UPDATE support_tickets SET updated_at = NOW() WHERE id = ?");
            $update_stmt->bind_param("i", $ticket_id);
            $update_stmt->execute();

            return ['success' => true, 'message' => 'Reply added successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get notification preferences
     */
    public function getNotificationPreferences($customer_id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM $this->table_notification_prefs WHERE customer_id = ?");
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $prefs = $stmt->get_result()->fetch_assoc();

            if (!$prefs) {
                // Create default preferences
                return $this->createDefaultPreferences($customer_id);
            }

            return [
                'success' => true,
                'preferences' => [
                    'email_order_confirmation' => boolval($prefs['email_order_confirmation']),
                    'email_shipment_notification' => boolval($prefs['email_shipment_notification']),
                    'email_delivery_notification' => boolval($prefs['email_delivery_notification']),
                    'email_refund_notification' => boolval($prefs['email_refund_notification']),
                    'email_promotion' => boolval($prefs['email_promotion']),
                    'email_newsletter' => boolval($prefs['email_newsletter']),
                    'email_reviews' => boolval($prefs['email_reviews']),
                    'sms_orders' => boolval($prefs['sms_orders']),
                    'sms_shipment' => boolval($prefs['sms_shipment']),
                    'push_notifications' => boolval($prefs['push_notifications'])
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create default notification preferences
     */
    private function createDefaultPreferences($customer_id) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO $this->table_notification_prefs (customer_id)
                VALUES (?)
            ");

            $stmt->bind_param("i", $customer_id);
            $stmt->execute();

            return $this->getNotificationPreferences($customer_id);

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreferences($customer_id, $prefs) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE $this->table_notification_prefs
                SET email_order_confirmation = ?,
                    email_shipment_notification = ?,
                    email_delivery_notification = ?,
                    email_refund_notification = ?,
                    email_promotion = ?,
                    email_newsletter = ?,
                    email_reviews = ?,
                    sms_orders = ?,
                    sms_shipment = ?,
                    push_notifications = ?
                WHERE customer_id = ?
            ");

            $email_order = boolval($prefs['email_order_confirmation'] ?? true);
            $email_shipment = boolval($prefs['email_shipment_notification'] ?? true);
            $email_delivery = boolval($prefs['email_delivery_notification'] ?? true);
            $email_refund = boolval($prefs['email_refund_notification'] ?? true);
            $email_promo = boolval($prefs['email_promotion'] ?? true);
            $email_news = boolval($prefs['email_newsletter'] ?? true);
            $email_review = boolval($prefs['email_reviews'] ?? true);
            $sms_orders = boolval($prefs['sms_orders'] ?? false);
            $sms_ship = boolval($prefs['sms_shipment'] ?? false);
            $push_notif = boolval($prefs['push_notifications'] ?? true);

            $stmt->bind_param(
                "iiiiiiiiii",
                $email_order,
                $email_shipment,
                $email_delivery,
                $email_refund,
                $email_promo,
                $email_news,
                $email_review,
                $sms_orders,
                $sms_ship,
                $push_notif,
                $customer_id
            );

            if (!$stmt->execute()) {
                return ['success' => false, 'error' => 'Failed to update preferences'];
            }

            return ['success' => true, 'message' => 'Preferences updated successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get account settings
     */
    public function getAccountSettings($customer_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM customer_account_settings WHERE customer_id = ?
            ");

            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $settings = $stmt->get_result()->fetch_assoc();

            if (!$settings) {
                return ['success' => false, 'error' => 'Settings not found'];
            }

            return [
                'success' => true,
                'settings' => [
                    'phone_number' => $settings['phone_number'] ?? '',
                    'date_of_birth' => $settings['date_of_birth'] ?? '',
                    'gender' => $settings['gender'] ?? '',
                    'company_name' => $settings['company_name'] ?? '',
                    'tax_id' => $settings['tax_id'] ?? '',
                    'preferred_language' => $settings['preferred_language'] ?? 'en',
                    'timezone' => $settings['timezone'] ?? 'UTC',
                    'two_factor_enabled' => boolval($settings['two_factor_enabled']),
                    'newsletter_signup' => boolval($settings['newsletter_signup']),
                    'marketing_consent' => boolval($settings['marketing_consent'])
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update account settings
     */
    public function updateAccountSettings($customer_id, $settings) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE customer_account_settings
                SET phone_number = ?,
                    date_of_birth = ?,
                    gender = ?,
                    company_name = ?,
                    tax_id = ?,
                    preferred_language = ?,
                    timezone = ?,
                    newsletter_signup = ?,
                    marketing_consent = ?
                WHERE customer_id = ?
            ");

            $phone = $settings['phone_number'] ?? '';
            $dob = $settings['date_of_birth'] ?? null;
            $gender = $settings['gender'] ?? '';
            $company = $settings['company_name'] ?? '';
            $tax_id = $settings['tax_id'] ?? '';
            $lang = $settings['preferred_language'] ?? 'en';
            $tz = $settings['timezone'] ?? 'UTC';
            $newsletter = boolval($settings['newsletter_signup'] ?? false);
            $marketing = boolval($settings['marketing_consent'] ?? false);

            $stmt->bind_param(
                "sssssssii",
                $phone,
                $dob,
                $gender,
                $company,
                $tax_id,
                $lang,
                $tz,
                $newsletter,
                $marketing,
                $customer_id
            );

            if (!$stmt->execute()) {
                return ['success' => false, 'error' => 'Failed to update settings'];
            }

            return ['success' => true, 'message' => 'Settings updated successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>
