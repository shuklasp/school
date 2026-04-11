<?php
require_once 'spp/sppinit.php';
$xmlPath = 'etc/forms/test_form.xml';
$xml = simplexml_load_file($xmlPath);
$arr = \SPP\SPPUtils::xml2phpArray($xml);
echo "XML Array Structure:\n";
print_r($arr);
