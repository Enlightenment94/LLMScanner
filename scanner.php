<?php

require_once(__DIR__ . "/FileScanner.php");
require_once(__DIR__ . "/llm.php");

function changeScan(){
    $pathsToScan = changeDetect();

    $temp = "";
    foreach($pathsToScan as $path){
        $temp = file_get_contents($path);
        $content = "Please respond only with 'yes' or 'no.' Is code virus ?? => " . $temp;
        $result = apiRequest($content);
        echo $result . " " . $path  . "\n";    
    }
}

function scanDetect($path) {
    try {
        // Validate path
        if (!is_dir($path)) {
            throw new Exception("Invalid directory path: $path");
        }

        // Get list of files
        $pathsToScan = listDir($path);
        
        if (empty($pathsToScan)) {
            throw new Exception("No files found in directory: $path");
        }

        $results = [
            'total_files' => count($pathsToScan),
            'scanned_files' => 0,
            'errors' => [],
            'scan_results' => []
        ];

        echo "Files to scan: " . $results['total_files'] . "\n";

        foreach (array_keys($pathsToScan) as $filePath) {
            try {
                if (!preg_match('/\.php$/i', $filePath)) {
                    continue;
                }

                if (!is_readable($filePath)) {
                    throw new Exception("File not readable: $filePath");
                }

                $code = file_get_contents($filePath);
                if ($code === false) {
                    throw new Exception("Could not read file: $filePath");
                }

                $result = apiRequest($code);
                
                $results['scan_results'][$filePath] = $result;
                $results['scanned_files']++;

                echo $result . " " . $filePath  . "\n";    

            } catch (Exception $e) {
                $results['errors'][] = [
                    'file' => $filePath,
                    'error' => $e->getMessage()
                ];
                echo "[ERROR] " . $e->getMessage() . "\n";
            }
        }

        echo "\nScan Summary:\n";
        echo "Total files: " . $results['total_files'] . "\n";
        echo "Scanned files: " . $results['scanned_files'] . "\n";
        echo "Errors: " . count($results['errors']) . "\n";

        return $results;

    } catch (Exception $e) {
        echo "[FATAL ERROR] " . $e->getMessage() . "\n";
        return [
            'error' => $e->getMessage(),
            'status' => 'failed'
        ];
    }
}

echo "\n";

//$results = scanDetect(dirname(__DIR__). "/bugplugin");
//$results = scanDetect( dirname(dirname(__DIR__)) . "/themes/envision" );


