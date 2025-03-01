<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- AOS Animation Library -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init();
</script>

<!-- Common JavaScript -->
<script src="/assets/js/utils/cache.js"></script>

<!-- Page-specific scripts -->
<?php 
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
if (file_exists(dirname($_SERVER['DOCUMENT_ROOT']) . "/public/assets/js/{$currentPage}Page/script.js")) {
    echo "<script src=\"/assets/js/{$currentPage}Page/script.js\"></script>";
}
?>

<!-- Initialize any components -->
<script>
    // Initialize any global components or features
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Page loaded successfully!');
    });
</script>