<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/include-all.php';
$gradients = getRandomGradientClass(true);

// Make sure you have no environment or config forcing "http://"
?>

<body class="text-TextWhite">
    <div id="preloader">
        <div class="spinner"></div>
    </div>
    <header>
        <div class="text-center mt-10 flex flex-col items-center justify-center space-y-2" data-aos="fade-down" data-aos-duration="1000">
            <h1 class="text-4xl font-bold <?php echo $gradients ?>" data-aos="fade-down" data-aos-duration="1000">Customize your experience.</h1>
            <h2 class="<?php echo $gradients; ?>" data-aos="fade-down" data-aos-duration="1000">Select what you want to find</h2>
            <h3 class="<?php echo $gradients; ?>" data-aos="fade-down" data-aos-duration="1000">And guess what... We will find it for you</h3>
        </div>
    </header>
    <main class="min-h-screen">
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
                <div class="question active transition-opacity duration-500 ease-in-out opacity-100 h-full md:h-96" id="question1">
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
                <div class="question transition-opacity duration-500 ease-in-out opacity-0 hidden h-auto md:h-96 mb-10 h-full" id="question2">
                    <div class="text-center flex flex-col items-center space-y-1 mb-12" data-aos="zoom-in" data-aos-duration="1000">
                        <h3 class="<?php echo $gradients; ?>">Second question</h3>
                        <h2 class="<?php echo $gradients; ?>">How many people need to be involved in total?</h2>
                    </div>
                    <div class="flex flex-col md:flex-row justify-evenly w-full md:divide-x md:divide-secondary space-y-6 md:space-y-0">
                        <div class="flex flex-col text-center items-center flex-grow" id="howMuchMan" data-aos="zoom-in" data-aos-duration="1000">
                            <p class="mb-3">People <span class="underline underline-offset-4 decoration-solid decoration-secondary font-extrabold text-lg">with</span> a penis</p>
                            <div class="flex flex-col items-center space-y-2">
                                <div class="flex space-x-4">
                                    <div class="flex flex-col items-center space-y-1">
                                        <label for="manMin">Min</label>
                                        <input type="number" id="manMin" min="0" max="99" value="1" class="w-16 text-center bg-transparent border border-secondaryDarker rounded" />
                                        <span id="manMinError" class="text-red-500 text-xs hidden">At least 1 person required</span>
                                    </div>
                                    <div class="flex flex-col items-center space-y-1">
                                        <label for="manMax">Max</label>
                                        <input type="number" id="manMax" min="0" max="99" value="1" class="w-16 text-center bg-transparent border border-secondaryDarker rounded" />
                                        <span id="manMaxError" class="text-red-500 text-xs hidden"></span>
                                    </div>
                                </div>
                            </div>
                            <div id="manIMG" class="grid gap-4 items-center justify-center mt-4 w-[239.828px]"></div>
                        </div>
                        <div class="flex flex-col text-center items-center flex-grow" id="howMuchWoman" data-aos="zoom-in" data-aos-duration="1000">
                            <p class="mb-3">People <span class="underline underline-offset-4 decoration-solid decoration-secondary font-extrabold text-lg">without</span> a penis</p>
                            <div class="flex flex-col items-center space-y-2">
                                <div class="flex space-x-4">
                                    <div class="flex flex-col items-center space-y-1">
                                        <label for="womanMin">Min</label>
                                        <input type="number"
                                            id="womanMin"
                                            min="0"
                                            max="99"
                                            value="1"
                                            class="w-16 text-center bg-transparent border border-secondaryDarker rounded" />
                                        <span id="womanMinError" class="text-red-500 text-xs hidden"></span>
                                    </div>
                                    <div class="flex flex-col items-center space-y-1">
                                        <label for="womanMax">Max</label>
                                        <input type="number"
                                            id="womanMax"
                                            min="0"
                                            max="99"
                                            value="1"
                                            class="w-16 text-center bg-transparent border border-secondaryDarker rounded" />
                                        <span id="womanMaxError" class="text-red-500 text-xs hidden"></span>
                                    </div>
                                </div>
                            </div>
                            <div id="womanIMG" class="grid gap-4 items-center justify-center mt-4 w-[239.828px]"></div>
                        </div>
                    </div>
                </div>
                <!-- question 3 -->
                <div class="question transition-opacity duration-500 ease-in-out opacity-0 hidden h-full md:h-96 mb-10  " id="question3">
                    <div class="text-center flex flex-col items-center space-y-1 mb-12" data-aos="zoom-in" data-aos-duration="1000">
                        <h3 class="<?php echo $gradients; ?>">third question</h3>
                        <h2 class="<?php echo $gradients; ?>">Is there a performer that you want to include or exclude?</h2>
                    </div>
                    <div class="flex flex-col items-center space-y-4">
                        <input type="text" id="searchBar" placeholder="Search by name" class="text-TextWhite bg-transparent border-2 border-secondary focus:outline-none rounded">
                        <div class="flex justify-between gap-4 w-70">
                            <label class="flex justify-between items-center gap-1 cursor-pointer">
                                <input type="checkbox" id="searchMan">
                                <span>With penis</span>
                            </label>
                            <label class="flex justify-between items-center gap-1 cursor-pointer">
                                <input type="checkbox" id="searchWoman">
                                <span>Without a penis</span>
                            </label>
                        </div>
                        <div class="flex flex-wrap gap-2 w-full max-w-3xl justify-center align-center">
                            <!-- New containers for included and excluded performer tags -->
                            <div id="taggsIncluded" class="flex flex-wrap gap-2"></div>
                            <div id="taggsExcluded" class="flex flex-wrap gap-2"></div>
                        </div>
                        <div id="searchResults" class="w-full max-w-3xl bg-darkPrimairy/50 rounded-lg text-center py-4 max-h-[65dvh] overflow-y-auto shadow-lg snap-y snap-proximity">
                            <!-- Search results will be inserted here -->
                        </div>
                    </div>
                </div>

                <!-- question 4 -->
                <div class="question transition-opacity duration-500 ease-in-out opacity-0 hidden h-auto md:h-96 mb-10 " id="question4">
                    <div class="text-center flex flex-col items-center space-y-1 mb-12" data-aos="zoom-in" data-aos-duration="1000">
                        <h3 class="<?php echo $gradients; ?>">Fourth question</h3>
                        <h2 class="<?php echo $gradients; ?>">Do you want to edit someone?</h2>
                    </div>
                    <div class="flex flex-col items-center space-y-4" id="peopleSummary"></div>
                </div>
                <!-- Add more questions here -->
            </div>
            <div class="flex flex-col self-center justify-self-center items-center justify-center space-y-4 aos-init aos-animate w-fit h-fit mb-6 md:mb-0 no-aos-mobile" data-aos="fade-left" data-aos-duration="1000">
                <button type="button"
                    id="nextButton"
                    class="rounded-full border-2 border-secondary p-3 sm:p-5 hover:bg-secondary hover:border-primairy hover:border-2 hover:text-gray-950 transition duration-500 ease-in-out"
                    onclick="nextQuestion()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </button>
            </div>
        </form>
    </main>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/scripts.php'; ?>

    <!-- Add global variables -->
    <script>
        window.APP_VERSION = '<?php echo time(); ?>';
        window.performerDetailsCache = new Map();
    </script>

    <!-- Load scripts in correct order -->
    <script src="/assets/js/utils/cache.js?v=<?php echo time(); ?>"></script>
    <script src="/assets/js/experiencePage/pagination.js?v=<?php echo time(); ?>"></script>
    <script src="/assets/js/experiencePage/question3.js?v=<?php echo time(); ?>"></script>
    <script type="module" src="/assets/js/modals/performerModal.js?v=<?php echo time(); ?>"></script>
    <script type="module" src="/assets/js/experiencePage/question2.js?v=<?php echo time(); ?>"></script>
    <script type="module" src="/assets/js/experiencePage/question4.js?v=<?php echo time(); ?>"></script>
</body>
</html>