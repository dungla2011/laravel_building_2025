/*!
 * Toast System JavaScript Library
 * Version: 1.0.0
 * Description: Reusable toast notification system with Bootstrap integration
 * Dependencies: Bootstrap 5.x
 * Author: LaravelOrion2025 Team
 * License: MIT
 */

class ToastSystem {
    constructor(options = {}) {
        this.options = {
            position: 'top-end', // top-start, top-center, top-end, bottom-start, bottom-center, bottom-end
            maxToasts: 5,
            defaultDelay: 5000,
            showProgress: false,
            animation: true,
            ...options
        };
        
        this.init();
    }
    
    init() {
        this.createContainer();
    }
    
    createContainer() {
        if (document.getElementById('toast-container')) {
            return;
        }
        
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = this.getContainerClasses();
        container.style.zIndex = '11000';
        
        document.body.appendChild(container);
    }
    
    getContainerClasses() {
        const position = this.options.position;
        let classes = 'position-fixed p-3';
        
        switch (position) {
            case 'top-start':
                classes += ' top-0 start-0';
                break;
            case 'top-center':
                classes += ' top-0 start-50 translate-middle-x';
                break;
            case 'top-end':
            default:
                classes += ' top-0 end-0';
                break;
            case 'bottom-start':
                classes += ' bottom-0 start-0';
                break;
            case 'bottom-center':
                classes += ' bottom-0 start-50 translate-middle-x';
                break;
            case 'bottom-end':
                classes += ' bottom-0 end-0';
                break;
        }
        
        return classes;
    }
    
    show(type, message, options = {}) {
        const config = {
            delay: this.options.defaultDelay,
            showProgress: this.options.showProgress,
            showCloseButton: true,
            title: null,
            ...options
        };
        
        const toastElement = this.createToastElement(type, message, config);
        this.addToContainer(toastElement);
        this.showToast(toastElement, config);
        
        return toastElement;
    }
    
    createToastElement(type, message, config) {
        const toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        const toastClass = this.getToastClass(type);
        const icon = this.getIcon(type);
        
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `toast ${toastClass} text-white mb-2`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        let headerHtml = '';
        if (config.title) {
            headerHtml = `
                <div class="toast-header">
                    <i class="fas fa-${icon} me-2"></i>
                    <strong class="me-auto">${config.title}</strong>
                    <small class="text-muted">now</small>
                    ${config.showCloseButton ? '<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>' : ''}
                </div>
            `;
        }
        
        const bodyHtml = `
            <div class="d-flex">
                <div class="toast-body">
                    ${!config.title ? `<i class="fas fa-${icon} me-2"></i>` : ''}
                    ${message}
                </div>
                ${!config.title && config.showCloseButton ? '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' : ''}
            </div>
        `;
        
        toast.innerHTML = headerHtml + bodyHtml;
        
        // Add progress bar if enabled
        if (config.showProgress && config.delay > 0) {
            const progressBar = document.createElement('div');
            progressBar.className = 'toast-progress';
            progressBar.style.animationDuration = `${config.delay}ms`;
            toast.appendChild(progressBar);
        }
        
        return toast;
    }
    
    getToastClass(type) {
        const classes = {
            'success': 'toast-success',
            'error': 'toast-error',
            'danger': 'toast-error',
            'warning': 'toast-warning',
            'info': 'toast-info',
            'primary': 'toast-info'
        };
        
        return classes[type] || 'toast-info';
    }
    
    getIcon(type) {
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-triangle', 
            'danger': 'exclamation-triangle',
            'warning': 'exclamation-circle',
            'info': 'info-circle',
            'primary': 'info-circle'
        };
        
        return icons[type] || 'info-circle';
    }
    
    addToContainer(toastElement) {
        const container = document.getElementById('toast-container');
        
        // Limit number of toasts
        while (container.children.length >= this.options.maxToasts) {
            const oldestToast = container.lastElementChild;
            if (oldestToast) {
                this.hideToast(oldestToast);
            }
        }
        
        // Add new toast at the beginning (top)
        container.insertAdjacentElement('afterbegin', toastElement);
    }
    
    showToast(toastElement, config) {
        const toast = new bootstrap.Toast(toastElement, {
            delay: config.delay,
            autohide: config.delay > 0
        });
        
        toast.show();
        
        // Event listeners
        toastElement.addEventListener('hidden.bs.toast', () => {
            this.removeToast(toastElement);
        });
        
        // Auto-hide with custom delay
        if (config.delay > 0) {
            setTimeout(() => {
                if (document.contains(toastElement)) {
                    toast.hide();
                }
            }, config.delay);
        }
    }
    
    hideToast(toastElement) {
        const toast = bootstrap.Toast.getInstance(toastElement);
        if (toast) {
            toast.hide();
        } else {
            this.removeToast(toastElement);
        }
    }
    
    removeToast(toastElement) {
        if (toastElement && toastElement.parentNode) {
            toastElement.remove();
        }
        
        // Clean up container if empty
        const container = document.getElementById('toast-container');
        if (container && container.children.length === 0) {
            container.remove();
        }
    }
    
    // Convenience methods
    success(message, options = {}) {
        return this.show('success', message, options);
    }
    
    error(message, options = {}) {
        return this.show('error', message, options);
    }
    
    warning(message, options = {}) {
        return this.show('warning', message, options);
    }
    
    info(message, options = {}) {
        return this.show('info', message, options);
    }
    
    // Clear all toasts
    clear() {
        const container = document.getElementById('toast-container');
        if (container) {
            Array.from(container.children).forEach(toast => {
                this.hideToast(toast);
            });
        }
    }
    
    // Update global options
    configure(newOptions) {
        this.options = { ...this.options, ...newOptions };
        
        // Recreate container if position changed
        const container = document.getElementById('toast-container');
        if (container) {
            container.className = this.getContainerClasses();
        }
    }
}

// Global instance
window.toastSystem = new ToastSystem();

// Legacy compatibility function
window.showToast = function(type, message, options = {}) {
    return window.toastSystem.show(type, message, options);
};

// jQuery plugin (if jQuery is available)
if (typeof jQuery !== 'undefined') {
    jQuery.fn.toast = function(type, message, options = {}) {
        return window.toastSystem.show(type, message, options);
    };
    
    // Add shortcuts to jQuery
    jQuery.toast = {
        success: (message, options) => window.toastSystem.success(message, options),
        error: (message, options) => window.toastSystem.error(message, options),
        warning: (message, options) => window.toastSystem.warning(message, options),
        info: (message, options) => window.toastSystem.info(message, options),
        clear: () => window.toastSystem.clear(),
        configure: (options) => window.toastSystem.configure(options)
    };
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ToastSystem;
}

if (typeof define === 'function' && define.amd) {
    define([], function() {
        return ToastSystem;
    });
}