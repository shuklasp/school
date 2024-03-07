<?php
/*
 * File index.php
 * Home page for administration of SPP
 */

?>
<?php
require_once '../sppinit.php';
require_once 'controller.modmain.php';
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
        <br />
        <div id="mainmenu"><a href="modmain.php">Modules Configuration</a></div>
        <br /><br />
        <div align="center" name="setupformdiv" id="setupformdiv">
            <form name="modform" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
            <table>
                <?php
                    foreach($modarray as $modgroup=>$modspec)
                    {
                        echo '<tr>';
                        echo '<td colspan="3"><strong>'.$modgroup.'</strong><hr /><td><tr></tr>';
                        echo '<tr><th>Active</th><th>Name</th><th>Description</th></tr>';
                        foreach ($modspec as $mname=>$spec) {
                            echo '<tr>';
                            echo '<td>'.$spec['chbox'].'</td>';
                            echo '<td>'.$spec['pubname'].'</td>';
                            echo '<td>'.$spec['pubdesc'].'</td>';
                            echo '</tr>';
                        }
                        echo '<td colspan="3"><hr /></td>';
                        echo '</tr>';
                    }
                ?>
            </table>
                <input type="submit">
            </form>
        </div>
        <br /> <br />
        <hr>
        <div align="center">Powered by <a href="http://spp.vshiksha.com" title="Satya Portal Pack Website" target="_blank">Satya Portal Pack</a>.</div>
    </body>
</html>
