<?php
    $baseDir = 'App';

    $directoryIterator = new RecursiveDirectoryIterator(
        $baseDir,
        RecursiveDirectoryIterator::SKIP_DOTS
    );

    $filterIterator = new RecursiveCallbackFilterIterator(
        $directoryIterator,
        function ($current, $key, $iterator) {
            // Sauter les dossiers nommés "Tables" (chargés par configs.php via glob)
            if ($current->isDir() && $current->getFilename() === 'Tables') {
                return false;
            }
            return true;
        }
    );

    $iterator = new RecursiveIteratorIterator($filterIterator);

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            require_once $file->getPathname();
        }
    }

require_once('vendor/Libs/sysCodeunit APIRestCaller.php');

?>


