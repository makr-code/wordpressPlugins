<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="themisdb-global-network-map">
    <div class="themisdb-global-network-map__canvas-wrap">
        <svg
            id="themisdb-global-network-map-svg"
            class="themisdb-global-network-map__svg"
            viewBox="0 0 1200 <?php echo esc_attr((int) $height); ?>"
            preserveAspectRatio="xMidYMid meet"
            aria-label="ThemisDB global network map"
            role="img"
        ></svg>
        <canvas
            id="themisdb-global-network-map-canvas"
            class="themisdb-global-network-map__canvas"
            width="1200"
            height="<?php echo esc_attr((int) $height); ?>"
            aria-label="ThemisDB network heatmap overlay"
        ></canvas>

        <div class="themisdb-global-network-map__controls" aria-label="Map controls">
            <div class="themisdb-global-network-map__button-group">
                <button type="button" class="themisdb-global-network-map__mode is-active" data-map-mode="globe" aria-pressed="true">Globe</button>
                <button type="button" class="themisdb-global-network-map__mode" data-map-mode="region" aria-pressed="false">Region</button>
                <button type="button" class="themisdb-global-network-map__mode" data-map-mode="country" aria-pressed="false">Country</button>
            </div>

            <div class="themisdb-global-network-map__split-button" aria-label="Focus target">
                <span class="themisdb-global-network-map__split-button-label">Target</span>
                <label class="themisdb-global-network-map__target" aria-label="Focus target selector">
                    <select id="themisdb-global-network-map-target" class="themisdb-global-network-map__target-select">
                        <option value="auto">Auto</option>
                        <option value="north-america">North America</option>
                        <option value="united-states">United States</option>
                        <option value="france">France</option>
                        <option value="germany">Germany</option>
                        <option value="europe">Europe</option>
                        <option value="south-america">South America</option>
                        <option value="brazil">Brazil</option>
                        <option value="africa">Africa</option>
                        <option value="south-africa">South Africa</option>
                        <option value="middle-east">Middle East</option>
                        <option value="india">India</option>
                        <option value="asia-pacific">Asia Pacific</option>
                        <option value="japan">Japan</option>
                        <option value="oceania">Oceania</option>
                        <option value="australia">Australia</option>
                    </select>
                </label>
            </div>

            <label class="themisdb-global-network-map__control" aria-label="Map zoom">
                <span>Zoom</span>
                <input id="themisdb-global-network-map-zoom" type="range" min="0.7" max="22" step="0.05" value="1.8" />
            </label>

            <label class="themisdb-global-network-map__control" aria-label="Rotation speed">
                <span>Spin</span>
                <input id="themisdb-global-network-map-rotation-speed" type="range" min="0.1" max="1.5" step="0.05" value="0.35" />
            </label>

            <button type="button" class="themisdb-global-network-map__button" data-map-reset aria-label="Reset view">
                Reset
            </button>
        </div>

        <div id="themisdb-global-network-map-debug" class="themisdb-global-network-map__debug" aria-live="polite">
            <span>Nodes: <strong data-debug="nodes">0</strong></span>
            <span>Edges: <strong data-debug="edges">0</strong></span>
            <span>FPS: <strong data-debug="fps">0</strong></span>
        </div>

        <div id="themisdb-global-network-map-tooltip" class="themisdb-global-network-map__tooltip" aria-live="polite"></div>
    </div>

    <?php if (!empty($atts['show_legend'])) : ?>
        <div class="themisdb-global-network-map__legend" aria-label="Network heatmap legend">
            <span><i class="dot dot--low"></i>Low</span>
            <span><i class="dot dot--mid"></i>Active</span>
            <span><i class="dot dot--high"></i>High</span>
        </div>
    <?php endif; ?>
</div>
