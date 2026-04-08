const scriptURL = 'https://script.google.com/macros/s/AKfycby4nCLo0kMLPc9pfj7xt_GJcVV-BgRip6k5ohihH_OdbYLNiRHmoka6uGq2iuwCM68X/exec';
const form = document.querySelector("#reservationForm");
const toast = document.querySelector("#notification-toast");

form.addEventListener("submit", e => {
  e.preventDefault(); // This stops the redirect! 
  
  // Start the "Branlly Magic" transmission
  fetch(scriptURL, { 
    method: 'POST', 
    body: new FormData(form)
  })
  .then(response => {
    // Show the styled notification pop-up 
    toast.classList.add("active");
    form.reset(); // Clear form for next entry [cite: 112]

    // Hide after 5 seconds
    setTimeout(() => {
      toast.classList.remove("active");
    }, 5000);
  })
  .catch(error => {
    console.error('Error!', error.message);
    alert("Submission failed. Please email branllyltd@gmail.com directly.");
  });
});