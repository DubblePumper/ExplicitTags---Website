<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/include-all.php';
$gradients = getRandomGradientClass(true);
?>

<style>
    .icon {
        width: 40.70644mm;
        height: 84.606247mm;
    }
    .image-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px;
        height: 150px; /* Set a fixed height */
        overflow-y: auto; /* Add scroll if content overflows */
    }
    .image-container img {
        flex: 1 1 calc(33.333% - 10px);
        max-width: calc(33.333% - 10px);
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
                <!-- question 1 -->
                <div class="question active transition-opacity duration-500 ease-in-out opacity-100 h-full" id="question1">
                    <div class="flex flex-col items-center space-y-1 mb-12" data-aos="zoom-in" data-aos-duration="1000">
                        <h3 class="<?php echo $gradients; ?>">First question</h3>
                        <h2 class="<?php echo $gradients; ?> ">Are you a person <span class="underline underline-offset-4 decoration-solid decoration-secondary font-extrabold text-lg ">with</span> or <span class="underline underline-offset-4 decoration-solid decoration-secondary font-extrabold text-lg">without</span> a penis</h2>
                    </div>
                    <div class="flex flex-row items-center justify-around gap-x-4">
                        <div class="transform transition duration-500 hover:scale-110 p-5 rounded-full border-2 border-transparent relative group" id="man">
                            <label class="flex flex-col items-center relative">
                                <input type="checkbox" name="gender" value="man" class="hidden" onchange="toggleBackground(this)">
                                <h2 class="text-center text-secondary font-bold absolute inset-0 grid place-items-center opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-10">With penis</h2>
                                <img class="man-front cursor-pointer opacity-100 group-hover:opacity-15 transition-opacity duration-500 mx-auto" src="/assets/images/website_Images/man_front.svg" alt="Man silhouette" data-aos="zoom-in" data-aos-duration="1000" />
                            </label>
                        </div>
                        <div class="transform transition duration-500 hover:scale-110 p-5 rounded-full border-2 border-transparent relative group" id="woman">
                            <label class="flex flex-col items-center relative">
                                <input type="checkbox" name="gender" value="woman" class="hidden" onchange="toggleBackground(this)">
                                <h2 class="text-center text-secondary font-bold absolute inset-0 grid place-items-center opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-10">Without penis</h2>
                                <img class="woman-front cursor-pointer opacity-100 group-hover:opacity-15 transition-opacity duration-500 mx-auto" src="/assets/images/website_Images/woman_front.svg" alt="Woman silhouette" data-aos="zoom-in" data-aos-duration="1000" />
                            </label>
                        </div>
                    </div>
                </div>
                <!-- question 2 -->
                <div class="question transition-opacity duration-500 ease-in-out opacity-0 hidden h-full" id="question2">
                    <div class="flex flex-col items-center space-y-1 mb-12" data-aos="zoom-in" data-aos-duration="1000">
                        <h3 class="<?php echo $gradients; ?>">Second question</h3>
                        <h2 class="<?php echo $gradients; ?>">How many people need to be involved?</h2>
                    </div>
                    <div class="flex flex-row items-center justify-around gap-x-4">
                        <div class="text-center" id="howMuchMan" data-aos="zoom-in" data-aos-duration="1000">
                            <p class="mb-3">People <span class="underline underline-offset-4 decoration-solid decoration-secondary font-extrabold text-lg ">with</span> a penis</p>
                            <button type="button" class="rounded-full border-2 border-secondaryDarker py-3 px-5 hover:bg-secondaryDarker hover:border-primairy hover:border-2 hover:text-gray-950 transition duration-500 ease-in-out" onclick="decrement('manCount')">-</button>
                            <input type="number" id="manCount" name="manCount" value="1" min="0" class="text-TextWhite mx-3 w-9 text-center bg-transparent border-transparent focus:border-0 focus:outline-none focus:ring-0 active:bg-transparent focus-within:bg-transparent">
                            <button type="button" class="rounded-full border-2 border-secondaryDarker py-3 px-5 hover:bg-secondaryDarker hover:border-primairy hover:border-2 hover:text-gray-950 transition duration-500 ease-in-out" onclick="increment('manCount')">+</button>
                            <div id="manCountImages" class="image-container"></div>
                        </div>
                        <div class="text-center" id="howMuchWoman" data-aos="zoom-in" data-aos-duration="1000">
                            <p class="mb-3">People <span class="underline underline-offset-4 decoration-solid decoration-secondary font-extrabold text-lg ">without</span> a penis</p>
                            <button type="button" class="rounded-full border-2 border-secondaryDarker py-3 px-5 hover:bg-secondaryDarker hover:border-primairy hover-border-2 hover:text-gray-950 transition duration-500 ease-in-out" onclick="decrement('womanCount')">-</button>
                            <input type="number" id="womanCount" name="womanCount" value="1" min="0" class="text-TextWhite mx-3 w-9 text-center bg-transparent border-transparent focus:border-0 focus:outline-none focus:ring-0 active:bg-transparent focus-within:bg-transparent">
                            <button type="button" class="rounded-full border-2 border-secondaryDarker py-3 px-5 hover:bg-secondaryDarker hover-border-primairy hover-border-2 hover:text-gray-950 transition duration-500 ease-in-out" onclick="increment('womanCount')">+</button>
                            <div id="womanCountImages" class="image-container"></div>
                        </div>
                    </div>

                    <script>
                        function increment(id) {
                            const input = document.getElementById(id);
                            input.value = parseInt(input.value) + 1;
                            updateImages(id);
                        }

                        function decrement(id) {
                            const input = document.getElementById(id);
                            if (input.value > 0) {
                                input.value = parseInt(input.value) - 1;
                                updateImages(id);
                            }
                        }

                        function updateImages(id) {
                            const container = document.getElementById(id + 'Images');
                            const count = parseInt(document.getElementById(id).value);
                            container.innerHTML = '';
                            for (let i = 0; i < count; i++) {
                                const img = document.createElement('img');
                                img.src = id === 'manCount' ? '/assets/images/website_Images/man_front.svg' : '/assets/images/website_Images/woman_front.svg';
                                img.classList.add('cursor-pointer', 'mt-4', 'w-24', 'h-24');
                                container.appendChild(img);
                            }
                        }

                        document.addEventListener('DOMContentLoaded', () => {
                            updateImages('manCount');
                            updateImages('womanCount');
                        });
                    </script>
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
        let currentQuestion = localStorage.getItem('currentQuestion') ? parseInt(localStorage.getItem('currentQuestion')) : 0;
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
                    localStorage.setItem('currentQuestion', currentQuestion);
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
                    localStorage.setItem('currentQuestion', currentQuestion);
                    showQuestion(currentQuestion);
                }, 500);
            }
        }

        showQuestion(currentQuestion);
    </script>
</body>