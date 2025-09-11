
class NotificationSystem {
    constructor() {
        this.createNotificationContainer();
        this.initAutoClose();
    }

    createNotificationContainer() {
        if (document.getElementById('notification-container')) return;

        const container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        `;
        document.body.appendChild(container);
    }


    show(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        const alertClass = this.getAlertClass(type);
        const icon = this.getIcon(type);

        notification.className = `alert ${alertClass} alert-dismissible fade show`;
        notification.style.cssText = `
            margin-bottom: 10px;
            animation: slideInRight 0.3s ease-out;
        `;

        notification.innerHTML = `
            <i class="bi ${icon} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <div class="progress" style="height: 2px; margin-top: 8px;">
                <div class="progress-bar" style="width: 100%; transition: width ${duration}ms linear;"></div>
            </div>
        `;

        document.getElementById('notification-container').appendChild(notification);


        setTimeout(() => {
            const progressBar = notification.querySelector('.progress-bar');
            if (progressBar) {
                progressBar.style.width = '0%';
            }
        }, 100);


        if (duration > 0) {
            setTimeout(() => {
                this.dismiss(notification);
            }, duration);
        }

        return notification;
    }


    getAlertClass(type) {
        const classes = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        };
        return classes[type] || 'alert-info';
    }


    getIcon(type) {
        const icons = {
            'success': 'bi-check-circle-fill',
            'error': 'bi-exclamation-triangle-fill',
            'warning': 'bi-exclamation-triangle',
            'info': 'bi-info-circle-fill'
        };
        return icons[type] || 'bi-info-circle-fill';
    }


    dismiss(notification) {
        if (notification && notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }


    initAutoClose() {

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.processExistingAlerts());
        } else {
            this.processExistingAlerts();
        }
    }


    processExistingAlerts() {
        const alerts = document.querySelectorAll('.alert:not(.alert-dismissible)');
        alerts.forEach(alert => {

            alert.classList.add('alert-dismissible');
            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'btn-close';
            closeBtn.setAttribute('data-bs-dismiss', 'alert');
            alert.appendChild(closeBtn);


            const progressContainer = document.createElement('div');
            progressContainer.className = 'progress';
            progressContainer.style.cssText = 'height: 2px; margin-top: 8px;';

            const progressBar = document.createElement('div');
            progressBar.className = 'progress-bar';
            progressBar.style.cssText = 'width: 100%; transition: width 5000ms linear;';

            progressContainer.appendChild(progressBar);
            alert.appendChild(progressContainer);


            setTimeout(() => {
                progressBar.style.width = '0%';
            }, 100);


            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.animation = 'fadeOut 0.3s ease-out';
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 300);
                }
            }, 5000);
        });
    }


    success(message, duration = 5000) {
        return this.show(message, 'success', duration);
    }


    error(message, duration = 8000) {
        return this.show(message, 'error', duration);
    }


    warning(message, duration = 6000) {
        return this.show(message, 'warning', duration);
    }


    info(message, duration = 5000) {
        return this.show(message, 'info', duration);
    }
}


const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
        }
        to {
            opacity: 0;
        }
    }

    #notification-container .alert {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border: none;
    }
`;
document.head.appendChild(style);


window.notify = new NotificationSystem();