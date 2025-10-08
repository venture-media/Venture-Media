document.addEventListener("DOMContentLoaded", function () {
  const header = document.getElementById("masthead");
  const logo = document.querySelector(".is-logo-image");
  const nav = document.getElementById("site-navigation");
  const toggle = document.querySelector(".menu-toggle");
  let startScrollY = null;

  // Compute absolute URL dynamically
  const baseUrl = window.location.origin + '/wp-staging'; // replace with your subdirectory if needed
  const logoBlack = baseUrl + '/wp-content/uploads/2025/09/venture-logo-black.svg';
  const logoWhite = baseUrl + '/wp-content/uploads/2025/09/venture-logo-white.svg';

  window.addEventListener("scroll", function () {
    const currentY = window.scrollY;

    // background + logo swap
    if (currentY > 0) {
      header.classList.add("scrolled");
      logo.src = logoBlack;
    } else {
      header.classList.remove("scrolled");
      logo.src = logoWhite;
    }

    // close menu only if moved far enough
    if (nav.classList.contains("toggled")) {
      if (startScrollY === null) startScrollY = currentY;
      if (Math.abs(currentY - startScrollY) > 300) {
        nav.classList.remove("toggled");
        toggle.setAttribute("aria-expanded", "false");
        startScrollY = null;
      }
    } else {
      startScrollY = null;
    }
  });
});
