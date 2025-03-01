<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/include-all.php';

$gradients =   getRandomGradientClass(true);
?>

<body class="text-TextWhite">
    <header>
        <div class="mt-10 flex flex-col items-center justify-center space-y-2" data-aos="fade-down" data-aos-duration="1000">
            <h1 class="text-4xl font-bold <?php echo $gradients; ?> text-center" data-aos="fade-down" data-aos-duration="1000">Welcome to <?php echo $siteName; ?></h1>
            <h2 class="<?php echo $gradients; ?> text-center" data-aos="fade-down" data-aos-duration="1000">Choose the following options to customize your search results</h2>
            <h3 class="<?php echo $gradients; ?> text-center" data-aos="fade-down" data-aos-duration="1000">Please follow the step by step guide</h3>
        </div>
    </header>
    <main>
        <div class="flex flex-col items-center mt-60 space-y-6">
            <a class="hover:transition hover:duration-[150ms] px-4 py-2 bg-primary text-white rounded border border-secondary hover:bg-secondary hover:text-gray-950" data-aos="fade-up" data-aos-duration="1500" href="experience.php">
                Click here to customize your search
            </a>
            <a class="hover:transition hover:duration-[150ms] px-4 py-2 bg-primary text-white rounded border border-secondary hover:bg-secondary hover:text-gray-950" data-aos="fade-up" data-aos-duration="1500" href="tag.php">
                Or tag an video yourself
            </a>
        </div>
    </main>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/scripts.php';
    ?>
</body>