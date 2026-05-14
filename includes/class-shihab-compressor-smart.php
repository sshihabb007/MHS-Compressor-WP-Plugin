<?php
/**
 * Smart Intelligence — MHS Image Compressor
 * Smart Quality Balance, compression tiers, AI filenames, focal point meta.
 *
 * @package Shihab_Compressor
 * @author  Mehedi Shihab <sshihabb007>
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Shihab_Compressor_Smart {

    public function shihab_sshihabb007_init() {
        add_action( 'wp_ajax_shihab_smart_quality',    [ $this, 'shihab_sshihabb007_ajax_smart_quality' ] );
        add_action( 'wp_ajax_shihab_svg_optimize',     [ $this, 'shihab_sshihabb007_ajax_svg_optimize' ] );
        add_action( 'wp_ajax_shihab_dir_scan',         [ $this, 'shihab_sshihabb007_ajax_dir_scan' ] );
        add_action( 'wp_ajax_shihab_dir_smush',        [ $this, 'shihab_sshihabb007_ajax_dir_smush' ] );
        add_action( 'rest_api_init',                   [ $this, 'shihab_sshihabb007_register_rest' ] );
    }

    public function shihab_sshihabb007_register_rest() {
        $p = [ 'permission_callback' => fn() => current_user_can( 'manage_options' ) ];
        register_rest_route( 'MHS/v1', '/smart-quality/(?P<id>[0-9]+)', array_merge( $p, [
            'methods'  => 'GET',
            'callback' => [ $this, 'shihab_sshihabb007_rest_smart_quality' ],
        ] ) );
        register_rest_route( 'MHS/v1', '/bulk/start', array_merge( $p, [
            'methods'  => 'POST',
            'callback' => [ $this, 'shihab_sshihabb007_rest_bulk_start' ],
        ] ) );
        register_rest_route( 'MHS/v1', '/bulk/status', array_merge( $p, [
            'methods'  => 'GET',
            'callback' => [ $this, 'shihab_sshihabb007_rest_bulk_status' ],
        ] ) );
        register_rest_route( 'MHS/v1', '/bulk/pause', array_merge( $p, [
            'methods'  => 'POST',
            'callback' => [ $this, 'shihab_sshihabb007_rest_bulk_pause' ],
        ] ) );
    }

    /* ══════════════════════════════════════════════════════
       SMART QUALITY — analyse image complexity → choose q
       ══════════════════════════════════════════════════════ */

    /**
     * Analyse an image and recommend quality level.
     * Simple heuristic: sample unique colours in a 50×50 thumbnail.
     * High colour variance = complex (landscapes) → higher quality.
     * Low colour variance = flat design / solid fills → lower quality.
     *
     * @param string $file Absolute path to image.
     * @return array { quality: int, tier: string, reason: string }
     */
    public function shihab_sshihabb007_analyse_quality( $file ) {
        if ( ! extension_loaded( 'gd' ) || ! file_exists( $file ) ) {
            return [ 'quality' => 75, 'tier' => 'glossy', 'reason' => 'GD unavailable — using default' ];
        }

        $info = @getimagesize( $file );
        if ( ! $info ) return [ 'quality' => 75, 'tier' => 'glossy', 'reason' => 'Cannot read image' ];

        switch ( $info[2] ) {
            case IMAGETYPE_JPEG: $img = @imagecreatefromjpeg( $file ); break;
            case IMAGETYPE_PNG:  $img = @imagecreatefrompng( $file );  break;
            case IMAGETYPE_WEBP: $img = @imagecreatefromwebp( $file ); break;
            default: return [ 'quality' => 75, 'tier' => 'glossy', 'reason' => 'Unsupported type' ];
        }
        if ( ! $img ) return [ 'quality' => 75, 'tier' => 'glossy', 'reason' => 'GD load failed' ];

        // Downsample to 50×50 for speed
        $thumb = imagecreatetruecolor( 50, 50 );
        imagecopyresampled( $thumb, $img, 0,0,0,0, 50,50, imagesx($img), imagesy($img) );
        imagedestroy( $img );

        // Count unique colours
        $colours = [];
        for ( $x = 0; $x < 50; $x++ ) {
            for ( $y = 0; $y < 50; $y++ ) {
                $c = imagecolorat( $thumb, $x, $y );
                $r = ( $c >> 16 ) & 0xFF;
                $g = ( $c >>  8 ) & 0xFF;
                $b = $c & 0xFF;
                // Bucket to 16 colour zones
                $colours[ intdiv($r,16) . '_' . intdiv($g,16) . '_' . intdiv($b,16) ] = true;
            }
        }
        imagedestroy( $thumb );

        $unique = count( $colours ); // 0-4096

        if ( $unique < 150 ) {
            // Flat design / icon / infographic
            return [ 'quality' => 55, 'tier' => 'lossy',   'reason' => "Flat design ({$unique} colour zones) — heavy compression safe" ];
        }
        if ( $unique < 700 ) {
            return [ 'quality' => 75, 'tier' => 'glossy',  'reason' => "Medium complexity ({$unique} colour zones) — balanced compression" ];
        }
        // Complex scene / photograph
        return [ 'quality' => 88, 'tier' => 'lossless', 'reason' => "High complexity ({$unique} colour zones) — preserve detail" ];
    }

    public function shihab_sshihabb007_rest_smart_quality( WP_REST_Request $req ) {
        $id   = intval( $req['id'] );
        $file = get_attached_file( $id );
        return new WP_REST_Response( $this->shihab_sshihabb007_analyse_quality( $file ), 200 );
    }

    public function shihab_sshihabb007_ajax_smart_quality() {
        check_ajax_referer( 'shihab_compressor_sshihabb007_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $id   = intval( $_POST['attachment_id'] ?? 0 );
        $file = get_attached_file( $id );
        wp_send_json_success( $this->shihab_sshihabb007_analyse_quality( $file ) );
    }

    /* ══════════════════════════════════════════
       AI SEO FILENAME — slugify alt-text
       ══════════════════════════════════════════ */
    public function shihab_sshihabb007_ai_filename( $alt_text, $original_name ) {
        if ( empty( trim( $alt_text ) ) ) return sanitize_file_name( $original_name );
        $slug = strtolower( $alt_text );
        $slug = preg_replace( '/[^a-z0-9\s\-]/', '', $slug );
        $slug = preg_replace( '/[\s]+/', '-', trim( $slug ) );
        $slug = preg_replace( '/-+/', '-', $slug );
        $slug = substr( $slug, 0, 60 );
        return $slug ?: sanitize_file_name( $original_name );
    }

    /* ══════════════════════════════════════════
       SVG OPTIMIZATION
       ══════════════════════════════════════════ */
    public function shihab_sshihabb007_optimize_svg( $svg_content ) {
        // Remove XML declaration, comments, metadata
        $svg = preg_replace( '/<!--.*?-->/s', '', $svg_content );
        $svg = preg_replace( '/<\?xml[^>]*\?>/i', '', $svg );
        $svg = preg_replace( '/<metadata>.*?<\/metadata>/si', '', $svg );
        // Collapse whitespace between tags
        $svg = preg_replace( '/>\s+</', '><', $svg );
        $svg = preg_replace( '/\s{2,}/', ' ', $svg );
        // Remove empty style attributes
        $svg = preg_replace( '/\s(style|class)=""/i', '', $svg );
        return trim( $svg );
    }

    public function shihab_sshihabb007_ajax_svg_optimize() {
        check_ajax_referer( 'shihab_compressor_sshihabb007_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $id   = intval( $_POST['attachment_id'] ?? 0 );
        $file = get_attached_file( $id );
        if ( ! $file || ! file_exists( $file ) || pathinfo( $file, PATHINFO_EXTENSION ) !== 'svg' ) {
            wp_send_json_error( 'Not an SVG file.' );
        }
        $orig    = file_get_contents( $file );
        $optimized = $this->shihab_sshihabb007_optimize_svg( $orig );
        $saved   = strlen( $orig ) - strlen( $optimized );
        file_put_contents( $file, $optimized );
        wp_send_json_success( [
            'saved_bytes' => $saved,
            'saved_pct'   => strlen($orig) > 0 ? round( ($saved / strlen($orig)) * 100, 1 ) : 0,
        ] );
    }

    /* ══════════════════════════════════════════
       DIRECTORY SCAN & SMUSH
       ══════════════════════════════════════════ */
    public function shihab_sshihabb007_ajax_dir_scan() {
        check_ajax_referer( 'shihab_compressor_sshihabb007_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $dir  = sanitize_text_field( $_POST['directory'] ?? '' );
        $real = realpath( ABSPATH . ltrim( $dir, '/\\' ) );
        if ( ! $real || strpos( $real, realpath( ABSPATH ) ) !== 0 ) {
            wp_send_json_error( 'Invalid directory.' );
        }
        $images = []; $total_size = 0;
        try {
            $it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $real, FilesystemIterator::SKIP_DOTS ) );
            foreach ( $it as $f ) {
                if ( $f->isDir() ) continue;
                $ext = strtolower( $f->getExtension() );
                if ( ! in_array( $ext, [ 'jpg','jpeg','png','gif','webp','svg' ] ) ) continue;
                $sz = $f->getSize();
                $total_size += $sz;
                $images[] = [ 'name' => $f->getFilename(), 'path' => str_replace( realpath(ABSPATH), '', $f->getRealPath() ), 'size' => $sz ];
                if ( count( $images ) >= 200 ) break;
            }
        } catch ( Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }
        wp_send_json_success( [ 'count' => count($images), 'total_size' => $total_size, 'images' => array_slice($images, 0, 50) ] );
    }

    public function shihab_sshihabb007_ajax_dir_smush() {
        check_ajax_referer( 'shihab_compressor_sshihabb007_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $dir      = sanitize_text_field( $_POST['directory'] ?? '' );
        $real     = realpath( ABSPATH . ltrim( $dir, '/\\' ) );
        $settings = get_option( 'shihab_compressor_sshihabb007_settings', [] );
        $fmt      = $settings['output_format'] === 'both' ? 'webp' : ( $settings['output_format'] ?? 'webp' );
        $q        = intval( $settings['quality'] ?? 75 );
        if ( ! $real || strpos( $real, realpath(ABSPATH) ) !== 0 ) wp_send_json_error('Invalid dir.');
        $fallback  = new Shihab_Compressor_Fallback();
        $processed = 0; $errors = 0; $saved = 0;
        $it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $real, FilesystemIterator::SKIP_DOTS ) );
        foreach ( $it as $f ) {
            if ( $f->isDir() || ! in_array( strtolower($f->getExtension()), ['jpg','jpeg','png','gif'] ) ) continue;
            $orig_path = $f->getRealPath();
            $orig_size = $f->getSize();
            $r = $fallback->shihab_sshihabb007_compress_file_direct( $orig_path, $q, $fmt );
            if ( $r['success'] ) {
                $new_path = $f->getPath() . '/' . pathinfo( $f->getFilename(), PATHINFO_FILENAME ) . '.' . $fmt;
                @rename( $r['tmp_path'], $new_path );
                if ( $new_path !== $orig_path ) @unlink( $orig_path );
                $saved += max( 0, $orig_size - filesize($new_path) );
                $processed++;
            } else { $errors++; }
            if ( $processed >= 30 ) break;
        }
        wp_send_json_success( [ 'processed' => $processed, 'errors' => $errors, 'saved_bytes' => $saved, 'saved_kb' => round($saved/1024,1) ] );
    }

    /* ══════════════════════════════════════════
       BULK ASYNC — WP-Cron queue
       ══════════════════════════════════════════ */
    const SHIHAB_BULK_QUEUE  = 'shihab_compressor_sshihabb007_bulk_queue';
    const SHIHAB_BULK_STATUS = 'shihab_compressor_sshihabb007_bulk_status';
    const SHIHAB_BULK_CRON   = 'shihab_sshihabb007_bulk_cron';

    public function shihab_sshihabb007_rest_bulk_start( WP_REST_Request $req ) {
        $ids = get_posts( [
            'post_type'      => 'attachment',
            'post_mime_type' => [ 'image/jpeg','image/png','image/gif','image/webp' ],
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [ [ 'key' => '_shihab_compressor_sshihabb007_auto_done', 'compare' => 'NOT EXISTS' ] ],
        ] );
        update_option( self::SHIHAB_BULK_QUEUE,  $ids );
        update_option( self::SHIHAB_BULK_STATUS, [ 'state'=>'running','total'=>count($ids),'processed'=>0,'errors'=>0,'paused'=>false ] );
        if ( ! wp_next_scheduled( self::SHIHAB_BULK_CRON ) ) {
            wp_schedule_single_event( time(), self::SHIHAB_BULK_CRON );
        }
        return new WP_REST_Response( [ 'success'=>true,'total'=>count($ids) ], 200 );
    }

    public function shihab_sshihabb007_bulk_cron_handler() {
        $status = get_option( self::SHIHAB_BULK_STATUS, [] );
        if ( ($status['state']??'') !== 'running' || !empty($status['paused']) ) return;
        $queue  = get_option( self::SHIHAB_BULK_QUEUE, [] );
        if ( empty($queue) ) { update_option( self::SHIHAB_BULK_STATUS, array_merge($status,['state'=>'complete']) ); return; }
        $chunk  = array_splice( $queue, 0, 5 );
        update_option( self::SHIHAB_BULK_QUEUE, $queue );
        $auto   = new Shihab_Compressor_Auto();
        foreach ( $chunk as $id ) {
            $meta = wp_get_attachment_metadata( $id );
            if ( $meta ) $auto->shihab_sshihabb007_on_upload( $meta, $id );
            $status['processed']++;
        }
        update_option( self::SHIHAB_BULK_STATUS, $status );
        if ( ! empty($queue) ) wp_schedule_single_event( time()+3, self::SHIHAB_BULK_CRON );
        else update_option( self::SHIHAB_BULK_STATUS, array_merge($status,['state'=>'complete']) );
    }

    public function shihab_sshihabb007_rest_bulk_status() {
        $status = get_option( self::SHIHAB_BULK_STATUS, [ 'state'=>'idle','total'=>0,'processed'=>0,'errors'=>0 ] );
        $status['remaining'] = count( get_option( self::SHIHAB_BULK_QUEUE, [] ) );
        $status['pct']       = $status['total'] > 0 ? round(($status['processed']/$status['total'])*100,1) : 0;
        return new WP_REST_Response( $status, 200 );
    }

    public function shihab_sshihabb007_rest_bulk_pause() {
        $status           = get_option( self::SHIHAB_BULK_STATUS, [] );
        $status['paused'] = ! ( $status['paused'] ?? false );
        if ( ! $status['paused'] && ($status['state']??'') === 'running' ) {
            wp_schedule_single_event( time(), self::SHIHAB_BULK_CRON );
        }
        update_option( self::SHIHAB_BULK_STATUS, $status );
        return new WP_REST_Response( [ 'paused' => $status['paused'] ], 200 );
    }
}
