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
abstract class SPP_HTML_Element extends SPP_Object {
    protected $tagname;
    protected $isemptyflag=false;
    protected $matter_text;
    protected $attributes=array();
    protected $attrlist=array();
    protected $stdattrlist=array();
    protected $eventattrlist=array();
    public static mixed $started_tags='';

    /**
     * Constructor
     *
     * @param string $ename
     */
    public function  __construct($ename, $isemptyflag=false) {
        //$this->tagname=$ename;
        $this->isemptyflag=$isemptyflag;
        $this->attributes['name']=$ename;
        $this->attributes['id']=$ename;
        $this->stdattrlist=array('class','id','style','title','dir','lang','xml:lang','accesskey','tabindex');
        $this->eventattrlist=array('onkeydown','onkeypress','onkeyup','onclick','ondblclick','onmousedown','onmousemove','onmouseover','onmouseout','onmouseup');
        SPP_HTML_Page::addElement($this);
    }

    //**
    // * function addClass()
    // * Adds a class to the element.
    // *
    // * @param string $classname
    // */
    
    public function addClass(string $classname){
        if(isset($this->attributes['class'])){
            $this->attributes['class'].=' '.$classname;
        }else{
            $this->attributes['class']=$classname;
        }
    }

    /**
     * function getName()
     * Gets the name of the element.
     *
     * @return string
     */
    public function getName()
    {
        return $this->tagname;
    }
    
    /**
     * function getHTML()
     * Gets the HTML representation of the element.
     *
     * @return string
     */
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

    public function render(){
        return $this->getHTML();
    }

    //**
    // * function __toString()
    // * Gets the HTML representation of the element.
    // *
    // * @return string
    // */
    
    public function __toString()
    {
        $str=(string)$this->render();
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

    public function getAttributes(){
        return $this->attributes;
    }

    public function renderAttributes(){
        $pstr='';
        foreach ($this->attributes as $key => $val) {
            $pstr .= ' ' . $key . '="' . $val . '"';
        }
        return $pstr;
    }

    public function removeAttribute($aname){
        unset($this->attributes[$aname]);
    }

    public function setMatter($matter){
        $this->matter_text=$matter;
    }

    public function getMatter(){
        return $this->matter_text;
    }

    public function clearMatter(){
        $this->matter_text='';

    }

    
    /**
     * function showMatter()
     * Shows the matter of the element.
     */
   public function showMatter()
    {
        if($this->matter_text!='')
        {
            echo $this->matter_text;
        }
    }

    /**
     * function showEnd()
     * Shows the end of the element.
     */
    public function showEnd()
    {
        echo '</'.$this->tagname.'>';
    }

    
    /**
     * function showAll()
     * Shows the element.
     */
    public function showAll()
    {
        $this->show();
        $this->showMatter();
        $this->showEnd();
    }


    /**
     * function writeToArray()
     * Writes the attributes of the element to an array.
     *
     * @param array $arr
     */

/*     public function writeToArray(($array) &$arr)
    {
        $arr['tagname']=$this->tagname;
        $arr['attributes']=$this->attributes;
    }
 */
    
    /**
     * function readFromArray()
     * Reads the attributes of the element from an array.
     *
     * @param array $arr
     */
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