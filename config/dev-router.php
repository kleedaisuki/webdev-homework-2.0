<?php
/**
 * Route virtual paths with file extensions through Yii when using php yii serve.
 */

$root = realpath(dirname(__DIR__) . '/web');
$path = rawurldecode(explode('?', $_SERVER['REQUEST_URI'], 2)[0]);
$file = str_contains($path, "\0") ? false : realpath($root . $path);
if ($file !== false && str_starts_with($file, $root . DIRECTORY_SEPARATOR) && is_file($file)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
require $_SERVER['SCRIPT_FILENAME'];
