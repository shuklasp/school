<?php
/*
 * File index.php
 * Home page for administration of SPP
 */

?>
<?php
require_once '../sppinit.php';
require_once 'controls.devsetup.php';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Developer Environment Setup | Satya Portal Pack</title>
        <?php SPP_HTML_Page::includeJSFiles(); SPP_HTML_Page::includeCSSFiles() ?>
    </head>
    <body>
        <img src="<?php echo SPP_IMG_URI.SPP_US.'spp-logo.jpg'; ?>" alt="Satya Portal Pack Logo" height="125px" width="250px">
        <br /><hr />
        <div align="center" name="errordiv" id="errordiv" style="background-color:gray;border-color:red;color:blue;border-left-width:30;border-right-width:30">
        <?php
        //echo $hello;
        echo SPP_Error::getOlErrors('!errno! - !errmsg! - !filename! - !linenum!.');
// echo SPP_Error::getOlErrors();
 SPP_Error::destroyErrors();
 ?>
        </div>
        <br /><br />
        <div id="mainmenu"><a href="modmain.php">Modules Configuration</a></div>
        <br /><br />
        <div align="center" name="setupformdiv" id="setupformdiv">
            <?php
                //print_r(SPP_HTML_Page::getElementsList());
                $frm=SPP_HTML_Page::getElement('installform')->startForm();
                //$frm->startForm();
            ?>
            <table>
                <tr><td>Application Name</td><td><?php echo SPP_HTML_Page::getElement('appname'); ?></td></tr>
                <tr><td></td><td><span id="appnameerror" style="color:red;background-color:grey"></span></td></tr>
                <tr><td>Database Type</td><td><?php echo SPP_HTML_Page::getElement('dbtype'); ?></td></tr>
                <tr><td></td><td><span id="dbtypeerror" style="color:red;background-color:grey"></span></td></tr>
                <tr><td>Database Name</td><td><?php echo SPP_HTML_Page::getElement('dbname'); ?></td></tr>
                <tr><td></td><td><span id="dbnameerror" style="color:red;background-color:grey"></span></td></tr>
                <tr><td>User Name</td><td><?php echo SPP_HTML_Page::getElement('dbuname'); ?></td></tr>
                <tr><td></td><td><span id="dbunameerror" style="color:red;background-color:grey"></span></td></tr>
                <tr><td>Password</td><td><?php echo SPP_HTML_Page::getElement('dbpasswd'); ?></td></tr>
                <tr><td></td><td><span id="dbpasswderror" style="color:red;background-color:grey"></span></td></tr>
                <tr><td>JLT</td><td><?php echo $dgt; ?></td></tr>
                <tr><td align="center" colspan="2"><?php echo SPP_HTML_Page::getElement('formsubmit'); ?></td></tr>
            </table>
            <?php
                SPP_HTML_Page::getElement('installform')->endForm();
            ?>
        <?php
        // put your code here
        ?>
        </div>
        <br /> <br />
        <hr>
        <div align="center">Powered by <a href="http://spp.vshiksha.com" title="Satya Portal Pack Website" target="_blank">Satya Portal Pack</a>.</div>
    </body>
</html>
