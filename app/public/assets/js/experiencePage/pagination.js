let currentQuestion = (() => {
    const param = parseInt(new URLSearchParams(window.location.search).get('question'));
    // Check if param is a valid number and within bounds (0 to number of questions - 1)
    if (isNaN(param) || param < 0 || param >= document.querySelectorAll('.question').length) {
        return 0; // Default to first question if invalid
    }
    return param;
})();
const questions = document.querySelectorAll('.question');
let debounceTimer; // Define debounceTimer
let isNavigating = false; // Lock flag for navigation

function toggleBackground(radio) {
    const radios = document.querySelectorAll('input[name="gender"]');
    radios.forEach((r) => {
        const parentDiv = r.parentElement.parentElement;
        if (r !== radio) {
            parentDiv.classList.remove('border-secondary', 'bg-darkPrimairy');
            parentDiv.classList.add('border-transparent');
            parentDiv.style.borderColor = 'transparent';
        }
    });

    if (radio.checked) {
        const parentDiv = radio.parentElement.parentElement;
        parentDiv.classList.remove('border-transparent');
        parentDiv.classList.add('border-secondary', 'bg-darkPrimairy');
        parentDiv.style.borderColor = '#40a6ea'; // Secondary color hex code
        // Cache the selected gender
        setCache('selectedGender', radio.value);
    }
}

// Add this new function
function initializeFromCache() {
    const savedGender = getCache('selectedGender');
    if (savedGender) {
        const radio = document.querySelector(`input[name="gender"][value="${savedGender}"]`);
        if (radio) {
            radio.checked = true;
            toggleBackground(radio);
        }
    }
}

function showQuestion(index) {
    // Ensure index stays within bounds
    index = Math.max(0, Math.min(index, questions.length - 1));
    
    questions.forEach((question, i) => {
        if (i === index) {
            // Remove hidden and display the question
            question.classList.remove('opacity-0', 'invisible', 'hidden');
            question.classList.add('opacity-100');
        } else {
            // Hide non-active questions
            question.classList.add('opacity-0', 'invisible', 'hidden');
            question.classList.remove('opacity-100');
        }
    });
    
    if (index === 2) {
        // Trigger validation to save current values to localStorage
        document.getElementById('manMin').dispatchEvent(new Event('change'));
        document.getElementById('manMax').dispatchEvent(new Event('change'));
        document.getElementById('womanMin').dispatchEvent(new Event('change'));
        document.getElementById('womanMax').dispatchEvent(new Event('change'));
    }
    checkButtons();
    updateURL(index);

    // Cache current question
    setCache('currentQuestion', index);
}

function nextQuestion() {
    if (isNavigating || currentQuestion >= questions.length - 1) return;
    
    // Add validation for the first question
    if (currentQuestion === 0) {
        const genderSelected = document.querySelector('input[name="gender"]:checked');
        if (!genderSelected) {
            // Add shake animation to the gender options
            const genderOptions = document.querySelectorAll('#man, #woman');
            genderOptions.forEach(option => {
                option.classList.add('shake');
                option.style.border = '2px solid red';
                setTimeout(() => {
                    option.classList.remove('shake');
                    option.style.border = '2px solid transparent';
                }, 1000);
            });
            return;
        }
    }

    isNavigating = true;

    questions[currentQuestion].classList.remove('opacity-100');
    questions[currentQuestion].classList.add('opacity-0');
    setTimeout(() => {
        questions[currentQuestion].classList.add('invisible');
        currentQuestion = Math.min(currentQuestion + 1, questions.length - 1);
        localStorage.setItem('currentQuestion', currentQuestion);
        showQuestion(currentQuestion);
        isNavigating = false;

        // Cache current question
        setCache('currentQuestion', currentQuestion);
    }, 500);
}

function prevQuestion() {
    if (isNavigating || currentQuestion <= 0) return;
    isNavigating = true;

    questions[currentQuestion].classList.remove('opacity-100');
    questions[currentQuestion].classList.add('opacity-0');
    setTimeout(() => {
        questions[currentQuestion].classList.add('invisible');
        currentQuestion = Math.max(currentQuestion - 1, 0);
        localStorage.setItem('currentQuestion', currentQuestion);
        showQuestion(currentQuestion);
        isNavigating = false;
    }, 500);
}

function checkButtons() {
    const prevButton = document.getElementById('prevButton');
    const nextButton = document.getElementById('nextButton');
    const totalQuestions = document.querySelectorAll('.question').length;
    
    // Hide prev button on first question
    if (currentQuestion === 0) {
        prevButton.classList.add('invisible');
    } else {
        prevButton.classList.remove('invisible');
    }
    
    // Hide next button on last question
    if (currentQuestion === totalQuestions - 1) {
        nextButton.classList.add('invisible');
    } else {
        nextButton.classList.remove('invisible');
    }
}

function updateURL(index) {
    const url = new URL(window.location);
    url.searchParams.set('question', index);
    window.history.pushState({}, '', url);
}

// Add CSS animation for shake effect
const style = document.createElement('style');
style.textContent = `
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

.shake {
    animation: shake 0.5s ease-in-out;
}`;
document.head.appendChild(style);

document.addEventListener('DOMContentLoaded', initializeFromCache);

showQuestion(currentQuestion);

// Make functions globally accessible
window.nextQuestion = nextQuestion;
window.prevQuestion = prevQuestion;
window.toggleBackground = toggleBackground;
window.initializeFromCache = initializeFromCache;
