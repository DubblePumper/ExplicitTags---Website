<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/include-all.php';
$gradients = getRandomGradientClass(true);

// Make sure you have no environment or config forcing "http://"
?>

<style>
    .rounded-full img {
        object-fit: cover;
        border-radius: 50%;
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
    <main>
        <form class="flex flex-row justify-evenly mt-20 w-full" id="experienceForm">
            <div class="flex flex-col self-center justify-self-center items-center justify-center space-y-4 aos-init aos-animate w-fit h-fit" data-aos="fade-right" data-aos-duration="1000">
                <button id="prevButton" type="button" class="rounded-full border-2 border-secondary p-5 hover:bg-secondary hover:border-primairy hover:border-2 hover:text-gray-950 transition duration-500 ease-in-out" onclick="prevQuestion()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </button>
            </div>
            <div class="grid grid-cols-1 gap-4 h-96 w-50">
                <!-- question 1 -->
                <div class="question active transition-opacity duration-500 ease-in-out opacity-100 h-full" id="question1">
                    <div class="text-center flex flex-col items-center space-y-1 mb-12" data-aos="zoom-in" data-aos-duration="1000">
                        <h3 class="<?php echo $gradients; ?>">First question</h3>
                        <h2 class="<?php echo $gradients; ?> ">Are you a person <span class="underline underline-offset-4 decoration-solid decoration-secondary font-extrabold text-lg ">with</span> or <span class="underline underline-offset-4 decoration-solid decoration-secondary font-extrabold text-lg">without</span> a penis</h2>
                    </div>
                    <div class="flex flex-row items-center justify-around gap-x-4">
                        <div class="transform transition duration-500 hover:scale-110 p-5 rounded-full border-2 border-transparent relative group" id="man">
                            <label class="flex flex-col items-center relative">
                                <input type="radio" name="gender" value="man" class="hidden" onchange="toggleBackground(this)">
                                <h2 class="text-center text-secondary font-bold absolute inset-0 grid place-items-center opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-10">With penis</h2>
                                <img class="man-front cursor-pointer opacity-100 group-hover:opacity-15 transition-opacity duration-500 mx-auto" src="/assets/images/website_Images/man_front.svg" alt="Man silhouette" data-aos="zoom-in" data-aos-duration="1000" />
                            </label>
                        </div>
                        <div class="transform transition duration-500 hover:scale-110 p-5 rounded-full border-2 border-transparent relative group" id="woman">
                            <label class="flex flex-col items-center relative">
                                <input type="radio" name="gender" value="woman" class="hidden" onchange="toggleBackground(this)">
                                <h2 class="text-center text-secondary font-bold absolute inset-0 grid place-items-center opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-10">Without penis</h2>
                                <img class="woman-front cursor-pointer opacity-100 group-hover:opacity-15 transition-opacity duration-500 mx-auto" src="/assets/images/website_Images/woman_front.svg" alt="Woman silhouette" data-aos="zoom-in" data-aos-duration="1000" />
                            </label>
                        </div>
                    </div>
                </div>
                <!-- question 2 -->
                <div class="question transition-opacity duration-500 ease-in-out opacity-0 hidden h-full mb-10" id="question2">
                    <div class="flex flex-col items-center space-y-1 mb-12" data-aos="zoom-in" data-aos-duration="1000">
                        <h3 class="<?php echo $gradients; ?>">Second question</h3>
                        <h2 class="<?php echo $gradients; ?>">How many people need to be involved in total?</h2>
                    </div>
                    <div class="flex flex-row justify-evenly w-full divide-x divide-secondary">

                        <div class="flex flex-col text-center items-center me-3" id="howMuchMan" data-aos="zoom-in" data-aos-duration="1000">
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

                        <div class="flex flex-col text-center items-center ms-3" id="howMuchWoman" data-aos="zoom-in" data-aos-duration="1000">
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
                <div class="question transition-opacity duration-500 ease-in-out opacity-0 hidden h-full mb-10" id="question3">
                    <div class="flex flex-col items-center space-y-1 mb-12" data-aos="zoom-in" data-aos-duration="1000">
                        <h3 class="<?php echo $gradients; ?>">third question</h3>
                        <h2 class="<?php echo $gradients; ?>">Is there a performer that you want to include or exclude?</h2>
                    </div>
                    <div class="flex flex-col items-center space-y-4">
                        <input type="text" id="searchBar" placeholder="Search by name" class="text-TextWhite bg-transparent border-b-2 border-secondary focus:outline-none focus:border-primairy">
                        <div class="flex space-x-4">
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" id="searchMan" class="text-secondary">
                                <span>Man</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" id="searchWoman" class="text-secondary">
                                <span>Woman</span>
                            </label>
                        </div>
                        <table class="table-auto border-collapse w-full text-TextWhite">
                            <thead class="mb-4">
                                <tr>
                                    <th class="px-4 py-2 text-center border border-slate-500">Image</th>
                                    <th class="px-4 py-2 text-end border border-slate-500">Name</th>
                                </tr>
                            </thead>
                            <tbody id="searchResults" class="first:mt-5">
                                <!-- Search results will be inserted here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add more questions here -->
            </div>
            <div class="flex flex-col self-center justify-self-center items-center justify-center space-y-4 aos-init aos-animate w-fit h-fit" data-aos="fade-left" data-aos-duration="1000">
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
        let currentQuestion = parseInt(new URLSearchParams(window.location.search).get('question')) || 0;
        const questions = document.querySelectorAll('.question');
        let debounceTimer; // Define debounceTimer

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

        function startSSE() {
            if (eventSource) {
                eventSource.close();
            }

            try {
                const params = new URLSearchParams({
                    search: encodeURIComponent(searchBar.value.toLowerCase()),
                    gender: getGenderFilter(),
                    t: Date.now()
                });

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
                searchResults.innerHTML = data.map(performer => {
                    // Sanitize the image URL
                    let imageUrl = performer.image_url;
                    if (!imageUrl) {
                        imageUrl = defaultImage;
                    }

                    return `
                        <tr class="first:mt-5">
                            <td class="flex justify-center px-4 py-2 border-r border-slate-500">
                                <div class="w-16 h-16 rounded-full overflow-hidden group">
                                    <img src="${imageUrl}" 
                                         alt="${performer.name}" 
                                         class="w-full h-full object-cover blur-sm transition-all duration-300 group-hover:blur-none"
                                         onerror="this.onerror=null; this.src='${defaultImage}';">
                                </div>
                            </td>
                            <td class="px-4 py-2 text-end">${performer.name}</td>
                        </tr>
                    `;
                }).join('');
            } catch (error) {
                console.error('Error rendering results:', error);
                searchResults.innerHTML = '<tr><td colspan="2" class="text-center py-4">Error displaying results</td></tr>';
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
                startSSE();
            }, 300);
        }

        window.addEventListener('DOMContentLoaded', startSSE);
    </script>
</body>
</html>