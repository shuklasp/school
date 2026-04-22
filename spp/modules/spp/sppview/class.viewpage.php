<?php

namespace SPPMod\SPPView;

require_once __DIR__ . '/class.viewtag.php';
require_once __DIR__ . '/class.sppformelement.php';
require_once __DIR__ . '/class.viewform.php';
require_once __DIR__ . '/formelements/classes.formelements.php';

use SPP\SPPException;
use SPP\SPPGlobal;
use \SPPMod\SPPView\Pages;
use \SPPMod\SPPView\SPPViewForm;

\SPP\SPPEvent::registerEvent('event_spp_include_css_files');
\SPP\SPPEvent::registerEvent('event_spp_include_js_files');
\SPP\SPPEvent::registerEvent('event_spp_process_xml_form');
\SPP\SPPEvent::registerEvent('event_spp_process_xml_form_element');
\SPP\SPPEvent::registerEvent('event_spp_process_xml_form_validation');

/**
 * class ViewPage
 *
 * Defines a HTML page in SPP.
 *
 * @author Satya Prakash Shukla
 */
class ViewPage extends \SPP\SPPObject
{
    protected static $pageid;
    protected static $jsincludelist = array();
    protected static $cssincludelist = array();
    protected static $formslist = array();
    protected static $elementslist = array();
    protected static $pagetitle;
    protected static $pagedescription;
    protected static $pagekeywords;
    protected static $pageauthor;
    protected static $pagecontent;
    protected static $pageheader;
    protected static $pagefooter;
    protected static $pagehead;
    protected static $pagebody;
    protected static $pagemeta;
    protected static $xml;
    protected static $validators = array();

    /**
     * Function setPageId($id)
     * Sets the page id.
     *
     * @param string $id
     * @return void
     */
    public static function setPageId($id)
    {
        self::$pageid = $id;
    }

    public static function showPage($page = null, array $options = [])
    {
        $q = isset($_GET['q']) ? $_GET['q'] : null;
        $pageData = array();
        $src = Pages::getDefault('pagedir');
        
        if ($q == null) {
            $def = Pages::getDefault('home');
            $pageData = Pages::getPage($def);
        } else {
            $pageData = Pages::getPage();
        }

        // Configuration defaults
        $doAugment = $options['augment'] ?? (bool)\SPP\Module::getConfig('auto_page_augmentation', 'sppview');
        $doInjectJs = $options['inject_js'] ?? (bool)\SPP\Module::getConfig('auto_js_injection', 'sppview');

        if ($pageData['special'] == 1) {
            include(SPP_APP_DIR . SPP_US . trim($pageData['url']));
            return true;
        }

        SPPGlobal::set('page', $pageData);
        SPPGlobal::set('url', $pageData['url']);
        SPPGlobal::set('params', $pageData['params']);
        SPPGlobal::set('q', $q);
        SPPGlobal::set('numparams', count($pageData['params']));
        
        $filename = SPP_APP_DIR . $src . SPP_US . $pageData['url'];
        
        if (file_exists($filename) && is_file($filename)) {
            
            if ($doAugment) {
                ob_start();
            }

            include($filename);

            if ($doAugment) {
                $html = ob_get_clean();
                $appName = \SPP\Scheduler::getContext();

                // 1. Scan for <php-comp name="X" ... /> tags
                $html = preg_replace_callback('/<php-comp\s+name="([^"]+)"([^>]*)\/?>/i', function($matches) use ($appName) {
                    $compName = $matches[1];
                    $attrs = $matches[2];
                    
                    // Parse attributes into a state object
                    $state = [];
                    if (preg_match_all('/([a-zA-Z0-9_-]+)="([^"]+)"/', $attrs, $attrMatches)) {
                        for ($i = 0; $i < count($attrMatches[0]); $i++) {
                            $state[$attrMatches[1][$i]] = $attrMatches[2][$i];
                        }
                    }

                    // Resolve JS Inclusion
                    self::resolveTieredJS($appName, $compName);

                    $jsonState = htmlspecialchars(json_encode($state), ENT_QUOTES, 'UTF-8');
                    return "<div data-spp-component=\"{$compName}\" data-state='{$jsonState}'></div>";
                }, $html);

                if ($doInjectJs) {
                    self::addJsIncludeFile('res/spp/js/spp-router.js');
                    self::addJsIncludeFile('res/spp/js/sppvalidations.js');
                    self::addJsIncludeFile('res/spp/js/spp-autoinit.js');
                }
                
                // 4. Inject Debug Bar (Phase 5 Evolution)
                if (\SPP\Module::isEnabled('sppdebug') || (defined('SPP_DEBUG') && SPP_DEBUG)) {
                    $debugData = \SPP\Core\Debug::getData();
                    $debugJson = htmlspecialchars(json_encode($debugData), ENT_QUOTES, 'UTF-8');
                    $html .= "<div id='spp-debug-bar' data-metrics='{$debugJson}'></div>";
                    self::addJsIncludeFile('res/spp/js/spp-debug.js');
                    self::addCssIncludeFile('res/spp/css/spp-debug.css');
                }

                // Pass the accumulated script list to the augmentor for internal DOM injection
                echo FormAugmentor::augment($html, self::$jsincludelist);
                
                self::includeJqueryDynamic();
                self::includeCSSFilesDynamic();
                return true;
            }

            self::includeJqueryDynamic();
            // ... rest of method
        }
    }

