<?php
/*
 * File controller.modmain.php
 * Controller for modmain.php
 */

$app=new SPP_App('sppadmin');
$app1=new SPP_App('demo');
SPP_Scheduler::setContext('demo');
$mods=SPP_Module::scanModules();
//print_r($mods);
//$opt=new SPP_Form_Input_Checkbox('sppoption');
$i=1;
foreach($mods as $modfile)
{
    $mod=new SPP_Module($modfile);
    $modarray[$mod->ModuleGroup][$mod->InternalName]['pubname']=$mod->PublicName;
    $modarray[$mod->ModuleGroup][$mod->InternalName]['pubdesc']=$mod->PublicDesc;
    if(SPP_Module::isEnabled($mod->InternalName))
    {
        $modarray[$mod->ModuleGroup][$mod->InternalName]['chbox']=new SPP_Form_Input_Checkbox('sppoption'.$i,$modfile,true);
        $i++;
//        $opt->addOption($modfile, true);
    }
    else
    {
        $modarray[$mod->ModuleGroup][$mod->InternalName]['chbox']=new SPP_Form_Input_Checkbox('sppoption'.$i,$modfile);
        $i++;
        //$opt->addOption($modfile);
    }
}
SPP_Scheduler::setContext('sppadmin');
//var_dump($_REQUEST);
//$mods=compact($mods);
/*foreach($mods as $mod)
{
    $opt->addOption($mod);
}*/
//echo $opt;
//print_r($mods);
?>