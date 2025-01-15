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

            <h1 class="text-4xl font-bold <?php echo $gradients[0]; ?> from-secondary to-tertery bg-clip-text text-transparent" data-aos="fade-down" data-aos-duration="1000">Welcome to <?php echo $siteName; ?></h1>
            <h2 class="<?php echo $gradients[1]; ?> from-secondary to-tertery bg-clip-text text-transparent" data-aos="fade-down" data-aos-duration="1000">Choose the following options to customize your search results</h2>
            <h3 class="<?php echo $gradients[2]; ?> from-secondary to-tertery bg-clip-text text-transparent" data-aos="fade-down" data-aos-duration="1000">Please follow the step by step guide</h3>
        </div>
    </header>
    <main>
            <div class="flex justify-center mt-60">
                <a class="hover:transition hover:duration-[150ms] px-4 py-2 bg-primary text-white rounded border border-secondary hover:bg-secondary hover:text-gray-950 " data-aos="fade-up" data-aos-duration="1500" href="experience.php">
                Click here to start!
                </a>
            </div>
    </main>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/scripts.php';
    ?>
</body>