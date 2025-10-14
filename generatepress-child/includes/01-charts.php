<?php
/**
 * -----------------------------
 * 01 Charts
 * -----------------------------
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function my_register_report_chart_widget( $widgets_manager ) {
    require_once get_stylesheet_directory() . '/elementor-widgets/class-report-chart-widget.php';
    $widgets_manager->register( new \Report_Chart_Widget() );
}
add_action( 'elementor/widgets/register', 'my_register_report_chart_widget' );
