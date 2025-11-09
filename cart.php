<?php
// checkout.php - Complete checkout process with world countries and states data
session_start();

// Include necessary files
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title_override = "Checkout - Bookory";

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: /bookshelf/cart.php");
    exit();
}

// World Countries and States Data
$world_data = [
    'US' => [
        'name' => 'United States',
        'states' => [
            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
            'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'FL' => 'Florida', 'GA' => 'Georgia',
            'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
            'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
            'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri',
            'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey',
            'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
            'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
            'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont',
            'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming'
        ]
    ],
    'CA' => [
        'name' => 'Canada',
        'states' => [
            'AB' => 'Alberta', 'BC' => 'British Columbia', 'MB' => 'Manitoba', 'NB' => 'New Brunswick',
            'NL' => 'Newfoundland and Labrador', 'NS' => 'Nova Scotia', 'NT' => 'Northwest Territories',
            'NU' => 'Nunavut', 'ON' => 'Ontario', 'PE' => 'Prince Edward Island', 'QC' => 'Quebec',
            'SK' => 'Saskatchewan', 'YT' => 'Yukon'
        ]
    ],
    'GB' => [
        'name' => 'United Kingdom',
        'states' => [
            'ENG' => 'England', 'SCT' => 'Scotland', 'WLS' => 'Wales', 'NIR' => 'Northern Ireland'
        ]
    ],
    'AU' => [
        'name' => 'Australia',
        'states' => [
            'NSW' => 'New South Wales', 'VIC' => 'Victoria', 'QLD' => 'Queensland', 'WA' => 'Western Australia',
            'SA' => 'South Australia', 'TAS' => 'Tasmania', 'ACT' => 'Australian Capital Territory', 'NT' => 'Northern Territory'
        ]
    ],
    'IN' => [
        'name' => 'India',
        'states' => [
            'AP' => 'Andhra Pradesh', 'AR' => 'Arunachal Pradesh', 'AS' => 'Assam', 'BR' => 'Bihar',
            'CT' => 'Chhattisgarh', 'GA' => 'Goa', 'GJ' => 'Gujarat', 'HR' => 'Haryana', 'HP' => 'Himachal Pradesh',
            'JH' => 'Jharkhand', 'KA' => 'Karnataka', 'KL' => 'Kerala', 'MP' => 'Madhya Pradesh', 'MH' => 'Maharashtra',
            'MN' => 'Manipur', 'ML' => 'Meghalaya', 'MZ' => 'Mizoram', 'NL' => 'Nagaland', 'OR' => 'Odisha',
            'PB' => 'Punjab', 'RJ' => 'Rajasthan', 'SK' => 'Sikkim', 'TN' => 'Tamil Nadu', 'TG' => 'Telangana',
            'TR' => 'Tripura', 'UP' => 'Uttar Pradesh', 'UT' => 'Uttarakhand', 'WB' => 'West Bengal',
            'AN' => 'Andaman and Nicobar Islands', 'CH' => 'Chandigarh', 'DN' => 'Dadra and Nagar Haveli',
            'DD' => 'Daman and Diu', 'DL' => 'Delhi', 'JK' => 'Jammu and Kashmir', 'LA' => 'Ladakh',
            'LD' => 'Lakshadweep', 'PY' => 'Puducherry'
        ]
    ],
    'DE' => [
        'name' => 'Germany',
        'states' => [
            'BW' => 'Baden-Württemberg', 'BY' => 'Bavaria', 'BE' => 'Berlin', 'BB' => 'Brandenburg',
            'HB' => 'Bremen', 'HH' => 'Hamburg', 'HE' => 'Hesse', 'MV' => 'Mecklenburg-Vorpommern',
            'NI' => 'Lower Saxony', 'NW' => 'North Rhine-Westphalia', 'RP' => 'Rhineland-Palatinate',
            'SL' => 'Saarland', 'SN' => 'Saxony', 'ST' => 'Saxony-Anhalt', 'SH' => 'Schleswig-Holstein', 'TH' => 'Thuringia'
        ]
    ],
    'FR' => [
        'name' => 'France',
        'states' => [
            'ARA' => 'Auvergne-Rhône-Alpes', 'BFC' => 'Bourgogne-Franche-Comté', 'BRE' => 'Brittany',
            'CVL' => 'Centre-Val de Loire', 'COR' => 'Corsica', 'GES' => 'Grand Est', 'HDF' => 'Hauts-de-France',
            'IDF' => 'Île-de-France', 'NOR' => 'Normandy', 'NAQ' => 'Nouvelle-Aquitaine', 'OCC' => 'Occitanie',
            'PDL' => 'Pays de la Loire', 'PAC' => 'Provence-Alpes-Côte d\'Azur'
        ]
    ],
    'JP' => [
        'name' => 'Japan',
        'states' => [
            'AICHI' => 'Aichi', 'AKITA' => 'Akita', 'AOMORI' => 'Aomori', 'CHIBA' => 'Chiba', 'EHIME' => 'Ehime',
            'FUKUI' => 'Fukui', 'FUKUOKA' => 'Fukuoka', 'FUKUSHIMA' => 'Fukushima', 'GIFU' => 'Gifu', 'GUNMA' => 'Gunma',
            'HIROSHIMA' => 'Hiroshima', 'HOKKAIDO' => 'Hokkaido', 'HYOGO' => 'Hyogo', 'IBARAKI' => 'Ibaraki',
            'ISHIKAWA' => 'Ishikawa', 'IWATE' => 'Iwate', 'KAGAWA' => 'Kagawa', 'KAGOSHIMA' => 'Kagoshima',
            'KANAGAWA' => 'Kanagawa', 'KOCHI' => 'Kochi', 'KUMAMOTO' => 'Kumamoto', 'KYOTO' => 'Kyoto',
            'MIE' => 'Mie', 'MIYAGI' => 'Miyagi', 'MIYAZAKI' => 'Miyazaki', 'NAGANO' => 'Nagano',
            'NAGASAKI' => 'Nagasaki', 'NARA' => 'Nara', 'NIIGATA' => 'Niigata', 'OITA' => 'Oita',
            'OKAYAMA' => 'Okayama', 'OKINAWA' => 'Okinawa', 'OSAKA' => 'Osaka', 'SAGA' => 'Saga',
            'SAITAMA' => 'Saitama', 'SHIGA' => 'Shiga', 'SHIMANE' => 'Shimane', 'SHIZUOKA' => 'Shizuoka',
            'TOCHIGI' => 'Tochigi', 'TOKUSHIMA' => 'Tokushima', 'TOKYO' => 'Tokyo', 'TOTTORI' => 'Tottori',
            'TOYAMA' => 'Toyama', 'WAKAYAMA' => 'Wakayama', 'YAMAGATA' => 'Yamagata', 'YAMAGUCHI' => 'Yamaguchi',
            'YAMANASHI' => 'Yamanashi'
        ]
    ],
    'BR' => [
        'name' => 'Brazil',
        'states' => [
            'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
            'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
            'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
            'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
            'SE' => 'Sergipe', 'TO' => 'Tocantins'
        ]
    ],
    'MX' => [
        'name' => 'Mexico',
        'states' => [
            'AGU' => 'Aguascalientes', 'BCN' => 'Baja California', 'BCS' => 'Baja California Sur',
            'CAM' => 'Campeche', 'CHP' => 'Chiapas', 'CHH' => 'Chihuahua', 'COA' => 'Coahuila',
            'COL' => 'Colima', 'DIF' => 'Mexico City', 'DUR' => 'Durango', 'GUA' => 'Guanajuato',
            'GRO' => 'Guerrero', 'HID' => 'Hidalgo', 'JAL' => 'Jalisco', 'MEX' => 'Mexico State',
            'MIC' => 'Michoacán', 'MOR' => 'Morelos', 'NAY' => 'Nayarit', 'NLE' => 'Nuevo León',
            'OAX' => 'Oaxaca', 'PUE' => 'Puebla', 'QUE' => 'Querétaro', 'ROO' => 'Quintana Roo',
            'SLP' => 'San Luis Potosí', 'SIN' => 'Sinaloa', 'SON' => 'Sonora', 'TAB' => 'Tabasco',
            'TAM' => 'Tamaulipas', 'TLA' => 'Tlaxcala', 'VER' => 'Veracruz', 'YUC' => 'Yucatán', 'ZAC' => 'Zacatecas'
        ]
    ],
    'IT' => [
        'name' => 'Italy',
        'states' => [
            'ABR' => 'Abruzzo', 'BAS' => 'Basilicata', 'CAL' => 'Calabria', 'CAM' => 'Campania',
            'EMR' => 'Emilia-Romagna', 'FVG' => 'Friuli-Venezia Giulia', 'LAZ' => 'Lazio', 'LIG' => 'Liguria',
            'LOM' => 'Lombardy', 'MAR' => 'Marche', 'MOL' => 'Molise', 'PIE' => 'Piedmont',
            'PUG' => 'Puglia', 'SAR' => 'Sardinia', 'SIC' => 'Sicily', 'TOS' => 'Tuscany',
            'TAA' => 'Trentino-Alto Adige', 'UMB' => 'Umbria', 'VDA' => 'Aosta Valley', 'VEN' => 'Veneto'
        ]
    ],
    'ES' => [
        'name' => 'Spain',
        'states' => [
            'AN' => 'Andalusia', 'AR' => 'Aragon', 'AS' => 'Asturias', 'IB' => 'Balearic Islands',
            'PV' => 'Basque Country', 'CN' => 'Canary Islands', 'CB' => 'Cantabria', 'CM' => 'Castile-La Mancha',
            'CL' => 'Castile and León', 'CT' => 'Catalonia', 'EX' => 'Extremadura', 'GA' => 'Galicia',
            'MD' => 'Madrid', 'MC' => 'Murcia', 'NC' => 'Navarre', 'RI' => 'La Rioja', 'VC' => 'Valencia',
            'CE' => 'Ceuta', 'ML' => 'Melilla'
        ]
    ],
    'NL' => [
        'name' => 'Netherlands',
        'states' => [
            'DR' => 'Drenthe', 'FL' => 'Flevoland', 'FR' => 'Friesland', 'GE' => 'Gelderland',
            'GR' => 'Groningen', 'LI' => 'Limburg', 'NB' => 'North Brabant', 'NH' => 'North Holland',
            'OV' => 'Overijssel', 'SH' => 'South Holland', 'UT' => 'Utrecht', 'ZE' => 'Zeeland'
        ]
    ],
    'RU' => [
        'name' => 'Russia',
        'states' => [
            'AD' => 'Adygea', 'AL' => 'Altai', 'BA' => 'Bashkortostan', 'BU' => 'Buryatia',
            'CE' => 'Chechnya', 'CU' => 'Chuvashia', 'DA' => 'Dagestan', 'IN' => 'Ingushetia',
            'KB' => 'Kabardino-Balkaria', 'KL' => 'Kalmykia', 'KC' => 'Karachay-Cherkessia',
            'KR' => 'Karelia', 'KO' => 'Komi', 'ME' => 'Mari El', 'MO' => 'Mordovia',
            'SA' => 'Sakha', 'SE' => 'North Ossetia', 'TA' => 'Tatarstan', 'TY' => 'Tuva',
            'UD' => 'Udmurtia', 'KK' => 'Khakassia'
        ]
    ],
    'CN' => [
        'name' => 'China',
        'states' => [
            'AH' => 'Anhui', 'BJ' => 'Beijing', 'CQ' => 'Chongqing', 'FJ' => 'Fujian',
            'GS' => 'Gansu', 'GD' => 'Guangdong', 'GX' => 'Guangxi', 'GZ' => 'Guizhou',
            'HI' => 'Hainan', 'HE' => 'Hebei', 'HL' => 'Heilongjiang', 'HA' => 'Henan',
            'HB' => 'Hubei', 'HN' => 'Hunan', 'JS' => 'Jiangsu', 'JX' => 'Jiangxi',
            'JL' => 'Jilin', 'LN' => 'Liaoning', 'NM' => 'Inner Mongolia', 'NX' => 'Ningxia',
            'QH' => 'Qinghai', 'SN' => 'Shaanxi', 'SD' => 'Shandong', 'SH' => 'Shanghai',
            'SX' => 'Shanxi', 'SC' => 'Sichuan', 'TJ' => 'Tianjin', 'XJ' => 'Xinjiang',
            'XZ' => 'Tibet', 'YN' => 'Yunnan', 'ZJ' => 'Zhejiang'
        ]
    ]
];

