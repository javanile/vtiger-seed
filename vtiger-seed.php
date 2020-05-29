<?php

if (!file_exists('vtiger.json')) {
    die("File not found: 'vtiger.json'\n");
}

$vtiger = json_decode(file_get_contents('vtiger.json'), true);

if (empty($vtiger['vtiger_dir'])) {
    die("Missing 'vtiger_dir' value on 'vtiger.json' file\n");
}

if (!file_exists($vtiger['vtiger_dir'].'/config.inc.php')) {
    die("Missing vtiger instance on '{$vtiger['vtiger_dir']}' directory\n");
}

if (empty($argv[1])) {
    die("Missing file operand\n");
}

if (!file_exists($argv[1])) {
    die("File not found: '{$argv[1]}'\n");
}

$module = 'Contacts';

$file = fopen($argv[1], "r");

while (!feof($file)) {
    $line = fgets($file);
    if (!$line) {
        continue;
    } else if (preg_match('/^\[([a-z][a-z0-9_]+)\]$/i', $line, $data)) {
        $module = $data[1];
    } else {
        chdir($vtiger['vtiger_dir']);
        set_include_path($vtiger['vtiger_dir']);
        require_once("modules/{$module}/{$module}.php");
    }
}

fclose($file);
