// --- Mobile Hamburger Menu Logic ---
const hamburger = document.getElementById('hamburger');
const navLinks = document.getElementById('nav-links');
const navItems = document.querySelectorAll('.nav-links a');

// Toggle menu when clicking the hamburger icon
hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    navLinks.classList.toggle('active');
});

// Close the menu automatically when a navigation link is clicked
navItems.forEach(item => {
    item.addEventListener('click', () => {
        hamburger.classList.remove('active');
        navLinks.classList.remove('active');
    });
});


// --- WhatsApp Form Logic ---
document.getElementById('whatsapp-form').addEventListener('submit', function(event) {
    event.preventDefault(); 

    const name = document.getElementById('clientName').value;
    const eventType = document.getElementById('eventType').value;
    const message = document.getElementById('clientMessage').value;

    const phoneNumber = "254720440062"; 

    const text = `Hello Pro Gigs, my name is ${name}. I am inquiring about a ${eventType} event. ${message}`;
    const encodedText = encodeURIComponent(text);

    const whatsappUrl = `https://wa.me/${phoneNumber}?text=${encodedText}`;
    window.open(whatsappUrl, '_blank');
});