const userBtn = document.querySelector('#user-btn');
userBtn.addEventListener('click', function(){
	const userBox = document.querySelector('.profile-detail');
	userBox.classList.toggle('active');
})

const toggle = document.querySelector('.menu');
toggle.addEventListener('click', function(){
	const sidebar = document.querySelector('.sidebar');
	sidebar.classList.toggle('active');
})

document.getElementById("myForm").addEventListener("submit", function(event) {
  event.preventDefault(); // Prevent default form submission behavior
  // Your form submission logic goes here
});
