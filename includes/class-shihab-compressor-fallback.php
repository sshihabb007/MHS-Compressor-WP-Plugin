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
        if ( empty( $_FILES['image'] ) ) {
            wp_send_json_error( [ 'message' => 'No file received — sshihabb007 fallback.' ] );
        }

        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if ( ! function_exists( 'media_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $shihab_sshihabb007_quality   = intval( $_POST['quality'] ?? 75 );
        $shihab_sshihabb007_format    = sanitize_text_field( $_POST['format'] ?? 'webp' );
        $shihab_sshihabb007_alt_text  = sanitize_text_field( $_POST['alt_text'] ?? '' );
        $shihab_sshihabb007_orig_size = intval( $_FILES['image']['size'] ?? 0 );

        // Try GD first, then Imagick
        $shihab_sshihabb007_result = $this->shihab_sshihabb007_compress_gd(
            $_FILES['image']['tmp_name'],
            $shihab_sshihabb007_quality,
            $shihab_sshihabb007_format
        );

        if ( ! $shihab_sshihabb007_result['success'] && extension_loaded( 'imagick' ) ) {
            $shihab_sshihabb007_result = $this->shihab_sshihabb007_compress_imagick(
                $_FILES['image']['tmp_name'],
                $shihab_sshihabb007_quality,
                $shihab_sshihabb007_format
            );
        }

        if ( ! $shihab_sshihabb007_result['success'] ) {
            wp_send_json_error( [ 'message' => 'PHP compression failed: ' . $shihab_sshihabb007_result['error'] ] );
        }

        // Save to WP Media Library
        $shihab_sshihabb007_tmp_path  = $shihab_sshihabb007_result['tmp_path'];
        $shihab_sshihabb007_basename  = pathinfo( sanitize_file_name( $_FILES['image']['name'] ), PATHINFO_FILENAME );
        $shihab_sshihabb007_newname   = $shihab_sshihabb007_basename . '.' . $shihab_sshihabb007_format;

        $shihab_sshihabb007_file_array = [
            'name'     => $shihab_sshihabb007_newname,
            'tmp_name' => $shihab_sshihabb007_tmp_path,
        ];

        $shihab_sshihabb007_id = media_handle_sideload( $shihab_sshihabb007_file_array, 0, $shihab_sshihabb007_basename );

        if ( is_wp_error( $shihab_sshihabb007_id ) ) {
            @unlink( $shihab_sshihabb007_tmp_path );
            wp_send_json_error( [ 'message' => $shihab_sshihabb007_id->get_error_message() ] );
        }

        if ( ! empty( $shihab_sshihabb007_alt_text ) ) {
            update_post_meta( $shihab_sshihabb007_id, '_wp_attachment_image_alt', $shihab_sshihabb007_alt_text );
        }
        update_post_meta( $shihab_sshihabb007_id, '_shihab_compressor_sshihabb007_optimized', 'php_fallback' );

        $shihab_sshihabb007_opt_size   = filesize( get_attached_file( $shihab_sshihabb007_id ) ) ?: 0;
        $shihab_sshihabb007_saved      = max( 0, $shihab_sshihabb007_orig_size - $shihab_sshihabb007_opt_size );

        @unlink( $shihab_sshihabb007_tmp_path );

        wp_send_json_success( [
            'attachment_id'  => $shihab_sshihabb007_id,
            'url'            => wp_get_attachment_url( $shihab_sshihabb007_id ),
            'saved_bytes'    => $shihab_sshihabb007_saved,
            'engine'         => 'php_fallback',
            'author' => 'MEHEDI HASAN SHIHAB sshihabb007',
        ] );
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


