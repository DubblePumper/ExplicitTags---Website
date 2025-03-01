document.addEventListener('DOMContentLoaded', function() {
    const uploadTabBtn = document.getElementById('uploadTabBtn');
    const urlTabBtn = document.getElementById('urlTabBtn');
    const uploadSection = document.getElementById('uploadSection');
    const urlSection = document.getElementById('urlSection');
    const dropZone = document.getElementById('dropZone');
    const videoFile = document.getElementById('videoFile');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const videoUrl = document.getElementById('videoUrl');
    const submitButton = document.getElementById('submitButton');
    const urlErrorElement = document.getElementById('urlError');
    
    // Initialize tabs immediately before any other scripts run
    initializeTabs();
    
    // Check initial state for submit button
    updateSubmitButtonState();
    
    function initializeTabs() {
        const activeTab = localStorage.getItem('activeVideoTab');
        if (activeTab === 'url') {
            // Initially set URL tab as active without animation
            urlTabBtn.classList.remove('border-transparent', 'text-TextWhite', 'hover:text-secondaryDarker');
            urlTabBtn.classList.add('border-secondary', 'text-secondary');
            uploadTabBtn.classList.remove('border-secondary', 'text-secondary');
            uploadTabBtn.classList.add('border-transparent', 'text-TextWhite', 'hover:text-secondaryDarker');
            
            uploadSection.classList.add('opacity-0', 'translate-x-8', 'pointer-events-none');
            uploadSection.classList.remove('opacity-100', 'translate-x-0');
            urlSection.classList.remove('opacity-0', 'translate-x-8', 'pointer-events-none');
            urlSection.classList.add('opacity-100', 'translate-x-0');
        } else {
            // Ensure upload tab is properly displayed as the default
            uploadTabBtn.classList.remove('border-transparent', 'text-TextWhite', 'hover:text-secondaryDarker');
            uploadTabBtn.classList.add('border-secondary', 'text-secondary');
            urlTabBtn.classList.remove('border-secondary', 'text-secondary');
            urlTabBtn.classList.add('border-transparent', 'text-TextWhite', 'hover:text-secondaryDarker');
            
            uploadSection.classList.remove('opacity-0', 'translate-x-8', 'pointer-events-none');
            uploadSection.classList.add('opacity-100', 'translate-x-0');
            urlSection.classList.add('opacity-0', 'translate-x-8', 'pointer-events-none');
            urlSection.classList.remove('opacity-100', 'translate-x-0');
        }
    }
    
    // Function to switch to upload tab with animation
    function switchToUploadTab() {
        // Active tab styling
        uploadTabBtn.classList.remove('border-transparent', 'text-TextWhite', 'hover:text-secondaryDarker');
        uploadTabBtn.classList.add('border-secondary', 'text-secondary');
        
        // Inactive tab styling
        urlTabBtn.classList.remove('border-secondary', 'text-secondary');
        urlTabBtn.classList.add('border-transparent', 'text-TextWhite', 'hover:text-secondaryDarker');
        
        // Animate out URL tab
        urlSection.classList.add('opacity-0', 'translate-x-8', 'pointer-events-none');
        urlSection.classList.remove('opacity-100', 'translate-x-0');
        
        // Animate in upload tab
        uploadSection.classList.remove('opacity-0', 'translate-x-8', 'pointer-events-none');
        uploadSection.classList.add('opacity-100', 'translate-x-0');
        
        // Save state to localStorage
        localStorage.setItem('activeVideoTab', 'upload');
        
        // Check submit button state after tab switch
        updateSubmitButtonState();
    }
    
    // Function to switch to URL tab with animation
    function switchToUrlTab() {
        // Active tab styling
        urlTabBtn.classList.remove('border-transparent', 'text-TextWhite', 'hover:text-secondaryDarker');
        urlTabBtn.classList.add('border-secondary', 'text-secondary');
        
        // Inactive tab styling
        uploadTabBtn.classList.remove('border-secondary', 'text-secondary');
        uploadTabBtn.classList.add('border-transparent', 'text-TextWhite', 'hover:text-secondaryDarker');
        
        // Animate out upload tab
        uploadSection.classList.add('opacity-0', 'translate-x-8', 'pointer-events-none');
        uploadSection.classList.remove('opacity-100', 'translate-x-0');
        
        // Animate in URL tab
        urlSection.classList.remove('opacity-0', 'translate-x-8', 'pointer-events-none');
        urlSection.classList.add('opacity-100', 'translate-x-0');
        
        // Save state to localStorage
        localStorage.setItem('activeVideoTab', 'url');
        
        // Check submit button state after tab switch
        updateSubmitButtonState();
    }
    
    // Function to update submit button state
    function updateSubmitButtonState() {
        const activeTab = localStorage.getItem('activeVideoTab');
        if (activeTab === 'url') {
            // For URL tab, perform validation and then update button state
            validateUrl(videoUrl.value.trim());
        } else {
            // Check if file is selected
            submitButton.disabled = !(videoFile.files && videoFile.files.length > 0);
        }
    }
    
    // Restore tab state from localStorage on page load
    const activeTab = localStorage.getItem('activeVideoTab');
    if (activeTab === 'url') {
        // Apply without animation on page load
        urlTabBtn.classList.remove('border-transparent', 'text-TextWhite', 'hover:text-secondaryDarker');
        urlTabBtn.classList.add('border-secondary', 'text-secondary');
        uploadTabBtn.classList.remove('border-secondary', 'text-secondary');
        uploadTabBtn.classList.add('border-transparent', 'text-TextWhite', 'hover:text-secondaryDarker');
        
        uploadSection.classList.add('opacity-0', 'translate-x-8', 'pointer-events-none');
        urlSection.classList.remove('opacity-0', 'translate-x-8', 'pointer-events-none');
        urlSection.classList.add('opacity-100', 'translate-x-0');
    } else {
        // Default to upload tab if no saved state or if it was 'upload'
        // No need to apply changes since that's the default state
    }
    
    // Tab switching with event listeners
    uploadTabBtn.addEventListener('click', switchToUploadTab);
    urlTabBtn.addEventListener('click', switchToUrlTab);
    
    // File upload handling
    videoFile.addEventListener('change', function(e) {
        if (this.files.length > 0) {
            fileName.textContent = this.files[0].name;
            fileInfo.classList.remove('hidden');
            submitButton.disabled = false;
        } else {
            fileInfo.classList.add('hidden');
            submitButton.disabled = true;
        }
    });
    
    // URL input handling with client-side validation only
    videoUrl.addEventListener('input', function() {
        validateUrl(this.value.trim());
    });
    
    videoUrl.addEventListener('change', function() {
        validateUrl(this.value.trim());
    });
    
    // Function to validate URL
    function validateUrl(url) {
        // Clear previous error and styling
        clearUrlError();
        videoUrl.classList.remove('invalid-input', 'shake');
        
        // Skip validation if field is empty
        if (!url) {
            submitButton.disabled = true;
            return;
        }
        
        let isValid = true;
        
        // Check URL format
        if (!isValidUrl(url)) {
            showUrlError('Please enter a valid URL');
            submitButton.disabled = true;
            isValid = false;
        }
        
        // Check if URL is from a supported site
        else if (!isFromSupportedSite(url)) {
            showUrlError('URL must be from a supported website');
            submitButton.disabled = true;
            isValid = false;
        }
        
        // If validation fails, add shake effect and red border
        if (!isValid) {
            videoUrl.classList.add('invalid-input');
            
            // Trigger shake animation
            // Remove and re-add the class to ensure animation plays again
            videoUrl.classList.remove('shake');
            // Force a reflow to ensure the animation restarts
            void videoUrl.offsetWidth;
            videoUrl.classList.add('shake');
        } else {
            // Enable submit button if validation passes
            submitButton.disabled = false;
        }
    }
    
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
            const hostname = urlObj.hostname.toLowerCase().replace(/^www\./, '');
            
            // Check against the global supportedSites array provided by tag.php
            if (window.supportedSites && Array.isArray(window.supportedSites)) {
                return window.supportedSites.some(domain => {
                    return hostname === domain.toLowerCase() || 
                           hostname.endsWith('.' + domain.toLowerCase());
                });
            }
            return false;
        } catch (e) {
            console.error("Error validating URL:", e);
            return false;
        }
    }
    
    function showUrlError(message) {
        if (urlErrorElement) {
            urlErrorElement.textContent = message;
            urlErrorElement.classList.remove('hidden');
            // Make error text more noticeable
            urlErrorElement.classList.add('text-red-400', 'font-medium');
        }
    }
    
    function clearUrlError() {
        if (urlErrorElement) {
            urlErrorElement.textContent = '';
            urlErrorElement.classList.add('hidden');
            // Remove error styling from input if present
            videoUrl.classList.remove('invalid-input');
        }
    }
    
    // Drag and drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    // Apply Tailwind classes on drag events
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight() {
        // Add Tailwind classes for highlight state
        dropZone.classList.remove('border-secondaryDarker/50');
        dropZone.classList.add('border-secondary', 'bg-secondary/10');
    }
    
    function unhighlight() {
        // Remove highlight state classes
        dropZone.classList.remove('border-secondary', 'bg-secondary/10');
        dropZone.classList.add('border-secondaryDarker/50');
    }
    
    dropZone.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        videoFile.files = files;
        
        if (files.length > 0) {
            fileName.textContent = files[0].name;
            fileInfo.classList.remove('hidden');
            submitButton.disabled = false;
        }
    }
});
