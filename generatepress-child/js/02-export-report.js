/*
=====================
  02 EXPORT REPORT SCRIPT
=====================

Purpose:
- Handles the export/print functionality for client reports.

Dependencies:
- html2canvas
- jspdf
*/


(function () {
  const HTML2CANVAS_CDN = "https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js";
  const JSPDF_CDN = "https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js";

  function loadScript(src) {
    return new Promise((resolve, reject) => {
      if (document.querySelector('script[src="' + src + '"]')) return resolve();
      const s = document.createElement("script");
      s.src = src;
      s.async = true;
      s.onload = resolve;
      s.onerror = () => reject(new Error("Failed to load " + src));
      document.head.appendChild(s);
    });
  }

  async function ensureLibs() {
    if (!window.html2canvas) await loadScript(HTML2CANVAS_CDN);
    if (!((window.jspdf && window.jspdf.jsPDF) || window.jsPDF)) await loadScript(JSPDF_CDN);
    const jsPDF = (window.jspdf && window.jspdf.jsPDF) ? window.jspdf.jsPDF : window.jsPDF;
    return { html2canvas: window.html2canvas, jsPDF };
  }

  async function handleExportClick(e) {
    e.preventDefault();
    const btn = e.currentTarget;
    if (btn.dataset.exporting === "1") return;
    btn.dataset.exporting = "1";

    const originalText = btn.innerText;
    btn.style.opacity = "0.6";
    btn.innerText = "Preparing PDF…";

    try {
      const libs = await ensureLibs();
      const html2canvas = libs.html2canvas;
      const { jsPDF } = libs;

      const details = btn.closest("details");
      const region = details?.querySelector('[role="region"]')
        || btn.closest(".print-target")
        || document.querySelector(".print-target")
        || details
        || btn.closest("section")
        || btn.parentElement;

      if (!region) throw new Error("No region found to capture.");

      //  Temporarily hide the export button before capture
      btn.style.visibility = "hidden";

      console.log("Export: capturing canvas...");
      const canvas = await html2canvas(region, {
        scale: 2,
        backgroundColor: "#ffffff",
        useCORS: true,
        foreignObjectRendering: false,
        removeContainer: true,
        logging: false
      });

      //  Restore visibility right after capture
      btn.style.visibility = "visible";

      const imgData = canvas.toDataURL("image/png");
      const pdf = new jsPDF({ orientation: "portrait", unit: "mm", format: "a4" });
      const pdfWidth = pdf.internal.pageSize.getWidth();
      const pdfHeight = pdf.internal.pageSize.getHeight();

      const aspect = canvas.width / canvas.height;
      let imgWidth = pdfWidth;
      let imgHeight = imgWidth / aspect;
      if (imgHeight > pdfHeight) {
        imgHeight = pdfHeight;
        imgWidth = imgHeight * aspect;
      }

      const x = (pdfWidth - imgWidth) / 2;
      const y = (pdfHeight - imgHeight) / 2;

      pdf.addImage(imgData, "PNG", x, y, imgWidth, imgHeight);
      pdf.save("report.pdf");

    } catch (err) {
      console.error("Export failed:", err);
      btn.innerText = "Export failed";
      setTimeout(() => (btn.innerText = originalText), 2000);
    } finally {
      //  Restore UI regardless of success or error
      btn.style.opacity = "";
      btn.innerText = originalText;
      btn.dataset.exporting = "0";
      btn.style.visibility = "visible";
    }
  }

  function attach() {
    document.querySelectorAll(".accordion-export-btn").forEach(btn => {
      if (!btn.dataset.exportHandlerAttached) {
        btn.addEventListener("click", handleExportClick);
        btn.dataset.exportHandlerAttached = "1";
      }
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", attach);
  } else attach();
})();
