<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Comparaison des programmes entre deux fichiers JSON</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: url('Fond.png') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100%;
        }
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5); /* Ajustez l'opacité pour baisser la luminosité */
            z-index: -1;
            height: 100%;
            width: 100%;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 600px;
            overflow-y: auto;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
        }
        input[type="file"],
        input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: rgb(139, 0, 0); /* Darker red for better contrast */
            color: #fff;
            border: none;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: rgb(189, 0, 0);
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .scrollable {
            max-height: 300px;
            overflow-y: auto;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Surfacing & Broadcast</h1>
        <form method="post" enctype="multipart/form-data">
            <label for="file1">Fichier JSON de broadcast :</label>
            <input type="file" name="file1" id="file1" required><br>
            <label for="file2">Fichier JSON de Surfacing :</label>
            <input type="file" name="file2" id="file2" required><br>
            <input type="submit" value="Analyser">
        </form>

        <?php
        function normalizeDate($dateString) {
            // Vérifier si la date contient un "T" (format ISO 8601)
            if (strpos($dateString, 'T') !== false) {
                return new DateTime($dateString); // Format ISO 8601
            } else {
                return DateTime::createFromFormat('Y-m-d H:i:s', $dateString); // Format "YYYY-MM-DD HH:MM:SS"
            }
        }
        function filterBroadcastWithinNext28Hours($programs) {
            $filteredPrograms = [];
            $currentTime = new DateTime(); // Heure actuelle
            $limitTime = (clone $currentTime) -> add(new DateInterval('PT28H')); // Heure actuelle + 28 heures

            foreach ($programs as $program) {
                if (isset($program['start_date'])) {
                    $programStartTime = $program['start_date'];
                    if (($programStartTime >= ($currentTime -> format('Y-m-d H:i:s')))  && ($programStartTime <= ($limitTime -> format('Y-m-d H:i:s')))) {
                        $filteredPrograms[] = $program;
                    }
                }
            }
            return $filteredPrograms;
        }
        function filterSurfacingWithinNext28Hours($programs) {
            $filteredPrograms = [];
            $currentTime = new DateTime(); // Heure actuelle
            $limitTime = (clone $currentTime) -> add(new DateInterval('PT28H')); // Heure actuelle + 28 heures

            foreach ($programs as $program) {
                if (isset($program['available_starting'])) {
                    $programStartTime = $program['available_starting'];
                    if (($programStartTime >= ($currentTime -> format('Y-m-d H:i:s')))  && ($programStartTime <= ($limitTime -> format('Y-m-d H:i:s')))) {
                        $filteredPrograms[] = $program;
                    }
                }
            }
            return $filteredPrograms;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Vérification des fichiers téléchargés
            if (isset($_FILES['file1']) && isset($_FILES['file2'])) {
                $file1 = $_FILES['file1'];
                $file2 = $_FILES['file2'];

                // Vérification des erreurs d'upload
                if ($file1['error'] === UPLOAD_ERR_OK && $file2['error'] === UPLOAD_ERR_OK) {
                    echo "<div class='success'>Les fichiers ont été téléchargés avec succès :</div>";
                    echo "<ul>";
                    echo "<li>Fichier 1 : " . htmlspecialchars($file1['name']) . "</li>";
                    echo "<li>Fichier 2 : " . htmlspecialchars($file2['name']) . "</li>";
                    echo "</ul>";

                    // Lecture et décodage des fichiers JSON
                    $responseContent = file_get_contents($file1['tmp_name']);
                    $surfacingContent = file_get_contents($file2['tmp_name']);
                    $responseData = json_decode($responseContent, true);
                    $surfacingData = json_decode($surfacingContent, true);

                    if ($responseData === null || $surfacingData === null) {
                        echo "<div class='error'>Erreur : Impossible de décoder les fichiers JSON.</div>";
                    } else {
                        // Extraction des programmes de response.json
                        $responsePrograms = [];
                        foreach ($responseData as $item) {
                            if (isset($item['id'], $item['title'], $item['start_date'], $item['end_date'], $item['provider_id'], $item['description'], $item['image_url'])) {
                                $responsePrograms[] = [
                                    'id' => $item['id'],
                                    'title' => $item['title'],
                                    'start_date' => normalizeDate($item['start_date']) -> format('Y-m-d H:i:s'),
                                    'end_date' => normalizeDate($item['end_date']) -> format('Y-m-d H:i:s'),
                                    'provider_id' => $item['provider_id'],
                                    'description' => $item['description'],
                                    'url' => $item['image_url']
                                ];
                            }
                        }
                        $responsePrograms = filterBroadcastWithinNext28Hours($responsePrograms);

                        // Extraction des programmes de Surfacing.json
                        $surfacingPrograms = [];
                        foreach ($surfacingData['programs'] as $program) {                            
                            if (isset($program['program_id'], $program['titles'][0]['title'], $program['playback_items'], $program['descriptions'][0]['description'], $program['images'][0]['url'])) {
                                foreach ($program['playback_items'] as $item) {
                                    if (isset($item['available_starting'], $item['available_ending'], $item['deeplink_payload'])) {
                                        $deeplinkPayload = $item['deeplink_payload'];                                        
                                        $deeplinkPayload = json_decode($deeplinkPayload, true);
                                        if (isset($deeplinkPayload['videoId'], $deeplinkPayload['type']) && $deeplinkPayload['type'] === 'live') {                                           
                                            $surfacingPrograms[] = [
                                                'program_id' => $program['program_id'],
                                                'title' => $program['titles'][0]['title'],
                                                'available_starting' => $item['available_starting'],
                                                'available_ending' => $item['available_ending'],
                                                'video_id' => $deeplinkPayload['videoId'],
                                                'description' => $program['descriptions'][0]['description'],
                                                'url' => $program['images'][0]['url']
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                        $surfacingPrograms = filterSurfacingWithinNext28Hours($surfacingPrograms);

                        // Comparaison des programmes
                        $noTitleMatch = [];
                        $noDateMatch = [];
                        $noProviderMatch = [];
                        $noDescriptionMatch = [];

                        foreach ($responsePrograms as $responseProgram) {
                            $titleMatch = false;
                            $dateMatch = false;
                            $providerMatch = false;
                            $descriptionMatch = false;

                            foreach ($surfacingPrograms as $surfacingProgram) {
                                if (strpos($responseProgram['title'], $surfacingProgram['title']) !== false) {
                                    $titleMatch = true;
                                    if (
                                        (normalizeDate($responseProgram['start_date']) -> format('Y-m-d H:i:s')) === (normalizeDate($surfacingProgram['available_starting']) -> format('Y-m-d H:i:s')) &&
                                        (normalizeDate($responseProgram['end_date']) -> format('Y-m-d H:i:s')) === (normalizeDate($surfacingProgram['available_ending']) -> format('Y-m-d H:i:s'))
                                    ) {
                                        $dateMatch = true;
                                        if ($responseProgram['provider_id'] === $surfacingProgram['video_id']) {
                                            $providerMatch = true;
                                            if ($responseProgram['description'] === $surfacingProgram['description']) {
                                                $descriptionMatch = true;
                                            }
                                            break;
                                        }
                                    }
                                }
                            }

                            if (!$titleMatch) {
                                $noTitleMatch[] = $responseProgram['id'];
                            } elseif (!$dateMatch) {
                                $noDateMatch[] = $responseProgram['id'];
                            } elseif (!$providerMatch) {
                                $noProviderMatch[] = $responseProgram['id'];
                            } elseif (!$descriptionMatch) {
                                $noDescriptionMatch[] = $responseProgram['id'];
                            }
                        }

                        // Création du répertoire pour les logs
                        if (!is_dir("BroadcastTest"))
                            mkdir("BroadcastTest", 0755);
                        //Répertoire des logs du test 
                        $directoryName = "BroadcastTest/BroadcastTest(" . date("Y-m-d_H-i-s") . ")";
                        mkdir($directoryName, 0755);
                        // Création du fichier de logs
                        $Logs = fopen($directoryName . "/Logs.txt", "c");
                        fwrite($Logs, "Programmes dont aucun titre ne correspond:\n");
                        foreach ($noTitleMatch as $id) {
                            fwrite($Logs, $id . "\n");
                        }
                        fwrite($Logs, "\nProgrammes dont aucune date ne correspond:\n");
                        foreach ($noDateMatch as $id) {
                            fwrite($Logs, $id . "\n");
                        }
                        fwrite($Logs, "\nProgrammes dont aucun provider_id ne correspond:\n");
                        foreach ($noProviderMatch as $id) {
                            fwrite($Logs, $id . "\n");
                        }
                        fwrite($Logs, "\nProgrammes dont aucune description ne correspond:\n");
                        foreach ($noDescriptionMatch as $id) {
                            fwrite($Logs, $id . "\n");
                        }
                        fclose($Logs);

                        $destination1 = $directoryName . "/" . $file1['name'];
                        $destination2 = $directoryName . "/" . $file2['name'];
                        // Déplacement des fichiers téléchargés vers le répertoire de logs
                        move_uploaded_file($file1['tmp_name'], $destination1) && move_uploaded_file($file2['tmp_name'], $destination2);


                        // Affichage des résultats
                        echo "<h2>Résultats de la comparaison :</h2>";

                        $categories = [
                            'Programmes dont aucun titre ne correspond' => $noTitleMatch,
                            'Programmes dont aucune date ne correspond' => $noDateMatch,
                            'Programmes dont aucun provider_id ne correspond' => $noProviderMatch,
                            'Programmes dont aucune description ne correspond' => $noDescriptionMatch
                        ];

                        foreach ($categories as $category => $data) {
                            echo "<details style='margin-bottom: 20px;'>";
                            echo "<summary><strong>$category</strong></summary>";
                            if (!empty($data)) {
                                echo "<table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>";
                                echo "<thead><tr><th style='border: 1px solid #ddd; padding: 8px;'>ID</th></tr></thead>";
                                echo "<tbody>";
                                foreach ($data as $id) {
                                    echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>$id</td></tr>";
                                }
                                echo "</tbody></table>";
                            } else {
                                echo "<div class='success' style='margin-top: 10px;'>Aucun problème détecté dans cette catégorie.</div>";
                            }
                            echo "</details>";
                        }
                    }
                } else {
                    echo "<div class='error'>Erreur lors du téléchargement des fichiers.</div>";
                }
            } else {
                echo "<div class='error'>Veuillez sélectionner les deux fichiers.</div>";
            }
        }
        ?>
    </div>
</body>
</html>
