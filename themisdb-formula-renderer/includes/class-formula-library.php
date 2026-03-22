<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-formula-library.php                          ║
  Version:         0.0.2                                              ║
  Last Modified:   2026-03-09 04:08:18                                ║
  Author:          unknown                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     275                                            ║
    • Open Issues:     TODOs: 0, Stubs: 0                             ║
╠═════════════════════════════════════════════════════════════════════╣
  Revision History:                                                   ║
    • 2a1fb0423  2026-03-03  Merge branch 'develop' into copilot/audit-src-module-docu... ║
    • 9d3ecaa0e  2026-02-28  Add ThemisDB Wiki Integration plugin with documentation i... ║
╠═════════════════════════════════════════════════════════════════════╣
  Status: ✅ Production Ready                                          ║
╚═════════════════════════════════════════════════════════════════════╝
 */


/**
 * Formula Library
 * 
 * Common mathematical formulas
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Formula_Library {
    
    /**
     * Get common formulas
     */
    public static function get_formulas() {
        return array(
            'algebra' => array(
                'name' => 'Algebra',
                'formulas' => array(
                    array(
                        'name' => 'Quadratic Formula',
                        'latex' => 'x = \frac{-b \pm \sqrt{b^2 - 4ac}}{2a}',
                        'description' => 'Solution to ax² + bx + c = 0'
                    ),
                    array(
                        'name' => 'Binomial Theorem',
                        'latex' => '(x+y)^n = \sum_{k=0}^{n} \binom{n}{k} x^{n-k} y^k',
                        'description' => 'Expansion of (x+y)ⁿ'
                    ),
                    array(
                        'name' => 'Logarithm Properties',
                        'latex' => '\log_b(xy) = \log_b(x) + \log_b(y)',
                        'description' => 'Product rule for logarithms'
                    )
                )
            ),
            'calculus' => array(
                'name' => 'Calculus',
                'formulas' => array(
                    array(
                        'name' => 'Derivative Power Rule',
                        'latex' => '\frac{d}{dx}(x^n) = nx^{n-1}',
                        'description' => 'Power rule for derivatives'
                    ),
                    array(
                        'name' => 'Integration by Parts',
                        'latex' => '\int u \, dv = uv - \int v \, du',
                        'description' => 'Integration by parts formula'
                    ),
                    array(
                        'name' => 'Fundamental Theorem',
                        'latex' => '\int_a^b f(x) \, dx = F(b) - F(a)',
                        'description' => 'Fundamental theorem of calculus'
                    )
                )
            ),
            'statistics' => array(
                'name' => 'Statistics',
                'formulas' => array(
                    array(
                        'name' => 'Mean',
                        'latex' => '\bar{x} = \frac{1}{n}\sum_{i=1}^{n} x_i',
                        'description' => 'Arithmetic mean'
                    ),
                    array(
                        'name' => 'Standard Deviation',
                        'latex' => '\sigma = \sqrt{\frac{1}{n}\sum_{i=1}^{n} (x_i - \mu)^2}',
                        'description' => 'Population standard deviation'
                    ),
                    array(
                        'name' => 'Normal Distribution',
                        'latex' => 'f(x) = \frac{1}{\sigma\sqrt{2\pi}} e^{-\frac{1}{2}\left(\frac{x-\mu}{\sigma}\right)^2}',
                        'description' => 'Probability density function'
                    )
                )
            ),
            'physics' => array(
                'name' => 'Physics',
                'formulas' => array(
                    array(
                        'name' => 'Einstein Mass-Energy',
                        'latex' => 'E = mc^2',
                        'description' => 'Mass-energy equivalence'
                    ),
                    array(
                        'name' => 'Newton\'s Second Law',
                        'latex' => 'F = ma',
                        'description' => 'Force equals mass times acceleration'
                    ),
                    array(
                        'name' => 'Kinetic Energy',
                        'latex' => 'KE = \frac{1}{2}mv^2',
                        'description' => 'Kinetic energy formula'
                    )
                )
            ),
            'geometry' => array(
                'name' => 'Geometry',
                'formulas' => array(
                    array(
                        'name' => 'Pythagorean Theorem',
                        'latex' => 'a^2 + b^2 = c^2',
                        'description' => 'Right triangle relationship'
                    ),
                    array(
                        'name' => 'Circle Area',
                        'latex' => 'A = \pi r^2',
                        'description' => 'Area of a circle'
                    ),
                    array(
                        'name' => 'Sphere Volume',
                        'latex' => 'V = \frac{4}{3}\pi r^3',
                        'description' => 'Volume of a sphere'
                    )
                )
            )
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $formulas = self::get_formulas();

        $_tfl_page = 'themisdb-formula-library';
        $_tfl_tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'library';
        if ( ! in_array( $_tfl_tab, array( 'library', 'shortcodes' ), true ) ) {
            $_tfl_tab = 'library';
        }
        $_tfl_url = function( $tab ) use ( $_tfl_page ) {
            return esc_url( admin_url( 'options-general.php?page=' . $_tfl_page . '&tab=' . $tab ) );
        };
        ?>
        <div class="wrap themisdb-formula-library">
            <h1 class="wp-heading-inline">
                <?php _e( 'Formula Library', 'themisdb-formula-renderer' ); ?>
                <a href="<?php echo $_tfl_url( 'shortcodes' ); ?>" class="page-title-action"><?php _e( 'Shortcode-Verwendung', 'themisdb-formula-renderer' ); ?></a>
            </h1>
            <hr class="wp-header-end">

            <nav class="nav-tab-wrapper wp-clearfix">
                <a href="<?php echo $_tfl_url( 'library' ); ?>"
                   class="nav-tab <?php echo $_tfl_tab === 'library' ? 'nav-tab-active' : ''; ?>">
                    <?php _e( 'Formel-Bibliothek', 'themisdb-formula-renderer' ); ?>
                </a>
                <a href="<?php echo $_tfl_url( 'shortcodes' ); ?>"
                   class="nav-tab <?php echo $_tfl_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">
                    <?php _e( 'Shortcode-Verwendung', 'themisdb-formula-renderer' ); ?>
                </a>
            </nav>

            <div class="themisdb-tab-content">

                <?php if ( $_tfl_tab === 'library' ): ?>
                <div class="themisdb-admin-modules">
                    <div class="card">
                        <h2><?php _e( 'Schnellaktionen', 'themisdb-formula-renderer' ); ?></h2>
                        <p><?php _e( 'Wechseln Sie direkt zur Shortcode-Referenz oder nutzen Sie die Bibliothek zum Kopieren vorhandener Formeln.', 'themisdb-formula-renderer' ); ?></p>
                        <p>
                            <a href="<?php echo $_tfl_url( 'shortcodes' ); ?>" class="button button-secondary"><?php _e( 'Shortcode-Verwendung', 'themisdb-formula-renderer' ); ?></a>
                        </p>
                    </div>
                    <div class="card">
                        <h2><?php _e( 'Bibliotheks-Überblick', 'themisdb-formula-renderer' ); ?></h2>
                        <table class="widefat striped">
                            <tbody>
                                <tr><th><?php _e( 'Kategorien', 'themisdb-formula-renderer' ); ?></th><td><?php echo esc_html( count( $formulas ) ); ?></td></tr>
                                <tr><th><?php _e( 'Kopiermodus', 'themisdb-formula-renderer' ); ?></th><td><?php _e( 'Shortcode und LaTeX', 'themisdb-formula-renderer' ); ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p><?php _e( 'Click any formula to copy its shortcode.', 'themisdb-formula-renderer' ); ?></p>

                <?php foreach ($formulas as $category_key => $category): ?>
                <div class="formula-category">
                    <h2><?php echo esc_html($category['name']); ?></h2>
                    <div class="formula-grid">
                        <?php foreach ($category['formulas'] as $formula): ?>
                        <div class="formula-card">
                            <h3><?php echo esc_html($formula['name']); ?></h3>
                            <p class="formula-description"><?php echo esc_html($formula['description']); ?></p>
                            <div class="formula-preview">
                                <?php echo do_shortcode('[themisdb_formula]' . $formula['latex'] . '[/themisdb_formula]'); ?>
                            </div>
                            <div class="formula-actions">
                                <button class="button button-primary copy-shortcode"
                                        data-shortcode='[themisdb_formula]<?php echo esc_attr($formula['latex']); ?>[/themisdb_formula]'>
                                    📋 Copy Shortcode
                                </button>
                                <button class="button copy-latex"
                                        data-latex="<?php echo esc_attr($formula['latex']); ?>">
                                    Copy LaTeX
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php elseif ( $_tfl_tab === 'shortcodes' ): ?>
                <h2><?php _e( 'Shortcode-Verwendung', 'themisdb-formula-renderer' ); ?></h2>
                <p><?php _e( 'Verwenden Sie den folgenden Shortcode, um LaTeX-Formeln auf beliebigen Seiten oder Beiträgen darzustellen.', 'themisdb-formula-renderer' ); ?></p>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e( 'Shortcode', 'themisdb-formula-renderer' ); ?></th>
                            <th><?php _e( 'Beschreibung', 'themisdb-formula-renderer' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>[themisdb_formula]E = mc^2[/themisdb_formula]</code></td>
                            <td><?php _e( 'Formel inline rendern (MathJax/KaTeX). Der LaTeX-Ausdruck wird zwischen den Tags platziert.', 'themisdb-formula-renderer' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>[themisdb_formula display="block"]x = \frac{-b \pm \sqrt{b^2-4ac}}{2a}[/themisdb_formula]</code></td>
                            <td><?php _e( 'Formel als eigenständigen Block zentriert darstellen.', 'themisdb-formula-renderer' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>[themisdb_formula]\sum_{i=1}^{n} i = \frac{n(n+1)}{2}[/themisdb_formula]</code></td>
                            <td><?php _e( 'Summenformel mit Grenzen.', 'themisdb-formula-renderer' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>[themisdb_formula]\int_0^\infty e^{-x^2} dx = \frac{\sqrt{\pi}}{2}[/themisdb_formula]</code></td>
                            <td><?php _e( 'Gaußsches Integral.', 'themisdb-formula-renderer' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>[themisdb_formula]\vec{F} = m \cdot \vec{a}[/themisdb_formula]</code></td>
                            <td><?php _e( 'Formel mit Vektornotation.', 'themisdb-formula-renderer' ); ?></td>
                        </tr>
                    </tbody>
                </table>

                <h3 style="margin-top:24px;"><?php _e( 'Automatisches Rendering', 'themisdb-formula-renderer' ); ?></h3>
                <p><?php _e( 'LaTeX-Ausdrücke können auch direkt im Post-Editor mit Standard-Delimitern verwendet werden:', 'themisdb-formula-renderer' ); ?></p>
                <table class="widefat striped" style="max-width:600px;">
                    <thead>
                        <tr>
                            <th><?php _e( 'Delimiter', 'themisdb-formula-renderer' ); ?></th>
                            <th><?php _e( 'Typ', 'themisdb-formula-renderer' ); ?></th>
                            <th><?php _e( 'Beispiel', 'themisdb-formula-renderer' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>$...$</code></td>
                            <td><?php _e( 'Inline', 'themisdb-formula-renderer' ); ?></td>
                            <td><code>$E = mc^2$</code></td>
                        </tr>
                        <tr>
                            <td><code>$$...$$</code></td>
                            <td><?php _e( 'Block', 'themisdb-formula-renderer' ); ?></td>
                            <td><code>$$\int_0^1 x\,dx = \tfrac{1}{2}$$</code></td>
                        </tr>
                        <tr>
                            <td><code>\(...\)</code></td>
                            <td><?php _e( 'Inline', 'themisdb-formula-renderer' ); ?></td>
                            <td><code>\(a^2+b^2=c^2\)</code></td>
                        </tr>
                        <tr>
                            <td><code>\[...\]</code></td>
                            <td><?php _e( 'Block', 'themisdb-formula-renderer' ); ?></td>
                            <td><code>\[\nabla \cdot \vec{E} = \frac{\rho}{\varepsilon_0}\]</code></td>
                        </tr>
                    </tbody>
                </table>
                <?php endif; ?>

            </div><!-- .themisdb-tab-content -->
        </div><!-- .wrap -->

        <style>
        .themisdb-formula-library { max-width: 1400px; }
        .themisdb-admin-modules { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:16px; margin:0 0 20px; }
        .themisdb-admin-modules .card { margin:0; max-width:none; }
        .themisdb-tab-content { background:#fff; border:1px solid #c3c4c7; border-top:none; padding:20px 24px; }
        .themisdb-tab-content > h2:first-child,
        .themisdb-tab-content > h3:first-child,
        .themisdb-tab-content > p:first-child { margin-top:0; }
        .themisdb-tab-content .widefat th { width:auto; }
        .themisdb-tab-content table.widefat code { background:#f6f7f7; padding:2px 6px; border-radius:3px; font-size:12px; }
        .formula-category { margin: 2rem 0; }
        .formula-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        .formula-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .formula-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .formula-card h3 { margin: 0 0 0.5rem 0; color: #2c3e50; }
        .formula-description { color: #666; font-size: 0.9em; margin-bottom: 1rem; }
        .formula-preview {
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .formula-actions { display: flex; gap: 0.5rem; }
        .formula-actions button { flex: 1; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('.copy-shortcode, .copy-latex').on('click', function() {
                const text = $(this).data('shortcode') || $(this).data('latex');
                const $btn = $(this);

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(() => {
                        const originalText = $btn.text();
                        $btn.text('✅ Copied!');
                        setTimeout(() => $btn.text(originalText), 2000);
                    }).catch(err => {
                        console.error('Clipboard copy failed:', err);
                        $btn.text('❌ Failed');
                        setTimeout(() => $btn.text($btn.data('original-text') || 'Copy'), 2000);
                    });
                } else {
                    const originalText = $btn.text();
                    try {
                        const textarea = document.createElement('textarea');
                        textarea.value = text;
                        textarea.style.position = 'fixed';
                        textarea.style.opacity = '0';
                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textarea);
                        $btn.text('✅ Copied!');
                    } catch (err) {
                        console.error('Fallback copy failed:', err);
                        $btn.text('❌ Failed');
                    }
                    setTimeout(() => $btn.text(originalText), 2000);
                }
            });
        });
        </script>
        <?php
    }
}
