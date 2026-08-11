/* Pro Gigs Audio Visual — Booking Form */
(function () {
'use strict';

/* Navbar + mobile menu (mirrors script.js) */
const navbar    = document.getElementById('navbar');
const hamburger = document.getElementById('hamburger');
const navLinks  = document.getElementById('nav-links');
const overlay   = document.getElementById('nav-overlay');

if (navbar) {
    window.addEventListener('scroll', () => {
        navbar.classList.toggle('scrolled', window.scrollY > 50);
    }, { passive: true });
}
function toggleMenu(open) {
    if (!navLinks || !hamburger) return;
    const isOpen = open !== undefined ? open : !navLinks.classList.contains('active');
    hamburger.classList.toggle('active', isOpen);
    navLinks.classList.toggle('active', isOpen);
    if (overlay) overlay.classList.toggle('active', isOpen);
    hamburger.setAttribute('aria-expanded', String(isOpen));
    document.body.style.overflow = (isOpen && window.innerWidth <= 768) ? 'hidden' : '';
}
hamburger?.addEventListener('click', e => { e.stopPropagation(); toggleMenu(); });
overlay?.addEventListener('click', () => toggleMenu(false));
document.querySelectorAll('.nav-links a').forEach(a => a.addEventListener('click', () => toggleMenu(false)));
document.addEventListener('click', e => {
    if (!navLinks || !navLinks.classList.contains('active')) return;
    if (!navLinks.contains(e.target) && !hamburger.contains(e.target)) toggleMenu(false);
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') toggleMenu(false); });
window.addEventListener('resize', () => { if (window.innerWidth > 768) toggleMenu(false); });

/* Minimum date = today */
document.addEventListener('DOMContentLoaded', () => {
    const d = document.getElementById('eventDate');
    if (d) d.setAttribute('min', new Date().toISOString().split('T')[0]);
});

/* Submit */
const form = document.getElementById('booking-form');
form?.addEventListener('submit', function (e) {
    e.preventDefault();

    const btn     = document.getElementById('submit-btn');
    const success = document.getElementById('success-message');

    const checked = document.querySelectorAll('input[name="services"]:checked');
    if (checked.length === 0) {
        alert('Please select at least one service required.');
        return;
    }

    btn.textContent = 'Sending Request…';
    btn.disabled = true;
    btn.style.opacity = '0.7';

    const data = {
        name:      document.getElementById('fullName').value.trim(),
        email:     document.getElementById('email').value.trim(),
        phone:     document.getElementById('phone').value.trim(),
        company:   document.getElementById('company').value.trim() || 'N/A',
        eventType: document.getElementById('eventType').value,
        eventDate: document.getElementById('eventDate').value,
        guests:    document.getElementById('guests').value || 'Not specified',
        venue:     document.getElementById('venue').value.trim() || 'Not specified',
        inquiries: document.getElementById('inquiries').value.trim() || 'None',
        services:  Array.from(checked).map(c => c.value).join(', ')
    };

    // Google Apps Script endpoint (Sheets + email notification)
    const scriptURL = 'https://script.google.com/macros/s/AKfycbzDRzqeNrwTcrCTTWchPiXE-tngL6wOYcXdgMWWRX4ow3inUQ6zhMd5nM0-50hu0vpFhA/exec';

    fetch(scriptURL, {
        method:  'POST',
        mode:    'no-cors',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(data)
    })
    .then(() => {
        form.style.display = 'none';
        success.style.display = 'block';
        const box = document.querySelector('.form-container');
        if (box) window.scrollTo({ top: box.offsetTop - 120, behavior: 'smooth' });
    })
    .catch(err => {
        console.error('Submission error:', err);
        btn.textContent = 'Submit Booking Request';
        btn.disabled = false;
        btn.style.opacity = '1';
        alert('Something went wrong. Please try again or email info@progigs.co.ke');
    });
});

})();