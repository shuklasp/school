<?php
//session_start();
require_once 'controls.devsetup.php';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Developer Environment Setup | Satya Portal Pack</title>
    </head>
    <body>
        <img src="<?php echo SPP_IMG_URI.SPPUS.'spp-logo.jpg'; ?>" alt="Satya Portal Pack Logo" height="125px" width="250px">
        <br /><hr />
        <br /><br />
        <div align="center" name="setupformdiv" id="setupformdiv">
            <?php
                $installform->startForm();
            ?>
            <table>
                <tr><td>Application Name</td><td><?php echo $appname->getHTML(); ?></td></tr>
                <tr><td>Database Type</td><td><?php echo $dbtype->getHTML(); ?></td></tr>
                <tr><td>Database Name</td><td><?php echo $dbname->getHTML(); ?></td></tr>
                <tr><td>User Name</td><td><?php echo $dbuname->getHTML(); ?></td></tr>
                <tr><td>Password</td><td><?php echo $dbpasswd->getHTML(); ?></td></tr>
                <tr><td align="center" colspan="2"><?php echo $formsubmit->getHTML(); ?></td></tr>
            </table>
            <?php
                $installform->endForm();
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
