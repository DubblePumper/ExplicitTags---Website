<?php
// No output before this PHP tag - not even whitespace
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

// Get page title based on current URL
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$pageTitle = 'ExplicitTags';
switch ($currentPage) {
    case 'index':
        $pageTitle = 'ExplicitTags - Home';
        break;
    case 'tag':
        $pageTitle = 'ExplicitTags - Tag Videos';
        break;
    case 'search':
        $pageTitle = 'ExplicitTags - Search Videos';
        break;
    case 'about':
        $pageTitle = 'ExplicitTags - About Us';
        break;
}

// Check if we need to buffer output in files that include head.php
if (!ob_get_level()) {
    ob_start();
}

// Define default values
$pageDescription = isset($pageDescription) ? $pageDescription : "Upload your adult videos and our AI will identify performers and tags";
$pageKeywords = isset($pageKeywords) ? $pageKeywords : "adult, video analysis, AI identification, porn tags, performer recognition";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords); ?>">
    
    <!-- Favicon -->
    <link rel="icon" href="/assets/favicon.ico" type="image/x-icon">

    <!-- Open Graph tags for social sharing -->
    <meta property="og:title" content="ExplicitTags - AI Adult Content Analyzer">
    <meta property="og:description" content="Advanced AI-powered system for analyzing and recommending adult content">
    <meta property="og:image" content="/assets/images/icons/ExplicitTags-logo-SVG-NoQuote.svg">
    <meta property="og:url" content="https://explicittags.com">
    <meta property="og:type" content="website">

    <!-- Twitter Card data -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="ExplicitTags - AI Adult Content Analyzer">
    <meta name="twitter:description" content="Advanced AI-powered system for analyzing and recommending adult content">
    <meta name="twitter:image" content="/assets/images/icons/ExplicitTags-logo-SVG-NoQuote.svg">

    <!-- Canonical link to avoid duplicate content issues -->
    <link rel="canonical" href="https://explicittags.com/">

    <!-- AOS Animation CDN -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" type="text/css">

    <!-- interactjs for drag and drop -->
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>

    <!-- tailwindCSS -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>

    <style type="text/tailwindcss">

    </style>

    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,container-queries"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        transparent: 'transparent',
                        current: 'currentColor',
                        'primairy': '#12143a',
                        'secondary': '#40a6ea',
                        'tertery': '#9d65ea',
                        'darkPrimairy': '#0d0d0d',
                        'secondaryTerteryMix': '#837de7',
                        'secondaryDarker': '#2b5891',
                        'white': '#e0e0e0',
                        'TextWhite': '#e0e0e0',
                        'BgDark': '#222222',
                    },
                    fontFamily: {
                        'sans': ['Helvetica', 'Roboto', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Jquery -->
    <script
        src="https://code.jquery.com/jquery-3.7.1.slim.min.js"
        integrity="sha256-kmHvs0B+OpCW5GVHUNjv9rOmY0IvSIRcf7zGUDTDQM8="
        crossorigin="anonymous"></script>
    <!-- Stylesheets and fonts -->
    <link rel="stylesheet" type="text/css" href="/assets/css/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css?family=Helvetica|Roboto" rel="stylesheet" type="text/css">

    <!-- threeJS -->
    <script async src="https://unpkg.com/es-module-shims/dist/es-module-shims.js"></script>
    <script type="importmap">
    {
        "imports": {
            "three": "https://unpkg.com/three@0.173.0/build/three.module.js",
            "three/examples/jsm/loaders/GLTFLoader": "https://unpkg.com/three@0.173.0/examples/jsm/loaders/GLTFLoader.js",
            "three/examples/jsm/loaders/DRACOLoader": "https://unpkg.com/three@0.173.0/examples/jsm/loaders/DRACOLoader.js",
            "three/examples/jsm/controls/OrbitControls": "https://unpkg.com/three@0.173.0/examples/jsm/controls/OrbitControls.js"
        }
    }
    </script>

    <script>
        // Wait for THREE.js and GLTFLoader to load
        function initThreeJs() {
            if (window.THREE && window.THREE.GLTFLoader) {
                return;
            }
            
            const checkInterval = setInterval(() => {
                if (typeof THREE !== 'undefined') {
                    window.THREE = THREE;
                    if (typeof THREE.GLTFLoader !== 'undefined') {
                        clearInterval(checkInterval);
                    }
                }
            }, 100);
        }
        initThreeJs();
    </script>

    <!-- Favicon links -->
    <link rel="icon" href="/assets/images/icons/ExplicitTags-logo-SVG-NoQuote.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/assets/images/icons/ExplicitTags-logo-SVG-NoQuote.svg" type="image/svg+xml">

    <!-- Additional meta tags -->
    <meta name="theme-color" content="#12143a">
    <meta name="msapplication-TileColor" content="#12143a">
    <meta name="msapplication-TileImage" content="/assets/images/icons/mstile-144x144.png">

    <!-- Preloader CSS -->
    <link rel="stylesheet" type="text/css" href="/assets/css/preloader.css?v=<?php echo time(); ?>">

    <!-- Load preloader script early -->
    <script src="/assets/js/preloader.js?v=<?php echo time(); ?>"></script>
</head>