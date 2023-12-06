<?php

if (! function_exists('alifc_storage_path')) {
    /**
     * Get the path to the storage folder.
     *
     * @param  string  $path
     * @return string
     */
    function alifc_storage_path(string $path = ''): string
    {
        $logPath = env('FC_STORAGE_PATH') ?: config('alifc.storagePath') ?: storage_path();

        if (empty($path)) {
            return $logPath;
        }

        return rtrim($logPath, '/').DIRECTORY_SEPARATOR.ltrim($path, '/');
    }
}
