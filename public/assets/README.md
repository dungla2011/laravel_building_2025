# Toast System Library

A reusable toast notification system with Bootstrap 5 integration.

## Files Structure
```
public/assets/
├── css/
│   ├── toast-system.css      # Full CSS with comments
│   └── toast-system.min.css  # Minified CSS
├── js/
│   └── toast-system.js       # JavaScript library
└── README.md                 # This file
```

## Installation

1. Include the CSS file in your layout:
```html
<link rel="stylesheet" href="{{ asset('assets/css/toast-system.min.css') }}">
```

2. Include the JavaScript file after Bootstrap:
```html
<script src="{{ asset('assets/js/toast-system.js') }}"></script>
```

## Basic Usage

### Simple Usage (Legacy Compatible)
```javascript
// Basic toast
showToast('success', 'Operation completed successfully!');
showToast('error', 'Something went wrong!');
showToast('warning', 'Please check your input.');
showToast('info', 'New updates available.');
```

### Advanced Usage with ToastSystem Class
```javascript
// Create custom instance
const customToast = new ToastSystem({
    position: 'bottom-end',
    maxToasts: 3,
    defaultDelay: 3000,
    showProgress: true
});

// Show toasts
customToast.success('Success message');
customToast.error('Error message');
customToast.warning('Warning message');
customToast.info('Info message');
```

### jQuery Support (if jQuery is loaded)
```javascript
// jQuery shortcuts
$.toast.success('Success message');
$.toast.error('Error message');
$.toast.warning('Warning message');
$.toast.info('Info message');

// Clear all toasts
$.toast.clear();

// Configure globally
$.toast.configure({
    position: 'top-center',
    defaultDelay: 4000
});
```

## Configuration Options

### ToastSystem Constructor Options
```javascript
const toast = new ToastSystem({
    position: 'top-end',        // Position: top-start, top-center, top-end, bottom-start, bottom-center, bottom-end
    maxToasts: 5,               // Maximum number of toasts to show
    defaultDelay: 5000,         // Default auto-hide delay (ms), 0 = no auto-hide
    showProgress: false,        // Show progress bar
    animation: true             // Enable animations
});
```

### Individual Toast Options
```javascript
toast.show('success', 'Message', {
    delay: 3000,                // Custom delay for this toast
    showProgress: true,         // Show progress bar
    showCloseButton: true,      // Show close button
    title: 'Success'            // Optional title
});
```

## Toast Types

| Type | CSS Class | Icon | Color |
|------|-----------|------|-------|
| `success` | `.toast-success` | `check-circle` | Green |
| `error` / `danger` | `.toast-error` | `exclamation-triangle` | Red |
| `warning` | `.toast-warning` | `exclamation-circle` | Yellow |
| `info` / `primary` | `.toast-info` | `info-circle` | Blue |

## Methods

### Global Instance Methods
```javascript
// Show methods
toastSystem.success(message, options);
toastSystem.error(message, options);
toastSystem.warning(message, options);
toastSystem.info(message, options);
toastSystem.show(type, message, options);

// Utility methods
toastSystem.clear();                    // Clear all toasts
toastSystem.configure(newOptions);      // Update global options
```

## CSS Customization

### Custom Colors
```css
#toast-container .toast.toast-custom {
    background-color: #6f42c1 !important;
    border-color: #5a2d91;
}
```

### Custom Animations
```css
@keyframes customSlideIn {
    from { transform: translateY(-100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

#toast-container .toast.custom-animation {
    animation: customSlideIn 0.5s ease-out;
}
```

## Examples

### Permission System Integration
```javascript
// Success permission update
toastSystem.success('Permission updated successfully!', {
    title: 'Success',
    showProgress: true,
    delay: 3000
});

// Error handling
toastSystem.error('Failed to update permission: Access denied', {
    title: 'Error',
    delay: 0  // Don't auto-hide errors
});
```

### Bulk Operations
```javascript
// Show progress toast
const progressToast = toastSystem.info('Processing bulk update...', {
    delay: 0,
    showCloseButton: false
});

// Update to success
setTimeout(() => {
    toastSystem.hideToast(progressToast);
    toastSystem.success('Bulk update completed successfully!');
}, 3000);
```

### Custom Configuration for Different Pages
```javascript
// Admin pages - top-end position
toastSystem.configure({
    position: 'top-end',
    maxToasts: 5,
    showProgress: true
});

// User dashboard - bottom-center position
toastSystem.configure({
    position: 'bottom-center',
    maxToasts: 3,
    showProgress: false
});
```

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+
- Bootstrap 5.x required

## Features

- ✅ **Responsive Design** - Works on all screen sizes
- ✅ **Accessibility** - ARIA labels and keyboard navigation
- ✅ **Animations** - Smooth slide in/out animations
- ✅ **Customizable** - Colors, positions, delays, icons
- ✅ **Progress Bars** - Optional visual progress indicators
- ✅ **Auto-cleanup** - Memory management and DOM cleanup
- ✅ **jQuery Compatible** - Works with or without jQuery
- ✅ **Module Support** - CommonJS, AMD, and global usage
- ✅ **Dark Mode** - Automatic dark mode support
- ✅ **Stack Management** - Limits number of simultaneous toasts

## License

MIT License - Feel free to use in your projects!