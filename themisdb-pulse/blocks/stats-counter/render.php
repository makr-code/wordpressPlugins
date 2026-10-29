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
$value_text = isset( $attributes['valueText'] ) ? (string) $attributes['valueText'] : '';

if ( '' !== $value_text ) {
    $display_value = $value_text;
} elseif ( floor( $target ) === $target ) {
    $display_value = $prefix . number_format_i18n( (int) $target ) . $suffix;
} else {
    $display_value = $prefix . number_format_i18n( $target, 1 ) . $suffix;
}

?>
<div class="tv3-stats-item">
    <div class="tv3-stats-value"><?php echo esc_html( $display_value ); ?></div>
    <div class="tv3-stats-label"><?php echo esc_html( $label ); ?></div>
</div>
