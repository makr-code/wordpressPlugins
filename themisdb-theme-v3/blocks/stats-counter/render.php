<?php
/**
 * Server render for themisdb/stats-counter block.
 *
 * @package ThemisDB_V3
 */

$target     = isset( $attributes['target'] ) ? (float) $attributes['target'] : 0.0;
$label      = isset( $attributes['label'] ) ? (string) $attributes['label'] : '';
$prefix     = isset( $attributes['prefix'] ) ? (string) $attributes['prefix'] : '';
$suffix     = isset( $attributes['suffix'] ) ? (string) $attributes['suffix'] : '';
$animate    = ! isset( $attributes['animate'] ) || (bool) $attributes['animate'];
$value_text = isset( $attributes['valueText'] ) ? (string) $attributes['valueText'] : '';

if ( '' !== $value_text ) {
    $display_value = $value_text;
} elseif ( floor( $target ) === $target ) {
    $display_value = $prefix . number_format_i18n( (int) $target ) . $suffix;
} else {
    $display_value = $prefix . number_format_i18n( $target, 1 ) . $suffix;
}

$counter_class = $animate && '' === $value_text ? 'themis-v3-counter tv3-stats-value' : 'tv3-stats-value';
?>
<div class="tv3-stats-item">
    <div class="<?php echo esc_attr( $counter_class ); ?>"
        <?php if ( $animate && '' === $value_text ) : ?>
            data-target="<?php echo esc_attr( $target ); ?>"
            data-suffix="<?php echo esc_attr( $suffix ); ?>"
            data-prefix="<?php echo esc_attr( $prefix ); ?>"
        <?php endif; ?>><?php echo esc_html( $display_value ); ?></div>
    <div class="tv3-stats-label"><?php echo esc_html( $label ); ?></div>
</div>
