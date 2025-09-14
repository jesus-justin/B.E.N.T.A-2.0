// Dark Mode Toggle System for BENTA
class DarkMode {
    constructor() {
        this.isDark = localStorage.getItem('darkMode') === 'true';
        this.init();
    }

    init() {
        this.applyTheme();
        this.createToggleButton();
        this.addSystemPreferenceListener();
    }

    applyTheme() {
        const root = document.documentElement;
        
        if (this.isDark) {
            root.classList.add('dark-mode');
            root.setAttribute('data-theme', 'dark');
        } else {
            root.classList.remove('dark-mode');
            root.setAttribute('data-theme', 'light');
        }

        // Update meta theme-color for mobile browsers
        this.updateMetaThemeColor();
    }

    toggle() {
        this.isDark = !this.isDark;
        localStorage.setItem('darkMode', this.isDark);
        this.applyTheme();
        this.updateToggleButton();
        this.showNotification();
    }

    createToggleButton() {
        // Check if toggle already exists
        if (document.querySelector('.dark-mode-toggle')) {
            return;
        }

        const toggle = document.createElement('button');
        toggle.className = 'dark-mode-toggle';
        toggle.innerHTML = `
            <i class="fas fa-${this.isDark ? 'sun' : 'moon'}"></i>
            <span class="toggle-text">${this.isDark ? 'Light' : 'Dark'} Mode</span>
        `;
        
        toggle.addEventListener('click', () => this.toggle());
        
        // Add to topbar if it exists
        const topbar = document.querySelector('.topbar');
        if (topbar) {
            const nav = topbar.querySelector('nav');
            if (nav) {
                nav.appendChild(toggle);
            } else {
                topbar.appendChild(toggle);
            }
        } else {
            // Add to body if no topbar
            document.body.appendChild(toggle);
        }

        this.updateToggleButton();
    }

    updateToggleButton() {
        const toggle = document.querySelector('.dark-mode-toggle');
        if (toggle) {
            const icon = toggle.querySelector('i');
            const text = toggle.querySelector('.toggle-text');
            
            if (this.isDark) {
                icon.className = 'fas fa-sun';
                text.textContent = 'Light Mode';
            } else {
                icon.className = 'fas fa-moon';
                text.textContent = 'Dark Mode';
            }
        }
    }

    updateMetaThemeColor() {
        let metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (!metaThemeColor) {
            metaThemeColor = document.createElement('meta');
            metaThemeColor.name = 'theme-color';
            document.head.appendChild(metaThemeColor);
        }
        
        metaThemeColor.content = this.isDark ? '#1a1a1a' : '#667eea';
    }

    addSystemPreferenceListener() {
        // Listen for system theme changes
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addListener((e) => {
                // Only auto-switch if user hasn't manually set a preference
                if (!localStorage.getItem('darkMode')) {
                    this.isDark = e.matches;
                    this.applyTheme();
                    this.updateToggleButton();
                }
            });
        }
    }

    showNotification() {
        const message = `Switched to ${this.isDark ? 'dark' : 'light'} mode`;
        if (window.showNotification) {
            window.showNotification(message, 'success');
        } else {
            // Fallback notification
            console.log(message);
        }
    }
}

// Initialize dark mode when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.darkMode = new DarkMode();
});

// Add CSS for dark mode toggle button
const darkModeStyles = `
    .dark-mode-toggle {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .dark-mode-toggle:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }

    .dark-mode-toggle i {
        font-size: 1rem;
    }

    .toggle-text {
        font-size: 0.8rem;
    }

    /* Dark mode styles */
    .dark-mode {
        --primary-gradient: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
        --admin-gradient: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
        --success-color: #48bb78;
        --error-color: #f56565;
        --warning-color: #ed8936;
        --info-color: #4299e1;
        --light-bg: #1a202c;
        --dark-text: #f7fafc;
        --light-text: #a0aec0;
        --border-color: #4a5568;
        --shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        --shadow-hover: 0 30px 60px rgba(0, 0, 0, 0.4);
    }

    .dark-mode body {
        background: var(--light-bg);
        color: var(--dark-text);
    }

    .dark-mode .card,
    .dark-mode .stat-card,
    .dark-mode .login-container {
        background: #2d3748;
        border: 1px solid var(--border-color);
    }

    .dark-mode .form-group input,
    .dark-mode .form-group select,
    .dark-mode .form-group textarea {
        background: #4a5568;
        border-color: var(--border-color);
        color: var(--dark-text);
    }

    .dark-mode .form-group input:focus,
    .dark-mode .form-group select:focus,
    .dark-mode .form-group textarea:focus {
        border-color: #4299e1;
        background: #2d3748;
    }

    .dark-mode .data-table {
        background: #2d3748;
        color: var(--dark-text);
    }

    .dark-mode .data-table th {
        background: #4a5568;
        color: var(--dark-text);
    }

    .dark-mode .data-table tr:hover {
        background: rgba(66, 153, 225, 0.1);
    }

    .dark-mode .alert-error {
        background: rgba(245, 101, 101, 0.1);
        color: #f56565;
        border-left-color: #f56565;
    }

    .dark-mode .alert-success {
        background: rgba(72, 187, 120, 0.1);
        color: #48bb78;
        border-left-color: #48bb78;
    }

    .dark-mode .alert-info {
        background: rgba(66, 153, 225, 0.1);
        color: #4299e1;
        border-left-color: #4299e1;
    }

    .dark-mode .category-item {
        background: #4a5568;
        color: var(--dark-text);
    }

    .dark-mode .category-item:hover {
        background: #2d3748;
    }

    .dark-mode .notification {
        background: #2d3748;
        color: var(--dark-text);
        border: 1px solid var(--border-color);
    }

    .dark-mode .security-notice {
        background: rgba(237, 137, 54, 0.1);
        color: #ed8936;
        border-left-color: #ed8936;
    }

    .dark-mode .errors {
        background: rgba(245, 101, 101, 0.1);
        color: #f56565;
        border-left-color: #f56565;
    }

    /* Chart.js dark mode */
    .dark-mode .chart-container canvas {
        filter: brightness(0.8) contrast(1.2);
    }

    /* Responsive dark mode toggle */
    @media (max-width: 768px) {
        .dark-mode-toggle .toggle-text {
            display: none;
        }
        
        .dark-mode-toggle {
            padding: 0.5rem;
            min-width: 40px;
            justify-content: center;
        }
    }
`;

// Inject dark mode styles
const styleSheet = document.createElement('style');
styleSheet.textContent = darkModeStyles;
document.head.appendChild(styleSheet);
