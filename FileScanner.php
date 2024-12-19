<?php

class FileScanner
{
    private string $directory;
    private string $logDirectory;

    public function __construct(string $directory, string $logDirectory)
    {
        $this->directory = $directory;
        $this->logDirectory = $logDirectory;
    }

    public function listFiles(string $outputFile): array
    {
        $file = fopen($outputFile, 'w');
        fwrite($file, "Lista plików wygenerowana: " . date('Y-m-d H:i:s') . "\n\n");

        $files = $this->scanDirectory($this->directory);

        foreach ($files as $path => $info) {
            fwrite($file, "$path => {$info['size']} bytes, Last modified: " . date('Y-m-d H:i:s', $info['date']) . "\n");
        }

        fclose($file);
        return $files;
    }

    private function scanDirectory(string $dir, array &$files = []): array
    {
        $dh = opendir($dir);
        while (($item = readdir($dh)) !== false) {
            if ($item == '.' || $item == '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->scanDirectory($path, $files);
            } else {
                $files[$path] = [
                    'size' => filesize($path),
                    'date' => filemtime($path),
                ];
            }
        }
        closedir($dh);
        return $files;
    }

    public function getOldestAndNewestFile(): ?array
    {
        $files = [];

        $dh = opendir($this->logDirectory);
        while (($item = readdir($dh)) !== false) {
            if ($item == '.' || $item == '..') continue;

            $path = $this->logDirectory . DIRECTORY_SEPARATOR . $item;
            if (is_file($path)) {
                $files[$path] = filemtime($path);
            }
        }
        closedir($dh);

        if (empty($files)) {
            return null;
        }

        asort($files);

        return [
            'oldest' => key($files),
            'newest' => key(array_slice($files, -1, 1, true)),
        ];
    }

    public function compareLines(string $oldFile, string $newFile): array
    {
        $oldLines = file($oldFile, FILE_IGNORE_NEW_LINES);
        $newLines = file($newFile, FILE_IGNORE_NEW_LINES);

        $addedLines = array_diff($newLines, $oldLines);
        $removedLines = array_diff($oldLines, $newLines);

        echo "Linie, które pojawiły się w nowym pliku (added):\n";
        foreach ($addedLines as $line) {
            echo $line . "\n";
        }

        echo "\nLinie, które zniknęły w nowym pliku (removed):\n";
        foreach ($removedLines as $line) {
            echo $line . "\n";
        }

        if (empty($addedLines) && empty($removedLines)) {
            echo "\nBrak różnic między plikami.\n";
        }

        return [$addedLines, $removedLines];
    }

    public function findPathsToScan(array $addedLines): array
    {
        $pathToScan = [];
        foreach ($addedLines as $line) {
            if (preg_match('/^(.*) =>/', $line, $matches)) {
                if (strpos($matches[1], 'LLMscanner/loggs') === false && pathinfo($matches[1], PATHINFO_EXTENSION) === 'php') {
                    $pathToScan[] = $matches[1];
                }
            }
        }
        return $pathToScan;
    }
}

function changeDetect(){
    // Example usage
    $date = date('Y-m-d_H-i-s');
    $logDirectory = './loggs';
    $scanner = new FileScanner('./../../..', $logDirectory);

    $outputFile = $logDirectory . '/' . $date . '_file_list.txt';
    $scanner->listFiles($outputFile);
    echo "Lista plików została zapisana w pliku: $outputFile\n";

    $files = $scanner->getOldestAndNewestFile();
    if ($files) {
        $oldestFile = $files['oldest'];
        $newestFile = $files['newest'];

        $lines = $scanner->compareLines($oldestFile, $newestFile);

        $pathToScan = $scanner->findPathsToScan($lines[0]);
    } else {
        echo "\nBrak plików w folderze loggs.\n";
    }

    return $pathToScan;
}


function listDir($directory){
    $date = date('Y-m-d_H-i-s');
    $logDirectory = './scan';
    $outputFile = $logDirectory . '/' . $date . '_file_list.txt';
    $scanner = new FileScanner($directory, $logDirectory);
    $files = $scanner->listFiles($outputFile);
    return $files;
}