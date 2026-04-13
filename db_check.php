<?php
require 'spp/sppinit.php';
$db = new \SPPMod\SPPDB\SPPDB();
$tables = ['users', 'roles', 'rights', 'roleright', 'userroles', 'config', 'sppgroups', 'sppgroupmembers', 'logger', 'profiletabs'];
foreach($tables as $t) {
    try {
        echo "\nTable: $t\n";
        $res = $db->execute_query("DESCRIBE $t");
        foreach($res as $col) {
            $null = ($col['Null'] === 'NO') ? 'NOT NULL' : '';
            $extra = $col['Extra'] ? ' ' . $col['Extra'] : '';
            $key = ($col['Key'] === 'PRI') ? ' PRIMARY KEY' : '';
            echo "  - {$col['Field']}: \"{$col['Type']} {$null}{$key}{$extra}\"\n";
        }
    } catch (Exception $e) {
        echo "Table $t missing\n";
    }
}
