<?php
/**
 * REST API Handler - MHS Image Compressor
 * Author: MEHEDI HASAN SHIHAB HASAN SHIHAB (sshihabb007)
 * URL: https://mehedi-hasan-shihab.netlify.app/
 * Endpoint: POST /wp-json/MHS/v1/upload
 *
 * @package Shihab_Compressor
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Shihab_Compressor_API {

    public function shihab_sshihabb007_register_routes() {
        register_rest_route( 'MHS/v1', '/upload', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'shihab_sshihabb007_handle_upload' ],
            'permission_callback' => [ $this, 'shihab_sshihabb007_check_permission' ],
        ] );
        register_rest_route( 'MHS/v1', '/stats', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'shihab_sshihabb007_get_stats' ],
            'permission_callback' => [ $this, 'shihab_sshihabb007_check_permission' ],
        ] );
        register_rest_route( 'MHS/v1', '/settings', [
            [ 'methods' => WP_REST_Server::READABLE,  'callback' => [ $this, 'shihab_sshihabb007_get_settings' ],    'permission_callback' => [ $this, 'shihab_sshihabb007_check_permission' ] ],
            [ 'methods' => WP_REST_Server::EDITABLE,  'callback' => [ $this, 'shihab_sshihabb007_update_settings' ], 'permission_callback' => [ $this, 'shihab_sshihabb007_check_permission' ] ],
        ] );
    }

    public function shihab_sshihabb007_check_permission() {
        if ( ! is_user_logged_in() )        return new WP_Error( 'shihab_unauthorized', 'Authentication required.', [ 'status' => 401 ] );
        if ( ! current_user_can( 'upload_files' ) ) return new WP_Error( 'shihab_forbidden', 'Insufficient permissions.', [ 'status' => 403 ] );
        return true;
    }

    public function shihab_sshihabb007_handle_upload( WP_REST_Request $shihab_req ) {
        if ( ! function_exists( 'wp_handle_sideload' ) )          require_once ABSPATH . 'wp-admin/includes/file.php';
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) require_once ABSPATH . 'wp-admin/includes/image.php';
        if ( ! function_exists( 'media_handle_sideload' ) )        require_once ABSPATH . 'wp-admin/includes/media.php';

        $shihab_files = $shihab_req->get_file_params();
        if ( empty( $shihab_files['image'] ) ) return new WP_Error( 'shihab_no_file', 'No image received.', [ 'status' => 400 ] );

        $shihab_filename   = sanitize_file_name( $shihab_req->get_param( 'filename' ) ?? 'upload.webp' );
        $shihab_alt_text   = sanitize_text_field( $shihab_req->get_param( 'alt_text' ) ?? '' );
        $shihab_format     = sanitize_text_field( $shihab_req->get_param( 'format' )   ?? 'webp' );
        $shihab_orig_size  = intval( $shihab_req->get_param( 'original_size' )          ?? 0 );
        $shihab_opt_size   = intval( $shihab_req->get_param( 'optimized_size' )         ?? 0 );

        $shihab_basename   = pathinfo( $shihab_filename, PATHINFO_FILENAME );
        $shihab_newname    = $shihab_basename . '.' . $shihab_format;

        $shihab_file_array = [ 'name' => $shihab_newname, 'tmp_name' => $shihab_files['image']['tmp_name'] ];
        $shihab_id         = media_handle_sideload( $shihab_file_array, 0, $shihab_basename );

        if ( is_wp_error( $shihab_id ) ) return new WP_Error( 'shihab_upload_failed', $shihab_id->get_error_message(), [ 'status' => 500 ] );

        if ( $shihab_alt_text ) {
            update_post_meta( $shihab_id, '_wp_attachment_image_alt', $shihab_alt_text );
            update_post_meta( $shihab_id, '_shihab_compressor_sshihabb007_ai_alt_text', $shihab_alt_text );
        }
        update_post_meta( $shihab_id, '_shihab_compressor_sshihabb007_optimized',      true );
        update_post_meta( $shihab_id, '_shihab_compressor_sshihabb007_savings_bytes',  max( 0, $shihab_orig_size - $shihab_opt_size ) );
        update_post_meta( $shihab_id, '_shihab_compressor_sshihabb007_format',         $shihab_format );
        update_post_meta( $shihab_id, '_shihab_compressor_sshihabb007_original_size',  $shihab_orig_size );
        update_post_meta( $shihab_id, '_shihab_compressor_sshihabb007_optimized_size', $shihab_opt_size );

        $this->shihab_sshihabb007_increment_stats( $shihab_orig_size - $shihab_opt_size, ! empty( $shihab_alt_text ) );

        $shihab_saved   = max( 0, $shihab_orig_size - $shihab_opt_size );
        return new WP_REST_Response( [
            'success'        => true,
            'attachment_id'  => $shihab_id,
            'url'            => wp_get_attachment_url( $shihab_id ),
            'filename'       => $shihab_newname,
            'alt_text'       => $shihab_alt_text,
            'format'         => $shihab_format,
            'original_size'  => $shihab_orig_size,
            'optimized_size' => $shihab_opt_size,
            'saved_bytes'    => $shihab_saved,
            'saved_percent'  => $shihab_orig_size > 0 ? round( ( $shihab_saved / $shihab_orig_size ) * 100, 1 ) : 0,
            'author' => 'MEHEDI HASAN SHIHAB sshihabb007',
        ], 201 );
    }

    private function shihab_sshihabb007_increment_stats( $shihab_bytes_saved, $shihab_has_ai ) {
        $shihab_stats = get_option( 'shihab_compressor_sshihabb007_stats', [ 'total_images' => 0, 'total_saved' => 0, 'total_ai_tags' => 0 ] );
        $shihab_stats['total_images']++;
        $shihab_stats['total_saved'] += max( 0, $shihab_bytes_saved );
        if ( $shihab_has_ai ) $shihab_stats['total_ai_tags']++;
        update_option( 'shihab_compressor_sshihabb007_stats', $shihab_stats );
    }

    public function shihab_sshihabb007_get_stats() {
        return new WP_REST_Response( get_option( 'shihab_compressor_sshihabb007_stats', [ 'total_images' => 0, 'total_saved' => 0, 'total_ai_tags' => 0 ] ), 200 );
    }

    public function shihab_sshihabb007_get_settings() {
        return new WP_REST_Response( get_option( 'shihab_compressor_sshihabb007_settings', [] ), 200 );
    }

    public function shihab_sshihabb007_update_settings( WP_REST_Request $shihab_req ) {
        $shihab_updated = array_merge( get_option( 'shihab_compressor_sshihabb007_settings', [] ), $shihab_req->get_json_params() );
        update_option( 'shihab_compressor_sshihabb007_settings', $shihab_updated );
        return new WP_REST_Response( [ 'success' => true, 'settings' => $shihab_updated ], 200 );
    }
}


