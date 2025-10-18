<?php
/**
 * Elementor Widget: World Map Visitors
 * Location: /elementor-widgets/class-world-map-widget.php
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class GP_World_Map_Visitors_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'gp_world_map_visitors';
    }

    public function get_title() {
        return __('World Map Visitors', 'generatepress-child');
    }

    public function get_icon() {
        return 'eicon-map-pin';
    }

    public function get_categories() {
        return ['general'];
    }

    public function get_keywords() {
        return ['map', 'world', 'visitors', 'countries', 'svg'];
    }

    protected function register_controls() {

        $this->start_controls_section(
            'section_content',
            [
                'label' => __('World Map Data', 'generatepress-child'),
            ]
        );

        $this->add_control(
            'country_ids',
            [
                'label' => __('Country IDs', 'generatepress-child'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'placeholder' => "#NA\n#ZA\n#BW",
                'description' => __('Enter one country ID per line (must match the SVG <path> IDs, including the # prefix).', 'generatepress-child'),
            ]
        );

        $this->add_control(
            'country_values',
            [
                'label' => __('Text Values', 'generatepress-child'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'placeholder' => "650 visitors\n232 visitors\n181 visitors",
                'description' => __('Enter one value per line, corresponding to the Country IDs above.', 'generatepress-child'),
            ]
        );

        $this->add_control(
            'highlight_color',
            [
                'label' => __('Highlight Color', 'generatepress-child'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f4a239',
                'selectors' => [],
            ]
        );

        $this->add_control(
            'base_color',
            [
                'label' => __('Base Color', 'generatepress-child'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#D1D74185',
                'selectors' => [],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $widget_id = esc_attr($this->get_id());

        $svg_path = get_stylesheet_directory() . '/elementor-widgets/assets/World_Map_grouped.svg';

        if (!file_exists($svg_path)) {
            echo '<p style="color:red;">SVG file not found: ' . esc_html($svg_path) . '</p>';
            return;
        }

        // Read SVG contents fresh on every render
        $svg_content = file_get_contents($svg_path);

        // Sanitize SVG output a bit
        $svg_content = preg_replace('/<\?xml.*?\?>/i', '', $svg_content);
        $svg_content = preg_replace('/<!DOCTYPE.*?>/i', '', $svg_content);

        // Prepare data arrays
        $ids_raw = trim($settings['country_ids']);
        $values_raw = trim($settings['country_values']);

        $ids = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $ids_raw)));
        $values = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $values_raw)));

        $data = [];
        foreach ($ids as $i => $id) {
            $data[$id] = isset($values[$i]) ? $values[$i] : '';
        }

        // Encode data for JS
        $data_json = wp_json_encode($data);

        $highlight_color = $settings['highlight_color'] ?: '#f4a239';
        $base_color = $settings['base_color'] ?: '#D1D74185';

        ?>
        <div id="gp-world-map-<?php echo $widget_id; ?>" class="gp-world-map-widget" 
             data-map-id="<?php echo $widget_id; ?>"
             data-country-values='<?php echo esc_attr($data_json); ?>'
             data-highlight-color="<?php echo esc_attr($highlight_color); ?>"
             data-base-color="<?php echo esc_attr($base_color); ?>">

            <div class="gp-world-map-svg-container">
                <?php echo $svg_content; ?>
            </div>
        </div>
        <?php
    }
}

// Register widget
add_action('elementor/widgets/register', function($widgets_manager) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $widgets_manager->register(new \GP_World_Map_Visitors_Widget());
});
