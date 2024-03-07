<?php
/**
 * class SPP_HTML_Page
 *
 * Defines a HTML page in SPP.
 *
 * @author Satya Prakash Shukla
 */
class SPP_HTML_Page extends SPP_Object {
    protected static $jsincludelist=array();
    protected static $cssincludelist=array();
    protected static $formslist=array();
    protected static $elementslist=array();

    public static function addJsIncludeFile($fpath)
    {
        foreach(self::$jsincludelist as $fl)
        {
            if($fl==$fpath)
            {
                return false;
            }
        }
        self::$jsincludelist[]=$fpath;
        return true;
    }

    public static function addCssIncludeFile($fpath)
    {
        foreach(self::$cssincludelist as $fl)
        {
            if($fl==$fpath)
            {
                return false;
            }
        }
        self::$cssincludelist[]=$fpath;
        return true;
    }

    public static function addForm(SPP_Form $form)
    {
        foreach(self::$formslist as $fl)
        {
            if($fl==$form)
            {
                return false;
            }
        }
        self::$formslist[$form->getAttribute('id')]=$form;
        return true;
    }

    /**
     * Function processForms()
     * Process all the forms on this page.
     */

    public static function processForms()
    {
        if(array_key_exists('__spp_form', $_POST))
        {
            $callfunc=$_POST['__spp_form'].'_submitted';
            self::$formslist[$_POST['__spp_form']]->doValidation();
            if(function_exists($callfunc))
            {
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
                    $frm=new SPP_Form($form['name'],$form['action']);
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


    public static function readXMLFile($fl)
    {
        if(file_exists($fl))
        {
            $xml=simplexml_load_file($fl);
            SPP_Event::fireEvent('event_spp_process_xml_form', 'SPP_HTML_Page::processXMLForm', array('xml'=>$xml));
            //print_r($xml);
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function processXMLForm()
    {
            $xml=SPP_Event::getVar('xml');
            $arr=SPP_Utils::xml2phpArray($xml);
            //echo '<br><br>';
            //print_r($arr['forms']);
            if(array_key_exists('forms', $arr))
            foreach($arr['forms'] as $forms)
            {
                if(array_key_exists('form', $forms))
                foreach($forms['form'] as $form)
                {
                    $frm=new SPP_Form($form['name'],$form['action']);
                    //echo 'form '.$form['name'].' created';
                    //print_r($form);
                    if(array_key_exists('controls', $form))
                    foreach($form['controls'] as $controls)
                    {
                        if(array_key_exists('control',$controls))
                        foreach($controls['control'] as $control)
                        {
                            $cnt=self::createElementFromArray($control);
                            $frm->addElement($cnt);
                        }
                    }
                    if(class_exists('SPP_Validator'))
                    {
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
            }
    }

    private static function validationsFromArray(SPP_Form $form,array $arr)
    {
        $val='';
        //print_r($arr);
        if(array_key_exists('control',$arr))
        {
            $val=new $arr['type'](self::$elementslist[$arr['control']]);
        }
        elseif(array_key_exists('controls', $arr))
        {
            //$ctrls='';
            //print_r($arr['controls']);
            foreach($arr['controls'] as $controls)
            {
                //print_r($controls);
                foreach($controls['control'] as $control)
                {
                    $ctrls[]=self::$elementslist[$control['name']];
                }
            }
            //print_r($ctrls);
            $val=new $arr['type']($ctrls);
        }
        else
        {
            SPP_Error::triggerDevError('Error reading validations from array');
        }
        if(array_key_exists('message', $arr))
        {
            $form->addValidator($val, $arr['message']);
        }
        else
        {
            $form->addValidator($val);
        }
        if(array_key_exists('attach', $arr))
        {
            foreach($arr['attach'] as $attach)
            {
                $element=self::$elementslist[$attach['element']];
                $form->attachValidator($val, $element, $attach['event'], $attach['errorholder']);
            }
        }
    }


    private static function createElementFromArray($arr)
    {
        //print_r($arr);
        $elem=new $arr['type']($arr['name']);
        $elem->readFromArray($arr);
        return $elem;
    }



    public static function addElement(SPP_HTML_Element $ename)
    {
        foreach(self::$elementslist as $fl)
        {
            if($fl==$ename)
            {
                return false;
            }
        }
        self::$elementslist[$ename->getAttribute('id')]=$ename;
        SPP_Event::fireEvent('event_spp_core_dojo_inc', 'SPP_HTML_Page::event_dojo_included');
        return true;
    }

    public static function event_dojo_included()
    {
        self::addJsIncludeFile(SPP_DOJO_URI.SPP_US.'dojo/dojo.js');
    }

    public static function getElement($ename)
    {
        if(array_key_exists($ename, self::$elementslist))
        {
            return self::$elementslist[$ename];
        }
        else
        {
            return null;
        }
    }

    public static function addClass($ename,$cname)
    {
        $elem=self::$elementslist[$ename];
        $iclass=$elem->getAttribute('id');
        if(trim($iclass)=='')
        {
            $elem->setAttribute('class',$cname);
        }
        else
        {
            $elem->setAttribute('class',$iclass.' '.$cname);
        }
    }

    public static function getElementsList()
    {
        return self::$elementslist;
    }

    public static function includeJSFiles()
    {
        $jsi=self::$jsincludelist;
        SPP_Event::startEvent('event_spp_include_js_files', array('incfiles'=>&$jsi));
        self::$jsincludelist=$jsi;
        echo '<!-- Including Javascript files for Satya Portal Pack -->';
        foreach(self::$jsincludelist as $fl)
        {
            echo '<script src="'.$fl.'" type="text/JavaScript"></script>';
        }
        echo '<!-- Include ends -->';
        SPP_Event::endEvent('event_spp_include_js_files');
    }

    public static function includeCSSFiles()
    {
        SPP_Event::startEvent('event_spp_include_css_files', array('incfiles'=>&self::$cssincludelist));
        echo '<!-- Including CSS files for Satya Portal Pack -->';
        foreach(self::$cssincludelist as $fl)
        {
            echo '<link rel="stylesheet" href="'.$fl.'" />';
        }
        echo '<!-- Include ends -->';
        SPP_Event::endEvent('event_spp_include_css_files');
    }

}
?>