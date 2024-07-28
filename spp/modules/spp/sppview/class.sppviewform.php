<?php

namespace SPPMod\SPPView;

use SPP\Exceptions\UnknownPropertyException as UnknownPropertyException;
use SPP\Exceptions\VarNotFoundException as VarNotFoundException;
/*require_once 'sppsystemexceptions.php';
require_once 'class.spphtmlelement.php';
require_once 'classes.sppvalidators.php';*/

/**
 * class Form
 * Handles a form in system.
 *
 * @author Satya Prakash Shukla
 */
class SPPViewForm extends \SPPMod\SPPView\ViewTag {
    private $method;
    private $action;
    private $onsubmit;
    private $elements=array();
    private $name;
    private $id;
    private $globalset;
    private $validators=array();
    private static $valstatus=true;

    public function  __construct($ename,$method='post',$act='') {
        parent::__construct('form',$ename);
        $this->isemptyflag=false;
        $this->attrlist=array('action','accept','accept-charset','enctype','method','name','target');
        $this->eventattrlist[]='onsubmit';
        $this->eventattrlist[]='onreset';
        ViewPage::addForm($this);
        if($act=='')
        {
            $this->attributes['action']=$_SERVER['PHP_SELF'];
        }
        else
        {
            $this->attributes['action']=$act;
        }
        /*if($met=='')
        {
            $this->attributes['method']='post';
        }
        else
        {
            $this->attributes['method']=$met;
        }*/
        if($method=='post' || $method=='get' || $method='put' || $method='delete')
        {
            $this->attributes['method']=$method;
        }
        else
        {
            throw new \SPP\SPPException('Invalid method '.$method.' declared for form '.$this->getAttribute('name');
        }
        $this->globalset=array();
    }

    public function setMethod($method)
    {
        $this->attributes['method']=$method;
    }

    public function setAction($action)
    {
        $this->attributes['action']=$action;
    }

    public function setOnsubmit($onsubmit){
        $this->attributes['onsubmit']=$onsubmit;
    }


    public function addValidator(SPP_Validator $val,$msg='')
    {
        if($msg!='')
        {
            $val->setMessage($msg);
        }
        $this->validators[]=$val;
    }

    public function attachValidator(SPP_Validator $val, \SPPMod\SPPView\ViewTag $elem, $event, $errhold, $msg='')
    {
        $val->setErrorHolder($errhold);
        $val->attachTo($elem, $event, $msg);
    }

    public function doValidation()
    {
        $res=true;
        foreach($this->validators as $val)
        {
            $res=$val->validate();
            if(self::$valstatus==true)
            {
                self::$valstatus=$res;
            }
        }
    }

    public static function isValidated()
    {
        return self::$valstatus;
    }


    /**
     * Function addElement()
     * Adds an element to the form.
     *
     * @param mixed $elem
     */

    public function addElement(\SPPMod\SPPView\ViewTag $elem)
    {
        $ename=$elem->getAttribute('id');
        if(array_key_exists($ename, $_POST))
        {
            $elem->setAttribute('value', $_POST[$ename]);
        }
        $this->elements[$ename]=$elem;
    }

    public function startForm()
    {
        echo parent::getHTML();
        echo '<input type="hidden" name="__spp_form" id="__spp_form" value="'.$this->getAttribute('name').'" />';
    }

    public function endForm()
    {
        echo '</form>';
    }

    /**
     * Function setFormGlobal()
     * @param string $gvar
     * @param mixed $gval
     * @return mixed
     */
    public function setFormGlobal($gvar,$gval)
    {
        return $this->globalset[$gvar]=$gval;
    }

    /**
     * Function getFormGlobal()
     * @param string $gvar
     * @return mixed
     */
    public function getFormGlobal($gvar)
    {
        if(array_key_exists($gvar,$this->globalset))
        {
            return $this->globalset[$gvar];
        }
        else
        {
            throw new VarNotFoundException('Variable '.$gvar.' not found in form '.$this->getAttribute('name').' in class '.get_class($this).'. Please check your code ');
        }
    }

    /**
     * Function get
     * Gets values of various properties.
     *
     * @param mixed $propname
     * @return mixed
     *
     * Defined Properties:
     * ------------------
     * name
     * id
     * action
     * method
     * element
     * 
     *
     */
    public function get($propname)
    {
        switch($propname)
        {
            case 'name':
                return $this->name;
            case 'id':
                return $this->id;
            case 'action':
                return $this->action;
            case 'method':
                return $this->method;
            case 'element':
                return $this->elements;
            case 'onsubmit':
                return $this->onsubmit;
            case 'validators':
                return $this->validators;
            default:
                throw new UnknownPropertyException('Unknown property '.$propname.' in form');
        }
    }

    /**
     * Function set()
     * Sets the value of a property.
     *
     * @param mixed $propname
     * @param mixed $propval
     * @return mixed
     *
     * Defined Properties:
     * ------------------
     *
     * name
     * id
     * action
     * method
     * onsubmit
     *
     */
    public function set($propname,$propval)
    {
        switch($propname)
        {
            case 'name':
                return $this->name=$propval;
                break;
            case 'id':
                return $this->id=$propval;
                break;
            case 'method':
                return $this->method=$propval;
                break;
            case 'action':
                return $this->action=$propval;
                break;
            case 'onsubmit':
                return $this->onsubmit=$propval;
                break;
            default:
                throw new UnknownPropertyException('Unknown property '.$propname.' in form');
                break;
        }
    }
}
