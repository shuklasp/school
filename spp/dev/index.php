<?php
require_once('../sppinit.php');

//require_once SPP_BASE_DIR.SPPUS.'sppbase.php')
//session_start();
//require_once SPP_BASE_DIR.SPPUS.'sppauth.php';
//SPP_Base::useModule('SPP_Auth');
//SPP_Base::useModule('SPPHtml');
/* if(!SPP_Dev::isDevEnvSetup())
{
    header('Location: '.SPP_Base::sppLink('devsetup.php'));
}
require_once 'model.index.php';
//require_once 'class.SPP_Dev.php';
if(array_key_exists('login', $_POST))
{
    SPP_Auth::login($_POST['login'], $_POST['passwd']);
}
?>

 */
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Developer Console | Satya Portal Pack</title>
        <?php SPP_HTML_Page::includeJSFiles(); ?>
        <?php SPP_HTML_Page::includeCssFiles(); ?>
        <link rel="stylesheet" href="devres/css/devconsole.css" />
    </head>
    <body>
        <img src="<?php echo SPP_IMG_URI.SPP_US.'spp-logo.jpg'; ?>" alt="Satya Portal Pack Logo" height="125px" width="250px">
        <br /><hr />
        <?php
        if(!SPP_Auth::authSessionExists())
        {
        ?>
        <br /><br /><br /><br />
        <div id="loginformdiv" align="center">
            <form action="<?php $_SERVER['PHP_SELF'] ?>" method="post">
                <table border="0">
                    <tr><td>User ID</td><td><?php echo $loginbox->getHTML(); ?></td></tr>
                    <tr><td>Password</td><td><?php echo $passwdbox->getHTML(); ?></td></tr>
                   <!-- <tr><td>Date</td><td><?php //echo $ldate->getHTML(); ?></td></tr> -->
                    <tr><td colspan="2" align="center"><?php echo $loginsubmit->getHTML(); ?></td></tr>
                </table>
            </form>
        </div>
        <?php
        }
        else
        {
            require('devconsole.php');
            SPP_Auth::logout();
        }
        ?>
        <br /> <br />
        <hr>
        <div align="center">Powered by <a href="http://spp.vshiksha.com" title="Satya Portal Pack Website" target="_blank">Satya Portal Pack</a>.</div>
    </body>
</html>
