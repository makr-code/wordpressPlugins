<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<section class="themisdb-global-network-map" aria-label="<?php echo esc_attr($atts['title'] ?: __('ThemisDB global network', 'themisdb-global-network-map')); ?>" data-gnm-config="<?php echo esc_attr(wp_json_encode($config)); ?>" style="--gnm-height: <?php echo esc_attr($height); ?>px">
    <?php if ($atts['title'] !== '') : ?>
        <h2 class="themisdb-global-network-map__title"><?php echo esc_html($atts['title']); ?></h2>
    <?php endif; ?>
    <div class="themisdb-global-network-map__canvas-wrap">
        <canvas class="themisdb-global-network-map__canvas" tabindex="0" role="img" aria-label="<?php esc_attr_e('Interactive globe. Arrow keys rotate, plus and minus zoom. Instance locations are available in the map menu.', 'themisdb-global-network-map'); ?>"></canvas>
        <div class="themisdb-global-network-map__city-layer" aria-hidden="true"></div>
        <div class="themisdb-global-network-map__tooltip" hidden></div>
    </div>
    <details class="themisdb-global-network-map__menu">
        <summary aria-label="<?php esc_attr_e('Map menu', 'themisdb-global-network-map'); ?>"><span aria-hidden="true">&#9776;</span><span class="themisdb-global-network-map__menu-label"><?php esc_html_e('Map menu', 'themisdb-global-network-map'); ?></span></summary>
        <div class="themisdb-global-network-map__panel">
    <?php if ($atts['show_controls']) : ?>
        <div class="themisdb-global-network-map__controls" role="group" aria-label="<?php esc_attr_e('Globe controls', 'themisdb-global-network-map'); ?>">
            <div class="themisdb-global-network-map__button-group">
                <?php foreach (array('globe' => __('Globe', 'themisdb-global-network-map'), 'region' => __('Region', 'themisdb-global-network-map'), 'country' => __('Country', 'themisdb-global-network-map')) as $value => $label) : ?>
                    <button type="button" data-map-mode="<?php echo esc_attr($value); ?>" aria-pressed="<?php echo $value === 'globe' ? 'true' : 'false'; ?>"><?php echo esc_html($label); ?></button>
                <?php endforeach; ?>
            </div>
            <label><?php esc_html_e('Target', 'themisdb-global-network-map'); ?><select data-map-target>
                <option value="auto"><?php esc_html_e('Hotspot tour', 'themisdb-global-network-map'); ?></option>
                <?php foreach (array('north-america' => 'North America', 'united-states' => 'United States', 'europe' => 'Europe', 'france' => 'France', 'germany' => 'Germany', 'south-america' => 'South America', 'brazil' => 'Brazil', 'africa' => 'Africa', 'south-africa' => 'South Africa', 'middle-east' => 'Middle East', 'india' => 'India', 'asia-pacific' => 'Asia Pacific', 'japan' => 'Japan', 'oceania' => 'Oceania', 'australia' => 'Australia') as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html__($label, 'themisdb-global-network-map'); ?></option>
                <?php endforeach; ?>
            </select></label>
            <label><?php esc_html_e('Zoom', 'themisdb-global-network-map'); ?><input data-map-zoom type="range" min="1" max="4" step="0.05" value="1" /></label>
            <label><?php esc_html_e('Flight speed', 'themisdb-global-network-map'); ?><input data-map-speed type="range" min="0.5" max="2" step="0.1" value="1" /></label>
            <label><input data-map-animation-toggle type="checkbox" checked /><?php esc_html_e('Auto rotate / hotspot tour', 'themisdb-global-network-map'); ?></label>
            <label><input data-map-mouse-toggle type="checkbox" checked /><?php esc_html_e('Drag', 'themisdb-global-network-map'); ?></label>
            <label><input data-map-cities-toggle type="checkbox" checked /><?php esc_html_e('Major cities (geographic reference)', 'themisdb-global-network-map'); ?></label>
            <button type="button" data-map-reset><?php esc_html_e('Reset', 'themisdb-global-network-map'); ?></button>
        </div>
    <?php endif; ?>
        <details class="themisdb-global-network-map__locations">
            <summary><?php esc_html_e('Instance locations', 'themisdb-global-network-map'); ?> <span data-map-count></span></summary>
            <ul data-map-locations></ul>
        </details>
        </div>
    </details>
    <?php if ($atts['show_legend']) : ?>
        <div class="themisdb-global-network-map__legend" aria-label="<?php esc_attr_e('Instance load', 'themisdb-global-network-map'); ?>">
            <span><i class="gnm-dot gnm-dot--low"></i>0–39%</span><span><i class="gnm-dot gnm-dot--mid"></i>40–74%</span><span><i class="gnm-dot gnm-dot--high"></i>75–100%</span>
        </div>
    <?php endif; ?>
    <aside class="themisdb-global-network-map__focus-card" data-map-focus-card hidden aria-label="<?php esc_attr_e('Focused instance location', 'themisdb-global-network-map'); ?>">
        <span class="themisdb-global-network-map__focus-eyebrow" data-focus-source></span>
        <strong data-focus-title></strong>
        <span data-focus-metrics></span>
        <span data-focus-location></span>
    </aside>
    <div class="themisdb-global-network-map__status" role="status" aria-live="polite">
        <span data-map-renderer><?php esc_html_e('Starting renderer…', 'themisdb-global-network-map'); ?></span>
        <span data-map-source><?php esc_html_e('Loading homecalls…', 'themisdb-global-network-map'); ?></span>
        <span data-map-hotspot></span>
        <span data-map-message></span>
    </div>
    <noscript><?php esc_html_e('Enable JavaScript to display the interactive globe and load instance locations.', 'themisdb-global-network-map'); ?></noscript>
</section>
