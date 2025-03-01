<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/include-all.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/utils/tagVideoThroughUrl/database-functions.php';
$gradients = getRandomGradientClass(true);

// Get supported websites from the database
$supportedWebsitesData = getSupportedWebsites();

// If database query fails, use fallback data
if (empty($supportedWebsitesData)) {
    // Fallback data if database query fails
    $supportedWebsitesData = [
        ['website_name' => 'Pornhub', 'website_url' => 'https://pornhub.com'],
        ['website_name' => 'RedTube', 'website_url' => 'https://redtube.com'],
        ['website_name' => 'SpankBang', 'website_url' => 'https://spankbang.com'],
        ['website_name' => 'Tnaflix', 'website_url' => 'https://tnaflix.com'],
        ['website_name' => 'Tube8', 'website_url' => 'https://tube8.com'],
        ['website_name' => 'xHamster', 'website_url' => 'https://xhamster.com'],
        ['website_name' => 'XNXX', 'website_url' => 'https://xnxx.com'],
        ['website_name' => 'XVideos', 'website_url' => 'https://xvideos.com'],
        ['website_name' => 'YouPorn', 'website_url' => 'https://youporn.com'],
    ];
}

// Extract names and domains for display and validation
$supportedSiteNames = array_column($supportedWebsitesData, 'website_name');
$supportedDomains = [];
foreach ($supportedWebsitesData as $site) {
    $domain = parse_url($site['website_url'], PHP_URL_HOST);
    if ($domain) {
        $supportedDomains[] = preg_replace('/^www\./', '', $domain);
    }
}

// Convert to comma-separated string for display in tooltip
$supportedSitesText = implode(', ', $supportedSiteNames);

// Additional code to handle redirection
$redirect = isset($_GET['redirect']) && $_GET['redirect'] === 'true';
$videoId = isset($_GET['id']) ? (int)$_GET['id'] : null;

// If redirected back with a video ID, redirect to the processing page
if ($redirect && $videoId) {
    header("Location: /utils/process-video?id={$videoId}");
    exit;
}
?>

