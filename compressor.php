<?php
/**
 * Plugin Name:       MHS Image Compressor by Shihab
 * Plugin URI:        https://www.linkedin.com/in/mehedi-hasan-shihab/
 * Description:       A futuristic client-side image optimizer using FFmpeg.wasm, AI alt-text via Transformers.js, and IndexedDB caching. Converts images to WebP/AVIF with smart resize, metadata stripping, and batch processing — all in the browser.
 * Version:           1.0.0
 * Author:            Mehedi
 * Author URI:        https://www.linkedin.com/in/mehedi-hasan-shihab/
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       shihab-compressor
 * Domain Path:       /languages
 *
 * @package           Shihab_Compressor
 * @author            Mehedi Hasan Shihab <sshihabb007>
 */

// Prevent direct access — sshihabb007 security gate
if (!defined('ABSPATH')) {
    exit;
}

// ─────────────────────────────────────────────
//  SHIHAB COMPRESSOR — Global Constants
// ─────────────────────────────────────────────
define('SHIHAB_COMPRESSOR_VERSION', '1.0.0');
define('SHIHAB_COMPRESSOR_PATH', plugin_dir_path(__FILE__));
define('SHIHAB_COMPRESSOR_URL', plugin_dir_url(__FILE__));
define('SHIHAB_COMPRESSOR_SLUG', 'shihab-compressor');
define('SHIHAB_COMPRESSOR_BASENAME', plugin_basename(__FILE__));

// ─────────────────────────────────────────────
//  Autoload — class-shihab-compressor-*.php
// ─────────────────────────────────────────────
$shihab_sshihabb007_includes = [
    'includes/class-shihab-compressor-admin.php',
    'includes/class-shihab-compressor-api.php',
    'includes/class-shihab-compressor-fallback.php',
    'includes/class-shihab-compressor-auto.php',
];

foreach ($shihab_sshihabb007_includes as $shihab_sshihabb007_file) {
    $shihab_sshihabb007_filepath = SHIHAB_COMPRESSOR_PATH . $shihab_sshihabb007_file;
    if (file_exists($shihab_sshihabb007_filepath)) {
        require_once $shihab_sshihabb007_filepath;
    }
}

// ─────────────────────────────────────────────
//  Cross-Origin Isolation Headers (PHP fallback)
//  Primary: .htaccess | Fallback: this hook
// ─────────────────────────────────────────────
add_action('send_headers', 'shihab_sshihabb007_set_coi_headers');
function shihab_sshihabb007_set_coi_headers()
{
    // Only on the plugin's admin page to avoid breaking other pages
    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'shihab-compressor') {
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Embedder-Policy: require-corp');
    }
}

// ─────────────────────────────────────────────
//  Plugin Activation / Deactivation
// ─────────────────────────────────────────────
register_activation_hook(__FILE__, 'shihab_sshihabb007_activate');
function shihab_sshihabb007_activate()
{
    add_option('shihab_compressor_sshihabb007_settings', [
        'output_format'     => 'webp',
        'quality'           => 75,
        'smart_resize'      => true,
        'strip_metadata'    => true,
        'batch_concurrency' => 3,
        'ai_alt_text'       => true,
        'indexeddb_cache'   => true,
        'auto_optimize'     => true,
    ]);
    add_option('shihab_compressor_sshihabb007_stats', [
        'total_images' => 0,
        'total_saved' => 0,
        'total_ai_tags' => 0,
    ]);
}

register_deactivation_hook(__FILE__, 'shihab_sshihabb007_deactivate');
function shihab_sshihabb007_deactivate()
{
    // Flush any transients created by the plugin
    delete_transient('shihab_compressor_sshihabb007_cache');
}

// ─────────────────────────────────────────────
//  Conflict Detection — admin_notices
// ─────────────────────────────────────────────
add_action('admin_notices', 'shihab_sshihabb007_detect_conflicts');
function shihab_sshihabb007_detect_conflicts()
{
    $shihab_sshihabb007_active_plugins = get_option('active_plugins', []);
    $shihab_sshihabb007_competitors = [
        'smush',
        'shortpixel',
        'imagify',
        'ewww-image-optimizer',
        'robin-image-optimizer',
        'wp-smushit',
    ];
    $shihab_sshihabb007_serialized = serialize($shihab_sshihabb007_active_plugins);
    foreach ($shihab_sshihabb007_competitors as $shihab_sshihabb007_slug) {
        if (strpos($shihab_sshihabb007_serialized, $shihab_sshihabb007_slug) !== false) {
            echo '<div class="notice notice-warning is-dismissible shihab-compressor-conflict-notice">';
            echo '<p><strong>⚡ MHS Compressor:</strong> Conflict detected — <code>' . esc_html($shihab_sshihabb007_slug) . '</code> is active. Two image optimizers may conflict. Consider deactivating one.</p>';
            echo '</div>';
        }
    }
}

// ─────────────────────────────────────────────
//  Boot Admin, REST API & Auto-Optimizer
// ─────────────────────────────────────────────
if (is_admin()) {
    $shihab_sshihabb007_admin = new Shihab_Compressor_Admin();
    $shihab_sshihabb007_admin->shihab_sshihabb007_init();
}

// Auto-Optimizer: runs on every image upload (front-end & admin)
$shihab_sshihabb007_auto = new Shihab_Compressor_Auto();
$shihab_sshihabb007_auto->shihab_sshihabb007_init();

add_action('rest_api_init', function () {
    $shihab_sshihabb007_api = new Shihab_Compressor_API();
    $shihab_sshihabb007_api->shihab_sshihabb007_register_routes();
});

// AJAX hooks for PHP fallback compression (older browsers)
add_action('wp_ajax_shihab_compressor_php_fallback', 'shihab_sshihabb007_ajax_fallback_handler');
function shihab_sshihabb007_ajax_fallback_handler()
{
    check_ajax_referer('shihab_compressor_sshihabb007_nonce', 'nonce');
    $shihab_sshihabb007_fallback = new Shihab_Compressor_Fallback();
    $shihab_sshihabb007_fallback->shihab_sshihabb007_handle_upload();
}
