document.addEventListener("DOMContentLoaded", function () {
  const nav = document.getElementById("site-navigation");
  const overlay = document.getElementById("overlay");

  if (!nav || !overlay) return;

  function updateOverlay() {
    if (nav.classList.contains("toggled")) {
      overlay.style.display = "block";
    } else {
      overlay.style.display = "none";
    }
  }

  // Watch for class changes on nav
  const observer = new MutationObserver(updateOverlay);
  observer.observe(nav, { attributes: true, attributeFilter: ["class"] });

  // Initial state
  updateOverlay();

  // clicking the overlay closes the menu
  overlay.addEventListener("click", function () {
    nav.classList.remove("toggled");
    const toggle = document.querySelector(".menu-toggle");
    if (toggle) toggle.setAttribute("aria-expanded", "false");
    updateOverlay();
  });
});
