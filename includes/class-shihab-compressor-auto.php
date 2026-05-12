<?php
/**
 * Auto-Optimizer — MHS Image Compressor
 *
 * Hooks into the WordPress media upload pipeline and automatically
 * converts + compresses every newly uploaded image to WebP/AVIF
 * using PHP GD or Imagick, then replaces the original file in-place.
 *
 * Hook used: wp_generate_attachment_metadata
 *   - Fires after WP has finished generating thumbnails.
 *   - Gives us the full file path + size data.
 *   - Safe to modify the original at this point.
 *
 * @package Shihab_Compressor
 * @author  Mehedi Shihab <sshihabb007>
 * @link    https://www.linkedin.com/in/mehedi-hasan-shihab/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Shihab_Compressor_Auto
 *
 * Automatic on-upload image optimization by Mehedi Shihab (sshihabb007).
 */
class Shihab_Compressor_Auto {

    /** @var array Plugin settings cache */
    private $shihab_sshihabb007_settings = [];

    /**
     * Boot: register WordPress hooks.
     */
    public function shihab_sshihabb007_init() {
        $this->shihab_sshihabb007_settings = get_option(
            'shihab_compressor_sshihabb007_settings',
            $this->shihab_sshihabb007_defaults()
        );

        // Only activate the hook when the feature is enabled in settings
        if ( ! empty( $this->shihab_sshihabb007_settings['auto_optimize'] ) ) {
            add_filter(
                'wp_generate_attachment_metadata',
                [ $this, 'shihab_sshihabb007_on_upload' ],
                20,
                2
            );
        }
    }

    /**
     * Default settings.
     *
     * @return array
     */
    private function shihab_sshihabb007_defaults() {
        return [
            'output_format'    => 'webp',
            'quality'          => 75,
            'smart_resize'     => true,
            'strip_metadata'   => true,
            'auto_optimize'    => true,
            'batch_concurrency'=> 3,
            'ai_alt_text'      => true,
            'indexeddb_cache'  => true,
        ];
    }

