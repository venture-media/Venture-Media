/*
=====================
  00 EXPORT REPORT SCRIPT
=====================

Purpose:
- Handles the export/print functionality for client reports.
- Ensures only the `.print-target` content is included in the print view.
- Compatible with `00-export-report.css` print rules.

Dependencies:
- jQuery (loaded by WordPress)
*/




/* =====================
  00.1. DOM Ready
===================== */
document.addEventListener("DOMContentLoaded", function () {
  const exportButtons = document.querySelectorAll(".accordion-export-btn");

  if (!exportButtons.length) return;

  
/* =====================
  00.2. Export Function
===================== */
  exportButtons.forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.preventDefault();

      // Identify target selector (optional: data attribute fallback)
      const targetSelector = btn.getAttribute("data-print-target") || ".print-target";
      const printTarget = document.querySelector(targetSelector);

      if (!printTarget) {
        console.warn("Export Report: No print target found for", targetSelector);
        return;
      }

      // Clone target content for isolated print
      const cloned = printTarget.cloneNode(true);
      const printWindow = window.open("", "_blank", "width=1000,height=800");

      // Basic HTML structure
      printWindow.document.write(`
        <html>
          <head>
            <title>Export Report</title>
            <style>
              html, body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-family: Effra, sans-serif;
                margin: 0;
                padding: 0;
              }
            </style>
          </head>
          <body>
            ${cloned.outerHTML}
          </body>
        </html>
      `);

      printWindow.document.close();

      // Wait for styles to render, then print
      printWindow.onload = function () {
        printWindow.focus();
        printWindow.print();

        // Close automatically after printing
        printWindow.onafterprint = function () {
          printWindow.close();
        };
      };
    });
  });
});
