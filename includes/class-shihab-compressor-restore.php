<?php
/**
 * Backup & Restore — MHS Image Compressor
 * @package Shihab_Compressor
 * @author  MEHEDI HASAN SHIHAB <sshihabb007>
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Shihab_Compressor_Restore {

    private $shihab_sshihabb007_backup_dir = '';

    public function shihab_sshihabb007_init() {
        $upload = wp_upload_dir();
        $this->shihab_sshihabb007_backup_dir = trailingslashit( $upload['basedir'] ) . 'mhs-backups/';
        wp_mkdir_p( $this->shihab_sshihabb007_backup_dir );
        add_action( 'rest_api_init', [ $this, 'shihab_sshihabb007_register_rest' ] );
        add_action( 'wp_ajax_shihab_restore_image', [ $this, 'shihab_sshihabb007_ajax_restore' ] );
        add_action( 'wp_ajax_shihab_list_backups',  [ $this, 'shihab_sshihabb007_ajax_list' ] );
        add_action( 'wp_ajax_shihab_delete_backup', [ $this, 'shihab_sshihabb007_ajax_delete_backup' ] );
    }

    public function shihab_sshihabb007_register_rest() {
        $perm = [ 'permission_callback' => [ $this, 'shihab_sshihabb007_can' ] ];
        register_rest_route( 'MHS/v1', '/restore/(?P<id>[0-9]+)', array_merge( $perm, [
            'methods' => 'POST', 'callback' => [ $this, 'shihab_sshihabb007_rest_restore' ],
        ] ) );
        register_rest_route( 'MHS/v1', '/backups', array_merge( $perm, [
            'methods' => 'GET', 'callback' => [ $this, 'shihab_sshihabb007_rest_list' ],
        ] ) );
    }
    public function shihab_sshihabb007_can() { return current_user_can( 'manage_options' ); }

    /** Copy original to backup folder before any optimization */
    public function shihab_sshihabb007_create_backup( $attachment_id ) {
        $file = get_attached_file( $attachment_id );
        if ( ! $file || ! file_exists( $file ) ) return false;
        if ( get_post_meta( $attachment_id, '_shihab_mhs_backup_path', true ) ) return true;
        $ext    = pathinfo( $file, PATHINFO_EXTENSION );
        $backup = $this->shihab_sshihabb007_backup_dir . $attachment_id . '_orig.' . $ext;
        if ( @copy( $file, $backup ) ) {
            update_post_meta( $attachment_id, '_shihab_mhs_backup_path',    $backup );
            update_post_meta( $attachment_id, '_shihab_mhs_backup_size',    filesize( $file ) );
            update_post_meta( $attachment_id, '_shihab_mhs_backup_created', current_time( 'timestamp' ) );
            return true;
        }
        return false;
    }

    /** Restore original from backup */
    public function shihab_sshihabb007_rest_restore( WP_REST_Request $req ) {
        $id     = intval( $req['id'] );
        $backup = get_post_meta( $id, '_shihab_mhs_backup_path', true );
        if ( ! $backup || ! file_exists( $backup ) ) {
            return new WP_Error( 'shihab_no_backup', 'No backup found.', [ 'status' => 404 ] );
        }
        $current = get_attached_file( $id );
        $ext     = pathinfo( $backup, PATHINFO_EXTENSION );
        $newfile = preg_replace( '/\.[^.]+$/', '.' . $ext, $current );
        if ( ! @copy( $backup, $newfile ) ) {
            return new WP_Error( 'shihab_restore_fail', 'Copy failed.', [ 'status' => 500 ] );
        }
        if ( $newfile !== $current && file_exists( $current ) ) @unlink( $current );
        update_attached_file( $id, $newfile );
        wp_update_post( [ 'ID' => $id, 'post_mime_type' => 'image/' . $ext ] );
        foreach ( [ '_shihab_compressor_sshihabb007_auto_done', '_shihab_compressor_sshihabb007_optimized',
                    '_shihab_compressor_sshihabb007_format', '_shihab_compressor_sshihabb007_savings_bytes' ] as $k ) {
            delete_post_meta( $id, $k );
        }
        $new_meta = wp_generate_attachment_metadata( $id, $newfile );
        if ( $new_meta ) wp_update_attachment_metadata( $id, $new_meta );
        return new WP_REST_Response( [ 'success' => true, 'url' => wp_get_attachment_url( $id ) ], 200 );
    }

    /** List all backed-up images */
    public function shihab_sshihabb007_rest_list() {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT post_id, meta_value AS bp FROM {$wpdb->postmeta}
             WHERE meta_key='_shihab_mhs_backup_path' ORDER BY post_id DESC LIMIT 200"
        );
        $list = [];
        foreach ( $rows as $row ) {
            $id      = intval( $row->post_id );
            $exists  = file_exists( $row->bp );
            $savings = get_post_meta( $id, '_shihab_compressor_sshihabb007_savings_bytes', true );
            $created = get_post_meta( $id, '_shihab_mhs_backup_created', true );
            $list[]  = [
                'id'      => $id,
                'title'   => get_the_title( $id ),
                'thumb'   => wp_get_attachment_image_url( $id, 'thumbnail' ),
                'exists'  => $exists,
                'bsize'   => $exists ? size_format( filesize( $row->bp ) ) : '—',
                'savings' => $savings ? size_format( $savings ) : '0 B',
                'created' => $created ? human_time_diff( $created ) . ' ago' : '—',
            ];
        }
        return new WP_REST_Response( $list, 200 );
    }

    public function shihab_sshihabb007_ajax_restore() {
        check_ajax_referer( 'shihab_compressor_sshihabb007_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $req = new WP_REST_Request(); $req['id'] = intval( $_POST['attachment_id'] ?? 0 );
        $r = $this->shihab_sshihabb007_rest_restore( $req );
        is_wp_error( $r ) ? wp_send_json_error( $r->get_error_message() ) : wp_send_json_success( $r->get_data() );
    }

    public function shihab_sshihabb007_ajax_list() {
        check_ajax_referer( 'shihab_compressor_sshihabb007_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        wp_send_json_success( $this->shihab_sshihabb007_rest_list()->get_data() );
    }

    public function shihab_sshihabb007_ajax_delete_backup() {
        check_ajax_referer( 'shihab_compressor_sshihabb007_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $id = intval( $_POST['attachment_id'] ?? 0 );
        $bp = get_post_meta( $id, '_shihab_mhs_backup_path', true );
        if ( $bp && file_exists( $bp ) ) @unlink( $bp );
        foreach ( [ '_shihab_mhs_backup_path', '_shihab_mhs_backup_size', '_shihab_mhs_backup_created' ] as $k ) {
            delete_post_meta( $id, $k );
        }
        wp_send_json_success( 'Backup deleted.' );
    }

    public function shihab_sshihabb007_disk_usage() {
        $total = 0;
        foreach ( glob( $this->shihab_sshihabb007_backup_dir . '*' ) ?: [] as $f ) $total += filesize( $f );
        return $total;
    }
}

