// Restrict event date selection to today and future dates only
document.addEventListener("DOMContentLoaded", () => {
    const dateInput = document.getElementById('eventDate');
    if(dateInput) {
        // Gets today's date formatted as YYYY-MM-DD
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }
});

document.getElementById('booking-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Set the minimum selectable date to today
    const dateInput = document.getElementById('eventDate');
    const today = new Date().toISOString().split('T')[0];
    dateInput.setAttribute('min', today);

    // Grab UI elements
    const form = document.getElementById('booking-form');
    const submitBtn = document.getElementById('submit-btn');
    const successMessage = document.getElementById('success-message');

    // Change button state to show it's working
    submitBtn.innerText = "Sending Request...";
    submitBtn.disabled = true;
    submitBtn.style.opacity = "0.7";

    // Gather form data
    const formData = {
        name: document.getElementById('fullName').value,
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value,
        company: document.getElementById('company').value || "N/A",
        eventType: document.getElementById('eventType').value,
        eventDate: document.getElementById('eventDate').value,
        inquiries: document.getElementById('inquiries').value || "None",
        services: []
    };

    // Gather checked services
    document.querySelectorAll('input[name="services"]:checked').forEach(checkbox => {
        formData.services.push(checkbox.value);
    });
    
    // Format services into a comma-separated string
    const servicesString = formData.services.join(', ') || "Not specified";

    // Paste your Google Apps Script Web App URL here
    const scriptURL = 'https://script.google.com/macros/s/AKfycbzDRzqeNrwTcrCTTWchPiXE-tngL6wOYcXdgMWWRX4ow3inUQ6zhMd5nM0-50hu0vpFhA/exec'; 
    
    // Send data to Google Sheets & Email
    fetch(scriptURL, {
        method: 'POST',
        mode: 'no-cors', // Bypasses cross-origin issues
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({...formData, services: servicesString})
    })
    .then(() => {
        // Hide the form and show the success message
        form.style.display = 'none';
        successMessage.style.display = 'block';
        
        // Scroll smoothly to the top of the container so they see the message
        window.scrollTo({
            top: document.querySelector('.form-container').offsetTop - 100,
            behavior: 'smooth'
        });
    })
    .catch(error => {
        console.error('Error!', error.message);
        // Reset button if there's an error so they can try again
        submitBtn.innerText = "Submit Booking Request";
        submitBtn.disabled = false;
        submitBtn.style.opacity = "1";
        alert("Something went wrong. Please try again or contact us directly via email.");
    });
});