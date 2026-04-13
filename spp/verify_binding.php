<?php
require_once('sppinit.php');

echo "--- Form Binding Verification ---\n";

try {
    // 1. Resolve path
    $formName = 'user_edit';
    $adminFormPath = SPP_BASE_DIR . SPP_DS . 'etc' . SPP_DS . 'apps' . SPP_DS . 'admin' . SPP_DS . 'forms' . SPP_DS . $formName . '.yml';
    
    if (!file_exists($adminFormPath)) {
        die("FAIL: Admin form path not found at $adminFormPath\n");
    }

    // 2. Build form
    $form = \SPPMod\SPPView\ViewFormBuilder::fromYaml($adminFormPath);
    echo "Form Title (Matter): " . $form->getMatter() . "\n";
    echo "Entity Class: " . $form->getEntityClass() . "\n";

    // 3. Bind a test entity (admin user)
    $admin = new \SPPMod\SPPAuth\SPPUser(1);
    $form->bind($admin);
    echo "Binding completed for user: " . $admin->username . "\n";

    // 4. Inspect HTML for populated values
    $html = $form->getHTML();
    if (strpos($html, 'value="admin"') !== false) {
        echo "SUCCESS: Username 'admin' found in rendered HTML.\n";
    } else {
        echo "FAILURE: Username 'admin' NOT found in HTML.\n";
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "--- Done ---\n";