// Initialize messages
$success_message = '';
$error_message = '';
$order_placed = false;

// Get cart items and calculate totals
$cart_items = $_SESSION['cart'];
$cart_total = 0;
$cart_count = 0;

foreach ($cart_items as $item) {
    $cart_total += $item['price'] * $item['quantity'];
    $cart_count += $item['quantity'];
}

$shipping_cost = $cart_total > 50 ? 0 : 5.99;
$tax_rate = 0.08; // 8% tax
$tax_amount = $cart_total * $tax_rate;
$final_total = $cart_total + $shipping_cost + $tax_amount;

// Handle AJAX request for states
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_states') {
    header('Content-Type: application/json');
    $country_code = $_GET['country'] ?? '';
    
    if (isset($world_data[$country_code])) {
        echo json_encode([
            'success' => true,
            'states' => $world_data[$country_code]['states']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'states' => []
        ]);
    }
    exit();
}

// Handle form submission (same as before but with world data validation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // ... (same form processing logic as before)
    $errors = [];
    
    // Billing information validation
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    
    // Payment information
    $payment_method = $_POST['payment_method'] ?? '';
    $card_number = trim($_POST['card_number'] ?? '');
    $expiry_month = trim($_POST['expiry_month'] ?? '');
    $expiry_year = trim($_POST['expiry_year'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');
    $card_name = trim($_POST['card_name'] ?? '');
    
    // Validation with world data
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($city)) $errors[] = "City is required";
    if (empty($country) || !isset($world_data[$country])) $errors[] = "Please select a valid country";
    if (empty($state) || !isset($world_data[$country]['states'][$state])) $errors[] = "Please select a valid state/province";
    if (empty($zip_code)) $errors[] = "ZIP/Postal code is required";
    
    if ($payment_method === 'credit_card') {
        if (empty($card_number)) $errors[] = "Card number is required";
        if (empty($expiry_month)) $errors[] = "Expiry month is required";
        if (empty($expiry_year)) $errors[] = "Expiry year is required";
        if (empty($cvv)) $errors[] = "CVV is required";
        if (empty($card_name)) $errors[] = "Cardholder name is required";
    }
    
    if (empty($errors)) {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Generate order number
            $order_number = 'ORD-' . date('Y') . '-' . strtoupper(substr(uniqid(), -8));
            
            // Insert order with country and state names
            $country_name = $world_data[$country]['name'];
            $state_name = $world_data[$country]['states'][$state];
            
            $order_stmt = $conn->prepare("
                INSERT INTO orders (
                    order_number, customer_email, first_name, last_name, phone, 
                    address, city, state, state_name, zip_code, country, country_name,
                    subtotal, tax_amount, shipping_cost, total_amount, 
                    payment_method, order_status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $order_stmt->bind_param("ssssssssssssdddds", 
                $order_number, $email, $first_name, $last_name, $phone,
                $address, $city, $state, $state_name, $zip_code, $country, $country_name,
                $cart_total, $tax_amount, $shipping_cost, $final_total, $payment_method
            );
            
            if (!$order_stmt->execute()) {
                throw new Exception("Failed to create order");
            }
            
            $order_id = $conn->insert_id;
            
            // Insert order items
            $item_stmt = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, product_title, quantity, price, total)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($cart_items as $product_id => $item) {
                $item_total = $item['price'] * $item['quantity'];
                $item_stmt->bind_param("iisida", 
                    $order_id, $product_id, $item['title'], 
                    $item['quantity'], $item['price'], $item_total
                );
                
                if (!$item_stmt->execute()) {
                    throw new Exception("Failed to add order item");
                }
                
                // Update product stock
                $stock_stmt = $conn->prepare("
                    UPDATE products 
                    SET stock_quantity = stock_quantity - ?, sales_count = sales_count + ? 
                    WHERE id = ?
                ");
                $stock_stmt->bind_param("iii", $item['quantity'], $item['quantity'], $product_id);
                $stock_stmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            // Clear cart
            $_SESSION['cart'] = [];
            
            // Set success message
            $_SESSION['order_success'] = [
                'order_number' => $order_number,
                'total' => $final_total,
                'email' => $email,
                'country' => $country_name,
                'state' => $state_name
            ];
            
            $order_placed = true;
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Order processing failed. Please try again.";
            error_log("Checkout error: " . $e->getMessage());
        }
    } else {
        $error_message = implode(", ", $errors);
    }
}

include 'includes/header.php';
?>

<style>
/* All the same styles as before */
.checkout-page {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

.checkout-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
    overflow: hidden;
}

.checkout-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 2rem;
    text-align: center;
}

.checkout-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
}

