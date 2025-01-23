<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/include-all.php';
$gradients = getRandomGradientClass(true);

// Make sure you have no environment or config forcing "http://"
?>

<style>
    .rounded-full img {
        object-fit: contain;
        border-radius: 50%;
    }
    @media (max-width: 768px) {
        .no-aos-mobile {
            opacity: 1 !important;
            transform: none !important;
            transition: none !important;
        }
    }
</style>

<body class="text-TextWhite">
    <header>
        <div class="text-center mt-10 flex flex-col items-center justify-center space-y-2" data-aos="fade-down" data-aos-duration="1000">
            <h1 class="text-4xl font-bold <?php echo $gradients ?>" data-aos="fade-down" data-aos-duration="1000">Customize your experience.</h1>
            <h2 class="<?php echo $gradients; ?>" data-aos="fade-down" data-aos-duration="1000">Select what you want to find</h2>
            <h3 class="<?php echo $gradients; ?>" data-aos="fade-down" data-aos-duration="1000">And guess what... We will find it for you</h3>
        </div>
    </header>
    <main class="overflow-y-auto min-h-screen">
        <form class="flex flex-col md:flex-row justify-evenly mt-20 w-full space-y-4 md:space-y-0" id="experienceForm">
            <div class="flex flex-col self-center justify-self-center items-center justify-center space-y-4 aos-init aos-animate w-fit h-fit no-aos-mobile" data-aos="fade-right" data-aos-duration="1000">
                <button id="prevButton" type="button" class="rounded-full border-2 border-secondary p-3 sm:p-5 hover:bg-secondary hover:border-primairy hover:border-2 hover:text-gray-950 transition duration-500 ease-in-out" onclick="prevQuestion()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </button>
            </div>
            <div class="grid grid-cols-1 gap-4 w-full md:w-2/3">
                <!-- question 1 -->
                <div class="question active transition-opacity duration-500 ease-in-out opacity-100 h-auto md:h-96" id="question1">
                    <div class="text-center flex flex-col items-center space-y-1 mb-12" data-aos="zoom-in" data-aos-duration="1000">
                        <h3 class="<?php echo $gradients; ?> ">First question</h3>
                        <h2 class="<?php echo $gradients; ?> ">Are you a person <span class="underline underline-offset-4 decoration-solid decoration-secondary font-extrabold text-lg ">with</span> or <span class="underline underline-offset-4 decoration-solid decoration-secondary font-extrabold text-lg">without</span> a penis</h2>
                    </div>
                    <div class="flex flex-row items-center justify-around gap-x-4">
                        <div class="transform transition duration-500 hover:scale-110 p-5 rounded-full border-2 border-transparent relative group" id="man">
                            <label class="flex flex-col items-center relative">
                                <input type="radio" name="gender" value="man" class="hidden" onchange="toggleBackground(this)">
                                <h2 class="text-center text-secondary font-bold absolute inset-0 grid place-items-center opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-10">With penis</h2>
                                <img class="man-front cursor-pointer opacity-100 group-hover:opacity-15 transition-opacity duration-500 mx-auto w-16 h-16 sm:w-24 sm:h-24 md:w-32 md:h-32" src="/assets/images/website_Images/man_front.svg" alt="Man silhouette" data-aos="zoom-in" data-aos-duration="1000" />
                            </label>
                        </div>
                        <div class="transform transition duration-500 hover:scale-110 p-5 rounded-full border-2 border-transparent relative group" id="woman">
                            <label class="flex flex-col items-center relative">
                                <input type="radio" name="gender" value="woman" class="hidden" onchange="toggleBackground(this)">
                                <h2 class="text-center text-secondary font-bold absolute inset-0 grid place-items-center opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-10">Without penis</h2>
                                <img class="woman-front cursor-pointer opacity-100 group-hover:opacity-15 transition-opacity duration-500 mx-auto w-16 h-16 sm:w-24 sm:h-24 md:w-32 md:h-32" src="/assets/images/website_Images/woman_front.svg" alt="Woman silhouette" data-aos="zoom-in" data-aos-duration="1000" />
                            </label>
                        </div>
                    </div>
                </div>
                <!-- question 2 -->
                <div class="question transition-opacity duration-500 ease-in-out opacity-0 hidden h-auto md:h-96 mb-10" id="question2">
                    <div class="text-center flex flex-col items-center space-y-1 mb-12" data-aos="zoom-in" data-aos-duration="1000">
                        <h3 class="<?php echo $gradients; ?>">Second question</h3>
                        <h2 class="<?php echo $gradients; ?>">How many people need to be involved in total?</h2>
                    </div>
                    <div class="flex flex-col md:flex-row justify-evenly w-full md:divide-x md:divide-secondary space-y-6 md:space-y-0">
                        <div class="flex flex-col text-center items-center flex-grow" id="howMuchMan" data-aos="zoom-in" data-aos-duration="1000">
                            <div class="self-center">
                                <p class="mb-3">People <span class="underline underline-offset-4 decoration-solid decoration-secondary font-extrabold text-lg ">with</span> a penis</p>
                                <div class="flex items-center justify-center space-x-2">
                                    <button id="minButtonMan" type="button" class="rounded-full border-2 border-secondaryDarker py-3 px-5 hover:bg-secondaryDarker hover:border-primairy hover:border-2 hover:text-gray-950 transition duration-500 ease-in-out" onclick="decrement('manCount')">-</button>
                                    <input type="number" id="manCount" name="manCount" value="1" min="0" max="99" class="text-TextWhite mx-3 w-11 text-center bg-transparent border-transparent focus:border-0 focus:outline-none focus:ring-0 active:bg-transparent focus-within:bg-transparent">
                                    <button type="button" id="plusButtonMan" class="rounded-full border-2 border-secondaryDarker py-3 px-5 hover:bg-secondaryDarker hover:border-primairy hover:border-2 hover:text-gray-950 transition duration-500 ease-in-out" onclick="increment('manCount','manCountPlus')">+</button>
                                </div>
                            </div>
                            <div id="manIMG" class="grid gap-4 items-center justify-center mt-4 w-[239.828px]">

                            </div>
                        </div>

                        <div class="flex flex-col text-center items-center flex-grow" id="howMuchWoman" data-aos="zoom-in" data-aos-duration="1000">
                            <div class="self-center">
                                <p class="mb-3">People <span class="underline underline-offset-4 decoration-solid decoration-secondary font-extrabold text-lg ">without</span> a penis</p>
                                <div class="flex items-center justify-center space-x-2">
                                    <button id="minButtonWoman" type="button" class="rounded-full border-2 border-secondaryDarker py-3 px-5 hover:bg-secondaryDarker hover:border-primairy hover:border-2 hover:text-gray-950 transition duration-500 ease-in-out" onclick="decrement('womanCount')">-</button>
                                    <input type="number" id="womanCount" name="womanCount" value="1" min="0" max="99" class="text-TextWhite mx-3 w-11 text-center bg-transparent border-transparent focus:border-0 focus:outline-none focus:ring-0 active:bg-transparent focus-within:bg-transparent">
                                    <button type="button" id="plusButtonWoman" class="rounded-full border-2 border-secondaryDarker py-3 px-5 hover:bg-secondaryDarker hover:border-primairy hover:border-2 hover:text-gray-950 transition duration-500 ease-in-out" onclick="increment('womanCount','womanCountPlus')">+</button>
                                </div>
                            </div>
                            <div id="womanIMG" class="grid gap-4 items-center justify-center mt-4 w-[239.828px]">

                            </div>
                        </div>

                    </div>
                </div>
                <!-- 3 questions -->
                <div class="question transition-opacity duration-500 ease-in-out opacity-0 hidden h-auto md:h-96 mb-10 " id="question3">
                    <div class="text-center flex flex-col items-center space-y-1 mb-12" data-aos="zoom-in" data-aos-duration="1000">
                        <h3 class="<?php echo $gradients; ?>">third question</h3>
                        <h2 class="<?php echo $gradients; ?>">Is there a performer that you want to include or exclude?</h2>
                    </div>
                    <div class="flex flex-col items-center space-y-4">
                        <input type="text" id="searchBar" placeholder="Search by name" class="text-TextWhite bg-transparent border-2 border-secondary focus:outline-none rounded">
                        <div class="flex justify-between gap-4 w-1/4">
                            <label class="flex justify-between items-center gap-2">
                                <input type="checkbox" id="searchMan" class="text-secondary">
                                <span>Man</span>
                            </label>
                            <label class="flex justify-between items-center gap-2">
                                <input type="checkbox" id="searchWoman" class="text-secondary">
                                <span>Woman</span>
                            </label>
                        </div>
                        <div class="flex flex-wrap gap-2 w-full max-w-3xl justify-center align-center" id="taggs">
                            <!-- Tags will be inserted here -->
                        </div>
                        <table class="table-auto border-collapse w-full text-TextWhite">
                            <thead>
                                <tr class="border-b border-secondary">
                                    <th class="flex justify-center px-4 py-2 text-center border-r border-secondary w-full whitespace-nowrap">Image</th>
                                    <th class="px-4 py-2 text-end w-1/2 whitespace-nowrap">Name</th>
                                </tr>
                            </thead>
                            <tbody id="searchResults" class="divide-y divide-secondary">
                                <!-- Search results will be inserted here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add more questions here -->
                <div class="question transition-opacity duration-500 ease-in-out opacity-0 hidden h-auto md:h-96 mb-10 " id="question3">
                    <div class="text-center flex flex-col items-center space-y-1 mb-12" data-aos="zoom-in" data-aos-duration="1000">
                        <h3 class="<?php echo $gradients; ?>">fourth question</h3>
                        <h2 class="<?php echo $gradients; ?>">Is there a performer that you want to include or exclude?</h2>
                    </div>
                    <div class="flex flex-col items-center space-y-4">

                    </div>
                </div>
            </div>
            <div class="flex flex-col self-center justify-self-center items-center justify-center space-y-4 aos-init aos-animate w-fit h-fit mb-6 md:mb-0 no-aos-mobile" data-aos="fade-left" data-aos-duration="1000">
                <button type="button" class="rounded-full border-2 border-secondary p-3 sm:p-5 hover:bg-secondary hover:border-primairy hover:border-2 hover:text-gray-950 transition duration-500 ease-in-out" onclick="nextQuestion()">
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

            let value = parseInt(manCount.value);
            let manImages = manIMG.querySelectorAll('img').length;

            // Ensure the number of images matches the input value
            while (manImages < value) {
                addImage(manIMG, '/assets/images/website_Images/man_front.svg', 'man-front');
                manImages++;
            }
            while (manImages > value) {
                manIMG.removeChild(manIMG.lastChild);
                manImages--;
            }

            updateGridColumns(manIMG, manImages);
            manCountValue = value;
        });

        womanCount.addEventListener('change', function() {
            updateButtonState(womanCount, womanMin, womanMax, minBtnWoman, plusBtnWoman);

            let value = parseInt(womanCount.value);
            let womanImages = womanIMG.querySelectorAll('img').length;

            // Ensure the number of images matches the input value
            while (womanImages < value) {
                addImage(womanIMG, '/assets/images/website_Images/woman_front.svg', 'woman-front');
                womanImages++;
            }
            while (womanImages > value) {
                womanIMG.removeChild(womanIMG.lastChild);
                womanImages--;
            }

            updateGridColumns(womanIMG, womanImages);
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

            updateGridColumns(manIMG, manCountValue);
            updateGridColumns(womanIMG, womanCountValue);
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
            img.classList.add('w-16','h-16','sm:w-24','sm:h-24','md:w-32','md:h-32');
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

        // -----------------------------------
        // Search functionality using SSE
        // -----------------------------------

        const searchBar = document.getElementById('searchBar');
        const searchMan = document.getElementById('searchMan');
        const searchWoman = document.getElementById('searchWoman');
        const searchResults = document.getElementById('searchResults');

        let eventSource;
        let selectedPerformerIds = new Set();

        function startSSE() {
            if (eventSource) {
                eventSource.close();
            }

            try {
                const searchTerm = searchBar.value.trim();
                const params = new URLSearchParams({
                    search: searchTerm,
                    gender: getGenderFilter(),
                    t: Date.now()
                });

                // Only start search if we have a search term
                if (searchTerm.length === 0 && !getGenderFilter()) {
                    searchResults.innerHTML = '<tr><td colspan="2" class="text-center py-4">Type to search...</td></tr>';
                    return;
                }

                eventSource = new EventSource(`/api/performers_sse.php?${params}`);
                let reconnectAttempts = 0;
                const maxReconnectAttempts = 3;

                eventSource.onopen = () => {
                    reconnectAttempts = 0;
                    searchResults.innerHTML = '<tr><td colspan="2" class="text-center py-4">Searching...</td></tr>';
                };

                eventSource.addEventListener('performers', (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        updateSearchResults(data);
                        eventSource.close(); // Close connection after receiving data
                    } catch (e) {
                        console.error('Error parsing performers data:', e);
                        searchResults.innerHTML = '<tr><td colspan="2" class="text-center py-4">Error loading results</td></tr>';
                    }
                });

                eventSource.addEventListener('error', (event) => {
                    console.error('SSE Error:', event);
                    
                    if (eventSource.readyState === EventSource.CLOSED) {
                        if (reconnectAttempts < maxReconnectAttempts) {
                            searchResults.innerHTML = `<tr><td colspan="2" class="text-center py-4">Reconnecting... (${reconnectAttempts + 1}/${maxReconnectAttempts})</td></tr>`;
                            reconnectAttempts++;
                            setTimeout(startSSE, 3000);
                        } else {
                            searchResults.innerHTML = '<tr><td colspan="2" class="text-center py-4">Connection failed. Please try again later.</td></tr>';
                        }
                    }
                });

            } catch (error) {
                console.error('Error creating EventSource:', error);
                searchResults.innerHTML = '<tr><td colspan="2" class="text-center py-4">Connection error</td></tr>';
            }
        }

        function updateSearchResults(data) {
            if (!Array.isArray(data) || data.length === 0) {
                searchResults.innerHTML = '<tr><td colspan="2" class="text-center py-4">No results found</td></tr>';
                return;
            }

            const defaultImage = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'white\' stroke-width=\'2\'%3E%3Ccircle cx=\'12\' cy=\'8\' r=\'5\'/%3E%3Cpath d=\'M3 21v-2a7 7 0 0 1 14 0v2\'/%3E%3C/svg%3E';
            
            try {
                const filteredData = data.filter(performer => !selectedPerformerIds.has(performer.performer_id));
                
                // Preload first 5 images
                const preloadImages = filteredData.slice(0, 5).map(performer => {
                    if (performer.image_url) {
                        const img = new Image();
                        img.src = performer.image_url;
                        return img;
                    }
                    return null;
                });

                searchResults.innerHTML = filteredData.map((performer, index) => {
                    let imageUrl = performer.image_url || defaultImage;
                    let performerId = performer.performer_id || performer.id;
                    
                    return `
                        <tr class="performer-row cursor-pointer transition-colors duration-300 hover:bg-secondary/20 border-b border-secondary" 
                            data-performer-id="${performerId || ''}"
                            data-performer-name="${performer.name || ''}"
                            onclick="togglePerformerSelection(this)">
                            <td class="flex justify-center px-4 py-2 border-r border-secondary">
                                <div class="w-16 h-16 rounded-none overflow-hidden group rounded-[50%]">
                                    <img src="${index < 5 ? imageUrl : defaultImage}" 
                                         data-src="${imageUrl}"
                                         alt="${performer.name}" 
                                         class="w-full h-full rounded-[50%] blur-sm transition-all duration-300 group-hover:blur-none ${index >= 5 ? 'lazy' : ''}"
                                         loading="${index < 5 ? 'eager' : 'lazy'}" style="clip-path: circle(50%);"
                                         >
                                </div>
                            </td>
                            <td class="px-4 py-2 text-end">${performer.name}</td>
                        </tr>
                    `;
                }).join('');

                // Initialize lazy loading for images
                initializeLazyLoading();

            } catch (error) {
                console.error('Error rendering results:', error);
                searchResults.innerHTML = '<tr><td colspan="2" class="text-center py-4">Error displaying results</td></tr>';
            }
        }

        // Add this new function for lazy loading
        function initializeLazyLoading() {
            const lazyImages = document.querySelectorAll('img.lazy');
            
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.1
            });

            lazyImages.forEach(img => imageObserver.observe(img));
        }

        function createTag(performerId, performerName) {
            return `
                <div class="flex items-center space-x-2 px-3 py-1 bg-darkPrimairy border-2 border-secondary rounded-full text-sm transition-all duration-300 hover:border-primairy">
                    <span>${performerName}</span>
                    <button type="button" onclick="removePerformer('${performerId}', this.parentElement)" class="text-secondary hover:text-primairy transition-colors duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            `;
        }

        function removePerformer(performerId, tagElement) {
            selectedPerformerIds.delete(performerId);
            tagElement.remove();
            // Refresh the search to show the removed performer
            startSSE();
        }

        function togglePerformerSelection(row) {
            try {
                const performerId = row.dataset.performerId;
                const performerName = row.dataset.performerName;
                
                if (!performerId) {
                    console.warn('No performer ID found for:', performerName);
                    return;
                }

                // Add to selected performers set
                selectedPerformerIds.add(performerId);
                
                // Add tag
                const tagsContainer = document.getElementById('taggs');
                tagsContainer.insertAdjacentHTML('beforeend', createTag(performerId, performerName));
                
                // Remove from search results
                row.remove();
                
                console.log('Selected performers:', Array.from(selectedPerformerIds));
            } catch (error) {
                console.error('Error toggling performer selection:', error);
            }
        }

        function getGenderFilter() {
            const isManChecked = searchMan.checked;
            const isWomanChecked = searchWoman.checked;
            
            if (isManChecked && !isWomanChecked) return 'Male';
            if (!isManChecked && isWomanChecked) return 'Female';
            return '';
        }

        searchBar.addEventListener('input', debounceSearch);
        searchMan.addEventListener('change', () => {
            clearTimeout(debounceTimer);
            startSSE();
        });
        searchWoman.addEventListener('change', () => {
            clearTimeout(debounceTimer);
            startSSE();
        });

        function debounceSearch() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const searchTerm = searchBar.value.trim();
                if (searchTerm.length > 0 || getGenderFilter()) {
                    startSSE();
                }
            }, 300);
        }

        window.addEventListener('DOMContentLoaded', startSSE);
    </script>
</body>
</html>