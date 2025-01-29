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
        if (r !== radio) {
            r.parentElement.parentElement.classList.remove('border-secondary', 'bg-darkPrimairy');
            r.parentElement.parentElement.classList.add('border-transparent');
        }
    });

    if (radio.checked) {
        radio.parentElement.parentElement.classList.add('border-secondary', 'bg-darkPrimairy');
        radio.parentElement.parentElement.classList.remove('border-transparent');
    }
}

function showQuestion(index) {
    // Ensure index stays within bounds
    index = Math.max(0, Math.min(index, questions.length - 1));
    
    questions.forEach((question, i) => {
        if (i === index) {
            question.classList.add('opacity-100');
            question.classList.remove('opacity-0', 'hidden');
        } else {
            question.classList.add('opacity-0', 'hidden');
            question.classList.remove('opacity-100');
        }
    });
    checkButtons();
    updateURL(index);
}

function nextQuestion() {
    if (isNavigating || currentQuestion >= questions.length - 1) return;
    isNavigating = true;

    questions[currentQuestion].classList.remove('opacity-100');
    questions[currentQuestion].classList.add('opacity-0');
    setTimeout(() => {
        questions[currentQuestion].classList.add('hidden');
        currentQuestion = Math.min(currentQuestion + 1, questions.length - 1);
        localStorage.setItem('currentQuestion', currentQuestion);
        showQuestion(currentQuestion);
        isNavigating = false;
    }, 500);
}

function prevQuestion() {
    if (isNavigating || currentQuestion <= 0) return;
    isNavigating = true;

    questions[currentQuestion].classList.remove('opacity-100');
    questions[currentQuestion].classList.add('opacity-0');
    setTimeout(() => {
        questions[currentQuestion].classList.add('hidden');
        currentQuestion = Math.max(currentQuestion - 1, 0);
        localStorage.setItem('currentQuestion', currentQuestion);
        showQuestion(currentQuestion);
        isNavigating = false;
    }, 500);
}

function checkButtons() {
    if (currentQuestion === 0) {
        document.getElementById('prevButton').classList.add('hidden');
    } else {
        document.getElementById('prevButton').classList.remove('hidden');
    }
}

function updateURL(index) {
    const url = new URL(window.location);
    url.searchParams.set('question', index);
    window.history.pushState({}, '', url);
}

showQuestion(currentQuestion);