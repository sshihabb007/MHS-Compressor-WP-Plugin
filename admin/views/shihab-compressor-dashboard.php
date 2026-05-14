<?php
/**
 * Dashboard View — MHS Image Compressor
 * Author: MEHEDI HASAN SHIHAB HASAN SHIHAB (sshihabb007)
 * URL: https://mehedi-hasan-shihab.netlify.app/
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$shihab_fmt     = $shihab_sshihabb007_settings['output_format']     ?? 'webp';
$shihab_qual    = $shihab_sshihabb007_settings['quality']            ?? 75;
$shihab_resize  = $shihab_sshihabb007_settings['smart_resize']       ?? true;
$shihab_strip   = $shihab_sshihabb007_settings['strip_metadata']     ?? true;
$shihab_batch   = $shihab_sshihabb007_settings['batch_concurrency']  ?? 3;
$shihab_ai      = $shihab_sshihabb007_settings['ai_alt_text']        ?? true;
$shihab_idb     = $shihab_sshihabb007_settings['indexeddb_cache']    ?? true;
$shihab_auto    = $shihab_sshihabb007_settings['auto_optimize']      ?? true;
$shihab_auto_log = get_option( 'shihab_compressor_sshihabb007_auto_log', [] );

$shihab_total_imgs  = $shihab_sshihabb007_stats['total_images']   ?? 0;
$shihab_total_saved = $shihab_sshihabb007_stats['total_saved']    ?? 0;
$shihab_total_ai    = $shihab_sshihabb007_stats['total_ai_tags']  ?? 0;

// Format saved bytes
$shihab_saved_label = $shihab_total_saved >= 1048576
    ? round( $shihab_total_saved / 1048576, 1 ) . ' MB'
    : round( $shihab_total_saved / 1024, 1 ) . ' KB';
?>
<div id="shihab-compressor-app">

  <!-- ── Header ─────────────────────────────────────────────── -->
  <div id="shihab-compressor-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#818cf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
      </svg>
      <div>
        <h1 style="font-size:1.5rem;margin:0;">MHS Compressor <span class="shihab-compressor-badge">v<?php echo SHIHAB_COMPRESSOR_VERSION; ?></span></h1>
        <p style="margin:0;font-size:0.72rem;color:#6366f1;font-family:'JetBrains Mono',monospace;">by MEHEDI HASAN SHIHAB · sshihabb007</p>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:12px;">
      <span id="shihab-compressor-engine-status" style="font-size:0.75rem;font-family:'JetBrains Mono',monospace;color:#fbbf24;display:flex;align-items:center;gap:6px;">
        <span style="width:8px;height:8px;border-radius:50%;background:#fbbf24;display:inline-block;animation:shihab-pulse 1.5s infinite;"></span>
        Warming up engine...
      </span>
      <a href="https://mehedi-hasan-shihab.netlify.app/" target="_blank" style="font-size:0.75rem;color:#6366f1;text-decoration:none;border:1px solid rgba(99,102,241,0.3);border-radius:50px;padding:4px 14px;transition:all 0.25s;" onmouseover="this.style.borderColor='#818cf8'" onmouseout="this.style.borderColor='rgba(99,102,241,0.3)'">
        Author ↗
      </a>
    </div>
  </div>

  <!-- ── Engine Loader ──────────────────────────────────────── -->
  <div id="shihab-compressor-engine-loader">
    <div class="shihab-loader-label">
      <span id="shihab-engine-label">⚡ Initialising FFmpeg.wasm core…</span>
      <span id="shihab-engine-pct" style="font-weight:700;">0%</span>
    </div>
    <div class="shihab-loader-bar-track">
      <div id="shihab-compressor-engine-bar"></div>
    </div>
  </div>

  <!-- ── Analytics Strip ────────────────────────────────────── -->
  <div id="shihab-compressor-analytics">
    <div class="shihab-compressor-stat-card">
      <div class="shihab-compressor-stat-label">💾 Space Saved</div>
      <div class="shihab-compressor-stat-value">
        <span id="shihab-stat-saved"><?php echo esc_html( $shihab_saved_label ); ?></span>
      </div>
    </div>
    <div class="shihab-compressor-stat-card">
      <div class="shihab-compressor-stat-label">🖼 Images Processed</div>
      <div class="shihab-compressor-stat-value">
        <span id="shihab-stat-images"><?php echo esc_html( $shihab_total_imgs ); ?></span>
      </div>
    </div>
    <div class="shihab-compressor-stat-card">
      <div class="shihab-compressor-stat-label">🤖 AI Tags Generated</div>
      <div class="shihab-compressor-stat-value">
        <span id="shihab-stat-ai"><?php echo esc_html( $shihab_total_ai ); ?></span>
      </div>
    </div>
  </div>

  <!-- ── Drop Zone ──────────────────────────────────────────── -->
  <div id="shihab-compressor-dropzone" role="button" tabindex="0" aria-label="Drop images here or click to browse">
    <svg class="shihab-compressor-drop-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
      <polyline points="17 8 12 3 7 8"/>
      <line x1="12" y1="3" x2="12" y2="15"/>
    </svg>
    <div class="shihab-compressor-drop-title">Drop images here</div>
    <div class="shihab-compressor-drop-sub">WebP · AVIF · PNG · JPEG · GIF — up to 50 files</div>
    <button id="shihab-compressor-browse-btn" type="button">Browse Files</button>
    <input type="file" id="shihab-compressor-file-input" multiple accept="image/*">
    <div id="shihab-compressor-selected-count" style="margin-top:16px;font-size:0.8rem;color:#4ade80;font-family:'JetBrains Mono',monospace;min-height:20px;"></div>
  </div>

  <!-- ── Settings Grid ──────────────────────────────────────── -->
  <div id="shihab-compressor-settings">

    <!-- Output Format -->
    <div class="shihab-compressor-setting-card">
      <span class="shihab-setting-label">Output Format</span>
      <div class="shihab-format-group">
        <button class="shihab-format-btn <?php echo $shihab_fmt === 'webp' ? 'shihab-active' : ''; ?>" id="shihab-fmt-webp" data-shihab-fmt="webp">WebP</button>
        <button class="shihab-format-btn <?php echo $shihab_fmt === 'avif' ? 'shihab-active' : ''; ?>" id="shihab-fmt-avif" data-shihab-fmt="avif">AVIF</button>
        <button class="shihab-format-btn <?php echo $shihab_fmt === 'both' ? 'shihab-active' : ''; ?>" id="shihab-fmt-both" data-shihab-fmt="both">Both</button>
      </div>
    </div>

    <!-- Quality -->
    <div class="shihab-compressor-setting-card">
      <span class="shihab-setting-label">Quality — <span id="shihab-qual-val"><?php echo intval($shihab_qual); ?></span>/100</span>
      <input type="range" class="shihab-range" id="shihab-compressor-quality"
             min="10" max="100" value="<?php echo intval($shihab_qual); ?>"
             style="--val:<?php echo intval($shihab_qual); ?>%">
    </div>

    <!-- Smart Resize -->
    <div class="shihab-compressor-setting-card">
      <span class="shihab-setting-label">Smart Resize ≤ 1920px</span>
      <label class="shihab-toggle">
        <input type="checkbox" id="shihab-toggle-resize" <?php checked( $shihab_resize, true ); ?>>
        <span class="shihab-toggle-track"></span>
        <span class="shihab-toggle-text">Auto-downscale 4K/HD images</span>
      </label>
    </div>

    <!-- Strip Metadata -->
    <div class="shihab-compressor-setting-card">
      <span class="shihab-setting-label">Strip Metadata</span>
      <label class="shihab-toggle">
        <input type="checkbox" id="shihab-toggle-strip" <?php checked( $shihab_strip, true ); ?>>
        <span class="shihab-toggle-track"></span>
        <span class="shihab-toggle-text">Remove GPS &amp; EXIF data</span>
      </label>
    </div>

    <!-- AI Alt Text -->
    <div class="shihab-compressor-setting-card">
      <span class="shihab-setting-label">🤖 AI Alt-Text</span>
      <label class="shihab-toggle">
        <input type="checkbox" id="shihab-toggle-ai" <?php checked( $shihab_ai, true ); ?>>
        <span class="shihab-toggle-track"></span>
        <span class="shihab-toggle-text">Auto-generate captions (Transformers.js)</span>
      </label>
    </div>

    <!-- Batch Concurrency -->
    <div class="shihab-compressor-setting-card">
      <span class="shihab-setting-label">Batch Concurrency</span>
      <div style="display:flex;align-items:center;gap:12px;">
        <input type="number" class="shihab-number-input" id="shihab-batch-concurrency" min="1" max="10" value="<?php echo intval($shihab_batch); ?>">
        <span style="font-size:0.8rem;color:#6366f1;">images at once</span>
      </div>
    </div>

    <!-- Auto-Optimize on Upload -->
    <div class="shihab-compressor-setting-card" style="border:1px solid rgba(74,222,128,0.25);background:rgba(74,222,128,0.04);">
      <span class="shihab-setting-label" style="color:#4ade80;">⚡ Auto-Optimize on Upload</span>
      <label class="shihab-toggle">
        <input type="checkbox" id="shihab-toggle-auto" <?php checked( $shihab_auto, true ); ?>>
        <span class="shihab-toggle-track"></span>
        <span class="shihab-toggle-text">Automatically compress every new image added to the Media Library</span>
      </label>
    </div>

  </div>

  <!-- ── Action Bar ──────────────────────────────────────────── -->
  <div id="shihab-compressor-action-bar">
    <button id="shihab-compressor-process-btn" type="button" disabled>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
      Compress &amp; Upload
    </button>
    <button id="shihab-compressor-clear-btn" type="button">Clear Queue</button>
    <div style="flex:1;"></div>
    <button id="shihab-compressor-save-btn" type="button">💾 Save Settings</button>
  </div>

  <!-- ── Neural Feed ─────────────────────────────────────────── -->
  <div id="shihab-compressor-feed">
    <div class="shihab-feed-header">
      <span class="shihab-feed-dot"></span>
      Neural Feed — Live Processing Log
    </div>
    <div id="shihab-compressor-feed-list">
      <div id="shihab-compressor-feed-empty">No images processed yet. Drop files above to begin.</div>
    </div>
  </div>

  <!-- ── Auto-Optimize Activity Log ──────────────────────────── -->
  <div id="shihab-auto-log-section" style="margin-top:24px;">
    <div class="shihab-feed-header" style="border-color:rgba(74,222,128,0.3);">
      <span class="shihab-feed-dot" style="background:#4ade80;box-shadow:0 0 8px #4ade80;"></span>
      Auto-Optimize Log — On-Upload Activity
      <span style="margin-left:auto;font-size:0.7rem;color:#6b7280;">Last <?php echo count($shihab_auto_log); ?> uploads</span>
    </div>
    <div id="shihab-auto-log-list" style="padding:8px 0;">
      <?php if ( empty( $shihab_auto_log ) ) : ?>
        <div style="text-align:center;color:#4b5563;font-style:italic;padding:20px;font-size:0.82rem;"
          id="shihab-auto-log-empty">No auto-optimized images yet. Upload any image to WordPress and it will appear here.</div>
      <?php else : ?>
        <?php foreach ( $shihab_auto_log as $shihab_entry ) :
          $shihab_ok   = $shihab_entry['status'] === 'success';
          $shihab_icon = $shihab_ok ? '✅' : '❌';
          $shihab_orig_f = $shihab_entry['orig'] >= 1048576
            ? round($shihab_entry['orig']/1048576,1).'MB'
            : round($shihab_entry['orig']/1024,1).'KB';
          $shihab_opt_f = $shihab_entry['opt'] >= 1048576
            ? round($shihab_entry['opt']/1048576,1).'MB'
            : round($shihab_entry['opt']/1024,1).'KB';
          $shihab_ts = human_time_diff( $shihab_entry['timestamp'], current_time('timestamp') ) . ' ago';
        ?>
        <div class="shihab-feed-row" style="display:flex;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid rgba(255,255,255,0.04);">
          <span style="font-size:1rem;"><?php echo $shihab_icon; ?></span>
          <span style="flex:1;font-size:0.82rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo esc_attr($shihab_entry['file']); ?>">
            <?php echo esc_html( $shihab_entry['file'] ); ?>
          </span>
          <?php if ($shihab_ok) : ?>
          <span style="font-size:0.75rem;color:#9ca3af;"><?php echo esc_html($shihab_orig_f); ?> &rarr; <?php echo esc_html($shihab_opt_f); ?></span>
          <span style="font-size:0.75rem;font-weight:700;color:<?php echo $shihab_entry['pct']>=30?'#4ade80':($shihab_entry['pct']>=10?'#facc15':'#f87171'); ?>;">-<?php echo $shihab_entry['pct']; ?>%</span>
          <?php endif; ?>
          <span style="font-size:0.7rem;color:#6b7280;white-space:nowrap;"><?php echo esc_html($shihab_ts); ?></span>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── Footer ─────────────────────────────────────────────── -->
  <div id="shihab-compressor-footer">
    ⚡ MHS Image Compressor v<?php echo SHIHAB_COMPRESSOR_VERSION; ?> &nbsp;·&nbsp;
    by <a href="https://mehedi-hasan-shihab.netlify.app/" target="_blank">MEHEDI HASAN SHIHAB</a> &nbsp;·&nbsp;
    <code>sshihabb007</code> &nbsp;·&nbsp;
    Engine: FFmpeg.wasm + Transformers.js + Dexie.js
  </div>

</div><!-- /#shihab-compressor-app -->


