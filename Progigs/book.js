/* Pro Gigs Audio Visual — Booking Form Script */

// Set minimum date to today
document.addEventListener('DOMContentLoaded', () => {
    const dateInput = document.getElementById('eventDate');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }
});

document.getElementById('booking-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const form       = document.getElementById('booking-form');
    const submitBtn  = document.getElementById('submit-btn');
    const successMsg = document.getElementById('success-message');

    // Basic validation: at least one service selected
    const checked = document.querySelectorAll('input[name="services"]:checked');
    if (checked.length === 0) {
        alert('Please select at least one service required.');
        return;
    }

    // Animate button to loading state
    submitBtn.textContent = 'Sending Request…';
    submitBtn.disabled    = true;
    submitBtn.style.opacity = '0.7';

    // Collect form data
    const services = Array.from(checked).map(cb => cb.value);
    const formData = {
        name:       document.getElementById('fullName').value.trim(),
        email:      document.getElementById('email').value.trim(),
        phone:      document.getElementById('phone').value.trim(),
        company:    document.getElementById('company').value.trim() || 'N/A',
        eventType:  document.getElementById('eventType').value,
        eventDate:  document.getElementById('eventDate').value,
        inquiries:  document.getElementById('inquiries').value.trim() || 'None',
        services:   services.join(', ')
    };

    // Google Apps Script endpoint (connected to Google Sheets + email)
    const scriptURL = 'https://script.google.com/macros/s/AKfycbzDRzqeNrwTcrCTTWchPiXE-tngL6wOYcXdgMWWRX4ow3inUQ6zhMd5nM0-50hu0vpFhA/exec';

    fetch(scriptURL, {
        method:  'POST',
        mode:    'no-cors',   // required to bypass CORS on Apps Script
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(formData)
    })
    .then(() => {
        // Show success, hide form
        form.style.display          = 'none';
        successMsg.style.display    = 'block';

        // Scroll into view smoothly
        const container = document.querySelector('.form-container');
        if (container) {
            window.scrollTo({ top: container.offsetTop - 120, behavior: 'smooth' });
        }
    })
    .catch(err => {
        console.error('Submission error:', err);
        submitBtn.textContent   = 'Submit Booking Request';
        submitBtn.disabled      = false;
        submitBtn.style.opacity = '1';
        alert('Something went wrong. Please try again or contact us directly at info@progigs.co.ke');
    });
});
