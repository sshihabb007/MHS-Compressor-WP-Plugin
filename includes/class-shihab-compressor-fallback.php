<?php
/**
 * PHP Fallback Compression — MHS Image Compressor
 *
 * Handles server-side compression using GD or Imagick when the browser
 * does not support SharedArrayBuffer / FFmpeg.wasm (older browsers).
 *
 * @package Shihab_Compressor
 * @author  MEHEDI HASAN SHIHAB <sshihabb007>
 * @link    https://mehedi-hasan-shihab.netlify.app/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Shihab_Compressor_Fallback
 *
 * Server-side PHP image compression fallback by MEHEDI HASAN SHIHAB (sshihabb007).
 * Uses GD → Imagick chain.
 */
class Shihab_Compressor_Fallback {

    /**
     * Handle the AJAX upload and compress via PHP.
     *
     * @return void Sends JSON response.
     */
    public function shihab_sshihabb007_handle_upload() {
        // ── Clean any stray output (PHP notices/warnings) so JSON is always pure
        if ( ob_get_level() ) {
            ob_clean();
        }

        if ( empty( $_FILES['image'] ) || $_FILES['image']['error'] !== UPLOAD_ERR_OK ) {
            $shihab_err_code = $_FILES['image']['error'] ?? -1;
            wp_send_json_error( [ 'message' => "No valid file received (error code: {$shihab_err_code})." ] );
        }

        // ── Load WP upload helpers ───────────────────────────────────────────
        if ( ! function_exists( 'wp_handle_sideload' ) )          require_once ABSPATH . 'wp-admin/includes/file.php';
        if ( ! function_exists( 'media_handle_sideload' ) )        require_once ABSPATH . 'wp-admin/includes/media.php';
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) require_once ABSPATH . 'wp-admin/includes/image.php';

        $shihab_sshihabb007_quality   = intval( $_POST['quality']  ?? 75 );
        $shihab_sshihabb007_format    = sanitize_text_field( $_POST['format']   ?? 'webp' );
        $shihab_sshihabb007_alt_text  = sanitize_text_field( $_POST['alt_text'] ?? '' );
        $shihab_sshihabb007_orig_size = intval( $_FILES['image']['size'] ?? 0 );
        $shihab_sshihabb007_src       = $_FILES['image']['tmp_name'];

        // ── Validate source exists ───────────────────────────────────────────
        if ( ! file_exists( $shihab_sshihabb007_src ) ) {
            wp_send_json_error( [ 'message' => 'Uploaded temp file missing on server.' ] );
        }

        // ── Compress: GD → Imagick ───────────────────────────────────────────
        $shihab_sshihabb007_result = $this->shihab_sshihabb007_compress_gd(
            $shihab_sshihabb007_src, $shihab_sshihabb007_quality, $shihab_sshihabb007_format
        );

        if ( ! $shihab_sshihabb007_result['success'] && extension_loaded( 'imagick' ) ) {
            $shihab_sshihabb007_result = $this->shihab_sshihabb007_compress_imagick(
                $shihab_sshihabb007_src, $shihab_sshihabb007_quality, $shihab_sshihabb007_format
            );
        }

        if ( ! $shihab_sshihabb007_result['success'] ) {
            if ( ob_get_level() ) ob_clean();
            wp_send_json_error( [ 'message' => 'Compression failed: ' . ( $shihab_sshihabb007_result['error'] ?? 'GD/Imagick error' ) ] );
        }

        // ── Build file array for WordPress sideload ──────────────────────────
        $shihab_sshihabb007_tmp      = $shihab_sshihabb007_result['tmp_path'];
        $shihab_sshihabb007_basename = pathinfo( sanitize_file_name( $_FILES['image']['name'] ), PATHINFO_FILENAME );
        $shihab_sshihabb007_newname  = $shihab_sshihabb007_basename . '.' . $shihab_sshihabb007_format;

