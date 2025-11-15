            </main>
        </div>
    </div>

    <!-- Validation and Utility Scripts -->
    <script>
        /**
         * Professional Admin Form Validation
         * Provides real-time validation feedback for all form fields
         */

        // Form Validation Helper
        const AdminValidation = {
            // Email validation
            isValidEmail: function(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            },

            // URL validation
            isValidUrl: function(url) {
                try {
                    new URL(url);
                    return true;
                } catch (e) {
                    return false;
                }
            },

            // ISBN validation (10 or 13 digits)
            isValidISBN: function(isbn) {
                const clean = isbn.replace(/[\s-]/g, '');
                return clean.length === 10 || clean.length === 13;
            },

            // Stock/Price validation (positive number)
            isValidNumber: function(value, isPositive = true) {
                const num = parseFloat(value);
                if (isNaN(num)) return false;
                return isPositive ? num > 0 : num >= 0;
            },

            // Required field validation
            isNotEmpty: function(value) {
                return value.trim().length > 0;
            },

            // Add error class and message
            addError: function(element, message = 'This field is required') {
                element.classList.add('form-error');
                let errorDiv = element.parentElement.querySelector('.error-message');
                if (!errorDiv) {
                    errorDiv = document.createElement('span');
                    errorDiv.className = 'error-message';
                    element.parentElement.appendChild(errorDiv);
                }
                errorDiv.textContent = message;
            },

            // Remove error
            removeError: function(element) {
                element.classList.remove('form-error');
                const errorDiv = element.parentElement.querySelector('.error-message');
                if (errorDiv) {
                    errorDiv.remove();
                }
            },

            // Validate entire form
            validateForm: function(formId) {
                const form = document.getElementById(formId);
                if (!form) return false;

                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');

                requiredFields.forEach(field => {
                    if (!this.isNotEmpty(field.value)) {
                        this.addError(field, 'This field is required');
                        isValid = false;
                    } else {
                        this.removeError(field);
                    }
                });

                return isValid;
            }
        };

        // Real-time validation on form fields
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input, textarea, select');

            inputs.forEach(input => {
                // On blur - validate
                input.addEventListener('blur', function() {
                    if (this.hasAttribute('required') && !AdminValidation.isNotEmpty(this.value)) {
                        AdminValidation.addError(this, 'This field is required');
                    }
                });

                // On input - remove error if field is filled
                input.addEventListener('input', function() {
                    if (AdminValidation.isNotEmpty(this.value)) {
                        AdminValidation.removeError(this);
                    }
                });

                // Specific validations
                if (this.type === 'email') {
                    input.addEventListener('blur', function() {
                        if (this.value && !AdminValidation.isValidEmail(this.value)) {
                            AdminValidation.addError(this, 'Please enter a valid email address');
                        }
                    });
                }

                if (input.name && input.name.includes('price') || input.name && input.name.includes('stock')) {
                    input.addEventListener('blur', function() {
                        if (this.value && !AdminValidation.isValidNumber(this.value, true)) {
                            AdminValidation.addError(this, 'Please enter a valid positive number');
                        }
                    });
                }
            });
        });

        // Close modals on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    modal.classList.remove('active');
                });
            }
        });

        // Smooth page transitions
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[href]');
            if (link && link.hostname === window.location.hostname && !link.target && !link.getAttribute('onclick')) {
                // Allow natural navigation for sidebar links
            }
        });
    </script>

    <!-- Custom page scripts can be included here -->
</body>
</html>
