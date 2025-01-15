<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/include-all.php';
$gradients = getRandomGradientClass(true);
?>

<style>
    .icon {
        width: 40.70644mm;
        height: 84.606247mm;
    }
</style>

<body class="text-TextWhite">
    <header>
        <div class="mt-10 flex flex-col items-center justify-center space-y-2" data-aos="fade-down" data-aos-duration="1000">
            <h1 class="text-4xl font-bold <?php echo $gradients ?>" data-aos="fade-down" data-aos-duration="1000">Customize your experience.</h1>
            <h2 class="<?php echo $gradients; ?>" data-aos="fade-down" data-aos-duration="1000">Select what you want to find</h2>
            <h3 class="<?php echo $gradients; ?>" data-aos="fade-down" data-aos-duration="1000">And guess what... We will find it for you</h3>
        </div>
    </header>
    <main>
        <form class="grid grid-cols-3 gap-5 mt-20 w-full" id="experienceForm">
            <div class="flex flex-col items-center justify-center space-y-4" data-aos="fade-right" data-aos-duration="1000">
                <button type="button" class="rounded-full border-2 border-secondary p-5 hover:bg-secondary hover:border-primairy hover:border-2 hover:text-gray-950 transition duration-500 ease-in-out" onclick="prevQuestion()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </button>
            </div>
            <div class="grid grid-cols-1 gap-4 h-96">
                <div class="question active transition-opacity duration-500 ease-in-out opacity-100 h-full" id="question1">
                    <div class="flex flex-col items-center space-y-1 mb-12" data-aos="zoom-in" data-aos-duration="1000">
                        <h3 class="<?php echo $gradients; ?>">First question</h3>
                        <h2 class="<?php echo $gradients; ?>">Are you a person with or without a penis</h2>
                    </div>
                    <div class="flex flex-row items-center justify-around space-x-4">
                        <div class="transform transition duration-500 hover:scale-110 p-5 rounded-full border-2 border-transparent" id="man" >
                            <label>
                                <input type="checkbox" name="gender" value="man" class="hidden" onchange="toggleBackground(this)">
                                <img class="man-front cursor-pointer bg-transparent" src="/assets/images/website_Images/man_front.svg" alt="Man silhouette"  data-aos="zoom-in" data-aos-duration="1000"/>
                            </label>
                        </div>
                        <div class="transform transition duration-500 hover:scale-110 p-5 rounded-full border-2 border-transparent" id="woman">
                            <label>
                                <input type="checkbox" name="gender" value="woman" class="hidden" onchange="toggleBackground(this)">
                                <img class="woman-front cursor-pointer" src="/assets/images/website_Images/woman_front.svg" alt="Woman silhouette" data-aos="zoom-in" data-aos-duration="1000"/>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="question transition-opacity duration-500 ease-in-out opacity-0 hidden h-full" id="question2">
                    <div class="flex flex-col items-center space-y-1 mb-12">
                        <h3 class="<?php echo $gradients; ?>">Second question</h3>
                        <h2 class="<?php echo $gradients; ?>">What is your age group?</h2>
                    </div>
                    <div class="flex flex-row items-center justify-around space-x-4">
                        <label>
                            <input type="radio" name="age" value="under18" class="hidden">
                            <span class="cursor-pointer">Under 18</span>
                        </label>
                        <label>
                            <input type="radio" name="age" value="18-24" class="hidden">
                            <span class="cursor-pointer">18-24</span>
                        </label>
                        <label>
                            <input type="radio" name="age" value="25-34" class="hidden">
                            <span class="cursor-pointer">25-34</span>
                        </label>
                        <label>
                            <input type="radio" name="age" value="35-44" class="hidden">
                            <span class="cursor-pointer">35-44</span>
                        </label>
                        <label>
                            <input type="radio" name="age" value="45+" class="hidden">
                            <span class="cursor-pointer">45+</span>
                        </label>
                    </div>
                </div>
                <!-- Add more questions here -->
            </div>
            <div class="flex flex-col items-center justify-center space-y-4" data-aos="fade-left" data-aos-duration="1000">
                <button type="button" class="rounded-full border-2 border-secondary p-5 hover:bg-secondary hover:border-primairy hover:border-2 hover:text-gray-950 transition duration-500 ease-in-out" onclick="nextQuestion()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </button>
            </div>
        </form>
    </main>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/scripts.php';
    ?>

    <script>
        let currentQuestion = 0;
        const questions = document.querySelectorAll('.question');

        function toggleBackground(checkbox) {
            if (checkbox.checked) {
                checkbox.parentElement.parentElement.classList.add('border-secondary', 'bg-darkPrimairy');
                checkbox.parentElement.parentElement.classList.remove('border-transparent');
            } else {
                checkbox.parentElement.parentElement.classList.remove('border-secondary', 'bg-darkPrimairy');
                checkbox.parentElement.parentElement.classList.add('border-transparent');
            }
        }

        function showQuestion(index) {
            questions.forEach((question, i) => {
                if (i === index) {
                    question.classList.add('opacity-100');
                    question.classList.remove('opacity-0', 'hidden');
                } else {
                    question.classList.add('opacity-0', 'hidden');
                    question.classList.remove('opacity-100');
                }
            });
        }

        function nextQuestion() {
            if (currentQuestion < questions.length - 1) {
                questions[currentQuestion].classList.remove('opacity-100');
                questions[currentQuestion].classList.add('opacity-0');
                setTimeout(() => {
                    questions[currentQuestion].classList.add('hidden');
                    currentQuestion++;
                    showQuestion(currentQuestion);
                }, 500);
            }
        }

        function prevQuestion() {
            if (currentQuestion > 0) {
                questions[currentQuestion].classList.remove('opacity-100');
                questions[currentQuestion].classList.add('opacity-0');
                setTimeout(() => {
                    questions[currentQuestion].classList.add('hidden');
                    currentQuestion--;
                    showQuestion(currentQuestion);
                }, 500);
            }
        }

        showQuestion(currentQuestion);
    </script>
</body>