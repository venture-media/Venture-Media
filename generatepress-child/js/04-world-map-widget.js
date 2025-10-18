/*
=====================
  04 World map widget
=====================
*/

(function ($) {
    "use strict";

    function initGPWorldMap($wrapper) {
        const svg = $wrapper.find("svg");
        if (!svg.length) return;

        const highlightColor = $wrapper.data("highlight-color") || "#f4a239";
        const baseColor = $wrapper.data("base-color") || "#D1D74185";
        const countryData = $wrapper.data("country-values") || {};

        // Reset all fills to base color
        svg.find("path").css("fill", baseColor);

        // Apply highlight and prepare title-based tooltips
        $.each(countryData, function (id) {
            const countryPath = svg.find(id);
            if (countryPath.length) {
                countryPath.css("fill", highlightColor);
            }
        });
        
        // Hover tooltip (uses <title> content)
        svg.find("path").on("mouseenter", function () {
            const $titleEl = $(this).find("title");
            const countryName = $titleEl.length ? $titleEl.text().trim() : $(this).attr("id") || "";
            if (!countryName) return;
        
            const $tooltip = $("<div class='gp-map-tooltip'></div>")
                .text(countryName)
                .appendTo("body");
        
            $(this).on("mousemove.gpMapTooltip", function (e) {
                $tooltip.css({
                    left: e.pageX + 10,
                    top: e.pageY + 10
                });
            });
        }).on("mouseleave", function () {
            $(".gp-map-tooltip").remove();
            $(this).off("mousemove.gpMapTooltip");
        });


    // Elementor front-end hook
    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/gp_world_map_visitors.default",
            function ($scope) {
                initGPWorldMap($scope);
            }
        );
    });

    // Frontend fallback (non-Elementor contexts)
    $(document).ready(function () {
        $(".gp-world-map-widget").each(function () {
            initGPWorldMap($(this));
        });
    });
})(jQuery);
