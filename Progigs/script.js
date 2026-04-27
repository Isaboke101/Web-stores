/* Pro Gigs Audio Visual — Main Script */

// ── NAVBAR: Scroll shrink + Hamburger ──────────────────────
const navbar    = document.getElementById('navbar');
const hamburger = document.getElementById('hamburger');
const navLinks  = document.getElementById('nav-links');
const overlay   = document.getElementById('nav-overlay');

// Shrink navbar on scroll
window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 60);
}, { passive: true });

// Open / close mobile menu
function toggleMenu(open) {
    const isOpen = open !== undefined ? open : !navLinks.classList.contains('active');
    hamburger.classList.toggle('active', isOpen);
    navLinks.classList.toggle('active', isOpen);
    overlay.classList.toggle('active', isOpen);
    hamburger.setAttribute('aria-expanded', isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
}

hamburger.addEventListener('click', () => toggleMenu());
overlay.addEventListener('click', () => toggleMenu(false));

// Close on link click
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => toggleMenu(false));
});

// Close on Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') toggleMenu(false);
});


// ── ACTIVE NAV LINK on scroll ──────────────────────────────
const sections    = document.querySelectorAll('section[id], header[id]');
const navAnchors  = document.querySelectorAll('.nav-links a[href^="#"]');

const sectionObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            navAnchors.forEach(a => {
                a.style.color = a.getAttribute('href') === `#${entry.target.id}`
                    ? 'var(--cyan)'
                    : '';
            });
        }
    });
}, { threshold: 0.35 });

sections.forEach(s => sectionObserver.observe(s));


// ── SCROLL REVEAL ──────────────────────────────────────────
const revealObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            revealObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

document.querySelectorAll('.scroll-reveal').forEach(el => {
    revealObserver.observe(el);
});


// ── WHATSAPP FORM ──────────────────────────────────────────
const waForm = document.getElementById('whatsapp-form');
if (waForm) {
    waForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const name    = document.getElementById('clientName').value.trim();
        const type    = document.getElementById('eventType').value.trim();
        const message = document.getElementById('clientMessage').value.trim();

        if (!name || !type || !message) {
            alert('Please fill in all fields before sending.');
            return;
        }

        const phoneNumber = '254720440062';
        const text = `Hello Pro Gigs! 👋\n\nMy name is *${name}*.\nI'm enquiring about a *${type}* event.\n\n${message}\n\nLooking forward to hearing from you!`;
        const encodedText = encodeURIComponent(text);
        const waURL = `https://wa.me/${phoneNumber}?text=${encodedText}`;

        window.open(waURL, '_blank', 'noopener,noreferrer');
    });
}
