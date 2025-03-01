<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/include-all.php';

try {
    // Databaseverbinding

    // JSON-bestand laden
    $json = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/performers_details_data.json');
    $data = json_decode($json, true);

    // Query's voorbereiden
    $performerStmt = $pdo->prepare("
        INSERT INTO performers (
            id, slug, name, bio, rating, is_parent, gender, birthday, deathday, 
            birthplace, ethnicity, nationality, hair_color, eye_color, height, 
            weight, measurements, waist_size, hip_size, cup_size, tattoos, 
            piercings, fake_boobs, same_sex_only, career_start_year, career_end_year, 
            image_amount, image_folder, page, performer_number
        ) VALUES (
            :id, :slug, :name, :bio, :rating, :is_parent, :gender, :birthday, :deathday, 
            :birthplace, :ethnicity, :nationality, :hair_color, :eye_color, :height, 
            :weight, :measurements, :waist_size, :hip_size, :cup_size, :tattoos, 
            :piercings, :fake_boobs, :same_sex_only, :career_start_year, :career_end_year, 
            :image_amount, :image_folder, :page, :performer_number
        )
    ");

    $imageStmt = $pdo->prepare("
        INSERT INTO performer_images (performer_id, image_url) 
        VALUES (:performer_id, :image_url)
    ");

    $checkPerformerStmt = $pdo->prepare("SELECT COUNT(*) FROM performers WHERE id = :id");

    // Data importeren
    foreach ($data as $performer) {
        // Check if performer already exists
        $checkPerformerStmt->execute([':id' => $performer['id']]);
        if ($checkPerformerStmt->fetchColumn() > 0) {
            continue; // Skip existing performer
        }

        // Convert boolean values to 0 or 1
        $performer['is_parent'] = $performer['is_parent'] ? 1 : 0;
        $performer['fake_boobs'] = $performer['fake_boobs'] ? 1 : 0;
        $performer['same_sex_only'] = $performer['same_sex_only'] ? 1 : 0;

        // Handle empty date values
        $performer['birthday'] = !empty($performer['birthday']) ? $performer['birthday'] : null;
        $performer['deathday'] = !empty($performer['deathday']) ? $performer['deathday'] : null;

        // Performer toevoegen
        $performerStmt->execute([
            ':id' => $performer['id'],
            ':slug' => $performer['slug'],
            ':name' => $performer['name'],
            ':bio' => $performer['bio'],
            ':rating' => $performer['rating'],
            ':is_parent' => $performer['is_parent'],
            ':gender' => $performer['gender'],
            ':birthday' => $performer['birthday'],
            ':deathday' => $performer['deathday'],
            ':birthplace' => $performer['birthplace'],
            ':ethnicity' => $performer['ethnicity'],
            ':nationality' => $performer['nationality'],
            ':hair_color' => $performer['hair_color'],
            ':eye_color' => $performer['eye_color'],
            ':height' => $performer['height'],
            ':weight' => $performer['weight'],
            ':measurements' => $performer['measurements'],
            ':waist_size' => $performer['waist_size'],
            ':hip_size' => $performer['hip_size'],
            ':cup_size' => $performer['cup_size'],
            ':tattoos' => $performer['tattoos'],
            ':piercings' => $performer['piercings'],
            ':fake_boobs' => $performer['fake_boobs'],
            ':same_sex_only' => $performer['same_sex_only'],
            ':career_start_year' => $performer['career_start_year'],
            ':career_end_year' => $performer['career_end_year'],
            ':image_amount' => $performer['image_amount'],
            ':image_folder' => $performer['image_folder'],
            ':page' => $performer['page'],
            ':performer_number' => $performer['performer_number'],
        ]);

        // Afbeeldingen toevoegen
        foreach ($performer['image_urls'] as $image_url) {
            $imageStmt->execute([
                ':performer_id' => $performer['id'],
                ':image_url' => $image_url,
            ]);
        }
    }

    echo "Data succesvol geïmporteerd!";
} catch (PDOException $e) {
    die("Fout: " . $e->getMessage());
}
?>