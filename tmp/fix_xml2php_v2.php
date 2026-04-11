<?php
$file = 'c:/projects/apache/school1/spp/core/class.spputils.php';
$content = file_get_contents($file);

$newMethod = <<<EOD
    public static function xml2phpArray(\$xml, \$arr = array()) {
        foreach (\$xml->attributes() as \$attr => \$val) {
            \$arr[\$attr] = (string)\$val;
        }
        
        \$iter = 0;
        foreach (\$xml->children() as \$b) {
            \$a = \$b->getName();
            if (!\$b->children()) {
                \$arr[\$a] = trim((string)\$b[0]);
            } else {
                \$arr[\$a][\$iter] = self::xml2phpArray(\$b, array());
            }
            \$iter++;
        }
        return \$arr;
    }
EOD;

$target = '/public static function xml2phpArray\(\$xml,\$arr=array\(\)\) \{(.*?)\s\s\s\s\}/s';
$updatedContent = preg_replace($target, $newMethod, $content);

if ($updatedContent !== $content) {
    file_put_contents($file, $updatedContent);
    echo "Successfully updated xml2phpArray in class.spputils.php for direct attribute access.\n";
} else {
    echo "Error: Could not find or replace xml2phpArray method.\n";
}
?>
