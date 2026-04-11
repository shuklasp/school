<?php
$file = 'c:/projects/apache/school1/spp/core/class.spputils.php';
$content = file_get_contents($file);

$newMethod = <<<EOD
    public static function xml2phpArray(\$xml, \$arr = array()) {
        foreach (\$xml->attributes() as \$attr => \$val) {
            \$arr['attributes'][\$attr] = (string)\$val;
        }
        
        \$iter = 0;
        foreach (\$xml->children() as \$b) {
            \$a = \$b->getName();
            if (!\$b->children()) {
                \$arr[\$a] = trim((string)\$b[0]);
            } else {
                \$arr[\$a][\$iter] = array();
                \$arr[\$a][\$iter] = self::xml2phpArray(\$b, \$arr[\$a][\$iter]);
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
    echo "Successfully updated xml2phpArray in class.spputils.php\n";
} else {
    echo "Error: Could not find or replace xml2phpArray method.\n";
    // Fallback search
    if (strpos($content, 'public static function xml2phpArray') !== false) {
         echo "Method signature found, but regex failed matching. Trying literal replacement.\n";
    }
}
?>
