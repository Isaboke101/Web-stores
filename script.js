document.addEventListener('DOMContentLoaded', () => {
    const mobileMenu = document.getElementById('mobile-menu');
    const navLinks = document.querySelector('.nav-links');
    const navIcons = document.querySelector('.nav-icons');

    mobileMenu.addEventListener('click', () => {
        // Toggle the 'active' class to show/hide the menu
        navLinks.classList.toggle('active');
        navIcons.classList.toggle('active');
        
        // Change icon from hamburger to 'X' (optional)
        const icon = mobileMenu.querySelector('i');
        if(navLinks.classList.contains('active')) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-xmark');
        } else {
            icon.classList.remove('fa-xmark');
            icon.classList.add('fa-bars');
        }
    });
});