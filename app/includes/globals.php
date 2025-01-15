<?php
// Define global variables
$siteName = "ExplicitTags";
$baseUrl = "http://localhost:8000";
$adminEmail = "admin@example.com";

// Any other global variables can be defined here
function getRandomGradientClass($isText = false) {

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

    $randomIndex = rand(0, count($gradients) - 1);

    $gradientClass = $gradients[$randomIndex];

    if ($isText) {
        $gradientClass .= ' from-secondary to-tertery bg-clip-text text-transparent';
    }

    return $gradientClass;
}
?>
