<?php
/**
 * Admin Module — MHS Image Compressor
 *
 * Registers the WordPress admin menu page, enqueues all assets,
 * and localizes JS data for the client-side engine.
 *
 * @package Shihab_Compressor
 * @author  MEHEDI HASAN SHIHAB <sshihabb007>
 * @link    https://mehedi-hasan-shihab.netlify.app/
 */

// Block direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Shihab_Compressor_Admin
 *
 * Handles the WP Admin registration, menu, and asset enqueueing
 * for the MHS Image Compressor plugin by sshihabb007.
 */
class Shihab_Compressor_Admin {

    /**
     * Initialize admin hooks.
     *
     * @return void
     */
    public function shihab_sshihabb007_init() {
        add_action( 'admin_menu',             [ $this, 'shihab_sshihabb007_register_menu' ] );
        add_action( 'admin_enqueue_scripts',  [ $this, 'shihab_sshihabb007_enqueue_assets' ] );
        add_action( 'wp_ajax_shihab_compressor_save_settings', [ $this, 'shihab_sshihabb007_save_settings' ] );
        add_action( 'wp_ajax_shihab_compressor_get_stats',     [ $this, 'shihab_sshihabb007_get_stats' ] );
    }

    /**
     * Register the top-level admin menu entry.
     *
     * @return void
     */
    public function shihab_sshihabb007_register_menu() {
        add_menu_page(
            __( '⚡ MHS Compressor', 'shihab-compressor' ),  // Page title
            __( 'MHS Compress', 'shihab-compressor' ),        // Menu title
            'manage_options',                                      // Capability
            'shihab-compressor',                                   // Menu slug
            [ $this, 'shihab_sshihabb007_render_dashboard' ],     // Callback
            $this->shihab_sshihabb007_get_menu_icon(),            // SVG icon
            58                                                     // Position (below Media)
        );
    }

    /**
     * Get the base64-encoded SVG icon for the menu.
     *
     * @return string
     */
    private function shihab_sshihabb007_get_menu_icon() {
        $shihab_sshihabb007_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%23a5b4fc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode( str_replace( '%23', '#', $shihab_sshihabb007_svg ) );
    }

    /**
     * Enqueue admin CSS + JS assets on the plugin's page only.
     *
     * @param string $shihab_sshihabb007_hook Current admin page hook.
     * @return void
     */
    public function shihab_sshihabb007_enqueue_assets( $shihab_sshihabb007_hook ) {
        if ( 'toplevel_page_shihab-compressor' !== $shihab_sshihabb007_hook ) {
            return;
        }

        // ── Tailwind CSS v4 via CDN ────────────────────────────────────────
        wp_enqueue_script(
            'shihab-compressor-tailwind',
            'https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4',
            [],
            null,
            false
        );

        // ── Google Fonts: Inter ────────────────────────────────────────────
        wp_enqueue_style(
            'shihab-compressor-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap',
            [],
            null
        );

        // ── Custom Admin CSS ───────────────────────────────────────────────
        wp_enqueue_style(
            'shihab-compressor-admin-css',
            SHIHAB_COMPRESSOR_URL . 'assets/css/shihab-compressor-admin.css',
            [ 'shihab-compressor-fonts' ],
            SHIHAB_COMPRESSOR_VERSION
        );

        // ── Dexie.js (IndexedDB) from CDN ─────────────────────────────────
        wp_enqueue_script(
            'shihab-compressor-dexie',
            'https://cdn.jsdelivr.net/npm/dexie@4.0.8/dist/dexie.min.js',
            [],
            '4.0.8',
            true
        );

        // ── Core Script ───────────────────────────────────────────────────
        wp_enqueue_script(
            'shihab-compressor-script',
            SHIHAB_COMPRESSOR_URL . 'assets/js/shihab-compressor-script.js',
            [ 'shihab-compressor-dexie' ],
            SHIHAB_COMPRESSOR_VERSION,
            true
        );

        // ── Localize: pass PHP data to JS ─────────────────────────────────
        $shihab_sshihabb007_settings = get_option( 'shihab_compressor_sshihabb007_settings', [] );
        $shihab_sshihabb007_stats    = get_option( 'shihab_compressor_sshihabb007_stats', [] );

        wp_localize_script(
            'shihab-compressor-script',
            'shihabCompressorData',
            [
                'nonce'       => wp_create_nonce( 'shihab_compressor_sshihabb007_nonce' ),
                'restUrl'     => esc_url_raw( rest_url( 'MHS/v1/upload' ) ),
                'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
                'swUrl'       => SHIHAB_COMPRESSOR_URL . 'assets/js/coi-serviceworker.js',
                'assetsUrl'   => SHIHAB_COMPRESSOR_URL . 'assets/',
                'pluginUrl'   => SHIHAB_COMPRESSOR_URL,
                'settings'    => $shihab_sshihabb007_settings,
                'stats'       => $shihab_sshihabb007_stats,
                'restNonce'   => wp_create_nonce( 'wp_rest' ),
                'version'     => SHIHAB_COMPRESSOR_VERSION,
                'author' => 'MEHEDI HASAN SHIHAB sshihabb007',
                'authorUrl'   => 'https://mehedi-hasan-shihab.netlify.app/',
            ]
        );
    }

