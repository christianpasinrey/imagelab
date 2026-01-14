import './bootstrap';
import Alpine from 'alpinejs';
import Cropper from 'cropperjs';

// Make Cropper available globally
window.Cropper = Cropper;

// Initialize Alpine
window.Alpine = Alpine;
Alpine.start();
