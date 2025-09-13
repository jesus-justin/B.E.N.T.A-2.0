// BENTA - Animation and UX Enhancement Scripts

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all animations and interactions
    initAnimations();
    initFormEnhancements();
    initLoadingStates();
    initSuccessAnimations();
    initHoverEffects();
    initSmoothScrolling();
    initNotificationSystem();
});

// Initialize page animations
function initAnimations() {
    // Animate elements on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationDelay = '0s';
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);

    // Observe all cards and stat cards
    document.querySelectorAll('.card, .stat-card, .quick-action').forEach(el => {
        observer.observe(el);
    });

    // Stagger animation for stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
}

// Form enhancements
function initFormEnhancements() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        // Add floating labels effect
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            // Add focus/blur animations
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });
            
            // Add typing animation
            input.addEventListener('input', function() {
                this.style.transform = 'scale(1.02)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            });
        });

        // Form submission with loading state
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
                
                // Add loading animation
                setTimeout(() => {
                    if (!submitBtn.disabled) {
                        submitBtn.classList.remove('loading');
                        submitBtn.disabled = false;
                    }
                }, 3000);
            }
        });
    });
}

// Loading states and button animations
function initLoadingStates() {
    const buttons = document.querySelectorAll('.btn');
    
    buttons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Add ripple effect
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
}

// Success animations
function initSuccessAnimations() {
    // Check for success parameter in URL
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');
    
    if (success) {
        showSuccessNotification(getSuccessMessage(success));
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}

// Hover effects
function initHoverEffects() {
    // Card hover effects
    const cards = document.querySelectorAll('.card, .stat-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Table row hover effects
    const tableRows = document.querySelectorAll('.data-table tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.01)';
            this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
            this.style.boxShadow = 'none';
        });
    });
}

// Smooth scrolling
function initSmoothScrolling() {
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Notification system
function initNotificationSystem() {
    // Create notification container if it doesn't exist
    if (!document.querySelector('.notification-container')) {
        const container = document.createElement('div');
        container.className = 'notification-container';
        document.body.appendChild(container);
    }
}

// Show success notification
function showSuccessNotification(message) {
    const container = document.querySelector('.notification-container');
    const notification = document.createElement('div');
    notification.className = 'notification notification-success';
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">✓</span>
            <span class="notification-message">${message}</span>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Auto remove
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Get success message based on parameter
function getSuccessMessage(success) {
    const messages = {
        'transaction_added': 'Transaction added successfully!',
        'transaction_updated': 'Transaction updated successfully!',
        'transaction_deleted': 'Transaction deleted successfully!',
        'expense_added': 'Expense added successfully!',
        'expense_updated': 'Expense updated successfully!',
        'expense_deleted': 'Expense deleted successfully!',
        'user_registered': 'Registration successful! Welcome to BENTA!',
        'user_logged_in': 'Welcome back!'
    };
    
    return messages[success] || 'Operation completed successfully!';
}

// Utility functions
function animateValue(element, start, end, duration) {
    const startTimestamp = performance.now();
    const step = (timestamp) => {
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const current = Math.floor(progress * (end - start) + start);
        element.textContent = current.toLocaleString();
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

// Animate numbers in stat cards
function animateStatNumbers() {
    const statCards = document.querySelectorAll('.stat-card .amount');
    statCards.forEach(card => {
        const text = card.textContent;
        const number = parseFloat(text.replace(/[^\d.-]/g, ''));
        if (!isNaN(number)) {
            card.textContent = '0';
            setTimeout(() => {
                animateValue(card, 0, number, 2000);
            }, 500);
        }
    });
}

// Initialize number animations when dashboard loads
if (document.querySelector('.stats-grid')) {
    setTimeout(animateStatNumbers, 1000);
}

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: scale(0);
        animation: ripple-animation 0.6s linear;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    .notification-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
    }
    
    .notification {
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        margin-bottom: 10px;
        transform: translateX(400px);
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        min-width: 300px;
    }
    
    .notification.show {
        transform: translateX(0);
        opacity: 1;
    }
    
    .notification-success {
        border-left: 4px solid #27ae60;
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        padding: 16px;
    }
    
    .notification-icon {
        background: #27ae60;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-size: 14px;
        font-weight: bold;
    }
    
    .notification-message {
        color: #2c3e50;
        font-weight: 500;
    }
    
    .animate-in {
        animation: fadeInUp 0.6s ease-out forwards;
    }
    
    .focused {
        transform: translateY(-2px);
    }
    
    .focused label {
        color: #667eea;
        transform: scale(0.9);
    }
`;
document.head.appendChild(style);