    /**
     * Tiered JS Resolution Logic:
     * 1. Static: Check if pre-built JS exists.
     * 2. Fallback: Use dynamic generator route via SPPAjax.
     */
    public static function resolveTieredJS(string $appName, string $compName): void
    {
        $staticPath = "res/apps/{$appName}/generated/{$compName}.js";
        if (file_exists(SPP_APP_DIR . '/' . $staticPath)) {
            self::addJsIncludeFile($staticPath);
        } else {
            // Priority 3: Fallback - Dynamic generation route
            self::addJsIncludeFile("?__js_comp={$compName}");
        }
    }


    public static function includeCSSFilesDynamic()
    {
        foreach (self::$cssincludelist as $cssfile) {
            self::includeCSSDynamic($cssfile);
        }
    }

    public static function includeJSDynamic($jsfile)
    {
        echo '<script type="text/javascript" src="' . htmlspecialchars((string) $jsfile, ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
    }

    public static function includeJSFilesDynamic()
    {
        foreach (self::$jsincludelist as $jsfile) {
            self::includeJSDynamic($jsfile);
        }
    }

    public static function includeCSSDynamic($cssfile)
    {
        echo '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars((string) $cssfile, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
    }

    public static function includeJqueryDynamic()
    {
        // Using document.write synchronously guarantees blocking execution before dependent scripts load
        echo '<script type="text/javascript">if(typeof jQuery === "undefined") { document.write(\'<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"><\/script>\'); }</script>' . "\n";
    }

    public static function render($page = null)
    {
        $url = ($page != null) ? Pages::getPage($page)['url'] : null;
        $pageid = self::getPageId();
        echo self::getPageHeader();
        echo self::getPageHead();
        echo self::getPageMeta();
        echo self::getPageBody();
        echo self::getPageFooter();
    }

    /**
     * Function getPageId()
     * Gets the page id.
     *
     * @return string
     */
    public static function getPageId()
    {
        return self::$pageid;
    }

    /**
     * Function addValidator($validator)
     * Adds a validator to the list of validators.
     *
     * @param object $validator
     * @return void
     */
    public static function addValidator($validator)
    {
        self::$validators[] = $validator;
    }


    /**
     * Function getValidators()
     * Gets the list of validators.
     *
     * @return array
     */
    public static function getValidators()
    {
        return self::$validators;
    }


    /***** Getters and Setters *****/
    /**
     * Function setPageTitle($title)
     * Sets the title of the page.
     *
     * @param string $title
     * @return void
     */
    public static function setPageTitle($title)
    {
        self::$pagetitle = $title;
    }

    /**
     * Function getPageTitle()
     * Gets the title of the page.
     *
     * @return string
     */
    public static function getPageTitle()
    {
        return self::$pagetitle;
    }

    /**
     * Function setPageDescription($desc)
     * Sets the description of the page.
     *
     * @param string $desc
     * @return void
     */
    public static function setPageDescription($desc)
    {
        self::$pagedescription = $desc;
    }

    /**
     * Function getPageDescription()
     * Gets the description of the page.
     *
     * @return string
     */
    public static function getPageDescription()
    {
        return self::$pagedescription;
    }

    /**
     * Function setPageKeywords($keywords)
     * Sets the keywords of the page.
     *
     * @param string $keywords
     * @return void
     */
    public static function setPageKeywords($keywords)
    {
        self::$pagekeywords = $keywords;
    }

    /**
     * Function getPageKeywords()
     * Gets the keywords of the page.
     *
     * @return string
     */
    public static function getPageKeywords()
    {
        return self::$pagekeywords;
    }

    /**
     * Function setPageAuthor($author)
     * Sets the author of the page.
     *
     * @param string $author
     * @return void
     */
    public static function setPageAuthor($author)
    {
        self::$pageauthor = $author;
    }

    /**
     * Function getPageAuthor()
     * Gets the author of the page.
     *
     * @return string
     */
    public static function getPageAuthor()
    {
        return self::$pageauthor;
    }

    /**
     * Function setPageContent($content)
     * Sets the content of the page.
     *
     * @param string $content
     * @return void
     */
    public static function setPageContent($content)
    {
        self::$pagecontent = $content;
    }

    /**
     * Function getPageContent()
     * Gets the content of the page.
     *
     * @return string
     */
    public static function getPageContent()
    {
        return self::$pagecontent;
    }

    /**
     * Function setPageHeader($header)
     * Sets the header of the page.
     *
     * @param string $header
     * @return void
     */
    public static function setPageHeader($header)
    {
        self::$pageheader = $header;
    }

    /**
     * Function getPageHeader()
     * Gets the header of the page.
     *
     * @return string
     */
    public static function getPageHeader()
    {
        return self::$pageheader;
    }

    /**
     * Function setPageFooter($footer)
     * Sets the footer of the page.
     *
     * @param string $footer
     * @return void
     */
    public static function setPageFooter($footer)
    {
        self::$pagefooter = $footer;
    }

    /**
     * Function getPageFooter()
     * Gets the footer of the page.
     *
     * @return string
     */
    public static function getPageFooter()
    {
        return self::$pagefooter;
    }

    /**
     * Function setPageHead($head)
     * Sets the head of the page.
     *
     * @param string $head
     * @return void
     */
    public static function setPageHead($head)
    {
        self::$pagehead = $head;
    }

    /**
     * Function getPageHead()
     * Gets the head of the page.
     *
     * @return string
     */
    public static function getPageHead()
    {
        return self::$pagehead;
    }

    /**
     * Function setPageBody($body)
     * Sets the body of the page.
     *
     * @param string $body
     * @return void
     */
    public static function setPageBody($body)
    {
        self::$pagebody = $body;
    }

    /**
     * Function getPageBody()
     * Gets the body of the page.
     *
     * @return string
     */
    public static function getPageBody()
    {
        return self::$pagebody;
    }

    /**
     * Function setPageMeta($meta)
     * Sets the meta of the page.
     *
     * @param string $meta
     * @return void
     */
    public static function setPageMeta($meta)
    {
        self::$pagemeta = $meta;
    }

    /**
     * Function getPageMeta()
     * Gets the meta of the page.
     *
     * @return string
     */
    public static function getPageMeta()
    {
        return self::$pagemeta;
    }

    public static function getXML()
    {
        return self::$xml;
    }

    /**
     * Function setXML($xml)
     * Sets the XML of the page.
     *
     * @param string $xml
     * @return void
     */
    public static function setXML($xml)
    {
        self::$xml = $xml;
    }

    /**
     * Function getJsIncludeList()
     * Gets the list of js includes.
     *
     * @return array
     */
    public static function getJsIncludeList()
    {
        return self::$jsincludelist;
    }

    /**
     * Function getCssIncludeList()
     * Gets the list of css includes.
     *
     * @return array
     */
    public static function getCssIncludeList()
    {
        return self::$cssincludelist;
    }

    /**
     * Function getFormsList()
     * Gets the list of forms.
     *
     * @return array
     */
    public static function getFormsList()
    {
        return self::$formslist;
    }

    /**
     * Function addJsIncludeFile($fpath)
     * Adds a js include file to the list.
     *
     * @param string $fpath
     * @return bool
     */
    public static function addJsIncludeFile($fpath)
    {
        foreach (self::$jsincludelist as $fl) {
            if ($fl == $fpath) {
                return false;
            }
        }
        self::$jsincludelist[] = $fpath;
        return true;
    }

    /**
     * Function addCssIncludeFile($fpath)
     * Adds a css include file to the list.
     *
     * @param string $fpath
     * @return bool
     */
    public static function addCssIncludeFile($fpath)
    {
        foreach (self::$cssincludelist as $fl) {
            if ($fl == $fpath) {
                return false;
            }
        }
        self::$cssincludelist[] = $fpath;
        return true;
    }


    /**
     * Function addForm(SPPViewForm $form)
     * Adds a form to the list.
     *
     * @param ViewForm $form
     * @return bool
     */
    public static function addForm(ViewForm $form)
    {
        foreach (self::$formslist as $fl) {
            if ($fl == $form) {
                return false;
            }
        }
        self::$formslist[$form->getAttribute('id')] = $form;
        return true;
    }

    /**
     * Function processForms()
     * Process all the forms on this page.
     */

    public static function processForms()
    {
        if (array_key_exists('__spp_form', $_POST)) {
            $formId = $_POST['__spp_form'];
            if (!array_key_exists($formId, self::$formslist)) {
                throw new SPPException('Submitted form ID "' . htmlspecialchars($formId, ENT_QUOTES, 'UTF-8') . '" is not registered on this page.');
            }
            $callfunc = $formId . '_submitted';
            self::$formslist[$formId]->doValidation();
            if (function_exists($callfunc)) {
                $callfunc();
            }
        }
    }

    /*public static function readXMLFile($fl)
    {
        if(file_exists($fl))
        {
            $xml=simplexml_load_file($fl);
            $arr=(array)$xml;
            //print_r($xml);
            if(array_key_exists('forms', $arr))
            foreach($arr as $forms)
            {
                $forms=(array)$forms;
                if(array_key_exists('form', $forms))
                $form_array=$forms['form'];
                print_r($form_array);
                foreach($form_array as $form)
                {
                    $form=(array)$form;
                    //print_r($form);
                    $frm=new SPPViewForm($form['name'],$form['action']);
                    //echo 'form '.$form['name'].' created';
                    if(array_key_exists('controls', $form))
                    //$controls_array=
                    foreach($form['controls'] as $controls)
                    {
                        //$controls=(array)$controls;
                        //print_r($controls);
                        if(array_key_exists('control',$controls))
                        foreach($controls['control'] as $control)
                        {
                            $cnt=self::createElementFromArray($control);
                            $frm->addElement($cnt);
                        }
                    }
                    if(array_key_exists('validations', $form))
                    foreach($form['validations'] as $validations)
                    {
                        if(array_key_exists('validation', $validations))
                        foreach($validations['validation'] as $validation)
                        {
                            self::validationsFromArray($frm, $validation);
                        }
                    }
                }
            }
            return true;
        }
        else
        {
            return false;
        }
    }*/


    /**
     * Function readXMLFile($fl)
     * Reads the XML file and creates the forms.
     *
     * @param string $fl
     * @return bool
     */
    public static function readXMLFile($fl)
    {
        if (file_exists($fl)) {
            self::$xml = simplexml_load_file($fl);
            //\SPP\SPPEvent::fireEvent('event_spp_process_xml_form', 'SPP_HTML_Page::processXMLForm', array('xml'=>$xml));
            //print_r($xml);
            return true;
        } else {
            return false;
        }
    }


    /**
     * Function processXMLForm()
     * Processes the XML file and creates the forms.
     *
     */
            /**
     * Helper to ensure an item is an array of items for iteration.
     */
    private static function wrapArray($item): array
    {
        if (!is_array($item)) return [];
        if (isset($item[0])) return $item;
        return [$item];
    }

    public static function processXMLForm()
    {
        $xml = self::$xml;
        $arr = \SPP\SPPUtils::xml2phpArray($xml);
        
        if (!isset($arr['form'])) {
            return;
        }

        $forms = self::wrapArray($arr['form']);

        foreach ($forms as $form) {
            $fname = $form['name'] ?? 'unnamed_form';
            $faction = $form['action'] ?? '';
            $fid = $form['id'] ?? null;
            $fmethod = $form['method'] ?? 'post';

            $frm = new ViewForm($fname, $fmethod, $faction, $fid);
            self::$formslist[$fname] = $frm;

            if (isset($form['controls'])) {
                $controlsBlocks = self::wrapArray($form['controls']);
                foreach ($controlsBlocks as $cb) {
                    if (isset($cb['control'])) {
                        $controls = self::wrapArray($cb['control']);
                        foreach ($controls as $control) {
                            $cnt = self::createElementFromArray($control);
                            $frm->addElement($cnt);
                        }
                    }
                }
            }
            
            if (class_exists('\SPPMod\SPPView\ViewValidator')) {
                if (isset($form['validations'])) {
                    $validationsBlocks = self::wrapArray($form['validations']);
                    foreach ($validationsBlocks as $vb) {
                        if (isset($vb['validation'])) {
                            $validations = self::wrapArray($vb['validation']);
                            foreach ($validations as $validation) {
                                self::validationsFromArray($frm, $validation);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Function validationsFromArray($form, $arr)
     * Creates the validations from the array.
     *
     * @param ViewForm $form
     * @param array $arr

     */
    private static function validationsFromArray(ViewForm $form, array $arr)
    {
        $val = '';
        //print_r($arr);
        if (array_key_exists('control', $arr)) {
            $val = new $arr['type'](self::$elementslist[$arr['control']]);
        } elseif (array_key_exists('controls', $arr)) {
            //$ctrls='';
            //print_r($arr['controls']);
            foreach ($arr['controls'] as $controls) {
                //print_r($controls);
                foreach ($controls['control'] as $control) {
                    $ctrls[] = self::$elementslist[$control['name']];
                }
            }
            //print_r($ctrls);
            $val = new $arr['type']($ctrls);
        } else {
            \SPP\SPPError::triggerDevError('Error reading validations from array');
        }
        if (array_key_exists('message', $arr)) {
            $form->addValidator($val, $arr['message']);
        } else {
            $form->addValidator($val);
        }
        if (array_key_exists('attach', $arr)) {
            foreach ($arr['attach'] as $attach) {
                $element = self::$elementslist[$attach['element']];
                $form->attachValidator($val, $element, $attach['event'], $attach['errorholder']);
            }
        }
    }



    /**
     * Function createElementFromArray($arr)
     * Creates the element from the array.
     *
     * @param array $arr
     * @return \SPPMod\SPPView\ViewTag
     */
    private static function createElementFromArray($arr): ViewTag
    {
        // Ensure form elements are loaded
        require_once __DIR__ . '/class.sppformelement.php';
        require_once __DIR__ . '/formelements/classes.formelements.php';

        $type = $arr['type'];
        // Prepend namespace if not absolute
        if (strpos($type, '\\') !== 0 && strpos($type, 'SPPMod\\SPPView\\') !== 0) {
            $type = __NAMESPACE__ . '\\' . $type;
        }
        
        if (!class_exists($type)) {
            throw new \SPP\SPPException("Form element class '{$type}' not found.");
        }

        $elem = new $type($arr['name']);
        $elem->readFromArray($arr);
        return $elem;
    }


    /**
     * Function addElement($ename)
     * Adds the element to the list.
     *
     * @param \SPPMod\SPPView\ViewTag $ename
     * @return bool
     */
    public static function addElement(\SPPMod\SPPView\ViewTag $ename)
    {
        foreach (self::$elementslist as $fl) {
            if ($fl == $ename) {
                return false;
            }
        }
        self::$elementslist[$ename->getAttribute('id')] = $ename;
        //\SPP\SPPEvent::fireEvent('event_spp_core_dojo_inc', 'SPP_HTML_Page::event_dojo_included');
        return true;
    }

    /*     public static function event_dojo_included()
        {
            self::addJsIncludeFile(SPP_DOJO_URI . SPP_US . 'dojo/dojo.js');
        }
     */
    /**
     * Function getElement($ename)
     * Returns the element from the list.
     *
     * @param string $ename
     * @return mixed
     */
    public static function getElement($ename)
    {
        if (array_key_exists($ename, self::$elementslist)) {
            return self::$elementslist[$ename];
        } else {
            return null;
        }
    }

    /**
     * Function addClass($ename, $cname)
     * Adds the class to the element.
     *
     * @param string $ename
     * @param string $cname
     * @return void
     */
    public static function addClass($ename, $cname)
    {
        if (!isset(self::$elementslist[$ename])) {
            return;
        }
        $elem = self::$elementslist[$ename];
        $iclass = (string) $elem->getAttribute('class');
        if (trim($iclass) == '') {
            $elem->setAttribute('class', $cname);
        } else {
            $elem->setAttribute('class', $iclass . ' ' . $cname);
        }
    }

    /**
     * Function getElementsList()
     * Returns the list of elements.
     *
     * @return array
     */
    public static function getElementsList()
    {
        return self::$elementslist;
    }

    /**
     * Function includeJSFiles()
     * Includes the javascript files.
     *
     * @return void
     */
    public static function includeJSFiles()
    {
        $jsi = self::$jsincludelist;
        \SPP\SPPEvent::startEvent('event_spp_include_js_files');
        self::$jsincludelist = $jsi;
        echo '<!-- Including Javascript files for Satya Portal Pack -->';
        foreach (self::$jsincludelist as $fl) {
            echo '<script src="' . htmlspecialchars((string) $fl, ENT_QUOTES, 'UTF-8') . '" type="text/JavaScript"></script>';
        }
        echo '<!-- Include ends -->';
        \SPP\SPPEvent::endEvent('event_spp_include_js_files');
    }


    /**
     * Function includeCSSFiles()
     * Includes the CSS files.
     *
     * @return void
     */
    public static function includeCSSFiles()
    {
        \SPP\SPPEvent::startEvent('event_spp_include_css_files');
        echo '<!-- Including CSS files for Satya Portal Pack -->';
        foreach (self::$cssincludelist as $fl) {
            echo '<link rel="stylesheet" href="' . htmlspecialchars((string) $fl, ENT_QUOTES, 'UTF-8') . '" />';
        }
        echo '<!-- Include ends -->';
        \SPP\SPPEvent::endEvent('event_spp_include_css_files');
    }

    /**
     * Function redirect($page, $params = [])
     * Redirects to an internal SPP route or an external URL.
     *
     * @param string $page   The page name (from pages.yml) or a full destination URL.
     * @param array  $params Optional associative array of query parameters.
     */
    public static function redirect($page, $params = [])
    {
        // 1. Determine if it's an internal route or external URL
        $url = $page;
        if (!preg_match('/^(http|https|ftp):\/\//i', (string)$page)) {
            // Internal SPP routing uses the 'q' parameter via index.php
            $url = '?q=' . urlencode((string)$page);
        }

        // 2. Append additional parameters if provided
        if (!empty($params)) {
            $query = http_build_query($params);
            $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
        }

        // 3. Perform the redirect
        if (!headers_sent()) {
            // Standard HTTP redirect
            header('Location: ' . $url);
            exit;
        } else {
            // Fallback for when output has already started (JavaScript/Meta Refresh)
            echo '<script type="text/javascript">window.location.href="' . addslashes((string)$url) . '";</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars((string)$url) . '" /></noscript>';
            exit;
        }
    }

    /**
     * Function getForm($id)
     * Returns the form from the list.
     *
     * @param string $id
     * @return mixed
     */
    public static function getForm($id)
    {
        if (array_key_exists($id, self::$formslist)) {
            return self::$formslist[$id];
        } else {
            return null;
        }
    }
}
