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
$fields = null;
while (!feof($file)) {
    $line = trim(fgets($file));
    if (!$line) {
        continue;
    } else if (preg_match('/^\[([a-z][a-z0-9_]+)\]$/i', $line, $data)) {
        $fields = null;
        $module = $data[1];
        chdir($vtiger['vtiger_dir']);
        set_include_path($vtiger['vtiger_dir']);
        require_once 'config.php';
        require_once 'includes/Loader.php';
        include_once 'config.php';
        include_once 'include/Webservices/Relation.php';
        include_once 'vtlib/Vtiger/Module.php';
        include_once 'includes/main/WebUI.php';
        require_once 'includes/runtime/BaseModel.php';
        require_once 'data/CRMEntity.php';
        require_once "modules/Vtiger/models/Module.php";
        require_once "modules/{$module}/{$module}.php";
    } else if (empty($fields)) {
        $fields = str_getcsv($line);
    } else {
        $assigned_user_name = "admin";
        $seed_user = new Users();
        $assigned_user_id = $seed_user->retrieve_user_id($assigned_user_name);
        global $current_user;
        $current_user = new Users();
        $result = $current_user->retrieve_entity_info($assigned_user_id,'Users');

        $values = str_getcsv($line);
        $entity = new $module();
        $id = 0;
        foreach ($fields as $index => $field) {
            if (strtolower($field) != 'id') {
                $entity->column_fields[$field] = trim($values[$index]);
                $entity->column_fields->changed[] = $field;
            } else {
                $id = $values[$index];
            }
        }
        $entity->column_fields["assigned_user_id"] = $assigned_user_id;

        if ($id > 0) {
            $sql = 'SELECT * FROM vtiger_crmentity WHERE crmid=? LIMIT 1';
            $res = $adb->pquery($sql, [$id]);
            if (!$res || $adb->num_rows($res) >= 1) {
                $entity->id = $id;
                $entity->mode = 'edit';
            }
        }

        $res = $entity->save($module);
    }
}

fclose($file);
