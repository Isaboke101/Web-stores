// Wait for the HTML to fully load before running the script
document.addEventListener("DOMContentLoaded", () => {

    // --- Date Picker Logic: Prevent past dates ---
    const dateInput = document.getElementById('event_date');
    if (dateInput) {
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1); // Add 1 day to make it tomorrow
        
        // Format to YYYY-MM-DD for the HTML input
        const yyyy = tomorrow.getFullYear();
        const mm = String(tomorrow.getMonth() + 1).padStart(2, '0');
        const dd = String(tomorrow.getDate()).padStart(2, '0');
        
        dateInput.min = `${yyyy}-${mm}-${dd}`;
    }

    // --- WhatsApp Contact Form Logic ---
    // Changed "whatsappContactForm" back to "contactForm" below:
    const waForm = document.getElementById("contactForm"); 
    
    if (waForm) {
        waForm.addEventListener("submit", (e) => {
            e.preventDefault(); 
            
            // Grab the values from the form
            const name = document.getElementById("waName").value.trim();
            const email = document.getElementById("waEmail").value.trim();
            const message = document.getElementById("waMessage").value.trim();
            
            // Format the message for WhatsApp 
            const whatsappDraft = `Hello Branlly Services!\n\nI am reaching out from your website's contact page.\n\n*Name:* ${name}\n*Email:* ${email}\n*Message:* ${message}`;
            
            // Encode the text
            const encodedText = encodeURIComponent(whatsappDraft);
            
            // Branlly WhatsApp Number
            const branllyNumber = "254720775160"; // Replace with your actual number in international format without '+' or dashes
            
            // Construct the final URL and open it
            const whatsappURL = `https://wa.me/${branllyNumber}?text=${encodedText}`;
            
            window.open(whatsappURL, "_blank");
            waForm.reset();
        });
    }
    
    // 1. RE-PASTE YOUR GOOGLE DEPLOYMENT URL HERE
    const scriptURL = 'https://script.google.com/macros/s/AKfycbxVixXjiLfs2RBzcAQGxgwCKA67C_n3Gjej83ien-yzkJZ9pkwdLCqRU0DSspSIhnNh/exec'; 
    
    const form = document.querySelector("#reservationForm");
    const toast = document.querySelector("#notification-toast");

    // Double-check if the elements actually exist on the page
    if (form && toast) {
        console.log("Branlly Systems Ready: Form and Toast found.");// --- Real-Time Input Validation ---
        const emailInput = document.getElementById('client_email');
        const phoneInput = document.getElementById('client_phone');
        const emailMsg = document.getElementById('email_msg');
        const phoneMsg = document.getElementById('phone_msg');

        // Regex Rules
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; // Standard email format
        const phoneRegex = /^(?:\+254|254|0)?(7\d{8}|1\d{8})$/; // Kenyan phone formats

        // The Checker Function
        function validateInput(inputEl, msgEl, regex, invalidText, validText) {
            let value = inputEl.value.trim();
            
            // If it's the phone, remove spaces so the regex can read it clearly
            if (inputEl.id === 'client_phone') {
                value = value.replace(/\s+/g, ''); 
            }

            if (value === "") {
                // Reset if empty
                msgEl.textContent = "";
                msgEl.className = "validation-msg";
                inputEl.className = "";
                return false;
            } else if (!regex.test(value)) {
                // Fails the test (Red)
                msgEl.textContent = invalidText;
                msgEl.className = "validation-msg invalid";
                inputEl.classList.remove('valid');
                inputEl.classList.add('invalid');
                return false;
            } else {
                // Passes the test (Green)
                msgEl.textContent = validText;
                msgEl.className = "validation-msg valid";
                inputEl.classList.remove('invalid');
                inputEl.classList.add('valid');
                return true;
            }
        }

        // Trigger checks as the user types
        if (emailInput && phoneInput) {
            emailInput.addEventListener('input', () => {
                validateInput(emailInput, emailMsg, emailRegex, "Please enter a valid email address.", "Valid email format.");
            });

            phoneInput.addEventListener('input', () => {
                validateInput(phoneInput, phoneMsg, phoneRegex, "Please enter a valid Kenyan mobile number.", "Valid number.");
            });
        }

        form.addEventListener("submit", e => {
            e.preventDefault(); // Stop the page reload

            // Strict Validation Gate
            const isEmailValid = validateInput(emailInput, emailMsg, emailRegex, "Required.", "Valid.");
            const isPhoneValid = validateInput(phoneInput, phoneMsg, phoneRegex, "Required.", "Valid.");

            if (!isEmailValid || !isPhoneValid) {
                alert("Please fix the errors in your contact details before submitting.");
                return; // This stops the code from sending bad data to Google Sheets
            }

            console.log("Transmitting brief...");
            
            const btn = form.querySelector('button[type="submit"]');
            const originalBtnText = btn.innerText;
            btn.innerText = "Transmitting...";

            // Send data to Google
            fetch(scriptURL, { 
                method: 'POST', 
                body: new FormData(form),
                mode: 'no-cors' // Bypasses strict security errors
            })
            .then(() => {
                console.log("Transmission successful. Triggering toast.");
                // Show the toast
                toast.classList.add("active");
                form.reset(); 
                btn.innerText = originalBtnText;

                // Hide the toast after 5 seconds
                setTimeout(() => {
                    toast.classList.remove("active");
                }, 5000);
            })
            .catch(error => {
                console.error('Transmission Error:', error);
                btn.innerText = originalBtnText;
                alert("Submission failed. Please contact us at 0720775160.");
            });
        });
    } else {
        console.error("Branlly Error: Cannot find #reservationForm or #notification-toast on this page.");
    }
});