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

    let highlightColor = $wrapper.data("highlight-color") || "#f4a239";
    let baseColor = $wrapper.data("base-color") || "#D1D741";
    const countryData = $wrapper.data("country-values") || {};

    // Function: add opacity if 6-digit hex
    function withOpacity(hex) {
        if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
            return hex + "85"; // add opacity (~52%)
        }
        return hex; // leave #xxx or invalid as-is
    }

    const highlightBase = withOpacity(highlightColor);
    const baseBase = withOpacity(baseColor);

    // Set base fills first
    svg.find("path").css("fill", baseBase);

    // Apply highlight fills and hover behaviour
    $.each(countryData, function (id) {
        const $country = svg.find(id);
        if ($country.length) {
            $country.css("fill", highlightBase);

            // Hover -> full color; leave -> faded
            $country.on("mouseenter", function () {
                $(this).css("fill", highlightColor);
            }).on("mouseleave", function () {
                $(this).css("fill", highlightBase);
            });
        }
    });

    // Tooltip (show <title> only)
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
}


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
