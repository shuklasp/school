<?php
/**
 * file class.spphtml.php
 * Defines the SPP_HTML_Element class.
 */
 
/**
 * class SPP_HTML_Element
 * Represents a HTML element.
 *
 * @author Satya Prakash Shukla
 */
class SPP_HTML_Element extends SPP_Object {
    protected $tagname;
    protected $isemptyflag=false;
    protected $matter_text;
    protected $attributes=array();
    protected $attrlist=array();
    protected $stdattrlist=array();
    protected $eventattrlist=array();
    public static $started_tags = '';

    /**
     * Constructor
     *
     * @param string $ename
     */
    public function  __construct($ename) {
        $this->attributes['name']=$ename;
        $this->attributes['id']=$ename;
        $this->stdattrlist=array('class','id','style','title','dir','lang','xml:lang','accesskey','tabindex');
        $this->eventattrlist=array('onkeydown','onkeypress','onkeyup','onclick','ondblclick','onmousedown','onmousemove','onmouseover','onmouseout','onmouseup');
        SPP_HTML_Page::addElement($this);
    }

    public function getHTML()
    {
        $pstr='<'.$this->tagname;
        foreach($this->attributes as $key=>$val)
        {
            $pstr.=' '.$key.'="'.$val.'"';
        }
        if($this->isemptyflag)
        {
            $pstr.=' />';
        }
        else
        {
            $pstr.='>';
            //self::$started_tags->push($this->tagname);
        }
        return $pstr;
    }

    public function __toString()
    {
        $str=(string)$this->getHTML();
        return $str;
    }

    /**
     * function show()
     * Shows the element.
     */
    public function show()
    {
        if(self::$started_tags=='')
        {
            self::$started_tags=new SPP_Stack();
        }
        $pstr='<'.$this->tagname;
        foreach($this->attributes as $key=>$val)
        {
            $pstr.=' '.$key.'="'.$val.'"';
        }
        if($this->isemptyflag)
        {
            $pstr.=' />';
        }
        else
        {
            $pstr.='>';
            self::$started_tags->push($this->tagname);
        }
        echo $pstr;
    }

    public function readFromArray(array $arr)
    {
        if(array_key_exists('attributes',$arr))
        {
            foreach($arr['attributes'] as $attributes)
            {
                if(array_key_exists('attribute', $attributes))
                {
                    foreach($attributes['attribute'] as $attribute)
                    {
                        $this->setAttribute($attribute['name'],$attribute['value']);
                    }
                }
            }
        }
    }

    /**
     * function endMe()
     * Ends this html element.
     */
    public function endMe()
    {
        echo '</'.$this->tagname.'>';
    }

    /**
     * function endOne()
     * Ends one currently active html element.
     */
    public static function endOne()
    {
        $ele=self::$started_tags->pop();
        if($ele!=false)
        {
            echo '</'.$ele.'>';
        }
    }

    /**
     * function endAll()
     * Ends all currently active html elements.
     */
    public static function endAll()
    {
        while($ele=self::$started_tags->pop())
        {
            echo '</'.$ele.'>';
        }
    }

    /**
     * function setNotEmpty()
     * Sets an element as not empty.
     */
    public function setNotEmpty()
    {
        $this->isemptyflag=false;
    }

    /**
     * function setEmpty()
     * Sets an element as empty.
     */
    public function setEmpty()
    {
        $this->isemptyflag=true;
    }

    /**
     * function isEmpty()
     * Determines whether an element is empty ot not.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->isemptyflag;
    }

    /**
     * function setAttribute()
     * Sets the value an attribute of the element.
     *
     * @param string $aname
     * @param string $aval
     */
    public function setAttribute($aname,$aval)
    {
        //$flag=0;
        //if(array_search($aname,$this->attrlist)||array_search($aname,$this->stdattrlist)||array_search($aname,$this->eventattrlist))
        //{
            $this->attributes[$aname]=$aval;
        //}
        //else
        //{
        //    throw new InvalidHTMLAttributeException('Invalid attribute '.$aname);
        //}
    }

    /**
     * function getAttribute()
     * Gets the value an attribute of the element.
     *
     * @param string $aname
     * @return string
     */
    public function getAttribute($aname)
    {
        //$flag=0;
        //    if(array_search($aname,$this->attrlist)||array_search($aname,$this->stdattrlist)||array_search($aname,$this->eventattrlist))
        //    {
                return $this->attributes[$aname];
        //    }
        //    else
        //    {
        //        return null;
        //    }
    }
}
?>