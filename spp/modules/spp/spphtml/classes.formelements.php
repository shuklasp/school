<?php
//require_once 'class.sppformelement.php';

class SPP_Form_Input extends SPP_Form_Element{
    public function  __construct($ename) {
        parent::__construct($ename);
        $this->isemptyflag=true;
        $this->tagname='input';
        $this->attrlist=array('accept','align','alt','checked','disabled','maxlength','name','readonly','size','src','type','value');
    }
}

class SPP_Form_Input_Text extends SPP_Form_Element{
    public function  __construct($ename) {
        parent::__construct($ename);
        $this->isemptyflag=true;
        $this->tagname='input';
        $this->attrlist=array('accept','align','alt','checked','disabled','maxlength','name','readonly','size','src','type','value');
        $this->setAttribute('type', 'text');
    }
}

class SPP_Form_Input_Password extends SPP_Form_Element{
    public function  __construct($ename) {
        parent::__construct($ename);
        $this->isemptyflag=true;
        $this->tagname='input';
        $this->attrlist=array('accept','align','alt','checked','disabled','maxlength','name','readonly','size','src','type','value');
        $this->setAttribute('type', 'password');
    }
}

class SPP_Form_Input_Submit extends SPP_Form_Element{
    public function  __construct($ename) {
        parent::__construct($ename);
        $this->isemptyflag=true;
        $this->tagname='input';
        $this->attrlist=array('accept','align','alt','checked','disabled','maxlength','name','readonly','size','src','type','value');
        $this->setAttribute('type', 'submit');
    }
}

class SPP_Form_Input_Button extends SPP_Form_Element{
    public function  __construct($ename) {
        parent::__construct($ename);
        $this->isemptyflag=true;
        $this->tagname='input';
        $this->attrlist=array('accept','align','alt','checked','disabled','maxlength','name','readonly','size','src','type','value');
        $this->setAttribute('type', 'button');
    }
}

class SPP_Form_TextArea extends SPP_Form_Element{
    public function  __construct($ename) {
        parent::__construct($ename);
        $this->isemptyflag=false;
        $this->tagname='textarea';
        $this->attrlist=array('cols','rows','disabled','name','readonly');
    }
}

class SPP_Form_Button extends SPP_Form_Element{
    public function  __construct($ename) {
        parent::__construct($ename);
        $this->isemptyflag=true;
        $this->tagname='button';
        $this->attrlist=array('disabled','name','type','value');
    }
}

class SPP_Form_Option extends SPP_Form_Element{
    private $opttext;

    public function  __construct($disptext,$optvalue,$ename='') {
        parent::__construct($ename);
        $this->isemptyflag=false;
        $this->tagname='option';
        $this->attrlist=array('disabled','label','selected','value');
        $this->opttext=$disptext;
        $this->setAttribute('value', $optvalue);
    }

    public function show()
    {
        parent::show();
        echo $this->opttext;
        self::endOne();
    }

    public function render()
    {
        $htm=parent::render();
        $htm.=$this->opttext;
        $htm.='</option>';
        return $htm;
    }

}

class SPP_Form_Select extends SPP_Form_Element{
    private $options;
    private $optkey=0;
    public function  __construct($ename) {
        parent::__construct($ename);
        $this->isemptyflag=false;
        $this->tagname='select';
        $this->attrlist=array('disabled','multiple','name','size');
        $this->addOption('Select', '', true);
    }

    public function addOption($disptext,$optvalue,$selected=false,$ename='',$optgroup='')
    {
        $this->options[$this->optkey++]=array(new SPP_Form_Option($disptext,$optvalue,$ename=''), $optgroup);
        if($selected)
        {
            $this->options[$this->optkey-1][0]->setAttribute('selected','true');
        }
    }