.checkout-steps {
    display: flex;
    justify-content: center;
    margin-top: 1rem;
}

.step {
    display: flex;
    align-items: center;
    margin: 0 1rem;
}

.step-number {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-right: 0.5rem;
}

.step.active .step-number {
    background: white;
    color: #667eea;
}

.checkout-content {
    padding: 2rem;
}

.form-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.form-section h3 {
    color: #1f2937;
    font-weight: 600;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 0.5rem;
}

.form-control {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.75rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-label {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.country-flag {
    width: 20px;
    height: 15px;
    margin-right: 8px;
    border-radius: 2px;
}

.loading-states {
    color: #6b7280;
    font-style: italic;
}

/* ... rest of the styles remain the same ... */
</style>

<!-- Add flag icons CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/6.6.6/css/flag-icon.min.css">

<div class="checkout-page">
    <div class="container">
        <?php if ($order_placed && isset($_SESSION['order_success'])): ?>
            <!-- Order Success Page -->
            <div class="checkout-container">
                <div class="success-page">
                    <div class="success-icon">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <h1 class="h2 fw-bold text-success mb-3">Order Placed Successfully!</h1>
                    <p class="lead mb-4">Thank you for your order. We've sent a confirmation email to your inbox.</p>
                    
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <h5 class="card-title">Order Details</h5>
                                    <hr>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Order Number:</span>
                                        <strong><?php echo htmlspecialchars($_SESSION['order_success']['order_number']); ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Total Amount:</span>
                                        <strong>$<?php echo number_format($_SESSION['order_success']['total'], 2); ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Email:</span>
                                        <span><?php echo htmlspecialchars($_SESSION['order_success']['email']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span>Shipping to:</span>
                                        <span><?php echo htmlspecialchars($_SESSION['order_success']['state'] . ', ' . $_SESSION['order_success']['country']); ?></span>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="/bookshelf/shop.php" class="btn btn-primary">
                                            <i class="bi bi-arrow-left me-2"></i>Continue Shopping
                                        </a>
                                        <a href="/bookshelf/" class="btn btn-outline-secondary">
                                            <i class="bi bi-house me-2"></i>Back to Home
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['order_success']); ?>
        <?php else: ?>
            <!-- Checkout Form -->
            <div class="checkout-container">
                <!-- Header -->
                <div class="checkout-header">
                    <h1><i class="bi bi-shield-check me-3"></i>Secure Checkout</h1>
                    <div class="checkout-steps">
                        <div class="step active">
                            <div class="step-number">1</div>
                            <span>Cart</span>
                        </div>
                        <div class="step active">
                            <div class="step-number">2</div>
                            <span>Checkout</span>
                        </div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <span>Complete</span>
                        </div>
                    </div>
                </div>

                <div class="checkout-content">
                    <!-- Messages -->
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row g-4">
                        <!-- Checkout Form -->
                        <div class="col-lg-8">
                            <form method="POST" id="checkoutForm">
                                <!-- Billing Information -->
                                <div class="form-section">
                                    <h3><i class="bi bi-person me-2"></i>Billing Information</h3>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">First Name *</label>
                                            <input type="text" name="first_name" class="form-control" required 
                                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Last Name *</label>
                                            <input type="text" name="last_name" class="form-control" required
                                                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email Address *</label>
                                            <input type="email" name="email" class="form-control" required
                                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone Number *</label>
                                            <input type="tel" name="phone" class="form-control" required
                                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Address *</label>
                                            <input type="text" name="address" class="form-control" required
                                                   value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">City *</label>
                                            <input type="text" name="city" class="form-control" required
                                                   value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                                        </div>
                                        
                                        <!-- Country Selection with Flags -->
                                        <div class="col-md-6">
                                            <label class="form-label">Country *</label>
                                            <select name="country" id="countrySelect" class="form-control" required onchange="loadStates()">
                                                <option value="">Select Country</option>
                                                <?php foreach ($world_data as $country_code => $country_info): ?>
                                                    <option value="<?php echo $country_code; ?>" 
                                                            data-flag="<?php echo strtolower($country_code === 'GB' ? 'gb' : $country_code); ?>"
                                                            <?php echo ($_POST['country'] ?? '') === $country_code ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($country_info['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <!-- Dynamic State Selection -->
                                        <div class="col-md-6">
                                            <label class="form-label">State/Province *</label>
                                            <select name="state" id="stateSelect" class="form-control" required>
                                                <option value="">First select a country</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">ZIP/Postal Code *</label>
                                            <input type="text" name="zip_code" class="form-control" required
                                                   value="<?php echo htmlspecialchars($_POST['zip_code'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Method (same as before) -->
                                <div class="form-section">
                                    <h3><i class="bi bi-credit-card me-2"></i>Payment Method</h3>
                                    
                                    <div class="payment-method" onclick="selectPayment('credit_card')">
                                        <input type="radio" name="payment_method" value="credit_card" id="credit_card" 
                                               <?php echo ($_POST['payment_method'] ?? 'credit_card') === 'credit_card' ? 'checked' : ''; ?>>
                                        <i class="bi bi-credit-card"></i>
                                        <strong>Credit Card</strong>
                                        <div class="mt-2">
                                            <small class="text-muted">Visa, Mastercard, American Express</small>
                                        </div>
                                    </div>

                                    <div class="payment-method" onclick="selectPayment('paypal')">
                                        <input type="radio" name="payment_method" value="paypal" id="paypal"
                                               <?php echo ($_POST['payment_method'] ?? '') === 'paypal' ? 'checked' : ''; ?>>
                                        <i class="bi bi-paypal text-primary"></i>
                                        <strong>PayPal</strong>
                                        <div class="mt-2">
                                            <small class="text-muted">Pay with your PayPal account</small>
                                        </div>
                                    </div>

                                    <!-- Credit Card Details -->
                                    <div id="creditCardDetails" class="mt-3" style="<?php echo ($_POST['payment_method'] ?? 'credit_card') !== 'credit_card' ? 'display: none;' : ''; ?>">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label">Card Number *</label>
                                                <input type="text" name="card_number" class="form-control card-input" 
                                                       placeholder="1234 5678 9012 3456" maxlength="19"
                                                       value="<?php echo htmlspecialchars($_POST['card_number'] ?? ''); ?>">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Cardholder Name *</label>
                                                <input type="text" name="card_name" class="form-control"
                                                       value="<?php echo htmlspecialchars($_POST['card_name'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Expiry Month *</label>
                                                <select name="expiry_month" class="form-control">
                                                    <option value="">MM</option>
                                                    <?php for($i = 1; $i <= 12; $i++): ?>
                                                        <option value="<?php echo sprintf('%02d', $i); ?>" 
                                                                <?php echo ($_POST['expiry_month'] ?? '') === sprintf('%02d', $i) ? 'selected' : ''; ?>>
                                                            <?php echo sprintf('%02d', $i); ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Expiry Year *</label>
                                                <select name="expiry_year" class="form-control">
                                                    <option value="">YYYY</option>
                                                    <?php for($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                                                        <option value="<?php echo $i; ?>" 
                                                                <?php echo ($_POST['expiry_year'] ?? '') === (string)$i ? 'selected' : ''; ?>>
                                                            <?php echo $i; ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">CVV *</label>
                                                <input type="text" name="cvv" class="form-control" maxlength="4" placeholder="123"
                                                       value="<?php echo htmlspecialchars($_POST['cvv'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Security Badges -->
                                <div class="security-badges">
                                    <div class="security-badge">
                                        <i class="bi bi-shield-check me-1"></i>SSL Encrypted
                                    </div>
                                    <div class="security-badge">
                                        <i class="bi bi-lock me-1"></i>Secure Payment
                                    </div>
                                    <div class="security-badge">
                                        <i class="bi bi-credit-card me-1"></i>PCI Compliant
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Order Summary (same as before) -->
                        <div class="col-lg-4">
                            <div class="order-summary">
                                <h3><i class="bi bi-receipt me-2"></i>Order Summary</h3>
                                
                                <!-- Order Items -->
                                <div class="order-items mb-3">
                                    <?php foreach ($cart_items as $item): ?>
                                        <div class="order-item">
                                            <div class="item-info">
                                                <div class="item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                                <div class="item-details">Qty: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?></div>
                                            </div>
                                            <div class="item-price">
                                                $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Summary Totals -->
                                <div class="summary-row">
                                    <span>Subtotal (<?php echo $cart_count; ?> items):</span>
                                    <span>$<?php echo number_format($cart_total, 2); ?></span>
                                </div>
                                
                                <div class="summary-row">
                                    <span>Shipping:</span>
                                    <span>
                                        <?php if ($shipping_cost > 0): ?>
                                            $<?php echo number_format($shipping_cost, 2); ?>
                                        <?php else: ?>
                                            <span class="badge bg-success">FREE</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <div class="summary-row">
                                    <span>Tax:</span>
                                    <span>$<?php echo number_format($tax_amount, 2); ?></span>
                                </div>
                                
                                <div class="summary-row summary-total">
                                    <span>Total:</span>
                                    <span>$<?php echo number_format($final_total, 2); ?></span>
                                </div>
                                
                                <button type="submit" form="checkoutForm" name="place_order" class="place-order-btn">
                                    <i class="bi bi-lock me-2"></i>Place Order Securely
                                </button>
                                
                                <div class="text-center mt-3">
                                    <small class="text-light">
                                        <i class="bi bi-shield-check me-1"></i>
                                        Your payment information is encrypted and secure
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// World data for client-side access
const worldData = <?php echo json_encode($world_data); ?>;

// Load states based on selected country
function loadStates() {
    const countrySelect = document.getElementById('countrySelect');
    const stateSelect = document.getElementById('stateSelect');
    const selectedCountry = countrySelect.value;
    
    // Clear current states
    stateSelect.innerHTML = '<option value="">Loading states...</option>';
    stateSelect.disabled = true;
    
    if (selectedCountry && worldData[selectedCountry]) {
        // Populate states
        stateSelect.innerHTML = '<option value="">Select State/Province</option>';
        
        const states = worldData[selectedCountry].states;
        for (const [code, name] of Object.entries(states)) {
            const option = document.createElement('option');
            option.value = code;
            option.textContent = name;
            stateSelect.appendChild(option);
        }
        
        stateSelect.disabled = false;
        
        // Restore selected state if form was submitted with errors
        const selectedState = '<?php echo $_POST['state'] ?? ''; ?>';
        if (selectedState) {
            stateSelect.value = selectedState;
        }
    } else {
        stateSelect.innerHTML = '<option value="">First select a country</option>';
        stateSelect.disabled = false;
    }
}

// Payment method selection
function selectPayment(method) {
    document.getElementById(method).checked = true;
    
    const creditCardDetails = document.getElementById('creditCardDetails');
    if (method === 'credit_card') {
        creditCardDetails.style.display = 'block';
    } else {
        creditCardDetails.style.display = 'none';
    }
    
    // Update payment method styling
    document.querySelectorAll('.payment-method').forEach(el => {
        el.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
}

document.addEventListener('DOMContentLoaded', function() {
    // Load states on page load if country is already selected
    const countrySelect = document.getElementById('countrySelect');
    if (countrySelect.value) {
        loadStates();
    }
    
    // Format credit card number
    const cardNumberInput = document.querySelector('input[name="card_number"]');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || '';
            if (formattedValue !== value) {
                e.target.value = formattedValue;
            }
        });
    }
    
    // CVV validation
    const cvvInput = document.querySelector('input[name="cvv"]');
    if (cvvInput) {
        cvvInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
    }
    
    // Form validation
    const form = document.getElementById('checkoutForm');
    form.addEventListener('submit', function(e) {
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        if (paymentMethod === 'credit_card') {
            const cardNumber = document.querySelector('input[name="card_number"]').value;
            const expiryMonth = document.querySelector('select[name="expiry_month"]').value;
            const expiryYear = document.querySelector('select[name="expiry_year"]').value;
            const cvv = document.querySelector('input[name="cvv"]').value;
            const cardName = document.querySelector('input[name="card_name"]').value;
            
            if (!cardNumber || !expiryMonth || !expiryYear || !cvv || !cardName) {
                alert('Please fill in all credit card details');
                e.preventDefault();
                return;
            }
        }
        
        // Validate country and state
        const country = document.getElementById('countrySelect').value;
        const state = document.getElementById('stateSelect').value;
        
        if (!country) {
            alert('Please select a country');
            e.preventDefault();
            return;
        }
        
        if (!state) {
            alert('Please select a state/province');
            e.preventDefault();
            return;
        }
        
        // Show loading state
        const submitBtn = document.querySelector('.place-order-btn');
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing Order...';
        submitBtn.disabled = true;
    });
    
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>

<style>
.payment-method {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-method:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
}

.payment-method.selected {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.1);
}

.payment-method input[type="radio"] {
    margin-right: 0.75rem;
}

.payment-method i {
    font-size: 1.5rem;
    margin-right: 0.5rem;
    color: #667eea;
}

.order-summary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 16px;
    padding: 2rem;
    position: sticky;
    top: 120px;
}

.order-summary h3 {
    border-bottom: 1px solid rgba(255,255,255,0.2);
    padding-bottom: 1rem;
    margin-bottom: 1.5rem;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.order-item:last-child {
    border-bottom: none;
}

.item-info {
    flex: 1;
}

.item-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.item-details {
    font-size: 0.875rem;
    opacity: 0.8;
}

.item-price {
    font-weight: 600;
    white-space: nowrap;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    padding: 0.5rem 0;
}

.summary-total {
    font-size: 1.25rem;
    font-weight: 700;
    border-top: 1px solid rgba(255,255,255,0.2);
    padding-top: 1rem;
    margin-top: 1rem;
}

.place-order-btn {
    background: white;
    color: #667eea;
    border: none;
    padding: 1rem 2rem;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1.1rem;
    width: 100%;
    transition: all 0.3s ease;
    margin-top: 1.5rem;
}

.place-order-btn:hover {
    background: #f8fafc;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.success-page {
    text-align: center;
    padding: 4rem 2rem;
}

.success-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #10b981, #34d399);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2rem;
    color: white;
    font-size: 3rem;
}

.security-badges {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 2rem;
    flex-wrap: wrap;
}

.security-badge {
    background: #f8f9fa;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.card-input {
    background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAyNCAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjI0IiBoZWlnaHQ9IjE2IiByeD0iMiIgZmlsbD0iIzY2N2VlYSIvPgo8cmVjdCB4PSIyIiB5PSI2IiB3aWR0aD0iMjAiIGhlaWdodD0iMiIgZmlsbD0id2hpdGUiLz4KPHJlY3QgeD0iMiIgeT0iMTAiIHdpZHRoPSI0IiBoZWlnaHQ9IjIiIGZpbGw9IndoaXRlIi8+Cjwvc3ZnPgo=');
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 30px;
    padding-right: 3rem;
}

@media (max-width: 768px) {
    .checkout-steps {
        flex-direction: column;
        align-items: center;
    }
    
    .step {
        margin: 0.5rem 0;
    }
    
    .checkout-content {
        padding: 1rem;
    }
    
    .form-section {
        padding: 1rem;
    }
    
    .order-summary {
        position: relative;
        top: 0;
        margin-top: 2rem;
    }
}
</style>

<?php 
// Enhanced database schema with country and state names
/*
ALTER TABLE orders ADD COLUMN country_name VARCHAR(100) AFTER country;
ALTER TABLE orders ADD COLUMN state_name VARCHAR(100) AFTER state;
*/
include 'includes/footer.php'; 
?>