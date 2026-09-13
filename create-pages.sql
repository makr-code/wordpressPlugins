-- Create Landing Pages for Hero Local Spotlight Sections
-- Run in WordPress database

-- Blog
INSERT INTO wp_posts (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
SELECT 1, NOW(), NOW(), 'Blog - Aktuelle Artikel und News zu ThemisDB', 'Blog', 'Die letzten Artikel und News', 'publish', 'closed', 'closed', 'blog', '', '', NOW(), NOW(), '', 0, 'http://localhost/wordpress/blog/', 0, 'page', '', 0
WHERE NOT EXISTS (SELECT ID FROM wp_posts WHERE post_name = 'blog' AND post_type = 'page');

-- Dokumentation
INSERT INTO wp_posts (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
SELECT 1, NOW(), NOW(), 'Dokumentation - Alles was Sie ueber ThemisDB wissen muessen', 'Dokumentation', 'Umfassende Dokumentation fuer ThemisDB', 'publish', 'closed', 'closed', 'documentation', '', '', NOW(), NOW(), '', 0, 'http://localhost/wordpress/documentation/', 0, 'page', '', 0
WHERE NOT EXISTS (SELECT ID FROM wp_posts WHERE post_name = 'documentation' AND post_type = 'page');

-- Features
INSERT INTO wp_posts (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
SELECT 1, NOW(), NOW(), 'Features - Entdecken Sie die Funktionen von ThemisDB', 'Features', 'Features und Capabilities von ThemisDB', 'publish', 'closed', 'closed', 'features', '', '', NOW(), NOW(), '', 0, 'http://localhost/wordpress/features/', 0, 'page', '', 0
WHERE NOT EXISTS (SELECT ID FROM wp_posts WHERE post_name = 'features' AND post_type = 'page');

-- Downloads
INSERT INTO wp_posts (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
SELECT 1, NOW(), NOW(), 'Downloads - Laden Sie ThemisDB herunter', 'Downloads', 'Download-Seite fuer ThemisDB v3', 'publish', 'closed', 'closed', 'downloads', '', '', NOW(), NOW(), '', 0, 'http://localhost/wordpress/downloads/', 0, 'page', '', 0
WHERE NOT EXISTS (SELECT ID FROM wp_posts WHERE post_name = 'downloads' AND post_type = 'page');

-- Newsletter
INSERT INTO wp_posts (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
SELECT 1, NOW(), NOW(), 'Newsletter - Erhalten Sie Updates zu ThemisDB', 'Newsletter', 'Newsletter-Anmeldung fuer ThemisDB Updates', 'publish', 'closed', 'closed', 'newsletter', '', '', NOW(), NOW(), '', 0, 'http://localhost/wordpress/newsletter/', 0, 'page', '', 0
WHERE NOT EXISTS (SELECT ID FROM wp_posts WHERE post_name = 'newsletter' AND post_type = 'page');

-- Docker
INSERT INTO wp_posts (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
SELECT 1, NOW(), NOW(), 'Docker - Offizielles Docker Image fuer ThemisDB', 'Docker', 'Docker-Deployment fuer ThemisDB', 'publish', 'closed', 'closed', 'docker', '', '', NOW(), NOW(), '', 0, 'http://localhost/wordpress/docker/', 0, 'page', '', 0
WHERE NOT EXISTS (SELECT ID FROM wp_posts WHERE post_name = 'docker' AND post_type = 'page');

-- Verify
SELECT ID, post_title, post_name FROM wp_posts WHERE post_type = 'page' AND post_status = 'publish' ORDER BY post_title;
