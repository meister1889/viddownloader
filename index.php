<?php
// index.php
require_once 'config.php';
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VideoDownloader_</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&display=swap');

        body {
            font-family: 'JetBrains Mono', 'Consolas', monospace;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            background-attachment: fixed;
            color: #d4d4d4;
            min-height: 100vh;
            /* Allow natural scrolling, remove flex column constraints that might have broken it */
            overflow-x: hidden;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse-subtle {
            0% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.05); }
            50% { box-shadow: 0 0 20px 0 rgba(255, 255, 255, 0.1); }
            100% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.05); }
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }

        /* Glassmorphism Panel */
        .glass-panel {
            background: rgba(10, 10, 10, 0.4);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.6);
            transition: all 0.4s ease;
            /* float animation causes stability issues during clicks with some browsers/testing tools. We can apply it differently or remove if it causes Playwright to fail on 'stable' check. Let's make it a very subtle transform to avoid layout shift, or remove the infinite animation. */
            /* Removed animation: float 6s ease-in-out infinite; */
        }

        .glass-panel:hover {
            border-color: rgba(255, 255, 255, 0.15);
            background: rgba(15, 15, 15, 0.5);
        }

        /* Inputs */
        .terminal-input {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #404040;
            color: #ffffff;
            transition: all 0.3s ease;
        }

        .terminal-input:focus {
            border-color: #d4d4d4;
            outline: none;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.1);
            background: rgba(20, 20, 20, 0.8);
        }

        /* Buttons & Ripple */
        .btn-glow {
            background: #1a1a1a;
            border: 1px solid #404040;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .btn-glow:hover {
            background: #2d2d2d;
            box-shadow: 0 0 20px rgba(160, 160, 160, 0.2);
            transform: scale(1.02);
            border-color: #a0a0a0;
        }

        .btn-glow:active {
            transform: scale(0.98);
        }

        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            transform: scale(0);
            animation: ripple-effect 0.6s linear;
            pointer-events: none;
        }

        @keyframes ripple-effect {
            to { transform: scale(4); opacity: 0; }
        }

        /* Toast Notifications */
        #toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 100;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            min-width: 280px;
            padding: 1rem;
            border-radius: 8px;
            color: white;
            font-size: 0.9rem;
            backdrop-filter: blur(8px);
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast-error { background: rgba(185, 28, 28, 0.8); border: 1px solid #ef4444; }
        .toast-success { background: rgba(21, 128, 61, 0.8); border: 1px solid #22c55e; }
        .toast-info { background: rgba(30, 64, 175, 0.8); border: 1px solid #3b82f6; }

        /* Skeleton loader */
        .skeleton {
            background: linear-gradient(90deg, #1a1a1a 25%, #2d2d2d 50%, #1a1a1a 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite linear;
            border-radius: 8px;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Custom Dropdown */
        .custom-select-wrapper {
            position: relative;
            user-select: none;
        }

        .custom-select {
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .custom-select__trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #404040;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .custom-select__trigger:hover {
            border-color: #a0a0a0;
            background: rgba(20, 20, 20, 0.8);
        }

        .custom-options {
            position: absolute;
            display: block;
            top: 100%;
            left: 0;
            right: 0;
            background: #1a1a1a;
            border: 1px solid #404040;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.8);
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateY(-10px);
            z-index: 20;
            overflow: hidden;
            margin-top: 5px;
        }

        .custom-select.open .custom-options {
            opacity: 1;
            visibility: visible;
            pointer-events: all;
            transform: translateY(0);
        }

        .custom-option {
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.2s ease;
            border-bottom: 1px solid #2d2d2d;
        }

        .custom-option:last-child {
            border-bottom: none;
        }

        .custom-option:hover {
            background: #2d2d2d;
            padding-left: 1.5rem;
        }

        .custom-option.selected {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-weight: bold;
            border-left: 2px solid #fff;
        }

        .arrow {
            position: relative;
            width: 12px;
            height: 12px;
            transition: transform 0.3s ease;
        }
        .arrow::before, .arrow::after {
            content: '';
            position: absolute;
            width: 2px;
            height: 8px;
            background: #a0a0a0;
            border-radius: 2px;
            bottom: 2px;
        }
        .arrow::before {
            left: 2px;
            transform: rotate(-45deg);
        }
        .arrow::after {
            right: 2px;
            transform: rotate(45deg);
        }
        .custom-select.open .arrow {
            transform: rotate(180deg);
        }

        /* Toggle Switch */
        .toggle-checkbox:checked {
            right: 0;
            border-color: #d4d4d4;
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: #404040;
        }
        .toggle-checkbox:checked + .toggle-label:after {
            transform: translateX(100%);
            border-color: white;
        }

        /* Icons */
        .icon-gray {
            filter: grayscale(100%) opacity(0.5);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transform: scale(0.9);
        }
        .icon-active {
            filter: grayscale(0%) opacity(1);
            transform: scale(1.1);
        }

        /* Container transitions */
        #preview-section {
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            transition: opacity 0.6s ease, max-height 0.6s ease;
        }

        #preview-section.show {
            opacity: 1;
            max-height: 1000px;
            overflow: visible; /* Allow custom dropdown to overflow */
        }

        /* Ensure spacing from fixed header and footer */
        .main-wrapper {
            padding-top: 5rem;
            padding-bottom: 5rem;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Video Image Cover */
        .img-overlay {
            background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%);
        }
    </style>
</head>
<body class="antialiased">
    <!-- CSRF Token -->
    <input type="hidden" id="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

    <!-- Header -->
    <header class="w-full p-4 border-b border-white/10 bg-black/60 backdrop-blur-md fixed top-0 z-50">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <span class="text-xl font-bold text-white tracking-wider">> VideoDownloader_</span>
            <div class="w-2 h-2 rounded-full bg-green-500 shadow-[0_0_10px_rgba(34,197,94,0.8)] animate-pulse"></div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-wrapper p-4 sm:p-6 lg:p-8">
        <div class="w-full max-w-2xl glass-panel p-8 md:p-10 animate-fade-in relative">

            <!-- Platform Icons -->
            <div class="flex justify-center gap-8 mb-8" id="platform-icons">
                <!-- TikTok Icon -->
                <svg id="icon-tiktok" class="w-10 h-10 icon-gray cursor-help" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 2.23-.69 4.46-2.15 6.24-1.46 1.78-3.52 2.87-5.83 3.1-2.31.23-4.66-.2-6.57-1.42-1.91-1.22-3.14-3.15-3.52-5.36-.38-2.21.05-4.52 1.25-6.38 1.2-1.86 3.12-3.04 5.29-3.32 2.17-.28 4.39.2 6.13 1.43V6.86c-1.29-.86-2.82-1.32-4.37-1.23-1.55.09-3.05.7-4.27 1.75-1.22 1.05-2.07 2.48-2.45 4.02-.38 1.54-.25 3.16.38 4.6 1.19 2.76 3.82 4.71 6.8 5.12 2.98.41 6.01-.54 8.04-2.58 2.03-2.04 3.06-4.94 2.83-7.86-.01-5.18-.01-10.36-.02-15.54z"/>
                </svg>
                <!-- Instagram Icon -->
                <svg id="icon-instagram" class="w-10 h-10 icon-gray cursor-help" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                </svg>
            </div>

            <!-- Input Area -->
            <div class="mb-2 relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <span class="text-gray-500">></span>
                </div>
                <input type="text" id="url-input"
                       class="w-full py-4 pl-10 pr-4 rounded-lg terminal-input text-base sm:text-lg tracking-wide placeholder-gray-600 focus:placeholder-gray-400"
                       placeholder="Paste URL here..."
                       autocomplete="off">
            </div>

            <p class="text-xs text-gray-500 mb-8 px-2" id="input-help">Automatically strips parameters & tracking IDs.</p>

            <!-- Loading Skeleton -->
            <div id="loading-skeleton" class="hidden w-full flex-col gap-6 animate-fade-in mt-4">
                <div class="flex flex-col md:flex-row gap-6">
                    <div class="w-full md:w-1/3 aspect-[9/16] skeleton"></div>
                    <div class="w-full md:w-2/3 flex flex-col gap-4 justify-center">
                        <div class="h-6 w-3/4 skeleton"></div>
                        <div class="h-4 w-1/2 skeleton"></div>
                        <div class="h-8 w-1/3 skeleton mt-4"></div>
                    </div>
                </div>
                <div class="h-14 w-full skeleton mt-4"></div>
            </div>

            <!-- Preview & Options Area -->
            <div id="preview-section" class="w-full mt-4 flex flex-col gap-8">

                <!-- Video Preview Card -->
                <div class="group relative flex flex-col md:flex-row gap-6 bg-[#0a0a0a] p-4 rounded-xl border border-white/5 shadow-inner transition-all hover:border-white/10">
                    <!-- Thumbnail -->
                    <div class="w-full md:w-1/3 aspect-[9/16] bg-black rounded-lg overflow-hidden relative shadow-lg">
                        <img id="video-thumbnail" src="" alt="Preview" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                        <div class="absolute inset-0 img-overlay flex flex-col justify-end p-3">
                            <span id="platform-badge" class="self-start px-2 py-1 bg-white/10 backdrop-blur-md rounded text-xs font-bold text-white mb-auto border border-white/10 uppercase tracking-wider"></span>
                        </div>
                    </div>

                    <!-- Metadata -->
                    <div class="w-full md:w-2/3 flex flex-col justify-center gap-4 py-2">
                        <h3 id="video-title" class="text-white font-medium line-clamp-3 text-lg leading-snug"></h3>
                        <p id="video-author" class="text-gray-400 text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <span></span>
                        </p>

                        <!-- Watermark Toggle (Only shows if multiple options exist usually) -->
                        <div class="mt-2 flex items-center justify-between p-3 rounded-lg bg-white/5 border border-white/5">
                            <span class="text-sm text-gray-300">Include Watermark</span>
                            <div class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
                                <input type="checkbox" name="toggle" id="watermark-toggle" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 border-[#1a1a1a] appearance-none cursor-pointer transition-transform duration-300 ease-in-out z-10"/>
                                <label for="watermark-toggle" class="toggle-label block overflow-hidden h-6 rounded-full bg-[#1a1a1a] cursor-pointer transition-colors duration-300 border border-[#404040]"></label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Quality Selector -->
                <div class="flex flex-col gap-2 z-20">
                    <label class="text-xs text-gray-500 uppercase tracking-wider ml-1">Resolution / Quality</label>
                    <div class="custom-select-wrapper">
                        <div class="custom-select" id="quality-custom-select">
                            <div class="custom-select__trigger">
                                <span id="custom-select-text" class="text-white">Auto (Best Available)</span>
                                <div class="arrow"></div>
                            </div>
                            <div class="custom-options" id="custom-options-container">
                                <!-- Options injected via JS -->
                            </div>
                        </div>
                    </div>
                    <!-- Hidden select for form value logic -->
                    <select id="quality-selector" class="hidden"></select>
                </div>

                <!-- Download Progress Bar -->
                <div id="progress-container" class="hidden w-full h-1 bg-[#1a1a1a] rounded-full overflow-hidden mt-2 relative">
                    <div id="progress-bar" class="h-full bg-white shadow-[0_0_10px_#fff] w-0 transition-all duration-300 ease-out"></div>
                </div>

                <!-- Download Action -->
                <button id="download-btn" class="w-full py-4 rounded-xl font-bold text-white btn-glow text-lg tracking-wide flex justify-center items-center gap-3">
                    <svg class="w-5 h-5 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Execute Download</span>
                </button>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full p-6 text-center text-gray-600 text-sm border-t border-white/5 bg-black/60 backdrop-blur-md relative bottom-0">
        <p class="tracking-widest uppercase text-xs">Supported: <span class="text-gray-400">TikTok</span> | <span class="text-gray-400">Instagram</span></p>
    </footer>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Elements
            const urlInput = document.getElementById('url-input');
            const loadingSkeleton = document.getElementById('loading-skeleton');
            const previewSection = document.getElementById('preview-section');
            const qualitySelector = document.getElementById('quality-selector');
            const downloadBtn = document.getElementById('download-btn');

            const videoThumbnail = document.getElementById('video-thumbnail');
            const videoTitle = document.getElementById('video-title');
            const videoAuthor = document.querySelector('#video-author span');
            const platformBadge = document.getElementById('platform-badge');

            const iconTiktok = document.getElementById('icon-tiktok');
            const iconInstagram = document.getElementById('icon-instagram');
            const watermarkToggle = document.getElementById('watermark-toggle');

            const customSelect = document.getElementById('quality-custom-select');
            const customSelectText = document.getElementById('custom-select-text');
            const customOptionsContainer = document.getElementById('custom-options-container');

            const progressContainer = document.getElementById('progress-container');
            const progressBar = document.getElementById('progress-bar');

            const csrfToken = document.getElementById('csrf_token').value;

            let currentVideoData = null;
            let typingTimer;
            const doneTypingInterval = 800;

            // Ripple Effect on Button
            downloadBtn.addEventListener('click', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const ripple = document.createElement('span');
                ripple.className = 'ripple';
                ripple.style.left = `${x}px`;
                ripple.style.top = `${y}px`;

                this.appendChild(ripple);
                setTimeout(() => ripple.remove(), 600);
            });

            // Custom Select Logic
            customSelect.querySelector('.custom-select__trigger').addEventListener('click', function() {
                customSelect.classList.toggle('open');
            });

            window.addEventListener('click', function(e) {
                if (!customSelect.contains(e.target)) {
                    customSelect.classList.remove('open');
                }
            });

            function populateCustomSelect(resolutions) {
                customOptionsContainer.innerHTML = '';
                qualitySelector.innerHTML = '';

                resolutions.forEach((res, index) => {
                    // Update native hidden select
                    const option = document.createElement('option');
                    option.value = index;
                    option.dataset.isWatermarked = res.quality === 'watermark' ? 'true' : 'false';
                    qualitySelector.appendChild(option);

                    // Update custom select
                    const customOpt = document.createElement('div');
                    customOpt.className = `custom-option ${index === 0 ? 'selected' : ''}`;
                    customOpt.dataset.value = index;
                    customOpt.dataset.isWatermarked = res.quality === 'watermark' ? 'true' : 'false';

                    // Add badge to label if watermark
                    let labelHtml = `<span>${res.label}</span>`;
                    if (res.quality === 'watermark') {
                        labelHtml += `<span class="text-xs bg-gray-800 text-gray-400 px-2 py-1 rounded ml-2">WM</span>`;
                    } else if (res.quality === 'hd') {
                         labelHtml += `<span class="text-xs bg-white/10 text-white px-2 py-1 rounded ml-2">HD</span>`;
                    }

                    customOpt.innerHTML = labelHtml;

                    customOpt.addEventListener('click', function() {
                        // Update UI
                        customOptionsContainer.querySelectorAll('.custom-option').forEach(el => el.classList.remove('selected'));
                        this.classList.add('selected');
                        customSelectText.innerText = res.label;
                        customSelect.classList.remove('open');

                        // Update hidden native select
                        qualitySelector.value = this.dataset.value;

                        // Sync toggle switch state
                        watermarkToggle.checked = this.dataset.isWatermarked === 'true';
                    });

                    customOptionsContainer.appendChild(customOpt);
                });

                if (resolutions.length > 0) {
                    customSelectText.innerText = resolutions[0].label;
                    qualitySelector.value = 0;
                    watermarkToggle.checked = resolutions[0].quality === 'watermark';
                }
            }

            // Watermark Toggle Logic (syncs with custom select)
            watermarkToggle.addEventListener('change', (e) => {
                const wantWatermark = e.target.checked;
                const options = Array.from(customOptionsContainer.querySelectorAll('.custom-option'));

                // Find matching option
                let targetOpt = options.find(opt => opt.dataset.isWatermarked === String(wantWatermark));

                if (targetOpt) {
                    targetOpt.click();
                } else {
                    // Revert toggle if no option available
                    e.target.checked = !wantWatermark;
                    showToast('This quality option is unavailable for this video.', 'info');
                }
            });


            // URL Input Logic
            function cleanUrl(url) {
                // Remove all query params
                return url.split('?')[0].trim();
            }

            urlInput.addEventListener('input', () => {
                clearTimeout(typingTimer);

                let rawUrl = urlInput.value.trim();
                if (rawUrl !== '') {
                    // Clean URL immediately on input if it has query params
                    if (rawUrl.includes('?')) {
                        rawUrl = cleanUrl(rawUrl);
                        urlInput.value = rawUrl;
                    }

                    // Update icons based on typing
                    detectPlatformIcon(rawUrl);
                    typingTimer = setTimeout(() => processUrl(rawUrl), doneTypingInterval);
                } else {
                    resetUI();
                }
            });

            urlInput.addEventListener('paste', (e) => {
                // Get pasted text immediately
                let pastedData = (e.clipboardData || window.clipboardData).getData('text');
                setTimeout(() => {
                    const cleaned = cleanUrl(pastedData);
                    urlInput.value = cleaned; // Instantly clean in input box
                    processUrl(cleaned);
                }, 50);
            });

            function detectPlatformIcon(url) {
                iconTiktok.classList.remove('icon-active');
                iconInstagram.classList.remove('icon-active');

                if (url.includes('tiktok.com')) iconTiktok.classList.add('icon-active');
                if (url.includes('instagram.com')) iconInstagram.classList.add('icon-active');
            }

            function showToast(message, type = 'info') {
                const container = document.getElementById('toast-container');
                const toast = document.createElement('div');
                toast.className = `toast toast-${type} shadow-lg`;

                let icon = '';
                if(type === 'error') icon = '<svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                if(type === 'success') icon = '<svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';

                toast.innerHTML = `<div class="flex items-center">${icon}<span>${message}</span></div>`;
                container.appendChild(toast);

                // Trigger reflow for animation
                void toast.offsetWidth;
                toast.classList.add('show');

                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => container.removeChild(toast), 400);
                }, 4000);
            }

            function resetUI() {
                previewSection.classList.remove('show');
                loadingSkeleton.classList.add('hidden');
                iconTiktok.classList.remove('icon-active');
                iconInstagram.classList.remove('icon-active');
                currentVideoData = null;
            }

            async function processUrl(rawUrl) {
                const url = cleanUrl(rawUrl);
                if (!url) return;

                if (!url.startsWith('http://') && !url.startsWith('https://')) {
                    showToast('Invalid URL format. Include http(s)://', 'error');
                    return;
                }

                resetUI();
                detectPlatformIcon(url);
                loadingSkeleton.classList.remove('hidden');

                try {
                    const response = await fetch('api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'fetch_info',
                            url: url,
                            csrf_token: csrfToken
                        })
                    });

                    const data = await response.json();

                    if (!response.ok || data.error) {
                        throw new Error(data.error || 'Metadata extraction failed.');
                    }

                    currentVideoData = data;
                    updateUIWithVideo(data);

                } catch (error) {
                    showToast(error.message, 'error');
                    resetUI();
                } finally {
                    loadingSkeleton.classList.add('hidden');
                }
            }

            function updateUIWithVideo(data) {
                // Image loading visual tweak
                videoThumbnail.style.opacity = '0';
                videoThumbnail.src = data.thumbnail || 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" fill="%23111"><rect width="100%" height="100%" fill="%23111"/></svg>';
                videoThumbnail.onload = () => { videoThumbnail.style.opacity = '1'; }

                videoTitle.innerText = data.title || 'Unknown Title';
                videoAuthor.innerText = `@${data.author}`;
                platformBadge.innerText = data.platform;

                populateCustomSelect(data.resolutions);

                previewSection.classList.add('show');

                // Scroll to preview elegantly
                setTimeout(() => {
                    previewSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 100);
            }

            // Download Trigger
            downloadBtn.addEventListener('click', () => {
                if (!currentVideoData) return;

                const selectedIndex = qualitySelector.value;
                const resolution = currentVideoData.resolutions[selectedIndex];

                if (!resolution || !resolution.url) {
                    showToast('Selected stream unavailable.', 'error');
                    return;
                }

                // UI Loading State
                const originalContent = downloadBtn.innerHTML;
                downloadBtn.disabled = true;
                downloadBtn.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Processing...</span>';

                progressContainer.classList.remove('hidden');
                progressBar.style.width = '15%';

                const platform = currentVideoData.platform;
                const filename = `${platform}_${Date.now()}.mp4`;
                const downloadUrl = `download.php?url=${encodeURIComponent(resolution.url)}&token=${encodeURIComponent(csrfToken)}&sig=${encodeURIComponent(resolution.signature)}&filename=${encodeURIComponent(filename)}`;

                // Simulate progress
                let progress = 15;
                const interval = setInterval(() => {
                    progress += Math.random() * 10;
                    if(progress > 90) progress = 90;
                    progressBar.style.width = `${progress}%`;
                }, 400);

                // Hidden iframe/anchor for download trigger
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = downloadUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);

                // Reset UI
                setTimeout(() => {
                    clearInterval(interval);
                    progressBar.style.width = '100%';

                    setTimeout(() => {
                        progressContainer.classList.add('hidden');
                        progressBar.style.width = '0%';
                        downloadBtn.disabled = false;
                        downloadBtn.innerHTML = originalContent;
                        showToast('Download started', 'success');
                    }, 500);
                }, 2000);
            });
        });
    </script>
</body>
</html>