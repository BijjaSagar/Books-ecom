<?php
/**
 * Currency Selector Component
 * Displays currency selector in header
 * Allows customers to switch currencies
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/CurrencyHelper.php';

$currency_helper = new CurrencyHelper($conn);

// Get current currency from session or default
$current_currency_code = $_SESSION['currency'] ?? 'USD';
$current_currency = $currency_helper->getCurrencyByCode($current_currency_code);

// Get all available currencies
$currencies = $currency_helper->getAllActiveCurrencies();
?>

<!-- Currency Selector Component -->
<div class="currency-selector-wrapper">
    <div class="currency-selector-button" id="currencyToggle">
        <svg class="currency-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M12 6v12M6 12h12"></path>
        </svg>
        <span class="currency-code" id="currentCurrencyCode">
            <?php echo htmlspecialchars($current_currency['currency_code'] ?? 'USD'); ?>
        </span>
        <svg class="chevron-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
    </div>

    <!-- Currency Dropdown -->
    <div class="currency-selector-dropdown" id="currencyDropdown" style="display: none;">
        <div class="currency-dropdown-header">
            <h4>Select Currency</h4>
            <button class="close-btn" id="currencyCloseBtn" type="button" aria-label="Close">×</button>
        </div>

        <div class="currency-dropdown-search">
            <input
                type="text"
                class="currency-search"
                id="currencySearch"
                placeholder="Search currencies..."
                autocomplete="off"
            >
        </div>

        <div class="currency-dropdown-list">
            <?php foreach ($currencies as $currency): ?>
                <button
                    type="button"
                    class="currency-option <?php echo ($currency['currency_code'] === $current_currency_code) ? 'active' : ''; ?>"
                    data-currency-code="<?php echo htmlspecialchars($currency['currency_code']); ?>"
                    data-currency-symbol="<?php echo htmlspecialchars($currency['currency_symbol']); ?>"
                    title="<?php echo htmlspecialchars($currency['currency_name']); ?>"
                >
                    <span class="currency-option-symbol">
                        <?php echo htmlspecialchars($currency['currency_symbol']); ?>
                    </span>
                    <span class="currency-option-info">
                        <span class="currency-option-name">
                            <?php echo htmlspecialchars($currency['currency_name']); ?>
                        </span>
                        <span class="currency-option-code">
                            <?php echo htmlspecialchars($currency['currency_code']); ?>
                        </span>
                    </span>
                    <?php if ($currency['currency_code'] === $current_currency_code): ?>
                        <span class="currency-option-checkmark">✓</span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="currency-dropdown-footer">
            <small>Rates updated: <span id="rateUpdateTime">Just now</span></small>
        </div>
    </div>
</div>

<!-- Styles -->
<style>
.currency-selector-wrapper {
    position: relative;
    display: inline-block;
}

.currency-selector-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    background: white;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    color: #1a3a52;
    transition: all 0.3s ease;
    min-width: 80px;
}

.currency-selector-button:hover {
    background: #f5f5f5;
    border-color: #d4a574;
}

.currency-selector-button .currency-icon {
    color: #d4a574;
}

.currency-selector-button .chevron-icon {
    margin-left: auto;
    transition: transform 0.3s ease;
}

.currency-selector-button.active .chevron-icon {
    transform: rotate(180deg);
}

.currency-selector-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 8px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    min-width: 280px;
    max-height: 400px;
    display: flex;
    flex-direction: column;
    animation: slideDown 0.2s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.currency-dropdown-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border-bottom: 1px solid #eee;
}

.currency-dropdown-header h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #1a3a52;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    color: #999;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-btn:hover {
    color: #1a3a52;
}

.currency-dropdown-search {
    padding: 12px 16px;
    border-bottom: 1px solid #eee;
}

.currency-search {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.3s ease;
}

.currency-search:focus {
    border-color: #d4a574;
    box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.1);
}

.currency-dropdown-list {
    overflow-y: auto;
    flex: 1;
}

.currency-option {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    padding: 12px 16px;
    border: none;
    background: white;
    cursor: pointer;
    text-align: left;
    transition: all 0.2s ease;
    font-size: 14px;
}

.currency-option:hover {
    background: #f9f9f9;
}

.currency-option.active {
    background: #fffcf7;
    color: #d4a574;
}

.currency-option-symbol {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: #f0f0f0;
    border-radius: 4px;
    font-weight: 600;
    color: #1a3a52;
    flex-shrink: 0;
}

.currency-option.active .currency-option-symbol {
    background: #fef3e6;
    color: #d4a574;
}

.currency-option-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
}

.currency-option-name {
    font-weight: 500;
    color: #1a3a52;
}

.currency-option-code {
    font-size: 12px;
    color: #999;
}

.currency-option-checkmark {
    color: #d4a574;
    font-weight: bold;
    margin-left: 8px;
}

.currency-dropdown-footer {
    padding: 12px 16px;
    border-top: 1px solid #eee;
    background: #f9f9f9;
    font-size: 12px;
    color: #999;
    text-align: center;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .currency-selector-dropdown {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        top: auto;
        max-height: 70vh;
        border-radius: 12px 12px 0 0;
        min-width: auto;
        margin-top: 0;
    }

    .currency-dropdown-list {
        max-height: calc(70vh - 200px);
    }
}
</style>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('currencyToggle');
    const dropdown = document.getElementById('currencyDropdown');
    const closeBtn = document.getElementById('currencyCloseBtn');
    const searchInput = document.getElementById('currencySearch');
    const currencyOptions = document.querySelectorAll('.currency-option');

    // Toggle dropdown
    toggle.addEventListener('click', function() {
        dropdown.style.display = dropdown.style.display === 'none' ? 'flex' : 'none';
        toggle.classList.toggle('active');

        // Focus search input
        if (dropdown.style.display === 'flex') {
            setTimeout(() => searchInput.focus(), 100);
        }
    });

    // Close dropdown
    closeBtn.addEventListener('click', function() {
        dropdown.style.display = 'none';
        toggle.classList.remove('active');
    });

    // Close when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.currency-selector-wrapper')) {
            dropdown.style.display = 'none';
            toggle.classList.remove('active');
        }
    });

    // Filter currencies
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();

        currencyOptions.forEach(option => {
            const name = option.textContent.toLowerCase();
            const matches = name.includes(searchTerm);
            option.style.display = matches ? '' : 'none';
        });
    });

    // Handle currency selection
    currencyOptions.forEach(option => {
        option.addEventListener('click', function() {
            const currencyCode = this.dataset.currencyCode;
            changeCurrency(currencyCode);
        });
    });

    function changeCurrency(code) {
        // Update session via AJAX
        fetch('/api/set-currency.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                currency_code: code
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI
                document.getElementById('currentCurrencyCode').textContent = code;

                // Update active state
                currencyOptions.forEach(option => {
                    option.classList.toggle('active', option.dataset.currencyCode === code);
                });

                // Close dropdown
                dropdown.style.display = 'none';
                toggle.classList.remove('active');

                // Reload page to refresh prices
                window.location.reload();
            } else {
                alert('Failed to change currency: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error changing currency');
        });
    }

    // Keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            dropdown.style.display = 'none';
            toggle.classList.remove('active');
        }
    });
});
</script>
