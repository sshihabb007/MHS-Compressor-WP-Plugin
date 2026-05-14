/**
 * MHS Image Compressor â€” Core Client-Side Engine
 * Author: MEHEDI HASAN SHIHAB HASAN SHIHAB (sshihabb007)
 * URL: https://mehedi-hasan-shihab.netlify.app/
 *
 * Engine stack:
 *  - FFmpeg.wasm  â†’ WebP/AVIF conversion
 *  - Transformers.js â†’ AI alt-text captioning
 *  - Dexie.js     â†’ IndexedDB caching
 */

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   SECTION 1 â€” Service Worker Registration (COI)
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
(function shihabSshihabb007RegisterSW() {
  if ('serviceWorker' in navigator && shihabCompressorData?.swUrl) {
    navigator.serviceWorker.register(shihabCompressorData.swUrl)
      .then(reg => console.log('[shihab-compressor] COI SW registered:', reg.scope))
      .catch(err => console.warn('[shihab-compressor] SW registration failed:', err));
  }
})();

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   SECTION 2 â€” Dexie.js IndexedDB Cache (shihabCompressorDB)
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
const shihabCompressorDB = new Dexie('shihabCompressorCache');
shihabCompressorDB.version(1).stores({
  images: '++id, originalHash, format, timestamp'
});

/**
 * Generate a SHA-256 hash string from an ArrayBuffer.
 * Used as cache key to detect duplicate images.
 * @param {ArrayBuffer} shihabBuffer
 * @returns {Promise<string>}
 */
async function shihabSshihabb007HashBuffer(shihabBuffer) {
  const shihabHashBuf = await crypto.subtle.digest('SHA-256', shihabBuffer);
  return Array.from(new Uint8Array(shihabHashBuf))
    .map(b => b.toString(16).padStart(2, '0'))
    .join('');
}

/**
 * Check IndexedDB for a cached result.
 * @param {string} shihabHash
 * @param {string} shihabFormat
 * @returns {Promise<object|null>}
 */
async function shihabSshihabb007GetCached(shihabHash, shihabFormat) {
  try {
    return await shihabCompressorDB.images
      .where({ originalHash: shihabHash, format: shihabFormat })
      .first() || null;
  } catch { return null; }
}

/**
 * Save a result to IndexedDB cache.
 * @param {object} shihabEntry
 */