    /**
     * Fired by wp_generate_attachment_metadata.
     * Compresses the uploaded image and replaces the original on disk.
     *
     * @param array $shihab_sshihabb007_meta     Attachment metadata.
     * @param int   $shihab_sshihabb007_attach_id Attachment post ID.
     * @return array Modified (or original) metadata.
     */
    public function shihab_sshihabb007_on_upload( $shihab_sshihabb007_meta, $shihab_sshihabb007_attach_id ) {
        // Skip if already processed by this plugin
        if ( get_post_meta( $shihab_sshihabb007_attach_id, '_shihab_compressor_sshihabb007_auto_done', true ) ) {
            return $shihab_sshihabb007_meta;
        }

        $shihab_sshihabb007_file = get_attached_file( $shihab_sshihabb007_attach_id );
        if ( ! $shihab_sshihabb007_file || ! file_exists( $shihab_sshihabb007_file ) ) {
            return $shihab_sshihabb007_meta;
        }

        // Only handle actual images
        $shihab_sshihabb007_mime = get_post_mime_type( $shihab_sshihabb007_attach_id );
        $shihab_sshihabb007_allowed_mimes = [ 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ];
        if ( ! in_array( $shihab_sshihabb007_mime, $shihab_sshihabb007_allowed_mimes, true ) ) {
            return $shihab_sshihabb007_meta;
        }

        $shihab_sshihabb007_orig_size = filesize( $shihab_sshihabb007_file );
        $shihab_sshihabb007_format    = $this->shihab_sshihabb007_settings['output_format'] ?? 'webp';
        // If "both" is selected, default to webp for auto-optimize
        if ( $shihab_sshihabb007_format === 'both' ) {
            $shihab_sshihabb007_format = 'webp';
        }
        $shihab_sshihabb007_quality = intval( $this->shihab_sshihabb007_settings['quality'] ?? 75 );

        // --- Attempt GD compression ---
        $shihab_sshihabb007_result = $this->shihab_sshihabb007_compress_gd(
            $shihab_sshihabb007_file,
            $shihab_sshihabb007_quality,
            $shihab_sshihabb007_format
        );

        // --- Fallback to Imagick ---
        if ( ! $shihab_sshihabb007_result['success'] && extension_loaded( 'imagick' ) ) {
            $shihab_sshihabb007_result = $this->shihab_sshihabb007_compress_imagick(
                $shihab_sshihabb007_file,
                $shihab_sshihabb007_quality,
                $shihab_sshihabb007_format
            );
        }

        if ( ! $shihab_sshihabb007_result['success'] ) {
            // Log the failure but don't break the upload
            $this->shihab_sshihabb007_log( $shihab_sshihabb007_attach_id, 'failed', $shihab_sshihabb007_result['error'] ?? 'Unknown error', 0, 0 );
            return $shihab_sshihabb007_meta;
        }

        $shihab_sshihabb007_tmp = $shihab_sshihabb007_result['tmp_path'];

        // Build new filename with new extension
        $shihab_sshihabb007_info    = pathinfo( $shihab_sshihabb007_file );
        $shihab_sshihabb007_newfile = $shihab_sshihabb007_info['dirname'] . '/' . $shihab_sshihabb007_info['filename'] . '.' . $shihab_sshihabb007_format;

        // Move compressed temp file over original (or to new filename)
        if ( ! @rename( $shihab_sshihabb007_tmp, $shihab_sshihabb007_newfile ) ) {
            // rename failed — try copy + delete
            @copy( $shihab_sshihabb007_tmp, $shihab_sshihabb007_newfile );
            @unlink( $shihab_sshihabb007_tmp );
        }

        // If the format changed (e.g. jpg → webp), remove the old original
        if ( $shihab_sshihabb007_newfile !== $shihab_sshihabb007_file && file_exists( $shihab_sshihabb007_file ) ) {
            @unlink( $shihab_sshihabb007_file );
        }

        // Update WP's record of where the file lives
        update_attached_file( $shihab_sshihabb007_attach_id, $shihab_sshihabb007_newfile );

        // Update MIME type in wp_posts
        wp_update_post( [
            'ID'             => $shihab_sshihabb007_attach_id,
            'post_mime_type' => 'image/' . $shihab_sshihabb007_format,
        ] );

        // Recalculate metadata for new file
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $shihab_sshihabb007_opt_size = file_exists( $shihab_sshihabb007_newfile ) ? filesize( $shihab_sshihabb007_newfile ) : 0;
        $shihab_sshihabb007_saved    = max( 0, $shihab_sshihabb007_orig_size - $shihab_sshihabb007_opt_size );
        $shihab_sshihabb007_pct      = $shihab_sshihabb007_orig_size > 0
            ? round( ( $shihab_sshihabb007_saved / $shihab_sshihabb007_orig_size ) * 100, 1 )
            : 0;

        // Mark as done + store stats in post meta
        update_post_meta( $shihab_sshihabb007_attach_id, '_shihab_compressor_sshihabb007_auto_done',      '1' );
        update_post_meta( $shihab_sshihabb007_attach_id, '_shihab_compressor_sshihabb007_optimized',      'auto' );
        update_post_meta( $shihab_sshihabb007_attach_id, '_shihab_compressor_sshihabb007_format',         $shihab_sshihabb007_format );
        update_post_meta( $shihab_sshihabb007_attach_id, '_shihab_compressor_sshihabb007_original_size',  $shihab_sshihabb007_orig_size );
        update_post_meta( $shihab_sshihabb007_attach_id, '_shihab_compressor_sshihabb007_optimized_size', $shihab_sshihabb007_opt_size );
        update_post_meta( $shihab_sshihabb007_attach_id, '_shihab_compressor_sshihabb007_savings_bytes',  $shihab_sshihabb007_saved );
        update_post_meta( $shihab_sshihabb007_attach_id, '_shihab_compressor_sshihabb007_savings_pct',    $shihab_sshihabb007_pct );

        // Persist to global stats
        $this->shihab_sshihabb007_increment_stats( $shihab_sshihabb007_saved );

        // Write to auto-optimize log
        $this->shihab_sshihabb007_log(
            $shihab_sshihabb007_attach_id,
            'success',
            basename( $shihab_sshihabb007_newfile ),
            $shihab_sshihabb007_orig_size,
            $shihab_sshihabb007_opt_size
        );

        // Rebuild attachment metadata with new file path
        $shihab_sshihabb007_new_meta = wp_generate_attachment_metadata( $shihab_sshihabb007_attach_id, $shihab_sshihabb007_newfile );
        if ( ! empty( $shihab_sshihabb007_new_meta ) ) {
            wp_update_attachment_metadata( $shihab_sshihabb007_attach_id, $shihab_sshihabb007_new_meta );
            return $shihab_sshihabb007_new_meta;
        }

        return $shihab_sshihabb007_meta;
    }

