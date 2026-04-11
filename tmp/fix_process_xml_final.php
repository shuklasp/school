<?php
$file = 'c:/projects/apache/school1/spp/modules/spp/sppview/class.viewpage.php';
$content = file_get_contents($file);

$newMethod = <<<EOD
    public static function processXMLForm()
    {
        \$xml = self::\$xml;
        \$arr = \SPP\SPPUtils::xml2phpArray(\$xml);
        
        // Root <forms> children are 'form' keys
        if (!isset(\$arr['form'])) {
            return;
        }

        foreach (\$arr['form'] as \$form) {
            // Check for direct keys (from attributes or child nodes)
            \$fname = \$form['name'] ?? 'unnamed_form';
            \$faction = \$form['action'] ?? '';
            \$fid = \$form['id'] ?? null;
            \$fmethod = \$form['method'] ?? 'post';

            \$frm = new ViewForm(\$fname, \$fmethod, \$faction, \$fid);

            if (isset(\$form['controls']) && is_array(\$form['controls'])) {
                foreach (\$form['controls'] as \$controls) {
                    if (isset(\$controls['control']) && is_array(\$controls['control'])) {
                        foreach (\$controls['control'] as \$control) {
                            \$cnt = self::createElementFromArray(\$control);
                            \$frm->addElement(\$cnt);
                        }
                    }
                }
            }
            
            if (class_exists('\\SPPMod\\SPPView\\ViewValidator')) {
                if (isset(\$form['validations']) && is_array(\$form['validations'])) {
                    foreach (\$form['validations'] as \$validations) {
                        if (isset(\$validations['validation']) && is_array(\$validations['validation'])) {
                            foreach (\$validations['validation'] as \$validation) {
                                self::validationsFromArray(\$frm, \$validation);
                            }
                        }
                    }
                }
            }
        }
    }
EOD;

$target = '/public static function processXMLForm\(\)(.*?)\s\s\s\s\}/s';
$updatedContent = preg_replace($target, $newMethod, $content);

if ($updatedContent !== $content) {
    file_put_contents($file, $updatedContent);
    echo "Successfully updated processXMLForm in class.viewpage.php with flatter array support.\n";
} else {
    echo "Error: Could not find or replace processXMLForm method.\n";
}
?>