async function shihabSshihabb007SetCache(shihabEntry) {
  try { await shihabCompressorDB.images.add(shihabEntry); }
  catch (e) { console.warn('[shihab-compressor] IndexedDB write error:', e); }
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   SECTION 3 â€” FFmpeg.wasm Engine
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
let shihabFFmpeg = null;
let shihabFFmpegReady = false;

const SHIHAB_FFMPEG_CDN = 'https://cdn.jsdelivr.net/npm/@ffmpeg/ffmpeg@0.12.10/dist/umd/ffmpeg.min.js';
const SHIHAB_CORE_CDN   = 'https://cdn.jsdelivr.net/npm/@ffmpeg/core@0.12.6/dist/umd/';

/**
 * Dynamically load FFmpeg.wasm and initialise the engine.
 * Reports progress to the engine loader bar.
 */
async function shihabSshihabb007LoadFFmpeg() {
  shihabSetEngineLabel('âš¡ Loading FFmpeg.wasmâ€¦');
  shihabSetEngineBar(5);

  // Load FFmpeg script dynamically
  await new Promise((resolve, reject) => {
    if (window.FFmpegWASM) return resolve();
    const shihabScript = document.createElement('script');
    shihabScript.src = SHIHAB_FFMPEG_CDN;
    shihabScript.onload  = resolve;
    shihabScript.onerror = reject;
    document.head.appendChild(shihabScript);
  });

  shihabSetEngineBar(20);
  shihabSetEngineLabel('âš¡ Initialising coreâ€¦');

  const { FFmpeg } = window.FFmpegWASM || window;
  shihabFFmpeg = new FFmpeg();

  shihabFFmpeg.on('log', ({ message: shihabMsg }) => {
    console.debug('[shihab-ffmpeg]', shihabMsg);
  });
  shihabFFmpeg.on('progress', ({ progress: shihabProg }) => {
    // per-file progress: 20â€“80% range during load
    const shihabPct = 20 + Math.round(shihabProg * 60);
    shihabSetEngineBar(shihabPct);
  });

  await shihabFFmpeg.load({
    coreURL: SHIHAB_CORE_CDN + 'ffmpeg-core.js',
    wasmURL: SHIHAB_CORE_CDN + 'ffmpeg-core.wasm',
  });

  shihabFFmpegReady = true;
  shihabSetEngineBar(100);
  shihabSetEngineLabel('âœ… MHS Engine Ready');

  const shihabStatus = document.getElementById('shihab-compressor-engine-status');
  if (shihabStatus) {
    shihabStatus.innerHTML = '<span style="width:8px;height:8px;border-radius:50%;background:#4ade80;display:inline-block;"></span> Engine Ready';
    shihabStatus.style.color = '#4ade80';
  }

  document.getElementById('shihab-compressor-process-btn').disabled =
    (shihabSshihabb007State.queue.length === 0);
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   SECTION 4 â€” State Management
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
const shihabSshihabb007State = {
  queue:       [],
  results:     [],
  settings: {
    format:        shihabCompressorData?.settings?.output_format    ?? 'webp',
    quality:       shihabCompressorData?.settings?.quality           ?? 75,
    smartResize:   shihabCompressorData?.settings?.smart_resize      ?? true,
    stripMeta:     shihabCompressorData?.settings?.strip_metadata    ?? true,
    concurrency:   shihabCompressorData?.settings?.batch_concurrency ?? 3,
    aiAltText:     shihabCompressorData?.settings?.ai_alt_text       ?? true,
    useCache:      shihabCompressorData?.settings?.indexeddb_cache   ?? true,
    autoOptimize:  shihabCompressorData?.settings?.auto_optimize     !== false,
  },
  liveBytesSaved: 0,
  liveImages:     parseInt(shihabCompressorData?.stats?.total_images  ?? 0),
  liveAiTags:     parseInt(shihabCompressorData?.stats?.total_ai_tags ?? 0),
};

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   SECTION 5 â€” Fallback Detection
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
const shihabSshihabb007HasSAB = typeof SharedArrayBuffer !== 'undefined';

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   SECTION 6 â€” FFmpeg Compression
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
const { fetchFile, toBlobURL } = window.FFmpegUtil || {};

/**
 * Build the FFmpeg command arguments for the given format + settings.
 * @param {string} shihabInName  Virtual FS input filename
 * @param {string} shihabOutName Virtual FS output filename
 * @param {string} shihabFmt     'webp' | 'avif'
 * @param {number} shihabQual    Quality 10-100
 * @param {boolean} shihabResize Enable smart resize
 * @param {boolean} shihabStrip  Strip metadata
 * @param {boolean} shihabAlpha  Input has alpha channel (PNG)
 * @returns {string[]}
 */
function shihabSshihabb007BuildFFmpegArgs(
  shihabInName, shihabOutName, shihabFmt, shihabQual, shihabResize, shihabStrip, shihabAlpha
) {
  const shihabArgs = ['-i', shihabInName];

  // Video filters
  const shihabFilters = [];
  if (shihabResize) shihabFilters.push("scale='min(1920,iw)':-1");
  if (shihabAlpha && shihabFmt === 'webp') shihabFilters.push('format=yuva420p');

  if (shihabFilters.length) shihabArgs.push('-vf', shihabFilters.join(','));

  // Metadata
  if (shihabStrip) shihabArgs.push('-map_metadata', '-1');

  // Format-specific quality
  if (shihabFmt === 'webp') {
    shihabArgs.push('-q:v', String(shihabQual));
  } else if (shihabFmt === 'avif') {
    // CRF: 0=best, 63=worst; map quality 100â†’0, 10â†’53
    const shihabCRF = Math.round(53 - (shihabQual / 100) * 53);
    shihabArgs.push('-crf', String(shihabCRF), '-b:v', '0');
  }

  shihabArgs.push(shihabOutName);
  return shihabArgs;
}

/**
 * Compress a single File using FFmpeg.wasm.
 * @param {File} shihabFile
 * @param {string} shihabFmt
 * @param {Function} shihabOnProgress  Callback (0-100)
 * @returns {Promise<{blob: Blob, originalSize: number, optimizedSize: number}>}
 */
async function shihabSshihabb007CompressWithFFmpeg(shihabFile, shihabFmt, shihabOnProgress) {
  const { fetchFile: shihabFetchFile } = await shihabSshihabb007GetFFmpegUtil();
  const shihabInName  = 'shihab_input_' + Date.now() + '.' + shihabFile.name.split('.').pop();
  const shihabOutName = 'shihab_output_' + Date.now() + '.' + shihabFmt;

  const shihabIsAlpha = shihabFile.type === 'image/png';
  const { settings: shihabS } = shihabSshihabb007State;

  shihabFFmpeg.on('progress', ({ progress: p }) => shihabOnProgress(Math.round(p * 100)));

  await shihabFFmpeg.writeFile(shihabInName, await shihabFetchFile(shihabFile));

  const shihabArgs = shihabSshihabb007BuildFFmpegArgs(
    shihabInName, shihabOutName,
    shihabFmt, shihabS.quality,
    shihabS.smartResize, shihabS.stripMeta, shihabIsAlpha
  );

  await shihabFFmpeg.exec(shihabArgs);

  const shihabData = await shihabFFmpeg.readFile(shihabOutName);
  const shihabBlob = new Blob([shihabData.buffer], { type: 'image/' + shihabFmt });

  // Cleanup virtual FS
  try { await shihabFFmpeg.deleteFile(shihabInName);  } catch {}
  try { await shihabFFmpeg.deleteFile(shihabOutName); } catch {}

  return {
    blob:          shihabBlob,
    originalSize:  shihabFile.size,
    optimizedSize: shihabBlob.size,
  };
}

/**
 * Lazy-load @ffmpeg/util from CDN.
 * @returns {Promise<{fetchFile: Function}>}
 */
async function shihabSshihabb007GetFFmpegUtil() {
  if (window.FFmpegUtil?.fetchFile) return window.FFmpegUtil;
  await new Promise((res, rej) => {
    const shihabS = document.createElement('script');
    shihabS.src = 'https://cdn.jsdelivr.net/npm/@ffmpeg/util@0.12.1/dist/umd/index.js';
    shihabS.onload = res; shihabS.onerror = rej;
    document.head.appendChild(shihabS);
  });
  return window.FFmpegUtil;
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   SECTION 7 â€” Transformers.js AI Alt-Text
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
let shihabSshihabb007AIPipeline = null;
let shihabSshihabb007AILoading  = false;

/**
 * Load the Transformers.js captioning model lazily.
 * @returns {Promise<Function>} Pipeline function
 */
async function shihabSshihabb007GetAIPipeline() {
  if (shihabSshihabb007AIPipeline) return shihabSshihabb007AIPipeline;
  if (shihabSshihabb007AILoading)  {
    await new Promise(r => setTimeout(r, 500));
    return shihabSshihabb007GetAIPipeline();
  }

  shihabSshihabb007AILoading = true;

  // Load Transformers.js from CDN
  if (!window.transformers) {
    await new Promise((res, rej) => {
      const shihabS = document.createElement('script');
      shihabS.type  = 'module';
      shihabS.textContent = `
        import { pipeline } from 'https://cdn.jsdelivr.net/npm/@xenova/transformers@2.17.2/dist/transformers.min.js';
        window.shihabCreatePipeline = pipeline;
        window.dispatchEvent(new Event('shihab-transformers-ready'));
      `;
      document.head.appendChild(shihabS);
      window.addEventListener('shihab-transformers-ready', res, { once: true });
      setTimeout(rej, 30000);
    });
  }

  shihabSshihabb007AIPipeline = await window.shihabCreatePipeline(
    'image-to-text',
    'Xenova/vit-gpt2-image-captioning'
  );
  shihabSshihabb007AILoading = false;
  return shihabSshihabb007AIPipeline;
}

/**
 * Generate AI alt text for an image file.
 * @param {File} shihabFile
 * @returns {Promise<string>}
 */
async function shihabSshihabb007GenerateAltText(shihabFile) {
  try {
    const shihabPipeline = await shihabSshihabb007GetAIPipeline();
    const shihabURL      = URL.createObjectURL(shihabFile);
    const shihabResult   = await shihabPipeline(shihabURL);
    URL.revokeObjectURL(shihabURL);
    return shihabResult?.[0]?.generated_text ?? '';
  } catch (shihabErr) {
    console.warn('[shihab-compressor] AI alt-text failed:', shihabErr);
    return '';
  }
}

/* -------------------------------------------------------------------
   SECTION 8 — Batch Wizard (concurrency-limited Promise queue)
   ------------------------------------------------------------------- */
async function shihabSshihabb007RunBatch(shihabTasks, shihabLimit) {
  const shihabResults = [];
  const shihabActive  = new Set();
  let shihabIdx = 0;
  return new Promise((shihabResolve) => {
    function shihabNext() {
      if (shihabIdx >= shihabTasks.length && shihabActive.size === 0) return shihabResolve(shihabResults);
      while (shihabActive.size < shihabLimit && shihabIdx < shihabTasks.length) {
        const shihabI = shihabIdx++;
        const shihabTask = shihabTasks[shihabI]();
        shihabActive.add(shihabTask);
        shihabTask.then(r => { shihabResults[shihabI] = r; shihabActive.delete(shihabTask); shihabNext(); })
                  .catch(e => { shihabResults[shihabI] = { error: e }; shihabActive.delete(shihabTask); shihabNext(); });
      }
    }
    shihabNext();
  });
}

/* -------------------------------------------------------------------
   SECTION 9 — WordPress Upload and PHP Fallback
   ------------------------------------------------------------------- */
async function shihabSshihabb007UploadToWP(shihabBlob, shihabFilename, shihabAltText, shihabFormat, shihabOriginalSize) {
  const shihabFD = new FormData();
  shihabFD.append('image', shihabBlob, shihabFilename);
  shihabFD.append('filename', shihabFilename);
  shihabFD.append('alt_text', shihabAltText);
  shihabFD.append('format', shihabFormat);
  shihabFD.append('original_size', shihabOriginalSize);
  shihabFD.append('optimized_size', shihabBlob.size);
  const shihabR = await fetch(shihabCompressorData.restUrl, {
    method: 'POST',
    headers: { 'X-WP-Nonce': shihabCompressorData.restNonce },
    body: shihabFD,
  });
  if (!shihabR.ok) { const e = await shihabR.json().catch(() => ({})); throw new Error(e.message || 'HTTP ' + shihabR.status); }
  return shihabR.json();
}

async function shihabSshihabb007PHPFallback(shihabFile, shihabAltText) {
  const shihabFD = new FormData();
  shihabFD.append('action', 'shihab_compressor_php_fallback');
  shihabFD.append('nonce',  shihabCompressorData.nonce);
  shihabFD.append('image',  shihabFile, shihabFile.name);
  shihabFD.append('quality', shihabSshihabb007State.settings.quality);
  shihabFD.append('format',  shihabSshihabb007State.settings.format);
  shihabFD.append('alt_text', shihabAltText);

  const shihabResp = await fetch(shihabCompressorData.ajaxUrl, { method: 'POST', body: shihabFD });
  const shihabText = await shihabResp.text();
  try {
    return JSON.parse(shihabText);
  } catch (shihabParseErr) {
    // Log raw server output so developers can diagnose PHP warnings/notices
    console.error('[MHS] PHP fallback raw response:', shihabText);
    throw new Error('Server returned non-JSON. Check PHP error log. Preview: ' + shihabText.slice(0, 120));
  }
}

/* -------------------------------------------------------------------
   SECTION 10 — Single Image Full Pipeline
   ------------------------------------------------------------------- */
async function shihabSshihabb007ProcessImage(shihabFile, shihabFmt) {
  const shihabRowId = 'shihab-row-' + Date.now() + '-' + Math.random().toString(36).slice(2);
  shihabSshihabb007AddFeedRow(shihabRowId, shihabFile.name, '...', '...', '...', '?', '');
  try {
    const shihabBuffer = await shihabFile.arrayBuffer();
    const shihabHash   = await shihabSshihabb007HashBuffer(shihabBuffer);
    const shihabCached = shihabSshihabb007State.settings.useCache ? await shihabSshihabb007GetCached(shihabHash, shihabFmt) : null;
    let shihabBlob, shihabOrigSize, shihabOptSize, shihabAltText = '';
    if (shihabCached) {
      shihabBlob = shihabCached.optimizedBlob; shihabOrigSize = shihabCached.originalSize;
      shihabOptSize = shihabBlob.size; shihabAltText = shihabCached.altText || '';
      shihabSshihabb007UpdateFeedRow(shihabRowId, { status: '? cached' });
    } else {
      const shihabAIPromise = shihabSshihabb007State.settings.aiAltText
        ? shihabSshihabb007GenerateAltText(shihabFile).catch(() => '') : Promise.resolve('');
      if (shihabSshihabb007HasSAB && shihabFFmpegReady) {
        const shihabC = await shihabSshihabb007CompressWithFFmpeg(shihabFile, shihabFmt,
          p => shihabSshihabb007UpdateFeedRow(shihabRowId, { progress: p }));
        shihabBlob = shihabC.blob; shihabOrigSize = shihabC.originalSize; shihabOptSize = shihabC.optimizedSize;
      } else {
        shihabAltText = await shihabAIPromise;
        let shihabFB;
        try {
          shihabFB = await shihabSshihabb007PHPFallback(shihabFile, shihabAltText);
        } catch (shihabFBErr) {
          shihabSshihabb007UpdateFeedRow(shihabRowId, { status: '❌ ' + shihabFBErr.message });
          return;
        }
        if (shihabFB && shihabFB.success) {
          const shihabD    = shihabFB.data || {};
          const shihabOrig = shihabD.original_size  || shihabFile.size;
          const shihabOpt  = shihabD.optimized_size || shihabOrig;
          const shihabSv   = Math.max(0, shihabOrig - shihabOpt);
          const shihabPct  = shihabOrig > 0 ? Math.round((shihabSv / shihabOrig) * 100) : 0;
          shihabSshihabb007UpdateFeedRow(shihabRowId, {
            origSize: shihabSshihabb007FormatBytes(shihabOrig),
            optSize:  shihabSshihabb007FormatBytes(shihabOpt),
            savings:  '-' + shihabPct + '%',
            status:   '✅',
            altText:  shihabAltText,
          });
          shihabSshihabb007IncrementStats(shihabSv, !!shihabAltText);
        } else {
          const shihabMsg = (shihabFB && shihabFB.data && shihabFB.data.message) || 'Compression failed';
          shihabSshihabb007UpdateFeedRow(shihabRowId, { status: '❌ ' + shihabMsg });
        }
        return;
      }
      shihabAltText = await shihabAIPromise;
      if (shihabSshihabb007State.settings.useCache) {
        await shihabSshihabb007SetCache({ originalHash: shihabHash, format: shihabFmt, originalSize: shihabOrigSize, optimizedBlob: shihabBlob, altText: shihabAltText, timestamp: Date.now() });
      }
    }
    shihabSshihabb007UpdateFeedRow(shihabRowId, { status: '?? uploading...' });
    const shihabBasename = shihabFile.name.replace(/\.[^.]+$/, '') + '.' + shihabFmt;
    await shihabSshihabb007UploadToWP(shihabBlob, shihabBasename, shihabAltText, shihabFmt, shihabOrigSize);
    const shihabSaved = Math.max(0, shihabOrigSize - shihabOptSize);
    const shihabPct   = shihabOrigSize > 0 ? Math.round((shihabSaved / shihabOrigSize) * 100) : 0;
    shihabSshihabb007UpdateFeedRow(shihabRowId, {
      origSize: shihabSshihabb007FormatBytes(shihabOrigSize), optSize: shihabSshihabb007FormatBytes(shihabOptSize),
      savings: '-' + shihabPct + '%', status: '?', altText: shihabAltText, progress: 100,
    });
    shihabSshihabb007IncrementStats(shihabSaved, !!shihabAltText);
  } catch (shihabErr) {
    shihabSshihabb007UpdateFeedRow(shihabRowId, { status: '? ' + (shihabErr.message || 'Error') });
  }
}

/* -------------------------------------------------------------------
   SECTION 11 — UI Helpers
   ------------------------------------------------------------------- */
function shihabSetEngineLabel(t) { const e = document.getElementById('shihab-engine-label'); if (e) e.textContent = t; }
function shihabSetEngineBar(p) {
  const bar = document.getElementById('shihab-compressor-engine-bar');
  const pct = document.getElementById('shihab-engine-pct');
  if (bar) bar.style.width = p + '%'; if (pct) pct.textContent = p + '%';
}
function shihabSshihabb007FormatBytes(b) {
  if (b >= 1048576) return (b / 1048576).toFixed(1) + ' MB';
  if (b >= 1024)    return (b / 1024).toFixed(1) + ' KB';
  return b + ' B';
}
function shihabSshihabb007AddFeedRow(id, name, orig, opt, sav, status, alt) {
  document.getElementById('shihab-compressor-feed-empty') && document.getElementById('shihab-compressor-feed-empty').remove();
  const list = document.getElementById('shihab-compressor-feed-list'); if (!list) return;
  const div = document.createElement('div');
  div.className = 'shihab-feed-row'; div.id = id;
  div.innerHTML = '<div class="shihab-feed-row-top">'
    + '<span class="shihab-feed-filename" title="' + name + '">' + name + '</span>'
    + '<span class="shihab-feed-sizes" id="' + id + '-sizes">' + orig + ' to ' + opt + '</span>'
    + '<span class="shihab-feed-savings shihab-savings-ok" id="' + id + '-savings">' + sav + '</span>'
    + '<span class="shihab-feed-status-icon" id="' + id + '-status">' + status + '</span>'
    + '</div>'
    + '<div class="shihab-mini-bar-track"><div class="shihab-mini-bar-fill" id="' + id + '-bar" style="width:0%"></div></div>'
    + (alt ? '<div class="shihab-feed-tags">?? "' + alt + '"</div>' : '');
  list.prepend(div);
}
function shihabSshihabb007UpdateFeedRow(id, d) {
  if (d.origSize !== undefined || d.optSize !== undefined) {
    const e = document.getElementById(id + '-sizes'); if (e) e.textContent = (d.origSize || '...') + ' to ' + (d.optSize || '...');
  }
  if (d.savings !== undefined) {
    const e = document.getElementById(id + '-savings');
    if (e) { e.textContent = d.savings; const p = Math.abs(parseInt(d.savings)); e.className = 'shihab-feed-savings ' + (p >= 30 ? 'shihab-savings-good' : p >= 10 ? 'shihab-savings-ok' : 'shihab-savings-low'); }
  }
  if (d.status  !== undefined) { const e = document.getElementById(id + '-status'); if (e) e.textContent = d.status; }
  if (d.progress !== undefined) { const e = document.getElementById(id + '-bar'); if (e) e.style.width = d.progress + '%'; }
  if (d.altText) {
    const row = document.getElementById(id);
    if (row && !row.querySelector('.shihab-feed-tags')) {
      const t = document.createElement('div'); t.className = 'shihab-feed-tags'; t.textContent = '?? "' + d.altText + '"'; row.appendChild(t);
    }
  }
}
function shihabSshihabb007IncrementStats(bytes, hasAI) {
  shihabSshihabb007State.liveBytesSaved += bytes; shihabSshihabb007State.liveImages++;
  if (hasAI) shihabSshihabb007State.liveAiTags++;
  shihabSshihabb007AnimateStat('shihab-stat-saved',  shihabSshihabb007FormatBytes(shihabSshihabb007State.liveBytesSaved));
  shihabSshihabb007AnimateStat('shihab-stat-images', shihabSshihabb007State.liveImages);
  shihabSshihabb007AnimateStat('shihab-stat-ai',     shihabSshihabb007State.liveAiTags);
}
function shihabSshihabb007AnimateStat(elId, val) {
  const el = document.getElementById(elId); if (!el) return;
  el.style.animation = 'none'; void el.offsetHeight;
  el.textContent = val; el.style.animation = 'shihab-odometer-tick 0.4s cubic-bezier(0.34,1.56,0.64,1)';
}

/* -------------------------------------------------------------------
   SECTION 12 — DOM Initialisation
   ------------------------------------------------------------------- */
document.addEventListener('DOMContentLoaded', async function shihabSshihabb007Init() {
  const shihabQS = document.getElementById('shihab-compressor-quality');
  const shihabQL = document.getElementById('shihab-qual-val');
  if (shihabQS) shihabQS.addEventListener('input', function () {
    shihabSshihabb007State.settings.quality = parseInt(this.value);
    if (shihabQL) shihabQL.textContent = this.value;
    this.style.setProperty('--val', this.value + '%');
  });

  document.querySelectorAll('.shihab-format-btn').forEach(btn => btn.addEventListener('click', function () {
    document.querySelectorAll('.shihab-format-btn').forEach(b => b.classList.remove('shihab-active'));
    this.classList.add('shihab-active');
    shihabSshihabb007State.settings.format = this.dataset.shihabFmt;
  }));

  [['shihab-toggle-resize','smartResize'],['shihab-toggle-strip','stripMeta'],['shihab-toggle-ai','aiAltText'],['shihab-toggle-auto','autoOptimize']].forEach(function(pair) {
    const el = document.getElementById(pair[0]);
    if (el) el.addEventListener('change', function() { shihabSshihabb007State.settings[pair[1]] = el.checked; });
  });

  const shihabBC = document.getElementById('shihab-batch-concurrency');
  if (shihabBC) shihabBC.addEventListener('change', function() { shihabSshihabb007State.settings.concurrency = Math.max(1, Math.min(10, parseInt(shihabBC.value) || 3)); });

  const shihabDZ    = document.getElementById('shihab-compressor-dropzone');
  const shihabInput = document.getElementById('shihab-compressor-file-input');
  const shihabCount = document.getElementById('shihab-compressor-selected-count');
  const shihabPBtn  = document.getElementById('shihab-compressor-process-btn');

  function shihabSshihabb007AddToQueue(files) {
    const imgs = files.filter(function(f) { return f.type.startsWith('image/'); });
    shihabSshihabb007State.queue.push.apply(shihabSshihabb007State.queue, imgs);
    if (shihabCount) shihabCount.textContent = shihabSshihabb007State.queue.length + ' file(s) queued';
    if (shihabPBtn) shihabPBtn.disabled = !shihabSshihabb007State.queue.length;
  }

  if (shihabDZ) {
    shihabDZ.addEventListener('dragover', function(e) { e.preventDefault(); shihabDZ.classList.add('shihab-drag-over'); });
    shihabDZ.addEventListener('dragleave', function() { shihabDZ.classList.remove('shihab-drag-over'); });
    shihabDZ.addEventListener('drop', function(e) { e.preventDefault(); shihabDZ.classList.remove('shihab-drag-over'); shihabSshihabb007AddToQueue(Array.from(e.dataTransfer.files)); });
  }
  const shihabBrowseBtn = document.getElementById('shihab-compressor-browse-btn');
  if (shihabBrowseBtn) shihabBrowseBtn.addEventListener('click', function() { if (shihabInput) shihabInput.click(); });
  if (shihabInput) shihabInput.addEventListener('change', function() { shihabSshihabb007AddToQueue(Array.from(shihabInput.files)); });

  const shihabClearBtn = document.getElementById('shihab-compressor-clear-btn');
  if (shihabClearBtn) shihabClearBtn.addEventListener('click', function() {
    shihabSshihabb007State.queue = [];
    if (shihabInput) shihabInput.value = '';
    if (shihabCount) shihabCount.textContent = '';
    if (shihabPBtn) shihabPBtn.disabled = true;
  });

  if (shihabPBtn) shihabPBtn.addEventListener('click', async function() {
    if (!shihabSshihabb007State.queue.length) return;
    shihabPBtn.disabled = true; shihabPBtn.textContent = 'Processing...';
    const shihabFmt   = shihabSshihabb007State.settings.format;
    const shihabLimit = shihabSshihabb007State.settings.concurrency;
    const shihabFiles = shihabSshihabb007State.queue.slice(); shihabSshihabb007State.queue = [];
    if (shihabCount) shihabCount.textContent = '';
    const shihabFmts  = shihabFmt === 'both' ? ['webp','avif'] : [shihabFmt];
    const shihabTasks = [];
    shihabFiles.forEach(function(f) {
      shihabFmts.forEach(function(fmt) {
        shihabTasks.push(function() { return shihabSshihabb007ProcessImage(f, fmt); });
      });
    });
    await shihabSshihabb007RunBatch(shihabTasks, shihabLimit);
    shihabPBtn.textContent = 'Compress & Upload';
    shihabPBtn.disabled = false;
  });

  const shihabSaveBtn = document.getElementById('shihab-compressor-save-btn');
  if (shihabSaveBtn) shihabSaveBtn.addEventListener('click', async function() {
    const s = shihabSshihabb007State.settings;
    const fd = new FormData();
    fd.append('action','shihab_compressor_save_settings'); fd.append('nonce', shihabCompressorData.nonce);
    fd.append('output_format', s.format); fd.append('quality', s.quality);
    fd.append('smart_resize', s.smartResize ? '1':'0'); fd.append('strip_metadata', s.stripMeta ? '1':'0');
    fd.append('batch_concurrency', s.concurrency); fd.append('ai_alt_text', s.aiAltText ? '1':'0');
    fd.append('indexeddb_cache', s.useCache ? '1':'0');
    fd.append('auto_optimize', s.autoOptimize ? '1':'0');
    const r = await (await fetch(shihabCompressorData.ajaxUrl, { method:'POST', body: fd })).json();
    shihabSaveBtn.textContent = r.success ? 'Saved!' : 'Error';
    setTimeout(function() { shihabSaveBtn.textContent = 'Save Settings'; }, 2000);
  });

  if (shihabSshihabb007HasSAB) {
    try { await shihabSshihabb007LoadFFmpeg(); }
    catch(e) {
      shihabSetEngineLabel('Engine load failed - PHP fallback active');
      shihabSetEngineBar(100); shihabFFmpegReady = false;
      if (shihabPBtn) shihabPBtn.disabled = !shihabSshihabb007State.queue.length;
    }
  } else {
    shihabSetEngineLabel('SharedArrayBuffer unavailable - PHP fallback active');
    shihabSetEngineBar(100);
    const st = document.getElementById('shihab-compressor-engine-status');
    if (st) { st.textContent = 'PHP Fallback Mode'; st.style.color = '#fbbf24'; }
    if (shihabPBtn) shihabPBtn.disabled = !shihabSshihabb007State.queue.length;
  }
});
/* END - MHS Compressor by MEHEDI HASAN SHIHAB sshihabb007 */


