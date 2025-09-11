<?php

$baseDir     = __DIR__;
$rotations   = [10, 80, 150, 220, 290];
$size        = 300;
$radius      = 150;

//Folder paths
$originalsDir = "$baseDir/originals";
$rotatedDir   = "$baseDir/rotated";
$tempDir      = "$baseDir/temp"; //Will be deleted at the end

//Create folder
@mkdir($originalsDir, 0777, true);
@mkdir($rotatedDir, 0777, true);
@mkdir($tempDir, 0777, true);

if ($argc > 1 && $argv[1] === 'repair') {
    echo "🛠 Repair mode activated...\n";

    $answersPath = "$baseDir/answers.json";
    $answers = file_exists($answersPath) ? json_decode(file_get_contents($answersPath), true) : [];
    if (!is_array($answers)) $answers = [];
    $changes = false;

    //Auxiliary functions
    function removeFileIfExists($path) {
        global $changes;
        if (file_exists($path)) {
            unlink($path);
            echo "🗑 File deleted: $path\n";
            $changes = true;
        }
    }

    function recreateRotationsFromOriginalTxt($originalTxt, $rotatedDir, $rotations, $tempDir, $base) {
        global $changes;
        // Original Base64 → WebP
        $originalData = base64_decode(file_get_contents($originalTxt));
        $tmpOriginalWebp = "$tempDir/{$base}_orig.webp";
        file_put_contents($tmpOriginalWebp, $originalData);

        foreach ($rotations as $i => $angle) {
            $index = $i + 1;
            $rotatedTxt = "$rotatedDir/{$base}_{$index}.txt";
            if (!file_exists($rotatedTxt)) {
                echo "♻️  Recreating rotation {$index} at {$angle}° for $base\n";
                $rotatedWebp = "$tempDir/{$base}_{$index}.webp";
                exec("magick \"$tmpOriginalWebp\" -background none -distort SRT $angle \"$rotatedWebp\"");
                file_put_contents($rotatedTxt, base64_encode(file_get_contents($rotatedWebp)));
                unlink($rotatedWebp);
                $changes = true;
            }
        }

        @unlink($tmpOriginalWebp);
    }

    //Captured files
    $originalTxts = array_map('basename', glob("$originalsDir/*.txt") ?: []);
    $rotatedTxts  = array_map('basename', glob("$rotatedDir/*.txt") ?: []);

    $answersNames = $answers; //answers.json is an array of originals
    $newAnswers = [];

    foreach ($answersNames as $name) {
        $originalFile = "$originalsDir/$name";
        $base = pathinfo($name, PATHINFO_FILENAME);

        if (!file_exists($originalFile)) {
            echo "❌ Original missing for $name → Removing entry + any rotations.\n";
            foreach (glob("$rotatedDir/{$base}_*.txt") ?: [] as $rotFile) {
                removeFileIfExists($rotFile);
            }
            continue;
        }

        //Original exists → ensure that all 5 rotations are present
        recreateRotationsFromOriginalTxt($originalFile, $rotatedDir, $rotations, $tempDir, $base);

        $newAnswers[] = $name;
    }

    //Remove orphaned originals (not in answers.json)
    foreach ($originalTxts as $file) {
        if (!in_array($file, $answersNames)) {
            removeFileIfExists("$originalsDir/$file");
            $base = pathinfo($file, PATHINFO_FILENAME);
            foreach (glob("$rotatedDir/{$base}_*.txt") ?: [] as $rotFile) {
                removeFileIfExists($rotFile);
            }
        }
    }

    //Remove orphaned rotations (without a matching original)
    $orphanRotations = [];
    $unexpectedFiles = [];

    foreach ($rotatedTxts as $file) {
        if (preg_match('/^(.+)_\d+\.txt$/', $file, $matches)) {
            $base = $matches[1] . ".txt";
            if (!in_array($base, $newAnswers)) {
                $orphanRotations[] = $file;
            }
        } else {
            $unexpectedFiles[] = $file;
        }
    }

    //Message + Deleting Orphans
    if ($orphanRotations) {
        foreach ($orphanRotations as $file) {
            removeFileIfExists("$rotatedDir/$file");
        }
    }

    //Message + Delete Unexpected
    if ($unexpectedFiles) {
        foreach ($unexpectedFiles as $file) {
            removeFileIfExists("$rotatedDir/$file");
        }
    }

    //Update answers.json
    file_put_contents($answersPath, json_encode(array_values($newAnswers), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    function rrmdir($dir) {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            $path = "$dir/$item";
            if (is_dir($path)) {
                rrmdir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
    rrmdir($tempDir);
    if ($changes) {
        echo "✅ Repair completed (inconsistencies fixed).\n";
    } else {
        echo "✨ Everything is fine – no repairs necessary.\n";
    }
    exit;
}

//Prepare or read answers.json (now simple array of filenames)
$answersPath = "$baseDir/answers.json";
if (file_exists($answersPath)) {
    $answers = json_decode(file_get_contents($answersPath), true);
    if (!is_array($answers)) $answers = [];
} else {
    $answers = [];
}

$imageFiles = glob("$baseDir/*.{jpg,jpeg,png}", GLOB_BRACE);

foreach ($imageFiles as $filePath) {
    $filename = basename($filePath);
    $base     = pathinfo($filename, PATHINFO_FILENAME);
    echo "🔄 Process: $filename\n";

    //Step 1: scale + center to 300x300
    $resized = "$tempDir/{$base}_resized.png";
    exec("magick \"$filePath\" -resize {$size}x{$size}^ -gravity center -extent {$size}x{$size} \"$resized\"");

    //Step 2: Create a round mask
    $mask = "$tempDir/{$base}_mask.png";
    exec("magick -size {$size}x{$size} xc:none -fill white -draw \"circle {$radius},{$radius} {$radius},0\" \"$mask\"");

    //Step 3: Apply mask
    $rounded = "$tempDir/{$base}_round.png";
    exec("magick \"$resized\" \"$mask\" -alpha off -compose CopyOpacity -composite \"$rounded\"");

    //Step 4: Save unrotated (original) as webp -> store in originals folder
    $originalWebp = "$originalsDir/{$base}.webp";
    exec("magick \"$rounded\" -strip -define webp:lossless=true \"$originalWebp\"");
    // Save Base64 of original
    file_put_contents("$originalsDir/{$base}.txt", base64_encode(file_get_contents($originalWebp)));
    unlink($originalWebp);

    //Step 5: For each rotation angle create a rotated variant and save as base_i.txt
    foreach ($rotations as $i => $angle) {
        $index = $i + 1; // _1 .. _5
        echo "↪️  Creating rotation {$index} at {$angle}°\n";
        $rotatedWebp = "$rotatedDir/{$base}_{$index}.webp";
        exec("magick \"$rounded\" -strip -background none -distort SRT $angle \"$rotatedWebp\"");
        file_put_contents("$rotatedDir/{$base}_{$index}.txt", base64_encode(file_get_contents($rotatedWebp)));
        unlink($rotatedWebp);
    }

    //Step 6: Add original txt filename to answers.json (only name)
    $entryName = $base . '.txt';
    if (!in_array($entryName, $answers, true)) {
        $answers[] = $entryName;
    }

    //clean up temp files for this image
    @unlink($resized);
    @unlink($mask);
    @unlink($rounded);
}

//Step 7: Write answers.json (array of strings)
$answers = array_values(array_unique($answers));
file_put_contents("$baseDir/answers.json", json_encode($answers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

//Step 8: Delete source images
foreach ($imageFiles as $filePath) {
    @unlink($filePath);
}

//Step 9: Delete the temp folder
foreach (glob("$tempDir/*") ?: [] as $f) {
    @unlink($f);
}
@rmdir($tempDir);

echo "✅ Done! Files saved in:\n";
echo "📁 $originalsDir (Originals as base.txt)\n";
echo "📁 $rotatedDir (Rotated as base_1.txt ... base_5.txt)\n";
echo "📝 $baseDir/answers.json (Array of names, only originals)\n";