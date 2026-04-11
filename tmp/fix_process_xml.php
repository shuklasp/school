<?php
$file = 'c:/projects/apache/school1/spp/modules/spp/sppview/class.viewpage.php';
$content = file_get_contents($file);

$newMethod = <<<EOD
    public static function processXMLForm()
    {
        \$xml = self::\$xml;
        \$arr = \SPP\SPPUtils::xml2phpArray(\$xml);
        
        \$formsToProcess = array();
        if (array_key_exists('forms', \$arr)) {
            \$formsToProcess = \$arr['forms'];
        } elseif (array_key_exists('form', \$arr)) {
            // Root was <forms>, children are directly 'form'
            \$formsToProcess = [ ['form' => \$arr['form']] ];
        }

        foreach (\$formsToProcess as \$forms) {
            if (array_key_exists('form', \$forms)) {
                foreach (\$forms['form'] as \$form) {
                    \$fname = \$form['attributes']['name'] ?? \$form['name'] ?? 'unnamed_form';
                    \$faction = \$form['attributes']['action'] ?? \$form['action'] ?? '';
                    \$fid = \$form['attributes']['id'] ?? \$form['id'] ?? null;
                    \$fmethod = \$form['attributes']['method'] ?? \$form['method'] ?? 'post';

                    \$frm = new ViewForm(\$fname, \$fmethod, \$faction, \$fid);

                    if (array_key_exists('controls', \$form)) {
                        foreach (\$form['controls'] as \$controls) {
                            if (array_key_exists('control', \$controls)) {
                                foreach (\$controls['control'] as \$control) {
                                    \$cnt = self::createElementFromArray(\$control);
                                    \$frm->addElement(\$cnt);
                                }
                            }
                        }
                    }
                    if (class_exists('\\SPPMod\\SPPView\\ViewValidator')) {
                        if (array_key_exists('validations', \$form)) {
                            foreach (\$form['validations'] as \$validations) {
                                if (array_key_exists('validation', \$validations)) {
                                    foreach (\$validations['validation'] as \$validation) {
                                        self::validationsFromArray(\$frm, \$validation);
                                    }
                                }
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
    echo "Successfully updated processXMLForm in class.viewpage.php\n";
} else {
    echo "Error: Could not find or replace processXMLForm method.\n";
}
?>
