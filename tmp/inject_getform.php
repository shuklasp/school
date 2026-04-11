<?php
$file = 'c:/projects/apache/school1/spp/modules/spp/sppview/class.viewpage.php';
$content = file_get_contents($file);

$method = <<<EOD

    /**
     * Function getForm(\$id)
     * Returns the form from the list.
     *
     * @param string \$id
     * @return mixed
     */
    public static function getForm(\$id)
    {
        if (array_key_exists(\$id, self::\$formslist)) {
            return self::\$formslist[\$id];
        } else {
            return null;
        }
    }

EOD;

// Find the last closing brace
$pos = strrpos($content, '}');
if ($pos !== false) {
    $newContent = substr($content, 0, $pos) . $method . "}\n";
    file_put_contents($file, $newContent);
    echo "Successfully updated class.viewpage.php with getForm method.\n";
} else {
    echo "Error: Could not find closing brace in class.viewpage.php\n";
}
?>
