function addImage(container, src, className) {
    let img = document.createElement('img');
    img.src = src;
    img.classList.add(className);
    img.classList.add('opacity-100');
    img.classList.add('group-hover:opacity-15');
    img.classList.add('transition-opacity');
    img.classList.add('duration-500');
    img.classList.add('mx-auto');
    img.classList.add('size-3/4');
    img.classList.add('w-16','h-16','sm:w-24','sm:h-24','md:w-32','md:h-32');
    container.appendChild(img);
}

// Two-way slider initialization
document.addEventListener('DOMContentLoaded', () => {
    const manMinInput = document.getElementById('manMin');
    const manMaxInput = document.getElementById('manMax');

    const manMinInputMinValue = parseInt(manMinInput.getAttribute('min'));
    const manMinInputMaxValue = parseInt(manMinInput.getAttribute('max'));


    const manMaxInputMinValue = parseInt(manMaxInput.getAttribute('min'));
    const manMaxInputMaxValue = parseInt(manMaxInput.getAttribute('max'));

    const manIMG = document.getElementById('manIMG');

    const womanMinInput = document.getElementById('womanMin');
    const womanMaxInput = document.getElementById('womanMax');

    const womanMinInputMinValue = parseInt(womanMinInput.getAttribute('min'));
    const womanMinInputMaxValue = parseInt(womanMinInput.getAttribute('max'));

    const womanMaxInputMinValue = parseInt(womanMaxInput.getAttribute('min'));
    const womanMaxInputMaxValue = parseInt(womanMaxInput.getAttribute('max'));

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

        if (parseInt(manMinInput.value) > parseInt(manMaxInput.value)) {
            manMaxInput.value = manMinInput.value;
        }
        if (parseInt(manMinInput.value) < manMinInputMinValue) {
            manMinInput.value = manMinInputMinValue;
        }
        if (parseInt(manMaxInput.value) > manMaxInputMaxValue) {
            manMaxInput.value = manMaxInputMaxValue;
        }

        // Check if both man and woman inputs are 0
        if (parseInt(manMaxInput.value) === 0 && womanMaxValue === 0) {
            manMinInput.value = 1;
            manMaxInput.value = 1;
            manMinError.classList.remove('hidden');
            manMaxError.classList.remove('hidden');
        } else {
            manMinError.classList.add('hidden');
            manMaxError.classList.add('hidden');
        }

        updateManImages();
    }

    function validateWomanInputs() {
        const womanMinError = document.getElementById('womanMinError');
        const womanMaxError = document.getElementById('womanMaxError');
        const manMaxValue = parseInt(manMaxInput.value) || 0;

        if (parseInt(womanMinInput.value) > parseInt(womanMaxInput.value)) {
            womanMaxInput.value = womanMinInput.value;
        }
        if (parseInt(womanMinInput.value) < womanMinInputMinValue) {
            womanMinInput.value = womanMinInputMinValue;
        }
        if (parseInt(womanMaxInput.value) > womanMaxInputMaxValue) {
            womanMaxInput.value = womanMaxInputMaxValue;
        }

        // Check if both man and woman inputs are 0
        if (parseInt(womanMaxInput.value) === 0 && manMaxValue === 0) {
            womanMinInput.value = 1;
            womanMaxInput.value = 1;
            womanMinError.classList.remove('hidden');
            womanMaxError.classList.remove('hidden');
        } else {
            womanMinError.classList.add('hidden');
            womanMaxError.classList.add('hidden');
        }

        if (maxVal === 0 && womanMaxValue === 0) {
            showError(manMaxError, 'At least one person required');
            manMinInput.value = 1;
            manMaxInput.value = 1;
            hasError = true;
        }

        updateManImages();
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

        const minVal = parseInt(womanMinInput.value);
        const maxVal = parseInt(womanMaxInput.value);

        // Validate min value
        if (minVal < womanMinInputMinValue) {
            showError(womanMinError, `Min value cannot be less than ${womanMinInputMinValue}`);
            womanMinInput.value = womanMinInputMinValue;
            hasError = true;
        }
        if (minVal > womanMinInputMaxValue) {
            showError(womanMinError, `Min value cannot be more than ${womanMinInputMaxValue}`);
            womanMinInput.value = womanMinInputMaxValue;
            hasError = true;
        }

        // Validate max value
        if (maxVal < womanMaxInputMinValue) {
            showError(womanMaxError, `Max value cannot be less than ${womanMaxInputMinValue}`);
            womanMaxInput.value = womanMaxInputMinValue;
            hasError = true;
        }
        if (maxVal > womanMaxInputMaxValue) {
            showError(womanMaxError, `Max value cannot be more than ${womanMaxInputMaxValue}`);
            womanMaxInput.value = womanMaxInputMaxValue;
            hasError = true;
        }

        // Validate min vs max
        if (minVal > maxVal) {
            showError(womanMinError, 'Min cannot be greater than max');
            womanMaxInput.value = minVal;
            hasError = true;
        }

        // Check if both inputs are 0
        if (maxVal === 0 && manMaxValue === 0) {
            showError(womanMaxError, 'At least one person required');
            womanMinInput.value = 1;
            womanMaxInput.value = 1;
            hasError = true;
        }

        updateWomanImages();
        return !hasError;
    }

    manMinInput.addEventListener('input', validateManInputs);
    manMaxInput.addEventListener('input', validateManInputs);
    womanMinInput.addEventListener('input', validateWomanInputs);
    womanMaxInput.addEventListener('input', validateWomanInputs);

    // Trigger image updates on page load
    updateManImages();
    updateWomanImages();
});

// Keep or adapt this function for your dynamic grid columns as needed
function updateGridColumns(container, count) {
    if (count === 1) {
        container.classList.add('grid-cols-1');
        container.classList.remove('grid-cols-2', 'grid-cols-3');
    } else if (count === 2) {
        container.classList.add('grid-cols-2');
        container.classList.remove('grid-cols-1', 'grid-cols-3');
    } else {
        container.classList.add('grid-cols-3');
        container.classList.remove('grid-cols-1', 'grid-cols-2');
    }
}