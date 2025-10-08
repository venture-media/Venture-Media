document.addEventListener("DOMContentLoaded", function () {
  const header = document.getElementById("masthead");
  const logo = document.querySelector(".is-logo-image");
  const nav = document.getElementById("site-navigation");
  const toggle = document.querySelector(".menu-toggle");
  let startScrollY = null;

  // Fallback in case the PHP snippet didn't load
  const logos = window.VentureLogo || {
    black: logo.src, // keep current src as fallback
    white: logo.src
  };

  window.addEventListener("scroll", function () {
    const currentY = window.scrollY;

    // background + logo swap
    if (currentY > 0) {
      header.classList.add("scrolled");
      logo.src = logos.black;
    } else {
      header.classList.remove("scrolled");
      logo.src = logos.white;
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
