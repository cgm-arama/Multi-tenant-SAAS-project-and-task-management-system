// FILE: /public/assets/js/app.js
// SplashProjects - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredInputs = form.querySelectorAll('[required]');
            let isValid = true;

            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = 'red';
                } else {
                    input.style.borderColor = '';
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
    });

    // Check for notifications periodically
    if (typeof BASE_URL !== 'undefined') {
        setInterval(updateNotificationCount, 60000); // Every minute
    }
});

// Update notification count
function updateNotificationCount() {
    fetch(BASE_URL + '/notification/getUnreadCount')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.notification-link .badge');
            if (data.count > 0) {
                if (badge) {
                    badge.textContent = data.count;
                } else {
                    const link = document.querySelector('.notification-link');
                    if (link) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'badge';
                        newBadge.textContent = data.count;
                        link.appendChild(newBadge);
                    }
                }
            } else if (badge) {
                badge.remove();
            }
        })
        .catch(error => console.error('Error fetching notifications:', error));
}

// Modal functions
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal on outside click
window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
});

// Helper function for AJAX requests
function apiRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        }
    };

    if (data) {
        options.body = JSON.stringify(data);
    }

    return fetch(url, options)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        });
}