        // mime map — WordPress needs this to accept non-standard uploads
        $shihab_sshihabb007_mime_map = [
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
        ];
        $shihab_sshihabb007_mime = $shihab_sshihabb007_mime_map[ $shihab_sshihabb007_format ] ?? 'image/webp';

        $shihab_sshihabb007_file_array = [
            'name'     => $shihab_sshihabb007_newname,
            'tmp_name' => $shihab_sshihabb007_tmp,
            'type'     => $shihab_sshihabb007_mime,
            'error'    => 0,
            'size'     => file_exists( $shihab_sshihabb007_tmp ) ? filesize( $shihab_sshihabb007_tmp ) : 0,
        ];

        // ── Temporarily allow WebP/AVIF uploads ─────────────────────────────
        add_filter( 'upload_mimes', [ $this, 'shihab_sshihabb007_allow_next_gen_mimes' ] );
        // ── Prevent auto-optimizer from re-processing this sideload ─────────
        add_filter( 'wp_generate_attachment_metadata', [ $this, 'shihab_sshihabb007_skip_auto_flag' ], 1, 2 );

        $shihab_sshihabb007_id = media_handle_sideload( $shihab_sshihabb007_file_array, 0, $shihab_sshihabb007_basename );

        remove_filter( 'upload_mimes', [ $this, 'shihab_sshihabb007_allow_next_gen_mimes' ] );
        remove_filter( 'wp_generate_attachment_metadata', [ $this, 'shihab_sshihabb007_skip_auto_flag' ], 1 );

        @unlink( $shihab_sshihabb007_tmp );

        if ( is_wp_error( $shihab_sshihabb007_id ) ) {
            if ( ob_get_level() ) ob_clean();
            wp_send_json_error( [ 'message' => 'Media library error: ' . $shihab_sshihabb007_id->get_error_message() ] );
        }

        // ── Save meta ────────────────────────────────────────────────────────
        if ( ! empty( $shihab_sshihabb007_alt_text ) ) {
            update_post_meta( $shihab_sshihabb007_id, '_wp_attachment_image_alt', $shihab_sshihabb007_alt_text );
        }
        update_post_meta( $shihab_sshihabb007_id, '_shihab_compressor_sshihabb007_optimized',      'php_fallback' );
        update_post_meta( $shihab_sshihabb007_id, '_shihab_compressor_sshihabb007_auto_done',       '1' );
        update_post_meta( $shihab_sshihabb007_id, '_shihab_compressor_sshihabb007_original_size',   $shihab_sshihabb007_orig_size );

        $shihab_sshihabb007_opt_size = filesize( get_attached_file( $shihab_sshihabb007_id ) ) ?: 0;
        $shihab_sshihabb007_saved    = max( 0, $shihab_sshihabb007_orig_size - $shihab_sshihabb007_opt_size );
        $shihab_sshihabb007_pct      = $shihab_sshihabb007_orig_size > 0
            ? round( ( $shihab_sshihabb007_saved / $shihab_sshihabb007_orig_size ) * 100, 1 ) : 0;

        update_post_meta( $shihab_sshihabb007_id, '_shihab_compressor_sshihabb007_savings_bytes', $shihab_sshihabb007_saved );

        // ── Increment global stats ───────────────────────────────────────────
        $shihab_st = get_option( 'shihab_compressor_sshihabb007_stats',
            [ 'total_images' => 0, 'total_saved' => 0, 'total_ai_tags' => 0 ] );
        $shihab_st['total_images']++;
        $shihab_st['total_saved'] += $shihab_sshihabb007_saved;
        if ( $shihab_sshihabb007_alt_text ) $shihab_st['total_ai_tags']++;
        update_option( 'shihab_compressor_sshihabb007_stats', $shihab_st );

