/**
 * Mobile Menu Component
 * Handles responsive navigation for mobile and tablet devices
 */

class MobileMenu {
    constructor(togglerSelector = '.navbar-toggler', navSelector = '.navbar-nav') {
        this.toggler = document.querySelector(togglerSelector);
        this.nav = document.querySelector(navSelector);

        if (this.toggler && this.nav) {
            this.init();
        }
    }

    init() {
        // Click handler for toggle button
        this.toggler.addEventListener('click', () => this.toggleMenu());

        // Close menu when clicking nav links
        const links = this.nav.querySelectorAll('a');
        links.forEach(link => {
            link.addEventListener('click', () => this.closeMenu());
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.navbar') && this.nav.classList.contains('active')) {
                this.closeMenu();
            }
        });

        // Close menu on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.nav.classList.contains('active')) {
                this.closeMenu();
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                this.closeMenu();
            }
        });
    }

    toggleMenu() {
        this.nav.classList.toggle('active');
        this.toggler.setAttribute('aria-expanded', this.nav.classList.contains('active'));
    }

    openMenu() {
        this.nav.classList.add('active');
        this.toggler.setAttribute('aria-expanded', 'true');
    }

    closeMenu() {
        this.nav.classList.remove('active');
        this.toggler.setAttribute('aria-expanded', 'false');
    }
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    new MobileMenu();
});
