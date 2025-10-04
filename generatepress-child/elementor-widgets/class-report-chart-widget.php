<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Report_Chart_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'report_chart';
    }

    public function get_title() {
        return __( 'Report Chart', 'your-text-domain' );
    }

    public function get_icon() {
        return 'eicon-chart-bar';
    }

    public function get_categories() {
        return [ 'general' ];
    }

    protected function register_controls() {

        $this->start_controls_section(
            'section_content',
            [ 'label' => __( 'Chart Settings', 'your-text-domain' ) ]
        );

        $this->add_control(
            'chart_title',
            [
                'label' => __( 'Chart Title', 'your-text-domain' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'My Chart', 'your-text-domain' ),
            ]
        );

        $this->add_control(
            'chart_type',
            [
                'label' => __( 'Chart Type', 'your-text-domain' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'bar',
                'options' => [
                    'line' => __( 'Line', 'your-text-domain' ),
                    'bar' => __( 'Column / Bar', 'your-text-domain' ),
                    'pie' => __( 'Pie', 'your-text-domain' ),
                    'doughnut' => __( 'Doughnut', 'your-text-domain' ),
                ],
            ]
        );

        $this->add_control(
            'color_mode',
            [
                'label' => __( 'Color Mode', 'your-text-domain' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'single',
                'options' => [
                    'single' => __( 'Single', 'your-text-domain' ),
                    'dual'   => __( 'Dual', 'your-text-domain' ),
                ],
            ]
        );

        $this->add_control(
            'labels',
            [
                'label' => __( 'Labels (one per line)', 'your-text-domain' ),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => "Namibia\nGermany\nNetherlands\nUK\nSouth Africa",
            ]
        );

        $this->add_control(
            'values',
            [
                'label' => __( 'Values (one per line)', 'your-text-domain' ),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => "1530\n650\n636\n556\n358",
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $id = 'chart-' . $this->get_id();

        // Process labels & values
        $labels = array_map('trim', explode("\n", $settings['labels']));
        $values = array_map('trim', explode("\n", $settings['values']));

        // Trim arrays to the same length
        $count = min(count($labels), count($values));
        $labels = array_slice($labels, 0, $count);
        $values = array_slice($values, 0, $count);

        // Generate colors
        $colors = [];
        if ($settings['color_mode'] === 'single') {
            foreach ($labels as $i => $label) {
                $colors[] = '#f4a239';
            }
        } else {
            foreach ($labels as $i => $label) {
                $colors[] = $i % 2 === 0 ? '#f4a239' : '#d1d741';
            }
        }
        ?>

        <div class="report-chart-widget">
            <h3><?php echo esc_html( $settings['chart_title'] ); ?></h3>
            <canvas id="<?php echo esc_attr( $id ); ?>"></canvas>
        </div>

        <script>
(function($){
    function initReportChart_<?php echo esc_attr(str_replace('-', '_', $id)); ?>() {
        if (typeof Chart === "undefined") {
            return setTimeout(initReportChart_<?php echo esc_attr(str_replace('-', '_', $id)); ?>, 50);
        }

        var canvasId = '<?php echo esc_js($id); ?>';
        var ctx = document.getElementById(canvasId).getContext('2d');

        // Registry to track existing charts
        window.reportCharts = window.reportCharts || {};

        // Destroy old chart if it exists
        if (window.reportCharts[canvasId]) {
            window.reportCharts[canvasId].destroy();
        }

        // Create new chart and store it
        window.reportCharts[canvasId] = new Chart(ctx, {
            type: '<?php echo esc_js($settings['chart_type']); ?>',
            data: {
                labels: [<?php echo '"' . implode('","', $labels) . '"'; ?>],
                datasets: [{
                    label: '<?php echo esc_js($settings['chart_title']); ?>',
                    data: [<?php echo implode(',', $values); ?>],
                    backgroundColor: [<?php echo '"' . implode('","', $colors) . '"'; ?>],
                    borderColor: '#ffffff',
                    borderWidth: 1,
                    fill: <?php echo $settings['chart_type'] === 'line' ? 'false' : 'true'; ?>
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: <?php echo in_array($settings['chart_type'], ['pie','doughnut']) ? 'true' : 'false'; ?>
                    }
                }
            }
        });
    }

    // Run on frontend + Elementor editor
    initReportChart_<?php echo esc_attr(str_replace('-', '_', $id)); ?>();
    $(window).on('elementor/frontend/init', function(){
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/report_chart.default',
            initReportChart_<?php echo esc_attr(str_replace('-', '_', $id)); ?>
        );
    });
})(jQuery);
</script>


        <?php
    }
}
