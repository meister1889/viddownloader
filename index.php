<?php
// index.php
require_once 'config.php';
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
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
            color: #d4d4d4;
            min-height: 100vh;
        }

        /* Glassmorphism */
        .glass-panel {
            background: rgba(45, 45, 45, 0.4);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.5);
        }

        /* Inputs and Selects */
        .terminal-input {
            background: rgba(10, 10, 10, 0.6);
            border: 1px solid #404040;
            color: #ffffff;
            transition: all 0.3s ease;
        }

        .terminal-input:focus {
            border-color: #ffffff;
            outline: none;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
        }

        /* Buttons */
        .btn-glow {
            background: #2d2d2d;
            border: 1px solid #525252;
            transition: all 0.3s ease;
        }

        .btn-glow:hover {
            background: #404040;
            box-shadow: 0 0 15px rgba(160, 160, 160, 0.3);
            transform: scale(1.02);
            border-color: #a0a0a0;
        }

        /* Toast Notifications */
        #toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 50;
        }

        .toast {
            min-width: 250px;
            padding: 1rem;
            margin-top: 0.5rem;
            border-radius: 8px;
            color: white;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast-error { background: rgba(220, 38, 38, 0.9); border: 1px solid #ef4444; }
        .toast-success { background: rgba(22, 163, 74, 0.9); border: 1px solid #22c55e; }
        .toast-info { background: rgba(37, 99, 235, 0.9); border: 1px solid #3b82f6; }

        /* Skeleton loader */
        .skeleton {
            background: linear-gradient(90deg, #2d2d2d 25%, #404040 50%, #2d2d2d 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Progress Bar */
        .progress-container {
            width: 100%;
            height: 4px;
            background-color: #1a1a1a;
            border-radius: 2px;
            overflow: hidden;
            display: none;
        }

        .progress-bar {
            height: 100%;
            background-color: #d4d4d4;
            width: 0%;
            transition: width 0.3s ease;
        }

        /* SVG Icons */
        .icon-gray {
            filter: grayscale(100%) brightness(1.5);
            opacity: 0.7;
            transition: all 0.3s ease;
        }
        .icon-gray:hover {
            filter: grayscale(0%) brightness(1);
            opacity: 1;
        }

        /* Custom Dropdown Animation */
        #resolution-dropdown-container {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease-out, opacity 0.5s ease;
            opacity: 0;
        }

        #resolution-dropdown-container.show {
            max-height: 500px;
            opacity: 1;
        }
    </style>
</head>
<body class="flex flex-col">
    <!-- CSRF Token -->
    <input type="hidden" id="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

    <!-- Header -->
    <header class="w-full p-4 border-b border-gray-800 bg-black/50 backdrop-blur-sm fixed top-0 z-10">
        <div class="max-w-4xl mx-auto flex items-center">
            <span class="text-xl font-bold text-white">> VideoDownloader_</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow flex flex-col items-center justify-center p-4 mt-16 mb-16">
        <div class="w-full max-w-2xl glass-panel p-8">

            <!-- Input Area -->
            <div class="mb-6 relative">
                <input type="text" id="url-input"
                       class="w-full p-4 rounded-lg terminal-input text-lg placeholder-gray-500"
                       placeholder="Paste TikTok or Instagram video URL here...">
            </div>

            <!-- Platform Icons -->
            <div class="flex justify-center gap-6 mb-8">
                <!-- TikTok Icon -->
                <svg class="w-8 h-8 icon-gray" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 2.23-.69 4.46-2.15 6.24-1.46 1.78-3.52 2.87-5.83 3.1-2.31.23-4.66-.2-6.57-1.42-1.91-1.22-3.14-3.15-3.52-5.36-.38-2.21.05-4.52 1.25-6.38 1.2-1.86 3.12-3.04 5.29-3.32 2.17-.28 4.39.2 6.13 1.43V6.86c-1.29-.86-2.82-1.32-4.37-1.23-1.55.09-3.05.7-4.27 1.75-1.22 1.05-2.07 2.48-2.45 4.02-.38 1.54-.25 3.16.38 4.6 1.19 2.76 3.82 4.71 6.8 5.12 2.98.41 6.01-.54 8.04-2.58 2.03-2.04 3.06-4.94 2.83-7.86-.01-5.18-.01-10.36-.02-15.54z"/>
                </svg>
                <!-- Instagram Icon -->
                <svg class="w-8 h-8 icon-gray" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                </svg>
            </div>

            <!-- Loading Skeleton -->
            <div id="loading-skeleton" class="hidden w-full flex-col gap-4 mt-8">
                <div class="h-48 w-full rounded-lg skeleton"></div>
                <div class="h-10 w-full rounded-lg skeleton mt-4"></div>
                <div class="h-12 w-full rounded-lg skeleton mt-2"></div>
            </div>

            <!-- Preview & Options Area -->
            <div id="resolution-dropdown-container" class="w-full mt-4 flex flex-col gap-6">
                <!-- Video Preview -->
                <div class="flex flex-col md:flex-row gap-6 bg-black/30 p-4 rounded-lg border border-gray-800">
                    <div class="w-full md:w-1/3 aspect-[9/16] bg-gray-900 rounded overflow-hidden relative border border-gray-700">
                        <img id="video-thumbnail" src="" alt="Video Preview" class="w-full h-full object-cover">
                        <span id="platform-badge" class="absolute top-2 right-2 bg-black/70 text-xs px-2 py-1 rounded border border-gray-600"></span>
                    </div>
                    <div class="w-full md:w-2/3 flex flex-col justify-center gap-4">
                        <h3 id="video-title" class="text-white font-medium line-clamp-3"></h3>
                        <p id="video-author" class="text-gray-400 text-sm"></p>
                        <div class="inline-block px-3 py-1 bg-gray-800 rounded border border-gray-600 w-fit text-sm">
                            <span id="quality-indicator">Ready to download</span>
                        </div>
                    </div>
                </div>

                <!-- Quality Selector -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm text-gray-400">Select Quality:</label>
                    <select id="quality-selector" class="w-full p-3 rounded-lg terminal-input text-base appearance-none cursor-pointer">
                        <!-- Options populated via JS -->
                    </select>
                </div>

                <!-- Download Progress -->
                <div class="progress-container" id="progress-container">
                    <div class="progress-bar" id="progress-bar"></div>
                </div>

                <!-- Download Action -->
                <button id="download-btn" class="w-full py-4 rounded-lg font-bold text-white btn-glow text-lg flex justify-center items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download Video
                </button>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full p-6 text-center text-gray-500 text-sm border-t border-gray-800 mt-auto bg-black/50 backdrop-blur-sm fixed bottom-0">
        <p>Supported platforms: [TikTok] [Instagram]</p>
    </footer>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const urlInput = document.getElementById('url-input');
            const loadingSkeleton = document.getElementById('loading-skeleton');
            const dropdownContainer = document.getElementById('resolution-dropdown-container');
            const qualitySelector = document.getElementById('quality-selector');
            const downloadBtn = document.getElementById('download-btn');
            const videoThumbnail = document.getElementById('video-thumbnail');
            const videoTitle = document.getElementById('video-title');
            const videoAuthor = document.getElementById('video-author');
            const platformBadge = document.getElementById('platform-badge');
            const qualityIndicator = document.getElementById('quality-indicator');
            const csrfToken = document.getElementById('csrf_token').value;
            const progressContainer = document.getElementById('progress-container');
            const progressBar = document.getElementById('progress-bar');

            let currentVideoData = null;
            let typingTimer;
            const doneTypingInterval = 1000;

            // Handle Input changes
            urlInput.addEventListener('input', () => {
                clearTimeout(typingTimer);
                if (urlInput.value.trim() !== '') {
                    typingTimer = setTimeout(processUrl, doneTypingInterval);
                } else {
                    resetUI();
                }
            });

            // Process URL on Paste
            urlInput.addEventListener('paste', () => {
                setTimeout(processUrl, 100);
            });

            function showToast(message, type = 'info') {
                const container = document.getElementById('toast-container');
                const toast = document.createElement('div');
                toast.className = `toast toast-${type}`;
                toast.innerText = message;

                container.appendChild(toast);

                // Trigger reflow
                void toast.offsetWidth;
                toast.classList.add('show');

                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => {
                        container.removeChild(toast);
                    }, 300);
                }, 4000);
            }

            function resetUI() {
                dropdownContainer.classList.remove('show');
                loadingSkeleton.classList.add('hidden');
                currentVideoData = null;
                qualitySelector.innerHTML = '';
            }

            async function processUrl() {
                const url = urlInput.value.trim();
                if (!url) return;

                // Basic validation
                if (!url.startsWith('http://') && !url.startsWith('https://')) {
                    showToast('Please enter a valid URL starting with http:// or https://', 'error');
                    return;
                }

                resetUI();
                loadingSkeleton.classList.remove('hidden');

                try {
                    const response = await fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'fetch_info',
                            url: url,
                            csrf_token: csrfToken
                        })
                    });

                    const data = await response.json();

                    if (!response.ok || data.error) {
                        throw new Error(data.error || 'Failed to fetch video details.');
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
                // Update Thumbnail & Info
                videoThumbnail.src = data.thumbnail || 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" fill="%23333"><rect width="100%" height="100%" fill="%231a1a1a"/></svg>';
                videoTitle.innerText = data.title || 'Untitled Video';
                videoAuthor.innerText = `@${data.author}`;
                platformBadge.innerText = data.platform.toUpperCase();

                // Populate Resolutions
                qualitySelector.innerHTML = '';
                data.resolutions.forEach((res, index) => {
                    const option = document.createElement('option');
                    option.value = index;
                    option.innerText = res.label;
                    qualitySelector.appendChild(option);
                });

                // Set initial quality indicator
                if (data.resolutions.length > 0) {
                     qualityIndicator.innerText = data.resolutions[0].label;
                }

                dropdownContainer.classList.add('show');
                showToast('Video found successfully!', 'success');
            }

            qualitySelector.addEventListener('change', (e) => {
                const selectedIndex = e.target.value;
                if (currentVideoData && currentVideoData.resolutions[selectedIndex]) {
                     qualityIndicator.innerText = currentVideoData.resolutions[selectedIndex].label;
                }
            });

            downloadBtn.addEventListener('click', () => {
                if (!currentVideoData) {
                    showToast('Please fetch a video first.', 'error');
                    return;
                }

                const selectedIndex = qualitySelector.value;
                const resolution = currentVideoData.resolutions[selectedIndex];

                if (!resolution || !resolution.url) {
                    showToast('Selected resolution is not available.', 'error');
                    return;
                }

                // Setup progress bar UI
                downloadBtn.disabled = true;
                downloadBtn.innerHTML = 'Downloading...';
                downloadBtn.classList.add('opacity-50');
                progressContainer.style.display = 'block';
                progressBar.style.width = '10%'; // initial state

                // Use the download.php handler
                const platform = currentVideoData.platform;
                const filename = `${platform}_video_${Date.now()}.mp4`;
                const downloadUrl = `download.php?url=${encodeURIComponent(resolution.url)}&token=${encodeURIComponent(csrfToken)}&sig=${encodeURIComponent(resolution.signature)}&filename=${encodeURIComponent(filename)}`;

                showToast('Starting download...', 'info');

                // Simulate progress since actual download progress from server to client via anchor click is hard
                let progress = 10;
                const progressInterval = setInterval(() => {
                    progress += 10;
                    if (progress > 90) clearInterval(progressInterval);
                    progressBar.style.width = `${progress}%`;
                }, 500);

                // Create a temporary link to trigger download
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = downloadUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);

                // Reset UI after a delay assuming download initiated
                setTimeout(() => {
                    clearInterval(progressInterval);
                    progressBar.style.width = '100%';

                    setTimeout(() => {
                        progressContainer.style.display = 'none';
                        progressBar.style.width = '0%';
                        downloadBtn.disabled = false;
                        downloadBtn.innerHTML = `
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download Video
                        `;
                        downloadBtn.classList.remove('opacity-50');
                        showToast('Download initiated successfully!', 'success');
                    }, 500);
                }, 3000);
            });
        });
    </script>
</body>
</html>