        if ( ob_get_level() ) ob_clean();
        wp_send_json_success( [
            'attachment_id'  => $shihab_sshihabb007_id,
            'url'            => wp_get_attachment_url( $shihab_sshihabb007_id ),
            'original_size'  => $shihab_sshihabb007_orig_size,
            'optimized_size' => $shihab_sshihabb007_opt_size,
            'saved_bytes'    => $shihab_sshihabb007_saved,
            'saved_pct'      => $shihab_sshihabb007_pct,
            'engine'         => 'php_fallback',
            'author'         => 'MEHEDI HASAN SHIHAB sshihabb007',
        ] );
    }

    /** Allow WebP & AVIF in WordPress upload_mimes filter */
    public function shihab_sshihabb007_allow_next_gen_mimes( $mimes ) {
        $mimes['webp'] = 'image/webp';
        $mimes['avif'] = 'image/avif';
        return $mimes;
    }

    /** Mark attachment so auto-optimizer skips it (prevents re-compression loop) */
    public function shihab_sshihabb007_skip_auto_flag( $metadata, $attachment_id ) {
        update_post_meta( $attachment_id, '_shihab_compressor_sshihabb007_auto_done', '1' );
        return $metadata;
    }


    /**
     * Public wrapper: compress any file path directly (used by Directory Smush).
     *
     * @param string $shihab_path   Absolute file path.
     * @param int    $shihab_q      Quality 0-100.
     * @param string $shihab_fmt    'webp' or 'avif'.
     * @return array { success: bool, tmp_path: string }
     */
    public function shihab_sshihabb007_compress_file_direct( $shihab_path, $shihab_q, $shihab_fmt ) {
        $r = $this->shihab_sshihabb007_compress_gd( $shihab_path, $shihab_q, $shihab_fmt );
        if ( ! $r['success'] && extension_loaded( 'imagick' ) ) {
            $r = $this->shihab_sshihabb007_compress_imagick( $shihab_path, $shihab_q, $shihab_fmt );
        }
        return $r;
    }

    /**
     * Compress image using PHP GD library.
     *
     * @param string $shihab_sshihabb007_source  Path to source image.
     * @param int    $shihab_sshihabb007_quality  Quality 0-100.
     * @param string $shihab_sshihabb007_format   'webp' or 'avif'.
     * @return array { success: bool, tmp_path: string, error: string }
     */
    private function shihab_sshihabb007_compress_gd( $shihab_sshihabb007_source, $shihab_sshihabb007_quality, $shihab_sshihabb007_format ) {
        if ( ! extension_loaded( 'gd' ) ) {
            return [ 'success' => false, 'error' => 'GD not available.' ];
        }

        $shihab_sshihabb007_info = @getimagesize( $shihab_sshihabb007_source );
        if ( ! $shihab_sshihabb007_info ) {
            return [ 'success' => false, 'error' => 'Cannot read image info.' ];
        }

        switch ( $shihab_sshihabb007_info[2] ) {
            case IMAGETYPE_JPEG:
                $shihab_sshihabb007_img = @imagecreatefromjpeg( $shihab_sshihabb007_source );
                break;
            case IMAGETYPE_PNG:
                $shihab_sshihabb007_img = @imagecreatefrompng( $shihab_sshihabb007_source );
                break;
            case IMAGETYPE_WEBP:
                $shihab_sshihabb007_img = @imagecreatefromwebp( $shihab_sshihabb007_source );
                break;
            default:
                return [ 'success' => false, 'error' => 'Unsupported input format.' ];
        }

        if ( ! $shihab_sshihabb007_img ) {
            return [ 'success' => false, 'error' => 'Failed to create GD resource.' ];
        }

        // Smart resize: cap at 1920px width
        $shihab_sshihabb007_orig_w = imagesx( $shihab_sshihabb007_img );
        $shihab_sshihabb007_orig_h = imagesy( $shihab_sshihabb007_img );
        if ( $shihab_sshihabb007_orig_w > 1920 ) {
            $shihab_sshihabb007_new_w = 1920;
            $shihab_sshihabb007_new_h = intval( $shihab_sshihabb007_orig_h * ( 1920 / $shihab_sshihabb007_orig_w ) );
            $shihab_sshihabb007_resized = imagecreatetruecolor( $shihab_sshihabb007_new_w, $shihab_sshihabb007_new_h );
            imagealphablending( $shihab_sshihabb007_resized, false );
            imagesavealpha( $shihab_sshihabb007_resized, true );
            imagecopyresampled( $shihab_sshihabb007_resized, $shihab_sshihabb007_img, 0, 0, 0, 0, $shihab_sshihabb007_new_w, $shihab_sshihabb007_new_h, $shihab_sshihabb007_orig_w, $shihab_sshihabb007_orig_h );
            imagedestroy( $shihab_sshihabb007_img );
            $shihab_sshihabb007_img = $shihab_sshihabb007_resized;
        }

        $shihab_sshihabb007_tmp = sys_get_temp_dir() . '/shihab_compressor_' . uniqid() . '.' . $shihab_sshihabb007_format;

        if ( $shihab_sshihabb007_format === 'webp' && function_exists( 'imagewebp' ) ) {
            $shihab_sshihabb007_ok = @imagewebp( $shihab_sshihabb007_img, $shihab_sshihabb007_tmp, $shihab_sshihabb007_quality );
        } elseif ( $shihab_sshihabb007_format === 'avif' && function_exists( 'imageavif' ) ) {
            $shihab_sshihabb007_ok = @imageavif( $shihab_sshihabb007_img, $shihab_sshihabb007_tmp, $shihab_sshihabb007_quality );
        } else {
            imagedestroy( $shihab_sshihabb007_img );
            return [ 'success' => false, 'error' => 'GD does not support ' . $shihab_sshihabb007_format ];
        }

        imagedestroy( $shihab_sshihabb007_img );

        return $shihab_sshihabb007_ok
            ? [ 'success' => true, 'tmp_path' => $shihab_sshihabb007_tmp ]
            : [ 'success' => false, 'error' => 'GD save failed.' ];
    }

    /**
     * Compress image using Imagick.
     *
     * @param string $shihab_sshihabb007_source  Path to source image.
     * @param int    $shihab_sshihabb007_quality  Quality 0-100.
     * @param string $shihab_sshihabb007_format   'webp' or 'avif'.
     * @return array
     */
    private function shihab_sshihabb007_compress_imagick( $shihab_sshihabb007_source, $shihab_sshihabb007_quality, $shihab_sshihabb007_format ) {
        try {
            $shihab_sshihabb007_imagick = new Imagick( $shihab_sshihabb007_source );
            $shihab_sshihabb007_imagick->stripImage(); // Metadata strip
            $shihab_sshihabb007_imagick->setImageCompressionQuality( $shihab_sshihabb007_quality );

            // Smart resize
            $shihab_sshihabb007_geom = $shihab_sshihabb007_imagick->getImageGeometry();
            if ( $shihab_sshihabb007_geom['width'] > 1920 ) {
                $shihab_sshihabb007_imagick->resizeImage( 1920, 0, Imagick::FILTER_LANCZOS, 1 );
            }

            $shihab_sshihabb007_imagick->setFormat( strtoupper( $shihab_sshihabb007_format ) );
            $shihab_sshihabb007_tmp = sys_get_temp_dir() . '/shihab_compressor_' . uniqid() . '.' . $shihab_sshihabb007_format;
            $shihab_sshihabb007_imagick->writeImage( $shihab_sshihabb007_tmp );
            $shihab_sshihabb007_imagick->clear();
            $shihab_sshihabb007_imagick->destroy();
            return [ 'success' => true, 'tmp_path' => $shihab_sshihabb007_tmp ];
        } catch ( Exception $shihab_sshihabb007_e ) {
            return [ 'success' => false, 'error' => $shihab_sshihabb007_e->getMessage() ];
        }
    }
}


