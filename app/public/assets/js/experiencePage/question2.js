// Helper functions defined at the top level
function showError(element, message) {
    element.textContent = message;
    element.classList.remove('hidden');
}

function hideError(element) {
    element.classList.add('hidden');
    element.textContent = '';
}

function addImage(container, src, className) {
    let img = document.createElement('img');
    img.src = src;
    img.loading = 'lazy'; // ensure native lazy loading is enabled
    img.classList.add(className, 'opacity-100', 'group-hover:opacity-15', 'transition-opacity', 
                     'duration-500', 'mx-auto', 'size-3/4', 'w-16', 'h-16', 'sm:w-24', 
                     'sm:h-24', 'md:w-32', 'md:h-32');
    container.appendChild(img);
}

function updateGridColumns(container, count) {
    container.classList.remove('grid-cols-1', 'grid-cols-2', 'grid-cols-3');
    if (count === 1) {
        container.classList.add('grid-cols-1');
    } else if (count === 2) {
        container.classList.add('grid-cols-2');
    } else {
        container.classList.add('grid-cols-3');
    }
}

function forceUpdateQuestion4() {
    window.dispatchEvent(new Event('myCustomUpdate'));
}

// Initialize input elements
document.addEventListener('DOMContentLoaded', () => {
    // Get all required elements
    const manMinInput = document.getElementById('manMin');
    const manMaxInput = document.getElementById('manMax');
    const womanMinInput = document.getElementById('womanMin');
    const womanMaxInput = document.getElementById('womanMax');
    const manIMG = document.getElementById('manIMG');
    const womanIMG = document.getElementById('womanIMG');

    function updateManImages() {
        while (manIMG.firstChild) {
            manIMG.removeChild(manIMG.firstChild);
        }
        const maxVal = parseInt(manMaxInput.value) || 0;
        for (let i = 0; i < maxVal; i++) {
            addImage(manIMG, '/assets/images/website_Images/man_front.svg', 'man-front');
        }
        updateGridColumns(manIMG, maxVal);
    }

    function updateWomanImages() {
        while (womanIMG.firstChild) {
            womanIMG.removeChild(womanIMG.firstChild);
        }
        const maxVal = parseInt(womanMaxInput.value) || 0;
        for (let i = 0; i < maxVal; i++) {
            addImage(womanIMG, '/assets/images/website_Images/woman_front.svg', 'woman-front');
        }
        updateGridColumns(womanIMG, maxVal);
    }

    function validateManInputs() {
        const manMinError = document.getElementById('manMinError');
        const manMaxError = document.getElementById('manMaxError');
        const womanMaxValue = parseInt(womanMaxInput.value) || 0;
        let hasError = false;

        // Reset errors
        hideError(manMinError);
        hideError(manMaxError);

        let minVal = parseInt(manMinInput.value);
        let maxVal = parseInt(manMaxInput.value);

        // All validations in one place
        if (minVal < 0) {
            showError(manMinError, 'Minimum value cannot be negative');
            manMinInput.value = 0;
            minVal = 0;
            hasError = true;
        }

        if (maxVal > 99) {
            showError(manMaxError, 'Maximum value cannot exceed 99');
            manMaxInput.value = 99;
            maxVal = 99;
            hasError = true;
        }

        if (minVal > maxVal) {
            showError(manMaxError, 'Maximum cannot be less than minimum');
            manMaxInput.value = minVal;
            maxVal = minVal;
            hasError = true;
        }

        if (maxVal === 0 && womanMaxValue === 0) {
            showError(manMaxError, 'At least one person required');
            manMinInput.value = 1;
            manMaxInput.value = 1;
            hasError = true;
        }

        updateManImages();
        if(!hasError) {
            localStorage.setItem('manMin', manMinInput.value);
            localStorage.setItem('manMax', manMaxInput.value);
            forceUpdateQuestion4(); // trigger re-render in question4
        }
        return !hasError;
    }

    function validateWomanInputs() {
        const womanMinError = document.getElementById('womanMinError');
        const womanMaxError = document.getElementById('womanMaxError');
        const manMaxValue = parseInt(manMaxInput.value) || 0;
        let hasError = false;

        // Reset errors
        hideError(womanMinError);
        hideError(womanMaxError);

        let minVal = parseInt(womanMinInput.value);
        let maxVal = parseInt(womanMaxInput.value);

        // All validations in one place
        if (minVal < 0) {
            showError(womanMinError, 'Minimum value cannot be negative');
            womanMinInput.value = 0;
            minVal = 0;
            hasError = true;
        }

        if (maxVal > 99) {
            showError(womanMaxError, 'Maximum value cannot exceed 99');
            womanMaxInput.value = 99;
            maxVal = 99;
            hasError = true;
        }

        if (minVal > maxVal) {
            showError(womanMaxError, 'Maximum cannot be less than minimum');
            womanMaxInput.value = minVal;
            maxVal = minVal;
            hasError = true;
        }

        if (maxVal === 0 && manMaxValue === 0) {
            showError(womanMaxError, 'At least one person required');
            womanMinInput.value = 1;
            womanMaxInput.value = 1;
            hasError = true;
        }

        updateWomanImages();
        if(!hasError) {
            localStorage.setItem('womanMin', womanMinInput.value);
            localStorage.setItem('womanMax', womanMaxInput.value);
            forceUpdateQuestion4(); // trigger re-render in question4
        }
        return !hasError;
    }

    // Attach event listeners
    manMinInput.addEventListener('input', validateManInputs);
    manMaxInput.addEventListener('input', validateManInputs);
    womanMinInput.addEventListener('input', validateWomanInputs);
    womanMaxInput.addEventListener('input', validateWomanInputs);

    // Add new cache update listeners
    manMinInput.addEventListener('input', () => setCache('manMin', manMinInput.value));
    manMaxInput.addEventListener('input', () => setCache('manMax', manMaxInput.value));
    womanMinInput.addEventListener('input', () => setCache('womanMin', womanMinInput.value));
    womanMaxInput.addEventListener('input', () => setCache('womanMax', womanMaxInput.value));

    // Add these new lines
    manMinInput.addEventListener('change', validateManInputs);
    manMaxInput.addEventListener('change', validateManInputs);
    womanMinInput.addEventListener('change', validateWomanInputs);
    womanMaxInput.addEventListener('change', validateWomanInputs);

    // Initialize values from cache
    const initFromCache = () => {
        manMinInput.value = getCache('manMin') || 1;
        manMaxInput.value = getCache('manMax') || 1;
        womanMinInput.value = getCache('womanMin') || 1;
        womanMaxInput.value = getCache('womanMax') || 1;
        
        // Trigger validation and updates
        validateManInputs();
        validateWomanInputs();
        updateManImages();
        updateWomanImages();
    }

    // Update cache whenever inputs change
    const updateCache = () => {
        setCache('manMin', manMinInput.value);
        setCache('manMax', manMaxInput.value);
        setCache('womanMin', womanMinInput.value);
        setCache('womanMax', womanMaxInput.value);
    }

    // Add cache updates to existing event listeners
    manMinInput.addEventListener('input', updateCache);
    manMaxInput.addEventListener('input', updateCache);
    womanMinInput.addEventListener('input', updateCache);
    womanMaxInput.addEventListener('input', updateCache);

    // Initialize from cache
    initFromCache();

    // Initial update
    updateManImages();
    updateWomanImages();
});
