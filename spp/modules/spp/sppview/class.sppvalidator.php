<?php

namespace SPPMod\SPPView;

/**
 * class SPPValidator
 * Defines a field validator.
 *
 * @author Satya Prakash Shukla
 */
/* require_once 'class.sppbase.php';
 require_once 'sppconstants.php';*/
 
abstract class SPPValidator extends \SPP\SPPObject {
    protected static $included=false;
    protected $callback;
    protected $jsfunc,$applicabletags=array(),$errorholder,$msg='';
    protected $attachedto=array();

    public function __construct(mixed $callback, $errorholder, $msg, $jsfunc)
    {
        $this->errorholder=$errorholder;
        $this->msg=$msg;
        $this->jsfunc=$jsfunc;
        if(!self::$included)
        {
            self::$included=true;
            ViewPage::addCssIncludeFile(SPP_CSS_URI . SPP_US . 'sppview/sppvalidations.css');
            ViewPage::addJsIncludeFile(SPP_JS_URI . SPP_US . 'sppview/sppvalidations.js');
        }
    }

    public function setErrorHolder($hld)
    {
        $this->errorholder=$hld;
        if(sizeof($this->attachedto)>0)
        {
            foreach($this->attachedto as $elms)
            {
                $elms['element']->setAttribute($elms['event'],$this->getJsFunction());
            }
        }
    }

    public function setMessage($msg)
    {
        $this->msg=$msg;
    }

    public abstract function getJsFunction();
    /*{
        $fn=$this->jsfunc.'('.$this->errorholder.','.$this->msg.', new Array()';
        foreach($this->tagids as $tag)
        {
            $fn.=','.$tag;
        }
        $fn.=')';
    }*/

    public abstract function validate();

    public function attachTo(\SPPMod\SPPView\ViewTag $elem,$event,$msg='')
    {
        if($msg!='')
        {
            $this->msg=$msg;
        }
        $elem->setAttribute($event, $this->getJsFunction());
        $this->attachedto[$elem->getAttribute('id')]['element']=$elem;
        $this->attachedto[$elem->getAttribute('id')]['event']=$event;
    }
}
