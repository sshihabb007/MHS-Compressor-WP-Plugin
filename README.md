# ⚡ MHS Image Compressor

> A futuristic, AI-powered WordPress image optimization plugin with client-side WebAssembly compression, smart intelligence, and a Cyberpunk Command Center dashboard.

**Version:** 1.0.0  
**Author:** MEHEDI HASAN SHIHAB (`sshihabb007`)  
**Website:** [https://mehedi-hasan-shihab.netlify.app/](https://mehedi-hasan-shihab.netlify.app/)  
**Repository:** [github.com/sshihabb007/MHS-Compressor-WP-Plugin](https://github.com/sshihabb007/MHS-Compressor-WP-Plugin)  
**License:** GPL-2.0+

---

## 📖 Overview

**MHS Image Compressor** is a next-generation WordPress image optimizer that moves the heavy lifting of compression entirely into the user's browser using **WebAssembly (FFmpeg.wasm)**. Images are converted, resized, and captioned locally before uploading — zero server strain, zero API fees.

When WebAssembly isn't available (older browsers / shared hosting), the plugin seamlessly falls back to **PHP server-side compression** using GD or Imagick — so it works everywhere.

Wrapped in a stunning **Cyberpunk Command Center** dashboard (Tailwind CSS v4 + glassmorphism), the plugin offers a premium, modern experience for WordPress media management.

---

## ✨ Feature Set

### 🔧 Core Compression Engine
| Feature | Description |
|---------|-------------|
| **Client-Side WebAssembly** | FFmpeg.wasm compresses images in the browser — no server load |
| **WebP & AVIF Output** | Convert JPEG/PNG/GIF to next-gen formats with up to 90% size savings |
| **PHP Fallback** | Auto-detects browser capability; falls back to GD or Imagick |
| **Compression Tiers** | **Lossless** (pixel-perfect) · **Glossy** (photographers) · **Lossy** (max speed) |
| **Quality Slider** | Fine-tune quality from 10–100 per session |
| **Batch Processing** | Drag-and-drop up to 50 images; concurrent semaphore queue |
| **IndexedDB Caching** | Previously compressed images served instantly from local cache (Dexie.js) |

---

### ⚡ Auto-Optimize on Upload
Every image added to the WordPress Media Library is **automatically** compressed the moment WordPress finishes processing it — including uploads from the Block Editor, WooCommerce, contact forms, and any plugin.

- Hooks into `wp_generate_attachment_metadata` (fires after thumbnail generation)
- Reads your saved settings (format, quality, smart resize)
- Replaces the original file with the optimized version on disk
- Updates WordPress MIME type, file path, and attachment metadata
- Writes a timestamped entry to the **Auto-Optimize Activity Log** in the dashboard
- Toggle: **⚡ Auto-Optimize on Upload** switch in Settings

---

### 🌐 Dynamic Pipeline (Edge Delivery)
Serve the perfect image for every device without storing dozens of pre-generated variants.

| Sub-Feature | Details |
|-------------|---------|
| **On-The-Fly Resizing** | `/mhs-img/{id}/{width}/{height}` endpoint generates & caches resized images on first request |
| **Dynamic Format Switching** | Reads browser `Accept` header — serves **AVIF** to Chrome/Safari, **WebP** to others, **JPEG** as universal fallback |
| **srcset Injection** | Rewrites WordPress `srcset` to point to dynamic endpoints for adaptive device scaling |
| **Lazy Loading** | Injects `loading="lazy"` and `decoding="async"` on every attachment image automatically |
| **Edge Cache** | Generated images cached in `/wp-content/uploads/mhs-cache/` with 1-year `Cache-Control` headers |

> **Toggle:** Enable `Dynamic Delivery` in Settings → saves as `dynamic_delivery`.

---

### 🤖 AI-Driven Smart Intelligence

| Feature | How It Works |
|---------|-------------|
| **Smart Quality Balance** | Samples a 50×50 thumbnail of every image, counts unique colour zones. Flat graphics → 55% lossy. Photos → 88% lossless. Everything else → 75% glossy. |
| **AI Alt-Text Generation** | Uses `Transformers.js` (Vit-GPT2) in-browser to generate descriptive, SEO-ready alt-text automatically |
| **AI SEO Filename** | Converts AI-generated alt-text into meaningful filenames (e.g. `blue-running-shoes.webp` instead of `IMG_023.webp`) |
| **REST API** | `GET /wp-json/MHS/v1/smart-quality/{id}` — analyse any attachment and get quality recommendation |

---

### 💾 Backup & One-Click Restore
Never lose an original again.

- Before every auto-optimization, the original file is copied to `/wp-content/uploads/mhs-backups/`
- Backed-up images are listed in the dashboard with file size, savings, and time-ago stamp
- One-click **Restore** reverts the attachment to its original format and regenerates all thumbnails
- REST API: `POST /wp-json/MHS/v1/restore/{id}`
- REST API: `GET /wp-json/MHS/v1/backups` — full list of backed-up images
- Toggle: `backup_originals` setting

---

### 🗂️ Bulk Async Optimization
Optimize your **entire existing media library** in the background — no page freezes, no timeouts.

- Scans the Media Library for all unoptimized images
- Processes **5 images per WP-Cron tick** (runs every ~3 seconds)
- **Pause / Resume** at any time
- Live progress bar: images processed · remaining · percentage complete
- REST API:
  - `POST /wp-json/MHS/v1/bulk/start` — start the queue
  - `GET /wp-json/MHS/v1/bulk/status` — live status polling
  - `POST /wp-json/MHS/v1/bulk/pause` — pause or resume

---

### 📁 Directory Smush
Optimize images **outside** the Media Library — theme folders, plugin assets, custom directories.

- Scan any path within your WordPress installation
- Preview up to 50 found images with file sizes
- Compress all found JPEG/PNG/GIF files in one click
- Converts to your chosen format (WebP/AVIF) and replaces originals
- Safety limit: max 30 files per AJAX call to prevent timeouts

---

### 🖼️ SVG Optimization
- Strips XML declarations, HTML comments, and `<metadata>` blocks
- Collapses whitespace between tags
- Removes empty `style` and `class` attributes
- Typical savings: **20–40%** on complex SVG files

---

### 🔒 Conflict Detection
On every admin page load, the plugin checks if any known competing image optimizers are active:
- Smush / WP Smushit
- ShortPixel
- Imagify
- EWWW Image Optimizer
- Robin Image Optimizer

If a conflict is detected, a dismissible admin notice warns you.

---

## 🛠️ Technology Stack

| Layer | Technology |
|-------|-----------|
| Compression (browser) | FFmpeg.wasm (via jsDelivr CDN) |
| AI (browser) | Transformers.js — `Xenova/vit-gpt2-image-captioning` |
| Caching (browser) | Dexie.js (IndexedDB wrapper) |
| Compression (server) | PHP GD / Imagick |
| Styling | Tailwind CSS v4 + Custom Glassmorphism CSS |
| Typography | Google Fonts — Inter + JetBrains Mono |
| WordPress APIs | REST API, WP-Cron, WP Ajax, Attachment Metadata |

---

## 📁 Plugin File Structure

```
compressor/
├── compressor.php                          ← Plugin bootstrap & hooks
├── uninstall.php                           ← Cleanup on deletion
├── .htaccess                               ← COOP/COEP headers for SharedArrayBuffer
├── README.md                               ← This file
│
├── admin/
│   └── views/
│       └── shihab-compressor-dashboard.php ← Cyberpunk dashboard HTML
│
├── assets/
│   ├── css/
│   │   └── shihab-compressor-admin.css     ← Glassmorphism + animation styles
│   └── js/
│       ├── shihab-compressor-script.js     ← Client-side engine (12 sections)
│       └── coi-serviceworker.js            ← Cross-Origin Isolation service worker
│
└── includes/
    ├── class-shihab-compressor-admin.php   ← WP menu, assets, settings AJAX
    ├── class-shihab-compressor-api.php     ← REST API endpoint /MHS/v1/upload
    ├── class-shihab-compressor-auto.php    ← Auto-optimize on upload hook
    ├── class-shihab-compressor-dynamic.php ← Dynamic delivery, srcset, lazy load
    ├── class-shihab-compressor-fallback.php← PHP GD/Imagick compression
    ├── class-shihab-compressor-restore.php ← Backup & one-click restore
    └── class-shihab-compressor-smart.php   ← Smart Quality, Bulk, Dir Smush, SVG
```

---

## 🚀 Installation

1. **Upload** the `compressor` folder to `/wp-content/plugins/`
2. **Activate** via the WordPress Plugins menu
3. Navigate to **MHS Compress** in the left admin sidebar

### ⚠️ Enable FFmpeg.wasm (SharedArrayBuffer)

FFmpeg.wasm requires `SharedArrayBuffer`, which needs strict COOP/COEP security headers. The plugin's `.htaccess` handles this automatically on Apache. If headers aren't applying, add this to your server config:

```apache
Header always set Cross-Origin-Opener-Policy "same-origin"
Header always set Cross-Origin-Embedder-Policy "require-corp"
```

> **Note:** On XAMPP/localhost, you may need to enable `mod_headers` in Apache. Until then, the plugin automatically runs in **PHP Fallback Mode** — fully functional for all compression tasks.

---

## 🎮 How to Use

### Manual Compression (Dashboard Drop Zone)
1. Go to **MHS Compress** in the WordPress sidebar
2. Configure your settings (format, quality, toggles)
3. Drag & drop images into the glowing drop zone, or click **Browse Files**
4. Click **⚡ Compress & Upload**
5. Watch the **Neural Feed** log for live per-image results

### Auto-Optimize (Hands-Free)
1. Enable the **⚡ Auto-Optimize on Upload** toggle
2. Click **Save Settings**
3. Upload any image anywhere in WordPress — it will be compressed automatically
4. View results in the **Auto-Optimize Log** section of the dashboard

### Bulk Optimize Existing Library
1. Use the REST API: `POST /wp-json/MHS/v1/bulk/start`
2. Poll status: `GET /wp-json/MHS/v1/bulk/status`
3. Pause/resume: `POST /wp-json/MHS/v1/bulk/pause`

### Directory Smush (Theme/Plugin Folders)
1. Call `wp_ajax_shihab_dir_scan` with a relative path to scan
2. Then `wp_ajax_shihab_dir_smush` to compress all found images

### One-Click Restore
- REST: `POST /wp-json/MHS/v1/restore/{attachment_id}`
- Or call `wp_ajax_shihab_restore_image` via AJAX with `attachment_id`

---

## ⚙️ Settings Reference

| Setting Key | Type | Default | Description |
|-------------|------|---------|-------------|
| `output_format` | string | `webp` | Output format: `webp`, `avif`, or `both` |
| `quality` | int | `75` | Compression quality (10–100) |
| `compression_tier` | string | `glossy` | `lossless`, `glossy`, or `lossy` |
| `smart_resize` | bool | `true` | Auto-downscale images wider than `max_width` |
| `max_width` | int | `2000` | Maximum image width in pixels |
| `strip_metadata` | bool | `true` | Remove GPS & EXIF data |
| `batch_concurrency` | int | `3` | Parallel images processed at once |
| `ai_alt_text` | bool | `true` | Generate alt-text with Transformers.js |
| `ai_smart_quality` | bool | `true` | Auto quality based on image complexity |
| `ai_seo_filename` | bool | `true` | Rename files using AI-generated alt-text |
| `indexeddb_cache` | bool | `true` | Cache compressed images in IndexedDB |
| `auto_optimize` | bool | `true` | Compress every new upload automatically |
| `backup_originals` | bool | `true` | Keep original files before optimization |
| `dynamic_delivery` | bool | `false` | Enable on-the-fly resizing & format switching |

---

## 🌐 REST API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/wp-json/MHS/v1/upload` | Upload & register compressed image |
| `GET`  | `/wp-json/MHS/v1/stats` | Plugin statistics (total saved, images, AI tags) |
| `GET`  | `/wp-json/MHS/v1/settings` | Read current settings |
| `PUT`  | `/wp-json/MHS/v1/settings` | Update settings |
| `POST` | `/wp-json/MHS/v1/bulk/start` | Start bulk optimization |
| `GET`  | `/wp-json/MHS/v1/bulk/status` | Live bulk progress |
| `POST` | `/wp-json/MHS/v1/bulk/pause` | Pause or resume bulk |
| `POST` | `/wp-json/MHS/v1/restore/{id}` | Restore image to original |
| `GET`  | `/wp-json/MHS/v1/backups` | List all backed-up images |
| `GET`  | `/wp-json/MHS/v1/smart-quality/{id}` | AI quality recommendation |

> All endpoints require authentication (`manage_options` capability).

---

## 📜 Credits & License

Built exclusively by **MEHEDI HASAN SHIHAB** (`sshihabb007`).

- **Website:** [https://mehedi-hasan-shihab.netlify.app/](https://mehedi-hasan-shihab.netlify.app/)
- **GitHub:** [sshihabb007](https://github.com/sshihabb007)
- **License:** GPL-2.0+
- **WordPress Requires:** 6.0+
- **PHP Requires:** 8.0+
