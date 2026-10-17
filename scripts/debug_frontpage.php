<?php
require 'C:/xampp/htdocs/wordpress/wp-load.php';
$theme = wp_get_theme();
echo "active_theme=" . $theme->get('Name') . PHP_EOL;
echo "stylesheet=" . $theme->get_stylesheet() . PHP_EOL;
echo "show_on_front=" . get_option('show_on_front') . PHP_EOL;
echo "page_on_front=" . get_option('page_on_front') . PHP_EOL;
echo "page_for_posts=" . get_option('page_for_posts') . PHP_EOL;
$front_id = (int) get_option('page_on_front');
if ($front_id > 0) {
    $p = get_post($front_id);
    echo "front_id=" . $front_id . PHP_EOL;
    echo "front_title=" . ($p ? $p->post_title : 'none') . PHP_EOL;
    echo "front_status=" . ($p ? $p->post_status : 'none') . PHP_EOL;
    if ($p) {
        echo "template_slug=" . get_page_template_slug($p->ID) . PHP_EOL;
        echo "content_preview=" . substr(wp_strip_all_tags($p->post_content), 0, 400) . PHP_EOL;
    }
}
$posts = get_posts([
    'post_type' => ['post', 'page'],
    'post_status' => 'publish',
    'tag' => 'hero',
    'posts_per_page' => 10,
    'orderby' => 'date',
    'order' => 'DESC',
]);
echo "hero_count=" . count($posts) . PHP_EOL;
foreach ($posts as $p) {
    echo "hero_post=" . $p->post_type . '|' . $p->ID . '|' . $p->post_title . PHP_EOL;
}
