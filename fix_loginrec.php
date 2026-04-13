<?php
require_once('spp/sppinit.php');
require_once('global.php');
require_once('vendor/autoload.php');

$db = new \SPPMod\SPPDB\SPPDB();

echo "Step: Creating missing login structure...\n";

// loginrec table
$db->execute_query("CREATE TABLE IF NOT EXISTS loginrec (
    sessid VARCHAR(100) PRIMARY KEY,
    uid INTEGER,
    logintime DATETIME,
    ipaddr VARCHAR(16),
    lastaccess DATETIME
) ENGINE=INNODB");

echo "Success: Login structure established.\n";