    public function readFromArray(array $arr)
    {
        parent::readFromArray($arr);
        if(array_key_exists('options',$arr))
        {
            foreach($arr['options'] as $options)
            {
                if(array_key_exists('option', $options))
                {
                    foreach($options['option'] as $option)
                    {
                        $disptext=(array_key_exists('text', $option))?$option['text']:'';
                        $optvalue=(array_key_exists('value', $option))?$option['value']:'';
                        $ename=(array_key_exists('name', $option))?$option['name']:'';
                        $selected=(array_key_exists('selected', $option))?$option['selected']:'';
                        $optgroup=(array_key_exists('optgroup', $option))?$option['optgroup']:'';
                        $this->addOption($disptext, $optvalue, $selected, $ename, $optgroup);
                    }
                }
            }
        }
    }

    public function getHTML()
    {
        $prevoptgroup='';
        $htm=parent::getHTML();
        foreach($this->options as $opt)
        {
            if($opt[1]!=$prevoptgroup)
            {
                if($prevoptgroup!='')
                {
                    $htm.= '</optgroup>';
                }
                if($opt[1]!='')
                {
                    $htm.= '<optgroup label="'.$opt[1].'">';
                }
            }
            $prevoptgroup=$opt[1];
            $htm.=$opt[0]->getHTML();
        }
        if($prevoptgroup!='')
        {
            $htm.= '</optgroup>';
        }
        $htm.='</select>';
        return $htm;
    }

    public function show()
    {
        $prevoptgroup='';
        parent::show();
        foreach($this->options as $opt)
        {
            if($opt[1]!=$prevoptgroup)
            {
                if($prevoptgroup!='')
                {
                    echo '</optgroup>';
                }
                if($opt[1]!='')
                {
                    echo '<optgroup label="'.$opt[1].'">';
                }
            }
            $prevoptgroup=$opt[1];
            $opt[0]->show();
        }
        if($prevoptgroup!='')
        {
            echo '</optgroup>';
        }
        $this->endOne();
    }
}

class SPP_Form_SQLDropDown extends SPP_Form_Select{
    public function  __construct($ename, $sql, $optdispfld, $optvalfld, $values=array(), $defval='', $optgrpfld='') {
        parent::__construct($ename);
        $db=new SPP_DB();
        $result=$db->execute_query($sql, $values);
        foreach($result as $res)
        {
            if($res[$optvalfld]==$defval)
            {
                parent::addOption($res[$optdispfld], $res[$optvalfld], true, $res[$optvalfld], $res[$optgrpfld]);
            }
            else
            {
                parent::addOption($res[$optdispfld], $res[$optvalfld], false, $res[$optvalfld], $res[$optgrpfld]);
            }
        }
    }
}

class SPP_Form_Input_Radio extends SPP_Form_Input{
    private $values=array();
    public function  __construct($ename,$val='',$checked=false) {
        parent::__construct($ename);
        if($val!='')
        {
            $this->addOption($val,$checked);
        }
    }

    public function addOption($optval,$checked=false)
    {
        $this->values[$optval]=$checked;
    }

    public function getHTML()
    {
        $htm='';
        foreach($this->values as $val=>$checked)
        {
            if($checked===true)
            {
                $htm.='<input type="radio" name="'.$this->getAttribute('name').'" id="'.$this->getAttribute('id').'" value="'.$val.'" checked="true" />';
            }
            else
            {
                $htm.='<input type="radio" name="'.$this->getAttribute('name').'" id="'.$this->getAttribute('id').'" value="'.$val.'" />';
            }
        }
        return $htm;
    }

    public function getElements()
    {
        $ele='';
        foreach($this->values as $val=>$checked)
        {
            if($checked===true)
            {
                $ele[$val]='<input type="radio" name="'.$this->getAttribute('name').'" id="'.$this->getAttribute('id').'" value="'.$val.'" checked="true" />';
            }
            else
            {
                $ele[$val]='<input type="radio" name="'.$this->getAttribute('name').'" id="'.$this->getAttribute('id').'" value="'.$val.'" />';
            }
        }
        return $ele;
    }
}

class SPP_Form_Input_Checkbox extends SPP_Form_Input{
    private $values=array();
    public function  __construct($ename,$val='',$checked=false) {
        parent::__construct($ename);
        if($val!='')
        {
            $this->addOption($val,$checked);
        }
    }

