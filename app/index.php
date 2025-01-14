<?php

require_once __DIR__ . '/includes/include-all.php';
?>

<body class="text-TextWhite">
    <main>
        <header>
            <div class="mt-10 flex flex-col items-center justify-center space-y-2" data-aos="fade-up"></div>
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

            <h1 class="text-4xl font-bold <?php echo $gradients[0]; ?> from-secondary to-tertery bg-clip-text text-transparent" data-aos="fade-up">Welcome to <?php echo $siteName; ?></h1>
            <h2 class="<?php echo $gradients[1]; ?> from-secondary to-tertery bg-clip-text text-transparent" data-aos="fade-up">Choose the following options to customize your search results</h2>
            <h3 class="<?php echo $gradients[2]; ?> from-secondary to-tertery bg-clip-text text-transparent" data-aos="fade-up">Please follow the step by step guide</h3>
            </div>
        </header>
    </main>
    <?php
    require_once __DIR__ . '/includes/scripts.php';
    ?>
</body>