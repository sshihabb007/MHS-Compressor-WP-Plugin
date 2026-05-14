<?php
/**
 * Uninstall Handler — MHS Image Compressor (sshihabb007)
 *
 * Called automatically by WordPress when the plugin is deleted.
 * Cleans up all options and data created by the plugin.
 *
 * @package Shihab_Compressor
 * @author  MEHEDI HASAN SHIHAB <sshihabb007>
 * @link    https://mehedi-hasan-shihab.netlify.app/
 */

// Block direct access — sshihabb007 security check
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// ─────────────────────────────────────────────
//  Remove all plugin options from wp_options
// ─────────────────────────────────────────────
$shihab_sshihabb007_options_to_remove = [
    'shihab_compressor_sshihabb007_settings',
    'shihab_compressor_sshihabb007_stats',
    'shihab_compressor_sshihabb007_license',
];

foreach ( $shihab_sshihabb007_options_to_remove as $shihab_sshihabb007_option ) {
    delete_option( $shihab_sshihabb007_option );
}

// ─────────────────────────────────────────────
//  Remove transients
// ─────────────────────────────────────────────
delete_transient( 'shihab_compressor_sshihabb007_cache' );

// ─────────────────────────────────────────────
//  Remove per-attachment meta added by the plugin
// ─────────────────────────────────────────────
global $wpdb;

$wpdb->delete(
    $wpdb->postmeta,
    [ 'meta_key' => '_shihab_compressor_sshihabb007_optimized' ],
    [ '%s' ]
);

$wpdb->delete(
    $wpdb->postmeta,
    [ 'meta_key' => '_shihab_compressor_sshihabb007_savings_bytes' ],
    [ '%s' ]
);

$wpdb->delete(
    $wpdb->postmeta,
    [ 'meta_key' => '_shihab_compressor_sshihabb007_ai_alt_text' ],
    [ '%s' ]
);