<body class="text-TextWhite">
    <div id="preloader">
        <div class="spinner"></div>
    </div>
    <header>
        <div class="text-center mt-4 md:mt-10 flex flex-col items-center justify-center space-y-1 md:space-y-2 px-4" data-aos="fade-down" data-aos-duration="1000">
            <h1 class="text-2xl md:text-4xl font-bold bg-gradient-to-bl from-secondary to-tertery bg-clip-text text-transparent" data-aos="fade-down" data-aos-duration="1000">Adult Video AI Analysis</h1>
            <h2 class="text-lg md:text-xl bg-gradient-to-bl from-secondary to-tertery bg-clip-text text-transparent" data-aos="fachfade-right" data-aos-duration="1000">and our AI will identify performers and tags</h3>
        </div>
    </header>
    <main class="min-h-screen">
        <div class="container mx-auto px-4 py-4 md:py-8 max-w-3xl">
            <div id="upload" class="bg-gray-900/50 p-4 md:p-6 rounded-lg shadow-lg mt-6 md:mt-10 bg-clip-padding backdrop-filter backdrop-blur-sm border border-primairy" data-aos="fade-up" data-aos-duration="1000">
                <!-- Update the form action to include the current path for redirection -->
                <form id="videoForm" action="/utils/process-video" method="post" enctype="multipart/form-data" class="space-y-6 md:space-y-8">
                    <input type="hidden" name="return_url" value="/tag?redirect=true">
                    <div class="tabs flex border-b border-gray-600 mb-4">
                        <button type="button" id="uploadTabBtn" class="tab-btn px-2 md:px-4 py-1 md:py-2 text-sm md:text-base border-b-2 border-secondary text-secondary font-medium transition-all duration-300 ease-in-out">Upload Video</button>
                        <button type="button" id="urlTabBtn" class="tab-btn px-2 md:px-4 py-1 md:py-2 text-sm md:text-base border-b-2 border-transparent text-TextWhite hover:text-secondaryDarker transition-all duration-300 ease-in-out">Video URL</button>
                    </div>
                    
                    <div class="relative">
                        <div id="uploadSection" class="tab-content absolute w-full transition-all duration-300 ease-in-out opacity-100 translate-x-0">
                            <div class="border-2 border-dashed border-secondaryDarker/50 rounded-lg p-3 md:p-6 text-center cursor-pointer hover:border-secondary transition-all" id="dropZone">
                                <input type="file" name="videoFile" id="videoFile" class="hidden" accept="video/*">
                                <label for="videoFile" class="cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 md:h-12 md:w-12 mx-auto text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p class="mt-2 text-xs md:text-sm text-TextWhite">Drag and drop your video here or click to browse</p>
                                    <p class="text-xs text-gray-400 mt-1">Supported formats: MP4, WebM, AVI, MOV</p>
                                </label>
                            </div>
                            <div id="fileInfo" class="mt-3 md:mt-4 hidden">
                                <p class="text-sm">Selected file: <span id="fileName" class="font-medium"></span></p>
                            </div>
                        </div>
                        
                        <div id="urlSection" class="tab-content absolute w-full transition-all duration-300 ease-in-out opacity-0 translate-x-8 pointer-events-none">
                            <div class="space-y-2">
                                <label for="videoUrl" class="block text-xs md:text-sm font-medium flex flex-wrap items-center">
                                    <span>Enter adult video URL</span>
                                    <div class="relative inline-block group">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 md:w-5 md:h-5 ml-1 inline cursor-help">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                        </svg>
                                        <div class="absolute z-10 left-0 md:left-full mt-2 md:mt-0 md:ml-2 md:top-1/2 md:-translate-y-1/2 w-64 md:w-80 px-3 py-2 bg-gray-800 text-xs text-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 whitespace-normal">
                                            <p class="mb-1 font-medium">Supported sites:</p>
                                            <p class="text-gray-300"><?php echo htmlspecialchars($supportedSitesText); ?></p>
                                        </div>
                                    </div>
                                </label>
                                <input type="url" name="videoUrl" id="videoUrl" placeholder="https://pornhub.com/view_video.php?viewkey=xxx" class="w-full px-3 md:px-4 py-1 md:py-2 text-sm rounded-md bg-darkPrimairy border border-secondary focus:border-secondaryTerteryMix focus:outline-none transition-colors duration-300">
                                <div class="flex items-start justify-between">
                                    <p class="text-xs text-gray-400">Paste a direct link to an adult video from a supported platform</p>
                                    <div id="urlError" class="hidden text-xs text-red-400 font-medium"></div>
                                </div>

                            </div>
                        </div>
                    </div>
                    
                    <!-- Empty space to ensure the form has the correct height regardless of which tab is active -->
                    <div class="tab-spacer h-64 md:h-72"></div>
                    
                    <div class="pt-3 md:pt-3">
                        <button type="submit" id="submitButton" class="w-full px-4 md:px-6 py-2 md:py-3 text-sm md:text-base text-white font-medium rounded-md hover:bg-secondaryTerteryMix/40 hover:text-secondary transition-all duration-500 border border-secondaryTerteryMix disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Process Video</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/scripts.php'; ?>

    <!-- Add global variables -->
    <script>
        window.APP_VERSION = '1740655170';
        window.performerDetailsCache = new Map();
        
        // Populate the list of supported sites for JavaScript validation
        window.supportedSites = <?php echo json_encode($supportedDomains); ?>;
        window.supportedSiteNames = <?php echo json_encode($supportedSiteNames); ?>;
    </script>
    
    <!-- Include the script file -->
    <script src="/assets/js/tagPage/functions.js?v=<?php echo time(); ?>"></script>
    
    <script>
    // Improved validation function with error handling
    document.addEventListener('DOMContentLoaded', function() {
        const videoUrlInput = document.getElementById('videoUrl');
        const urlErrorElement = document.getElementById('urlError');
        const submitButton = document.getElementById('submitButton');
        
        videoUrlInput.addEventListener('input', validateUrl);
        videoUrlInput.addEventListener('change', validateUrl);
        
        function validateUrl() {
            const url = videoUrlInput.value.trim();
            
            // Clear previous error
            urlErrorElement.textContent = '';
            urlErrorElement.classList.add('hidden');
            videoUrlInput.classList.remove('invalid-input', 'shake');
            
            // Skip validation if field is empty
            if (!url) {
                submitButton.disabled = true;
                return;
            }
            
            // Check URL format
            if (!isValidUrl(url)) {
                showUrlError('Please enter a valid URL');
                submitButton.disabled = true;
                return;
            }
            
            // Check if URL is from a supported site
            if (!isFromSupportedSite(url)) {
                showUrlError('URL must be from a supported website');
                submitButton.disabled = true;
                return;
            }
            
            // Enable submit button if validation passes
            submitButton.disabled = false;
        }
        
        // Added error handling for direct URLs
        function isValidUrl(url) {
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        }
        
        function isFromSupportedSite(url) {
            try {
                const urlObj = new URL(url);
                const hostname = urlObj.hostname.replace(/^www\./, '');
                
                // More robust hostname matching
                return window.supportedSites.some(site => {
                    const siteDomain = site.toLowerCase().replace(/\s/g, '');
                    return hostname === siteDomain || hostname.endsWith('.' + siteDomain);
                });
            } catch (e) {
                return false;
            }
        }
        
        function showUrlError(message) {
            urlErrorElement.textContent = message;
            urlErrorElement.classList.remove('hidden');
            videoUrlInput.classList.add('invalid-input', 'shake');
        }
    });
</script>

    <script>
        // Add code to handle form submission via JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('videoForm');
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Don't prevent default - we want the form to submit normally
                    
                    // We could add tracking here if needed
                    console.log('Form submitted');
                    
                    // For analytics/tracking purposes
                    if (window.gtag) {
                        gtag('event', 'form_submit', {
                            'event_category': 'video',
                            'event_label': 'process_request'
                        });
                    }
                });
            }
        });
    </script>
    
    <style>
        /* Shake animation for invalid input */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .shake {
            animation: shake 0.6s cubic-bezier(.36,.07,.19,.97) both;
        }
        
        .invalid-input {
            border-color: #ef4444 !important; /* Use Tailwind's red-500 color */
            box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.2);
        }
    </style>
</body>
</html>