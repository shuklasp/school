<?php
/*
 * File controller.modmain.php
 * Controller for modmain.php
 */

$app=new \SPP\App('sppadmin');
$app1=new \SPP\App('demo');
\SPP\Scheduler::setContext('demo');
$mods=\SPP\Module::scanModules();
//print_r($mods);
//$opt=new SPPViewForm_Input_Checkbox('sppoption');
$i=1;
foreach($mods as $modfile)
{
    $mod=new \SPP\Module($modfile);
    $modarray[$mod->ModuleGroup][$mod->InternalName]['pubname']=$mod->PublicName;
    $modarray[$mod->ModuleGroup][$mod->InternalName]['pubdesc']=$mod->PublicDesc;
    if(\SPP\Module::isEnabled($mod->InternalName))
    {
        $modarray[$mod->ModuleGroup][$mod->InternalName]['chbox']=new SPPViewForm_Input_Checkbox('sppoption'.$i,$modfile,true);
        $i++;
//        $opt->addOption($modfile, true);
    }
    else
    {
        $modarray[$mod->ModuleGroup][$mod->InternalName]['chbox']=new SPPViewForm_Input_Checkbox('sppoption'.$i,$modfile);
        $i++;
        //$opt->addOption($modfile);
    }
}
\SPP\Scheduler::setContext('sppadmin');
//var_dump($_REQUEST);
//$mods=compact($mods);
/*foreach($mods as $mod)
{
    $opt->addOption($mod);
}*/
//echo $opt;
//print_r($mods);
?>