<?php
/**
 * The sidebar containing the main widget area
 *
 * @package ThemisDB
 */

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
    return;
}
?>

<aside id="secondary" class="sidebar widget-area" aria-label="Sidebar Widgets">
    <div class="sidebar-inner sidebar-floating-overlay">
        <?php dynamic_sidebar( 'sidebar-1' ); ?>
    </div>
</aside>
