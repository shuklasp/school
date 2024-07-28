<?php
/**
 * function login
 *
 * @param string $uname
 * @param string $passwd
 * @param string $caller
 * @return void
 */
function login()
{
    $loginsuccess=false;
    $result=array('login'=>'false');
    $msg='';
    $uname=Ajax::getValue('uname');
    $passwd=Ajax::getValue('passwd');
    try{
        if(!\SPPMod\SPPAuth\SPPAuth::authSessionExists())
        {
            \SPPMod\SPPAuth\SPPAuth::login($uname, $passwd);
            $result['login']=true;
        }
    }
    catch(UserNotFoundException $e)
    {
        $result['msg']=$e->getMessage();
    }
    catch(UserBannedException $e)
    {
        $result['msg']=$e->getMessage();
    }
    catch(UserAuthenticationException $e){
        $result['msg']=$e->getMessage();
    }
    if(!Ajax::existsVar('caller'))
    {
        $result['callpage']=Ajax::getPageLocation('welcome');
        //$result['html']=Ajax::getScript(Ajax::getPageLocation('welcome'));
        //require('src/comp/home-welcome.php');
    }
    else{
        //require('../comp/'.$caller);
        $result['callpage']=Ajax::getPageLocation(Ajax::getValue('caller'));
        //$result['html']=Ajax::getScript(Ajax::getPageLocation(Ajax::getValue('caller')));
    }
    Ajax::returnAjax($result);
//    $res=array(
//        'success'=>$loginsuccess,
//        'message'=>$msg
//    );
}

function logout()
{
    $result=array();
    \SPPMod\SPPAuth\SPPAuth::logout();
    $result['callpage']='src/comp/loggedout.php';
    $result['msg']='';
    Ajax::returnAjax($result);
}
?>