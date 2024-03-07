<?php
/* 
 * File dojoinit.php
 * Initiates the dojo environment.
 */

require_once 'class.sppdojo.php';

SPP_Dojo::init();
//SPP_Dojo::setSource('spp');
SPP_Dojo::setTheme('tundra');
SPP_Dojo::setConfig('parseOnLoad', 'true');
SPP_Dojo::setConfig('isDebug', 'true');

function spp_dojo_include_js_files()
{
    SPP_Dojo::setSource('google');
    SPP_Dojo::includeDojoJS();
}

function spp_dojo_include_css_files()
{
    SPP_Dojo::includeDojoStyle();
}
?>