    /**
     * Compress via PHP GD.
     *
     * @param string $shihab_src    Source file path.
     * @param int    $shihab_q      Quality 0-100.
     * @param string $shihab_fmt    'webp' or 'avif'.
     * @return array
     */
    private function shihab_sshihabb007_compress_gd( $shihab_src, $shihab_q, $shihab_fmt ) {
        if ( ! extension_loaded( 'gd' ) ) {
            return [ 'success' => false, 'error' => 'GD not loaded.' ];
        }

        $shihab_info = @getimagesize( $shihab_src );
        if ( ! $shihab_info ) {
            return [ 'success' => false, 'error' => 'Cannot read image.' ];
        }

        switch ( $shihab_info[2] ) {
            case IMAGETYPE_JPEG:
                $shihab_img = @imagecreatefromjpeg( $shihab_src ); break;
            case IMAGETYPE_PNG:
                $shihab_img = @imagecreatefrompng( $shihab_src );  break;
            case IMAGETYPE_WEBP:
                $shihab_img = @imagecreatefromwebp( $shihab_src ); break;
            case IMAGETYPE_GIF:
                $shihab_img = @imagecreatefromgif( $shihab_src );  break;
            default:
                return [ 'success' => false, 'error' => 'Unsupported type.' ];
        }

        if ( ! $shihab_img ) {
            return [ 'success' => false, 'error' => 'GD resource failed.' ];
        }

        // Smart resize
        if ( ! empty( $this->shihab_sshihabb007_settings['smart_resize'] ) ) {
            $shihab_w = imagesx( $shihab_img );
            $shihab_h = imagesy( $shihab_img );
            if ( $shihab_w > 1920 ) {
                $shihab_nw = 1920;
                $shihab_nh = intval( $shihab_h * ( 1920 / $shihab_w ) );
                $shihab_rs = imagecreatetruecolor( $shihab_nw, $shihab_nh );
                imagealphablending( $shihab_rs, false );
                imagesavealpha( $shihab_rs, true );
                imagecopyresampled( $shihab_rs, $shihab_img, 0, 0, 0, 0, $shihab_nw, $shihab_nh, $shihab_w, $shihab_h );
                imagedestroy( $shihab_img );
                $shihab_img = $shihab_rs;
            }
        }

        $shihab_tmp = sys_get_temp_dir() . '/shihab_auto_' . uniqid() . '.' . $shihab_fmt;

        if ( $shihab_fmt === 'webp' && function_exists( 'imagewebp' ) ) {
            $shihab_ok = @imagewebp( $shihab_img, $shihab_tmp, $shihab_q );
        } elseif ( $shihab_fmt === 'avif' && function_exists( 'imageavif' ) ) {
            $shihab_ok = @imageavif( $shihab_img, $shihab_tmp, $shihab_q );
        } else {
            imagedestroy( $shihab_img );
            return [ 'success' => false, 'error' => 'GD does not support ' . $shihab_fmt ];
        }

        imagedestroy( $shihab_img );

        return $shihab_ok
            ? [ 'success' => true, 'tmp_path' => $shihab_tmp ]
            : [ 'success' => false, 'error'   => 'GD write failed.' ];
    }

