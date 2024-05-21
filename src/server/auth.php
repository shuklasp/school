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
    $uname=SPP_Ajax::getValue('uname');
    $passwd=SPP_Ajax::getValue('passwd');
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
    if(!SPP_Ajax::existsVar('caller'))
    {
        $result['callpage']=SPP_Ajax::getPageLocation('welcome');
        //$result['html']=SPP_Ajax::getScript(SPP_Ajax::getPageLocation('welcome'));
        //require('src/comp/home-welcome.php');
    }
    else{
        //require('../comp/'.$caller);
        $result['callpage']=SPP_Ajax::getPageLocation(SPP_Ajax::getValue('caller'));
        //$result['html']=SPP_Ajax::getScript(SPP_Ajax::getPageLocation(SPP_Ajax::getValue('caller')));
    }
    SPP_Ajax::returnAjax($result);
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
    SPP_Ajax::returnAjax($result);
}
?>