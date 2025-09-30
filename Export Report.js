document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".accordion-export-btn").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.preventDefault();

      const region = btn.closest("details").querySelector('[role="region"]');
      if (!region) return;

      region.classList.add("print-target");

      region.querySelectorAll("canvas").forEach(canvas => {
        try {
          const img = document.createElement("img");
          img.src = canvas.toDataURL("image/png");
          img.style.maxWidth = "100%";
          img.style.height = "auto";
          img.classList.add("canvas-print-clone");
          canvas.style.display = "none";
          canvas.after(img);
        } catch (err) {
          console.warn("Canvas conversion failed", err);
        }
      });

      window.print();

      window.addEventListener("afterprint", function cleanup() {
        region.classList.remove("print-target");
        region.querySelectorAll(".canvas-print-clone").forEach(img => img.remove());
        region.querySelectorAll("canvas").forEach(canvas => {
          canvas.style.display = "";
        });
        window.removeEventListener("afterprint", cleanup);
      });
    });
  });
});
