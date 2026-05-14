<?php
/**
 * Dynamic Pipeline — MHS Image Compressor
 * On-the-fly resizing, AVIF/WebP/JPEG format switching, srcset injection.
 *
 * @package Shihab_Compressor
 * @author  MEHEDI HASAN SHIHAB <sshihabb007>
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Shihab_Compressor_Dynamic {

    private $shihab_sshihabb007_settings  = [];
    private $shihab_sshihabb007_cache_dir = '';

    public function shihab_sshihabb007_init() {
        $this->shihab_sshihabb007_settings  = get_option( 'shihab_compressor_sshihabb007_settings', [] );
        $upload = wp_upload_dir();
        $this->shihab_sshihabb007_cache_dir = trailingslashit( $upload['basedir'] ) . 'mhs-cache/';
        wp_mkdir_p( $this->shihab_sshihabb007_cache_dir );

        // Always inject lazy-loading & async decoding
        add_filter( 'wp_get_attachment_image_attributes', [ $this, 'shihab_sshihabb007_lazy_attrs' ], 10, 3 );

        if ( ! empty( $this->shihab_sshihabb007_settings['dynamic_delivery'] ) ) {
            add_action( 'init',              [ $this, 'shihab_sshihabb007_register_rewrite' ] );
            add_filter( 'query_vars',        [ $this, 'shihab_sshihabb007_query_vars' ] );
            add_action( 'template_redirect', [ $this, 'shihab_sshihabb007_serve_dynamic' ] );
            add_filter( 'wp_calculate_image_srcset', [ $this, 'shihab_sshihabb007_srcset' ], 10, 5 );
        }
    }

    /* ── Rewrite ── */
    public function shihab_sshihabb007_register_rewrite() {
        add_rewrite_rule(
            '^mhs-img/([0-9]+)/([0-9]+)/([0-9]+)/?$',
            'index.php?shihab_mhs_id=$matches[1]&shihab_mhs_w=$matches[2]&shihab_mhs_h=$matches[3]',
            'top'
        );
    }
    public function shihab_sshihabb007_query_vars( $v ) {
        return array_merge( $v, [ 'shihab_mhs_id', 'shihab_mhs_w', 'shihab_mhs_h' ] );
    }

    /* ── Serve dynamic image ── */
    public function shihab_sshihabb007_serve_dynamic() {
        $id = intval( get_query_var( 'shihab_mhs_id' ) );
        if ( ! $id ) return;

        $w      = intval( get_query_var( 'shihab_mhs_w' ) );
        $h      = intval( get_query_var( 'shihab_mhs_h' ) );
        $fmt    = $this->shihab_sshihabb007_detect_format();
        $q      = intval( $this->shihab_sshihabb007_settings['quality'] ?? 75 );
        $file   = get_attached_file( $id );

        if ( ! $file || ! file_exists( $file ) ) { status_header( 404 ); exit; }

        $cache_key  = "mhs_{$id}_{$w}_{$h}_{$q}_{$fmt}";
        $cache_file = $this->shihab_sshihabb007_cache_dir . $cache_key . '.' . $fmt;

        if ( ! file_exists( $cache_file ) ) {
            $r = $this->shihab_sshihabb007_resize( $file, $w, $h, $q, $fmt );
            if ( ! $r['success'] ) { status_header( 500 ); exit; }
            rename( $r['tmp'], $cache_file );
        }

        header( 'Content-Type: image/' . $fmt );
        header( 'Cache-Control: public, max-age=31536000, immutable' );
        header( 'X-MHS-Dynamic: 1' );
        header( 'X-MHS-Format: ' . strtoupper( $fmt ) );
        readfile( $cache_file );
        exit;
    }

    /* ── Format detection ── */
    public function shihab_sshihabb007_detect_format() {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if ( strpos( $accept, 'image/avif' ) !== false && function_exists( 'imageavif' ) ) return 'avif';
        if ( strpos( $accept, 'image/webp' ) !== false && function_exists( 'imagewebp' ) ) return 'webp';
        return 'jpeg';
    }

    /* ── srcset injection ── */
    public function shihab_sshihabb007_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
        if ( empty( $sources ) ) return $sources;
        foreach ( $sources as $w => &$src ) {
            $src['url'] = home_url( "/mhs-img/{$attachment_id}/{$w}/0" );
        }
        return $sources;
    }

    /* ── Lazy loading attrs ── */
    public function shihab_sshihabb007_lazy_attrs( $attr, $attachment, $size ) {
        $attr['loading']  = $attr['loading']  ?? 'lazy';
        $attr['decoding'] = $attr['decoding'] ?? 'async';
        return $attr;
    }

    /* ── GD resize + convert ── */
    private function shihab_sshihabb007_resize( $src, $w, $h, $q, $fmt ) {
        if ( ! extension_loaded( 'gd' ) ) return [ 'success' => false ];
        $info = @getimagesize( $src );
        if ( ! $info ) return [ 'success' => false ];

        switch ( $info[2] ) {
            case IMAGETYPE_JPEG: $img = @imagecreatefromjpeg( $src ); break;
            case IMAGETYPE_PNG:  $img = @imagecreatefrompng( $src );  break;
            case IMAGETYPE_WEBP: $img = @imagecreatefromwebp( $src ); break;
            case IMAGETYPE_GIF:  $img = @imagecreatefromgif( $src );  break;
            default: return [ 'success' => false ];
        }
        if ( ! $img ) return [ 'success' => false ];

        $ow = imagesx( $img ); $oh = imagesy( $img );
        if ( $w && ! $h ) $h = intval( $oh * ( $w / $ow ) );
        if ( $h && ! $w ) $w = intval( $ow * ( $h / $oh ) );
        if ( ! $w )       { $w = $ow; $h = $oh; }

        $rs = imagecreatetruecolor( $w, $h );
        imagealphablending( $rs, false ); imagesavealpha( $rs, true );
        imagecopyresampled( $rs, $img, 0,0,0,0, $w,$h,$ow,$oh );
        imagedestroy( $img );

        $tmp = sys_get_temp_dir() . '/shihab_dyn_' . uniqid() . '.' . $fmt;
        if      ( $fmt === 'avif' && function_exists( 'imageavif' ) ) $ok = @imageavif( $rs, $tmp, $q );
        elseif  ( $fmt === 'webp' && function_exists( 'imagewebp' ) ) $ok = @imagewebp( $rs, $tmp, $q );
        else                                                           $ok = @imagejpeg( $rs, $tmp, $q );
        imagedestroy( $rs );

        return $ok ? [ 'success' => true, 'tmp' => $tmp ] : [ 'success' => false ];
    }

    /* ── Flush dynamic cache ── */
    public function shihab_sshihabb007_flush_cache() {
        $files = glob( $this->shihab_sshihabb007_cache_dir . 'mhs_*' );
        if ( $files ) array_map( 'unlink', $files );
        return count( $files ?: [] );
    }
}

