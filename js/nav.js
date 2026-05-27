document.addEventListener("DOMContentLoaded", function() {
  const toggle = document.getElementById('nav-toggle');
  const navLinks = document.querySelector('.nav-links');
  if (toggle && navLinks) {
    toggle.addEventListener('click', () => {
      navLinks.classList.toggle('nav-links--open');
      toggle.classList.toggle('nav-mobile-btn--open');
    });
  }
});
