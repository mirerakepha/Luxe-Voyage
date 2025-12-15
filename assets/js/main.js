function openRegisterChoice() {
    closeModals();
    document.getElementById("registerChoiceModal").style.display = "block";
}

function openRegister(role) {
    closeModals();
    document.getElementById("registerRole").value = role;
    document.getElementById("registerModal").style.display = "block";
}

function openLogin() {
    closeModals();
    document.getElementById("loginModal").style.display = "block";
}

function closeModals() {
    document.querySelectorAll(".modal").forEach(m => m.style.display = "none");
}


// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Form validation for registration
    const registerForm = document.querySelector('form[action="register.php"]');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = this.querySelector('input[name="password"]');
            const username = this.querySelector('input[name="username"]');
            const email = this.querySelector('input[name="email"]');
            const terms = this.querySelector('#terms');
            
            let isValid = true;
            let errorMessage = '';
            
            // Username validation
            if (username.value.length < 3) {
                isValid = false;
                errorMessage += 'Username must be at least 3 characters.\n';
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value)) {
                isValid = false;
                errorMessage += 'Please enter a valid email address.\n';
            }
            
            // Password validation
            if (password.value.length < 8) {
                isValid = false;
                errorMessage += 'Password must be at least 8 characters.\n';
            }
            
            if (!/(?=.*[a-zA-Z])(?=.*\d)/.test(password.value)) {
                isValid = false;
                errorMessage += 'Password must contain both letters and numbers.\n';
            }
            
            // Terms agreement
            if (terms && !terms.checked) {
                isValid = false;
                errorMessage += 'You must agree to the terms and conditions.\n';
            }
            
            if (!isValid) {
                e.preventDefault();
                alert(errorMessage);
            }
        });
    }

    // Form validation for login
    const loginForm = document.querySelector('form[action="login.php"]');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const identity = this.querySelector('input[name="identity"]');
            const password = this.querySelector('input[name="password"]');
            
            if (!identity.value.trim() || !password.value.trim()) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    }

    // Password visibility toggle (if you add eye icon)
    const passwordFields = document.querySelectorAll('input[type="password"]');
    passwordFields.forEach(field => {
        const wrapper = document.createElement('div');
        wrapper.className = 'password-wrapper';
        field.parentNode.insertBefore(wrapper, field);
        wrapper.appendChild(field);
        
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'password-toggle';
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
        wrapper.appendChild(toggleBtn);
        
        toggleBtn.addEventListener('click', function() {
            const type = field.type === 'password' ? 'text' : 'password';
            field.type = type;
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    });

    // Add CSS for password toggle
    const style = document.createElement('style');
    style.textContent = `
        .password-wrapper {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            padding: 5px;
        }
        .password-toggle:hover {
            color: var(--primary);
        }
    `;
    document.head.appendChild(style);

    // Add animation to feature cards on scroll
    const observerOptions = {
        threshold: 0.2,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);

    // Observe feature cards and account type cards
    document.querySelectorAll('.feature-card, .type-card').forEach(card => {
        observer.observe(card);
    });

    // Add CSS for animations
    const animationStyle = document.createElement('style');
    animationStyle.textContent = `
        .feature-card, .type-card {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        
        .feature-card.animate-in, .type-card.animate-in {
            opacity: 1;
            transform: translateY(0);
        }
        
        .feature-card:nth-child(1) { transition-delay: 0.1s; }
        .feature-card:nth-child(2) { transition-delay: 0.2s; }
        .feature-card:nth-child(3) { transition-delay: 0.3s; }
    `;
    document.head.appendChild(animationStyle);
});

// Notification system
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
        <button class="notification-close">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    // Add CSS for notifications
    if (!document.querySelector('#notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: 10px;
                color: white;
                display: flex;
                align-items: center;
                gap: 10px;
                z-index: 3000;
                animation: slideIn 0.3s ease;
                max-width: 400px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            }
            
            .notification-success {
                background: linear-gradient(135deg, var(--success), #00B894);
            }
            
            .notification-error {
                background: linear-gradient(135deg, var(--danger), #E74C3C);
            }
            
            .notification-close {
                background: none;
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
                margin-left: auto;
            }
            
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
    
    // Close button
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.remove();
    });
}

// Add slideOut animation
const slideOutStyle = document.createElement('style');
slideOutStyle.textContent = `
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(slideOutStyle);