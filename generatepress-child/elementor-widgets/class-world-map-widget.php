<?php
/**
 * Elementor Widget – World Map Interactive
 * 
 * Allows user to map visitor data by country.
 * Loads SVG fresh each render, applies base/highlight colors,
 * and shows tooltip with country name only.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class GP_World_Map_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'gp_world_map_widget';
    }

    public function get_title() {
        return __( 'World Map (Interactive)', 'generatepress-child' );
    }

    public function get_icon() {
        return 'eicon-globe';
    }

    public function get_categories() {
        return [ 'general' ];
    }

    protected function register_controls() {
        // Country IDs (e.g. #NA, #ZA, #BW)
        $this->start_controls_section(
            'content_section',
            [ 'label' => __( 'Map Data', 'generatepress-child' ) ]
        );

        $this->add_control(
            'country_ids',
            [
                'label' => __( 'Country IDs (one per line, e.g. #NA)', 'generatepress-child' ),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => "#NA\n#ZA\n#BW",
                'rows' => 10,
            ]
        );

        $this->add_control(
            'country_values',
            [
                'label' => __( 'Values (one per line, e.g. 650 visitors)', 'generatepress-child' ),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => "650 visitors\n232 visitors\n181 visitors",
                'rows' => 10,
            ]
        );

        $this->add_control(
            'base_color',
            [
                'label' => __( 'Base Color (#xxxxxx)', 'generatepress-child' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '#D1D741',
                'description' => __( 'For countries not included in the list', 'generatepress-child' ),
            ]
        );

        $this->add_control(
            'highlight_color',
            [
                'label' => __( 'Highlight Color (#xxxxxx)', 'generatepress-child' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '#f4a239',
                'description' => __( 'For countries in the list', 'generatepress-child' ),
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $widget_id = 'gp-world-map-' . $this->get_id();
        $svg_path  = get_stylesheet_directory() . '/elementor-widgets/assets/World_Map_grouped.svg';

        if ( ! file_exists( $svg_path ) ) {
            echo '<p style="color:red;">SVG file not found at ' . esc_html( $svg_path ) . '</p>';
            return;
        }

        $svg_content = file_get_contents( $svg_path );

        $country_ids_raw   = trim( $settings['country_ids'] );
        $country_values_raw = trim( $settings['country_values'] );

        $country_ids   = array_map( 'trim', explode( "\n", $country_ids_raw ) );
        $country_values = array_map( 'trim', explode( "\n", $country_values_raw ) );

        $country_data = [];
        foreach ( $country_ids as $index => $id ) {
            if ( ! empty( $id ) && isset( $country_values[$index] ) ) {
                $country_data[$id] = $country_values[$index];
            }
        }

        $base_color      = trim( $settings['base_color'] );
        $highlight_color = trim( $settings['highlight_color'] );

        // Add transparency if color is 6-digit hex
        $apply_opacity = function( $color ) {
            $color = trim( $color );
            if ( preg_match( '/^#[0-9A-Fa-f]{6}$/', $color ) ) {
                return $color . '85';
            }
            return $color;
        };

        $base_color_fill      = $apply_opacity( $base_color );
        $highlight_color_fill = $apply_opacity( $highlight_color );

        ?>
        <div id="<?php echo esc_attr( $widget_id ); ?>" class="gp-world-map-wrapper" style="position: relative;">
            <?php echo $svg_content; ?>
            <div class="gp-world-map-tooltip" style="display:none;"></div>
        </div>

        <script>
        (function($){
            const mapWrapper = $('#<?php echo esc_js( $widget_id ); ?>');
            const tooltip = mapWrapper.find('.gp-world-map-tooltip');
            const countryData = <?php echo wp_json_encode( $country_data ); ?>;
            const baseColor = '<?php echo esc_js( $base_color ); ?>';
            const highlightColor = '<?php echo esc_js( $highlight_color ); ?>';
            const baseColorFill = '<?php echo esc_js( $base_color_fill ); ?>';
            const highlightColorFill = '<?php echo esc_js( $highlight_color_fill ); ?>';

            // Apply base color to all paths
            mapWrapper.find('.worldcountry').css('fill', baseColorFill);

            // Apply highlight to selected ones
            Object.keys(countryData).forEach(id => {
                const path = mapWrapper.find(id);
                if (path.length) {
                    path.css('fill', highlightColorFill);
                }
            });

            // Tooltip behavior
            mapWrapper.find('.worldcountry').on('mousemove', function(e){
                const id = '#' + $(this).attr('id');
                const countryName = $(this).data('name') || $(this).attr('data-name') || $(this).attr('title') || '';
                const displayName = countryName || id.replace('country_map_', '');
                
                tooltip.text(displayName);
                tooltip.css({
                    left: e.offsetX + 'px',
                    top: e.offsetY + 'px',
                }).addClass('visible').show();
            }).on('mouseleave', function(){
                tooltip.removeClass('visible').hide();
            });

            // Hover fill
            mapWrapper.find('.worldcountry').hover(function(){
                const id = '#' + $(this).attr('id');
                const isHighlighted = Object.keys(countryData).includes(id);
                $(this).css('fill', isHighlighted ? highlightColor : baseColor);
            }, function(){
                const id = '#' + $(this).attr('id');
                const isHighlighted = Object.keys(countryData).includes(id);
                $(this).css('fill', isHighlighted ? highlightColorFill : baseColorFill);
            });
        })(jQuery);
        </script>
        <?php
    }
}
