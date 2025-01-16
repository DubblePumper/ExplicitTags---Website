<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/include-all.php';
$gradients = getRandomGradientClass(true);
?>


<body class="text-TextWhite">
    <header>
        <div class="text-center mt-10 flex flex-col items-center justify-center space-y-2" data-aos="fade-down" data-aos-duration="1000">
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
                    <div class="text-center flex flex-col items-center space-y-1 mb-12" data-aos="zoom-in" data-aos-duration="1000">
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
                        <h2 class="<?php echo $gradients; ?>">How many people need to be involved in total?</h2>
                    </div>
                    <div class="flex flex-row justify-around gap-x-4 w-full">
                        <div class="flex flex-col text-center" id="howMuchMan" data-aos="zoom-in" data-aos-duration="1000">
                            <div class="self-start">
                                <p class="mb-3">People <span class="underline underline-offset-4 decoration-solid decoration-secondary font-extrabold text-lg ">with</span> a penis</p>
                                <button id="minButtonMan" type="button" class="rounded-full border-2 border-secondaryDarker py-3 px-5 hover:bg-secondaryDarker hover:border-primairy hover:border-2 hover:text-gray-950 transition duration-500 ease-in-out" onclick="decrement('manCount')">-</button>
                                <input type="number" id="manCount" name="manCount" value="1" min="0" max="99" class="text-TextWhite mx-3 w-11 text-center bg-transparent border-transparent focus:border-0 focus:outline-none focus:ring-0 active:bg-transparent focus-within:bg-transparent">
                                <button type="button" id="plusButtonMan" class="rounded-full border-2 border-secondaryDarker py-3 px-5 hover:bg-secondaryDarker hover:border-primairy hover:border-2 hover:text-gray-950 transition duration-500 ease-in-out" onclick="increment('manCount','manCountPlus')">+</button>
                            </div>
                            <div id="manIMG" class="flex flex-wrap items-center justify-center gap-4">

                            </div>
                        </div>
                        <div class="flex flex-col text-center" id="howMuchWoman" data-aos="zoom-in" data-aos-duration="1000">
                            <div class="self-start">
                                <p class="mb-3">People <span class="underline underline-offset-4 decoration-solid decoration-secondary font-extrabold text-lg ">without</span> a penis</p>
                                <button id="minButtonWoman" type="button" class="rounded-full border-2 border-secondaryDarker py-3 px-5 hover:bg-secondaryDarker hover:border-primairy hover:border-2 hover:text-gray-950 transition duration-500 ease-in-out" onclick="decrement('womanCount')">-</button>
                                <input type="number" id="womanCount" name="womanCount" value="1" min="0" max="99" class="text-TextWhite mx-3 w-11 text-center bg-transparent border-transparent focus:border-0 focus:outline-none focus:ring-0 active:bg-transparent focus-within:bg-transparent">
                                <button
                                    type="button"
                                    id="plusButtonWoman"
                                    class="rounded-full border-2 border-secondaryDarker py-3 px-5 hover:bg-secondaryDarker hover:border-primairy hover:border-2 hover:text-gray-950 transition duration-500 ease-in-out"
                                    onclick="increment('womanCount','womanCountPlus')">+</button>
                            </div>
                            <div id="womanIMG" class="flex flex-row items-center justify-center gap-4">

                            </div>
                        </div>
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
        let currentQuestion = localStorage.getItem('currentQuestion') ? parseInt(localStorage.getItem('currentQuestion')) : 0;
        const questions = document.querySelectorAll('.question');

        function toggleBackground(checkbox) {
            if (checkbox.checked) {
                checkbox.parentElement.parentElement.classList.add('border-secondary');
                checkbox.parentElement.parentElement.classList.add('bg-darkPrimairy');
                checkbox.parentElement.parentElement.classList.remove('border-transparent');
            } else {
                checkbox.parentElement.parentElement.classList.remove('border-secondary');
                checkbox.parentElement.parentElement.classList.remove('bg-darkPrimairy');
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

        // -----------------------------------
        // Question 2
        // -----------------------------------

        // Declare manCount and womanCount before using them
        let manCount = document.getElementById('manCount');
        let womanCount = document.getElementById('womanCount');

        // woman and man img parent id
        let manIMG = document.getElementById('manIMG');
        let womanIMG = document.getElementById('womanIMG');

        let manCountValue = parseInt(manCount.value);
        let womanCountValue = parseInt(womanCount.value);

        // define global min/max values
        let manMin = parseInt(manCount.getAttribute('min'));
        let manMax = parseInt(manCount.getAttribute('max'));
        let womanMin = parseInt(womanCount.getAttribute('min'));
        let womanMax = parseInt(womanCount.getAttribute('max'));

        // fix button references
        let plusBtnMan = document.getElementById('plusButtonMan');
        let minBtnMan = document.getElementById('minButtonMan');
        let plusBtnWoman = document.getElementById('plusButtonWoman');
        let minBtnWoman = document.getElementById('minButtonWoman');

        // 2. Then add event listeners
        manCount.addEventListener('change', function() {
            updateButtonState(manCount, manMin, manMax, minBtnMan, plusBtnMan);

            let value = manCount.value;

            // check if value is increased
            if (value > manCountValue) {
                addImage(manIMG, '/assets/images/website_Images/man_front.svg', 'man-front');
            } else {
                manIMG.removeChild(manIMG.lastChild);
            }
            manCountValue = value;
        });

        womanCount.addEventListener('change', function() {
            updateButtonState(womanCount, womanMin, womanMax, minBtnWoman, plusBtnWoman);

            let value = womanCount.value;

            // check if value is increased
            if (value > womanCountValue) {
                addImage(womanIMG, '/assets/images/website_Images/woman_front.svg', 'woman-front');
            } else {
                womanIMG.removeChild(womanIMG.lastChild);
            }
            womanCountValue = value;
        });

        window.addEventListener('DOMContentLoaded', function() {
            // when page load, check value of manCount and womanCount. add the number of images based on the value
            for (let i = 0; i < manCountValue; i++) {
                addImage(manIMG, '/assets/images/website_Images/man_front.svg', 'man-front');
            }

            for (let i = 0; i < womanCountValue; i++) {
                addImage(womanIMG, '/assets/images/website_Images/woman_front.svg', 'woman-front');
            }
        });

        function addImage(container, src, className) {
            let img = document.createElement('img');
            img.src = src;
            img.classList.add(className);
            img.classList.add('cursor-pointer');
            img.classList.add('opacity-100');
            img.classList.add('group-hover:opacity-15');
            img.classList.add('transition-opacity');
            img.classList.add('duration-500');
            img.classList.add('mx-auto');
            img.classList.add('size-3/4');
            container.appendChild(img);
        }

        function updateButtonState(input, minVal, maxVal, minBtn, plusBtn) {
            let value = parseInt(input.value);
            if (value >= maxVal) {
                plusBtn.disabled = true;
                plusBtn.classList.add('opacity-50', 'pointer-events-none');
                input.value = maxVal;
            } else {
                plusBtn.disabled = false;
                plusBtn.classList.remove('opacity-50', 'pointer-events-none');
            }

            if (value <= minVal) {
                minBtn.disabled = true;
                minBtn.classList.add('opacity-50', 'pointer-events-none');
                input.value = minVal;
            } else {
                minBtn.disabled = false;
                minBtn.classList.remove('opacity-50', 'pointer-events-none');
            }
        }

        function increment(id) {
            let input = document.getElementById(id);
            let maxVal = (id === 'manCount') ? manMax : womanMax;
            if (parseInt(input.value) < maxVal) {
                input.value = parseInt(input.value) + 1;
                input.dispatchEvent(new Event('change')); // Trigger change event
            } else if (parseInt(input.value) === maxVal) {
                input.value = maxVal;
                input.dispatchEvent(new Event('change')); // Trigger change event
            }
        }

        function decrement(id) {
            let input = document.getElementById(id);
            let minVal = (id === 'manCount') ? manMin : womanMin;
            if (parseInt(input.value) > minVal) {
                input.value = parseInt(input.value) - 1;
                input.dispatchEvent(new Event('change')); // Trigger change event
            }
        }
    </script>
</body>