    /**
     * Render the Cyberpunk dashboard view.
     *
     * @return void
     */
    public function shihab_sshihabb007_render_dashboard() {
        $shihab_sshihabb007_view = SHIHAB_COMPRESSOR_PATH . 'admin/views/shihab-compressor-dashboard.php';
        if ( file_exists( $shihab_sshihabb007_view ) ) {
            // Pass settings to the view
            $shihab_sshihabb007_settings = get_option( 'shihab_compressor_sshihabb007_settings', [
                'output_format'     => 'webp',
                'quality'           => 75,
                'smart_resize'      => true,
                'strip_metadata'    => true,
                'batch_concurrency' => 3,
                'ai_alt_text'       => true,
                'indexeddb_cache'   => true,
                'auto_optimize'     => true,
            ] );
            $shihab_sshihabb007_stats = get_option( 'shihab_compressor_sshihabb007_stats', [
                'total_images'  => 0,
                'total_saved'   => 0,
                'total_ai_tags' => 0,
            ] );
            include $shihab_sshihabb007_view;
        }
    }

    /**
     * AJAX: Save plugin settings.
     *
     * @return void
     */
    public function shihab_sshihabb007_save_settings() {
        check_ajax_referer( 'shihab_compressor_sshihabb007_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized — sshihabb007 access denied.' ] );
        }

        // Helper: checkbox value — JS sends '1' or '0' as a string; intval() converts correctly.
        // NOTE: (bool)'0' === true in PHP, so never use (bool) on string checkbox values.
        $shihab_bool = fn( $key, $default = true ) => (bool) intval( $_POST[ $key ] ?? ( $default ? '1' : '0' ) );

        $shihab_sshihabb007_new_settings = [
            'output_format'      => sanitize_text_field( $_POST['output_format']      ?? 'webp' ),
            'quality'            => intval( $_POST['quality']            ?? 75 ),
            'compression_tier'   => sanitize_text_field( $_POST['compression_tier']   ?? 'glossy' ),
            'smart_resize'       => $shihab_bool( 'smart_resize',      true ),
            'max_width'          => intval( $_POST['max_width']          ?? 2000 ),
            'strip_metadata'     => $shihab_bool( 'strip_metadata',    true ),
            'batch_concurrency'  => intval( $_POST['batch_concurrency'] ?? 3 ),
            'ai_alt_text'        => $shihab_bool( 'ai_alt_text',       true ),
            'ai_smart_quality'   => $shihab_bool( 'ai_smart_quality',  true ),
            'ai_seo_filename'    => $shihab_bool( 'ai_seo_filename',   true ),
            'indexeddb_cache'    => $shihab_bool( 'indexeddb_cache',   true ),
            'auto_optimize'      => $shihab_bool( 'auto_optimize',     true ),
            'backup_originals'   => $shihab_bool( 'backup_originals',  true ),
            'dynamic_delivery'   => $shihab_bool( 'dynamic_delivery',  false ),
        ];

        update_option( 'shihab_compressor_sshihabb007_settings', $shihab_sshihabb007_new_settings );
        wp_send_json_success( [ 'message' => 'Settings saved.', 'settings' => $shihab_sshihabb007_new_settings ] );
    }

    /**
     * AJAX: Return current plugin stats as JSON.
     *
     * @return void
     */
    public function shihab_sshihabb007_get_stats() {
        check_ajax_referer( 'shihab_compressor_sshihabb007_nonce', 'nonce' );
        $shihab_sshihabb007_stats = get_option( 'shihab_compressor_sshihabb007_stats', [
            'total_images'  => 0,
            'total_saved'   => 0,
            'total_ai_tags' => 0,
        ] );
        wp_send_json_success( $shihab_sshihabb007_stats );
    }
}


