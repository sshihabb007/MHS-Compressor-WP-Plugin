# MHS Image Compressor ⚡

**A futuristic, highly optimized WordPress image compressor.**  
**Version:** 1.0.0  
**Author:** Mehedi Shihab (`sshihabb007`)  
**URL:** [https://www.linkedin.com/in/mehedi-hasan-shihab/](https://www.linkedin.com/in/mehedi-hasan-shihab/)

---

## 📖 Overview

**MHS Image Compressor** shifts the heavy lifting of image optimization away from your server and directly into the user's browser. By leveraging **WebAssembly (FFmpeg.wasm)**, **Transformers.js** for local AI, and **Dexie.js** for local caching, it processes, resizes, and captions images on the client side before uploading them to WordPress. 

Packaged in a stunning **Cyberpunk-themed Dashboard** (using Tailwind CSS v4 and glassmorphism), this plugin offers a premium, modern experience for WordPress media management.

---

## ✨ Features

- **Client-Side Compression:** Uses `FFmpeg.wasm` to convert JPEG, PNG, and GIF images into next-gen formats (**WebP** and **AVIF**) entirely in the browser. Zero server strain.
- **Local AI Alt-Text Generation:** Integrates `Transformers.js` (Vit-GPT2) to automatically scan images and generate descriptive, SEO-friendly alt-text without requiring external API keys.
- **IndexedDB Caching:** Images optimized once are cached locally using `Dexie.js`. If you try to compress the exact same image again, it loads instantly from the cache.
- **Smart Resizing:** Automatically detects 4K or ultra-high-definition images and downscales them to a web-friendly `1920px` width while maintaining aspect ratio.
- **Metadata Stripping:** Strips EXIF and GPS data to improve user privacy and reduce file size.
- **Batch Processing Engine:** Drag-and-drop up to 50 images at once. The concurrent semaphore queue handles batching efficiently without crashing the browser.
- **Intelligent Fallback:** If a user's browser or server doesn't support the required `SharedArrayBuffer` headers, it seamlessly falls back to a server-side PHP compression using `GD` or `Imagick`.
- **Conflict Detection:** Warns administrators if other optimization plugins (like Smush or Imagify) are active to prevent database conflicts.
- **Cyberpunk Command Center:** A visually striking interface featuring glowing SVG drop zones, live "Neural Feed" event logs, and real-time odometer analytics.

---

## 🛠️ Technology Stack

- **PHP 8+** (WordPress REST API, Plugin Architecture, GD/Imagick Fallback)
- **JavaScript (ES6+)**
- **FFmpeg.wasm** (WebAssembly media processing)
- **Transformers.js** (In-browser machine learning)
- **Dexie.js** (IndexedDB wrapper)
- **Tailwind CSS v4** (Styling)

---

## 🚀 How to Install & Configure

1. **Upload** the `compressor` folder to your `/wp-content/plugins/` directory.
2. **Activate** the plugin through the 'Plugins' menu in WordPress.
3. **Configure Headers (Crucial for FFmpeg.wasm):**
   FFmpeg.wasm requires `SharedArrayBuffer`, which mandates strict security headers. 
   If you are on an Apache server, the plugin's `.htaccess` attempts to inject these automatically:
   ```apache
   Header always set Cross-Origin-Opener-Policy "same-origin"
   Header always set Cross-Origin-Embedder-Policy "require-corp"
   ```
   *(Note: The plugin includes a Service Worker fallback (`coi-serviceworker.js`) for environments where modifying the Apache config isn't possible.)*

---

## 🎮 How to Use

1. **Navigate to the Dashboard:** Look for the **"MHS Compress"** menu item in your left-hand WordPress admin sidebar.
2. **Configure Settings:**
   - Choose your Output Format (`WebP`, `AVIF`, or `Both`).
   - Adjust the quality slider (default `75/100`).
   - Toggle **Smart Resize**, **Strip Metadata**, and **AI Alt-Text**.
   - Set your **Batch Concurrency** limit (how many images to process simultaneously).
3. **Drop Files:** Drag and drop your images into the glowing central Drop Zone, or click "Browse Files".
4. **Process:** Click **"Compress & Upload"**.
5. **Monitor:** Watch the **Neural Feed** log at the bottom. It will show you exactly how much space was saved and display the AI-generated alt-text for each image.
6. **Check Media Library:** Head to your standard WordPress Media Library. The newly optimized, alt-text-tagged images will be waiting for you.

---

## 📜 Credits & License

Built exclusively by **Mehedi Shihab (sshihabb007)**.
* **License:** GPL-2.0+