    public function addOption($optval,$checked=false)
    {
        $this->values[$optval]=$checked;
    }

    public function getHTML()
    {
        $htm='';
        foreach($this->values as $val=>$checked)
        {
            if($checked===true)
            {
                $htm.='<input type="checkbox" name="'.$this->getAttribute('name').'" id="'.$this->getAttribute('id').'" value="'.$val.'" checked="true" />';
            }
            else
            {
                $htm.='<input type="checkbox" name="'.$this->getAttribute('name').'" id="'.$this->getAttribute('id').'" value="'.$val.'" />';
            }
        }
        return $htm;
    }

    public function getElements()
    {
        $ele='';
        foreach($this->values as $val=>$checked)
        {
            if($checked===true)
            {
                $ele[$val]='<input type="checkbox" name="'.$this->getAttribute('name').'" id="'.$this->getAttribute('id').'" value="'.$val.'" checked="true" />';
            }
            else
            {
                $ele[$val]='<input type="checkbox" name="'.$this->getAttribute('name').'" id="'.$this->getAttribute('id').'" value="'.$val.'" />';
            }
        }
        return $ele;
    }
}


class SPP_Form_DateChooser extends SPP_Form_Input
{
    protected $dateattr=array();
    public function __construct($ename)
    {
        SPP_HTML_Page::addJsIncludeFile(SPP_JS_URI.SPP_US.'datechooser/datechooser.js');
        SPP_HTML_Page::addCssIncludeFile(SPP_JS_URI.SPP_US.'datechooser/datechooser.css');
        parent::__construct($ename);
        $this->setDateAttr('DateFormat', SPP_Config::get('defdateformat'));
        $this->updateClass();
    }

    public function setDateAttr($aname, $aval)
    {
        $this->dateattr[$aname]=$aval;
        $this->updateClass();
        return true;
    }

    public function getHTML()
    {
        $htm=parent::getHTML();
  /*      $htm.='<script type="text/JavaScript">
                function dateChooser'.$this->attributes['id'].'Selected(){
                    var dc=document.getElementById("'.$this->attributes['id'].'");
                    //dc.DateChooser.setEarliestDate(objDate);
                    //alert("Updating");
                    dc.DateChooser.updateFields();
   *  dc-onupdate=\'dateChooser'.$this->attributes['id'].'Selected\'
                }
            </script>';*/
        return $htm;
    }

    private function updateClass()
    {
        $cls='datechooser';
        foreach($this->dateattr as $arr=>$val)
        {
            switch($arr)
            {
                case 'DateFormat':
                    $cls.=' dc-dateformat=\''.$val.'\'';
                    break;
                case 'IconLink':
                    $cls.=' dc-iconlink=\''.$val.'\'';
                    break;
                case 'TextLink':
                    $cls.=' dc-textlink=\''.$val.'\'';
                    break;
                case 'OffsetX':
                    $cls.=' dc-offset-x=\''.$val.'\'';
                    break;
                case 'OffsetY':
                    $cls.=' dc-offset-y=\''.$val.'\'';
                    break;
                case 'CloseTime':
                    $cls.=' dc-closetime=\''.$val.'\'';
                    break;
                case 'OnUpdate':
                    $cls.=' dc-onupdate=\''.$val.'\'';
                    break;
                case 'StartDate':
                    $cls.=' dc-startdate=\''.$val.'\'';
                    break;
                case 'EarliestDate':
                    $cls.=' dc-earliestdate=\''.$val.'\'';
                    break;
                case 'LatestDate':
                    $cls.=' dc-latestdate=\''.$val.'\'';
                    break;
                case 'AllowedDays':
                    $cls.=' dc-alloweddays=\''.$val.'\'';
                    break;
                case 'WeekStartDay':
                    $cls.=' dc-weekstartday=\''.$val.'\'';
                    break;
                case 'LinkPosition':
                    $cls.=' dc-linkposition=\''.$val.'\'';
                    break;
            }
        }
        $this->attributes['class']=$cls;
    }

}