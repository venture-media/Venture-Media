<?php
// outputs JS with dynamic logo URLs
<script>
  window.VentureLogo = {
    black: "<?php echo esc_url( home_url('/wp-content/uploads/2025/09/venture-logo-black.svg') ); ?>",
    white: "<?php echo esc_url( home_url('/wp-content/uploads/2025/09/venture-logo-white.svg') ); ?>"
  };
</script>
