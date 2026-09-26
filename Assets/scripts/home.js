 // Elements
 const menuIcon = document.querySelector(".menu-icon");
 const hamburgerMenu = document.querySelector(".hamburger-menu");
 const menuOverlay = document.querySelector(".menu-overlay");
 const closeMenu = document.querySelector(".close-menu");

 // Show menu
 menuIcon.addEventListener("click", () => {
   hamburgerMenu.style.display = "block";
   menuOverlay.style.display = "block";
 });

 // Hide menu
 menuOverlay.addEventListener("click", () => {
   hamburgerMenu.style.display = "none";
   menuOverlay.style.display = "none";
 });

 closeMenu.addEventListener("click", () => {
   hamburgerMenu.style.display = "none";
   menuOverlay.style.display = "none";
 });

 // Change active link in bottom navigation
 const navLinks = document.querySelectorAll(".bottom-nav .nav-link");
 navLinks.forEach((link) => {
   link.addEventListener("click", (e) => {
     e.preventDefault();
     navLinks.forEach((nav) => nav.classList.remove("active")); // Remove all active classes
     link.classList.add("active"); // Add active to clicked
   });
 });