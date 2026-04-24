<?php
require_once __DIR__ . '/spp/sppinit.php';
\SPP\Scheduler::setContext('Student');

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$entityClass = '\SPPMod\SPPEntity\Student';
$item = $id ? $entityClass::find($id) : new $entityClass();

// Handle Form Submission
function student_form_submitted() {
    global $item;
    $item->loadFromArray($_POST);
    if ($item->save()) {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

\SPPMod\SPPView\ViewPage::processForms();

$blade = \SPP\App::getApp()->make('blade');

if ($action === 'list') {
    $items = $entityClass::all();
    echo $blade->render('student.index', [
        'title' => 'Student List',
        'items' => $items
    ]);
} else {
    echo $blade->render('student.form', [
        'entityName' => 'Student',
        'item' => $item
    ]);
}
