<?php

function readLinePrompt(string $prompt): string {
    echo $prompt;
    return trim(fgets(STDIN));
}

function pause(): void {
    readLinePrompt("Tekan Enter untuk lanjut...");
}

$numbers = [];
$isSorted = false;

while (true) {
    echo "\n=========================\n";
    echo "       MENU PILIHAN\n";
    echo "=========================\n";
    echo "1. Input angka\n";
    echo "2. Sorting\n";
    echo "3. Searching\n";
    echo "4. Selesai\n";

    $choice = readLinePrompt("Masukkan pilihan [1/2/3/4] : ");

    // Input
    if ($choice === "1") {
    $nInput = readLinePrompt("Masukkan jumlah nilai tugas : ");
    $n = (int)$nInput;

    if ($n <= 0) {
        echo "Jumlah harus > 0.\n";
        pause();
        continue;
    }

    $numbers = [];
    echo "Input Angka Secara Acak\n";
    echo "-------------------------\n";

    for ($i = 1; $i <= $n; $i++) {
        $val = rand(1, 100);

        $numbers[] = $val;
        echo "Angka $i : $val\n";
    }

    $isSorted;
    pause();

    // Sorting
    } elseif ($choice === "2") {
        if (count($numbers) === 0) {
            echo "Data masih kosong. Pilih menu 1 dulu.\n";
            pause();
            continue;
        }

        sort($numbers);
        $isSorted = true;

        echo "\nTAMPIL HASIL SORTING\n";
        echo "Hasil sorting : " . implode(", ", $numbers) . "\n";
        pause();

    // Searching
    } elseif ($choice === "3") {
        if (count($numbers) === 0) {
            echo "Data masih kosong. Pilih menu 1 dulu.\n";
            pause();
            continue;
        }

        if (!$isSorted) {
            echo "Catatan: data belum disorting (tidak wajib untuk pencarian linear).\n";
        }

        $targetStr = readLinePrompt("Masukkan angka yang dicari : ");
        if (!is_numeric($targetStr)) {
            echo "Input harus angka.\n";
            pause();
            continue;
        }
        $target = (int)$targetStr;

        // Searching sederhana (linear search)
        $idx = array_search($target, $numbers, true);

        echo "\nTAMPIL HASIL SEARCHING\n";
        if ($idx !== false) {
            echo "Angka ditemukan.\n";
        } else {
            echo "Angka tidak ditemukan.\n";
        }
        pause();

    } elseif ($choice === "4") {
        echo "Selesai.\n";
        break;

    } else {
        echo "Pilihan tidak valid.\n";
        pause();
    }
}