    /**
     * Compress via Imagick.
     *
     * @param string $shihab_src  Source file path.
     * @param int    $shihab_q    Quality 0-100.
     * @param string $shihab_fmt  'webp' or 'avif'.
     * @return array
     */
    private function shihab_sshihabb007_compress_imagick( $shihab_src, $shihab_q, $shihab_fmt ) {
        try {
            $shihab_im = new Imagick( $shihab_src );

            if ( ! empty( $this->shihab_sshihabb007_settings['strip_metadata'] ) ) {
                $shihab_im->stripImage();
            }

            $shihab_im->setImageCompressionQuality( $shihab_q );

            if ( ! empty( $this->shihab_sshihabb007_settings['smart_resize'] ) ) {
                $shihab_geom = $shihab_im->getImageGeometry();
                if ( $shihab_geom['width'] > 1920 ) {
                    $shihab_im->resizeImage( 1920, 0, Imagick::FILTER_LANCZOS, 1 );
                }
            }

            $shihab_im->setFormat( strtoupper( $shihab_fmt ) );
            $shihab_tmp = sys_get_temp_dir() . '/shihab_auto_' . uniqid() . '.' . $shihab_fmt;
            $shihab_im->writeImage( $shihab_tmp );
            $shihab_im->clear();
            $shihab_im->destroy();

            return [ 'success' => true, 'tmp_path' => $shihab_tmp ];
        } catch ( Exception $shihab_e ) {
            return [ 'success' => false, 'error' => $shihab_e->getMessage() ];
        }
    }

    /**
     * Increment global plugin stats after auto-optimizing one image.
     *
     * @param int $shihab_saved_bytes Bytes saved.
     */
    private function shihab_sshihabb007_increment_stats( $shihab_saved_bytes ) {
        $shihab_stats = get_option( 'shihab_compressor_sshihabb007_stats', [
            'total_images'  => 0,
            'total_saved'   => 0,
            'total_ai_tags' => 0,
        ] );
        $shihab_stats['total_images']++;
        $shihab_stats['total_saved'] += max( 0, $shihab_saved_bytes );
        update_option( 'shihab_compressor_sshihabb007_stats', $shihab_stats );
    }

    /**
     * Append one entry to the auto-optimize activity log (last 50 entries).
     *
     * @param int    $shihab_id        Attachment ID.
     * @param string $shihab_status    'success' | 'failed'.
     * @param string $shihab_filename  File name or error message.
     * @param int    $shihab_orig      Original size in bytes.
     * @param int    $shihab_opt       Optimized size in bytes.
     */
    private function shihab_sshihabb007_log( $shihab_id, $shihab_status, $shihab_filename, $shihab_orig, $shihab_opt ) {
        $shihab_log   = get_option( 'shihab_compressor_sshihabb007_auto_log', [] );
        $shihab_saved = max( 0, $shihab_orig - $shihab_opt );
        $shihab_pct   = $shihab_orig > 0 ? round( ( $shihab_saved / $shihab_orig ) * 100, 1 ) : 0;

        array_unshift( $shihab_log, [
            'id'        => $shihab_id,
            'status'    => $shihab_status,
            'file'      => $shihab_filename,
            'orig'      => $shihab_orig,
            'opt'       => $shihab_opt,
            'saved'     => $shihab_saved,
            'pct'       => $shihab_pct,
            'timestamp' => current_time( 'timestamp' ),
        ] );

        // Keep only the latest 50 entries
        $shihab_log = array_slice( $shihab_log, 0, 50 );
        update_option( 'shihab_compressor_sshihabb007_auto_log', $shihab_log );
    }
}
