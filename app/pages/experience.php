<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/include-all.php';
?>

<body class="text-TextWhite">
    <header>
        <div class="mt-10 flex flex-col items-center justify-center space-y-2" data-aos="fade-down" data-aos-duration="1000">
            <?php
            $gradients = [
                'bg-gradient-to-r',
                'bg-gradient-to-l',
                'bg-gradient-to-t',
                'bg-gradient-to-b',
                'bg-gradient-to-tr',
                'bg-gradient-to-tl',
                'bg-gradient-to-br',
                'bg-gradient-to-bl'
            ];
            shuffle($gradients);
            ?>

            <h1 class="text-4xl font-bold <?php echo $gradients[0]; ?> from-secondary to-tertery bg-clip-text text-transparent" data-aos="fade-down" data-aos-duration="1000">Customize your experience.</h1>
            <h2 class="<?php echo $gradients[1]; ?> from-secondary to-tertery bg-clip-text text-transparent" data-aos="fade-down" data-aos-duration="1000">Select what you want to find</h2>
            <h3 class="<?php echo $gradients[2]; ?> from-secondary to-tertery bg-clip-text text-transparent" data-aos="fade-down" data-aos-duration="1000">And guess what... We will find it for you</h3>
        </div>
    </header>
    <main>
        <div class="flex justify-center mt-60">

            <button>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>


            <button>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </button>



        </div>
    </main>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/scripts.php';
    ?>
</body>