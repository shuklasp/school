<?php
/**
 * file class.sppviewtag.php
 * Defines the SPP_ViewTag class.
 */
 
/**
 * class SPP_ViewTag
 *
 * Represents a HTML Tag.
 *
 * @author Satya Prakash Shukla
 */
class SPP_ViewTag extends \SPP\SPP_Object {
    protected $tagname;
    protected $isemptyflag=false;
    protected $matter_text;
    protected $children=array();
    protected $attributes=array();
    protected $attrlist=array('name', 'id');
    protected $stdattrlist=array('name', 'id','title','dir','lang','xml:lang','accesskey','tabindex','coords','href','hreflang','rel','target','shape','type','usemap');
    protected $emptytags=array('area','base','br','col','command','embed','hr','img','input','keygen','link','meta','param','source','track','wbr');
    protected $eventattrlist=array('onclick', 'ondblclick', 'onmousedown', 'onmousemove', 'onmouseover', 'onmouseout', 'onmouseup', 'onkeydown', 'onkeypress', 'onkeyup', 'onfocus', 
        'onblur', 'oncontextmenu', 'onresize', 'onscroll', 'onselect', 'onunload');
    //public static mixed $started_tags='';

    /**
     * Constructor
     *
     * @param string $ename
     */
    public function  __construct($tagname, $ename, $add_default_class=true) {
        if(in_array($tagname,$this->emptytags))
        {
            $this->isemptyflag=true;
        }
        else
        {
            $this->isemptyflag=false;
        }
        $this->tagname=$tagname;
        $this->attributes['name']=$ename;
        $this->attributes['id']=$ename;
        //$this->stdattrlist=array('class','id','style','title','dir','lang','xml:lang','accesskey','tabindex');
        //$this->eventattrlist=array('onkeydown','onkeypress','onkeyup','onclick','ondblclick','onmousedown','onmousemove','onmouseover','onmouseout','onmouseup');
        $this->matter_text='';
        if($add_default_class)
        {
            $this->addClass('spp-element spp-'.$ename);
        }
    }

    /**
     * function addClass()
     * Adds a class to the element.
     *
     * @param string $classname
     */
    
    public function addClass(string $classname){
        if(isset($this->attributes['class'])){
            $this->attributes['class'].=' '.$classname;
        }else{
            $this->attributes['class']=$classname;
        }
    }

    /***
     * function wrapTag()
     * Wraps the element into another tag.
     *
     * @param SPP_ViewTag $tag
     */
    public function wrapTag(SPP_ViewTag $tag){
        if(!isset($this->children[$tag->id]))
        {
            $this->children[$tag->id]=$tag;
        }
        else
        {
            throw new \SPP\SPP_Exception('Cannot have multiple elements with same id: '.$tag->id);
        }
    }

    /***
     * function wrapIntoTag()
     * Wraps the element into another tag.
     *
     * @param string $tagname
     * @param string $ename
     *
     * @return SPP_ViewTag
     */
    public function wrapIntoTag(string $tagname, string $ename){
        $tag= new SPP_ViewTag($tagname, $ename);
        $tag->wrapTag($this);
        return $tag;
    }

    /**
     * function addAttribute()
     * Gets an attribute of the element.
     *
     * @param string $aname
     *
     * @return boolean
     */
    public function addAttribute(string $aname, string $avalue=''){
        if(!in_array($aname, $this->attrlist))
        {
            $this->attrlist[] = $aname;
            if(!$avalue=='')
            {
                $this->attributes[$aname] = $avalue;
            }
            return true;
        }
        else
        {
            return false;
        }
    }


    public function setMatterText(string $text){
        $this->matter_text=$text;
    }

    /**
     * function removeClass()
     * Removes a class from the element.
     *
     * @param string $classname
    */
    
    public function removeClass(string $classname){
        if(isset($this->attributes['class']))
        {
            $this->attributes['class']=str_replace($classname,'',$this->attributes['class']);
            return true;
        }
        else
        {
            return false;
        }   
    }

    /**
     * function remopveAttribute()
     * Removes an attribute from the element.
     *
     * @param string $aname
     *
     * @return boolean
     */
    public function removeAttribute($aname){
        if(isset($this->attributes[$aname]))
        {
            unset($this->attributes[$aname]);
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * function setAttribute()
     * Sets an attribute for the element.
     *
     * @param string $aname
     * @param string $avalue
     */
    public function setAttribute($aname,$avalue){
        $this->attributes[$aname]=$avalue;
    }

    /**
     * function setAttributes()
     * Sets the attributes for the element.
     *
     * @param array $attributes
     */
    public function setAttributes(array $attributes){
        if(empty($attributes))
        {
            return;
        }
        foreach($attributes as $key=>$val)
        {
            if(isset($this->attributes[$key]))
            {
                $this->attributes[$key]=$val;
            }
            else
            {
                $this->attributes[$key]=$val;
            }
        }
    }



    /**
     * function getTagName()
     * Gets the name of the element.
     *
     * @return string
     */
    public function getTagName()
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
            if(isset($this->matter_text))
            {
                $pstr.=$this->matter_text;
            }
            if(isset($this->children))
            {
                $pstr.=$this->getChildren();
            }
            $pstr.='</'.$this->tagname.'>';
        }
        return $pstr;
    }

    /***
     * function getStart()
     * Gets the start tag of the element.
     *
     * @return string
            if(isset($this->children))
            {
                $pstr.=$this->getChildren();
            }
     */
    public function getStart(){
        $pstr= '<'.$this->tagname;
        foreach($this->attributes as $key=>$val)
        {
            $pstr.=' '.$key.'="'.$val.'"';
        }
        if($this->isemptyflag)
        {
            return $pstr.' />';
        }
        else
        {
            return $pstr.'>';
        }
    }

    
    /***
     * function getEnd()
     * Gets the end tag of the element.
     *
     * @return string
     */
    public function getEnd(){
        if($this->isemptyflag)
        {
            return '';
        }
        else
        {
            return '</'.$this->tagname.'>';
        }
    }


    
    public function getAttributes(){
        return $this->attributes;
    }

    /***
     * function addChild()
     * Adds a child to the element.
     *
     * @param SPP_ViewTag $child
     */
    public function addChild(SPP_ViewTag $child){
        $this->children[]=$child;
    }

    /**
     * function getChildrenArray()
     * Gets the children of the element as an array.
     *
     * @return array
     */
    public function getChildrenArray(){
        return $this->children;
    }

    /**
     * function getChildren()
     * Gets the children of the element.
     *
     * @return string
     */
    public function getChildren(){
        $pstr='';
        foreach($this->children as $child)
        {
            $pstr.=$child->getHTML();
        }
        return $pstr;
    }


    /**
     * function __set($propname,$propvalue)
     * Sets the value of a property.
     *
     * @param string $propname
     * @param mixed $propvalue
     */

    public function __set($propname,$propvalue)
    {
        if(in_array($propname, $this->attrlist))
        {
            $this->attributes[$propname]=$propvalue;
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * public function __toString()
     * get the HTML representation of the element.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getHTML();
    }

    /**
     * function __get($propname)
     * Gets the value of a property.
     *
     * @param string $propname
     * @return mixed
     */
    public function __get($propname)
    {
        if(isset($this->attributes[$propname]))
        {
            return $this->attributes[$propname];
        }
        else
        {
            return null;
        }
    }


    /**
     * function __unset($propname)
     * Unsets a property.
     *
     * @param string $propname
     */
    public function __unset($propname)
    {
        if(isset($this->attributes[$propname]))
        {
            unset($this->attributes[$propname]);
        }
    }

    /**
     * function __isset($propname)
     * Checks if a property is set.
     *
     * @param string $propname
     * @return boolean
     */

    public function __isset($propname)
    {
        return isset($this->attributes[$propname]);
    }

    /**
     * function render()
     * Renders the element.
     */

    public function render(){
        echo $this->getHTML();
    }


    /**
     * function show()
     * Shows the element.
     */
/*     public function show()
    {
        if(self::$started_tags=='')
        {
            self::$started_tags=new \SPP\Stack();
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
 */

    public function renderAttributes(){
        $pstr='';
        foreach ($this->attributes as $key => $val) {
            $pstr .= ' ' . $key . '="' . $val . '"';
        }
        return $pstr;
    }

/*     public function removeAttribute($aname){
        unset($this->attributes[$aname]);
    }
 */
    public function setMatter($matter){
        $this->matter_text=$matter;
    }


    /***
     * function appendMatter($matter)
     * Appends matter to the element.
     *
     * @param string $matter
     */
    public function appendMatter($matter){
        if($this->matter_text=='')
        {
            $this->matter_text=$matter;
        }
        else
        {
            $this->matter_text.=$matter;
        }
    }


    /***
     * function getMatter()
     * Gets the matter of the element.
     *
     * @return string
     */
    public function getMatter(){
        return $this->matter_text;
    }

    /***
     * function clearMatter()
     * Clears the matter of the element.
     */
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
     * function writeToArray()
     * Writes the attributes of the element to an array.
     *
     * @param array $arr
     */

    public function writeToArray(array $arr)
    {
        $arr['tagname']=$this->tagname;
        $arr['attributes']=$this->attributes;
        $arr['children']=$this->children;
        $arr['matter']= $this->matter_text;
        return $arr;
    }


    /**
     * function setTagname()
     * Gets the tagname of the element.
     *
     * @return string
     */
    public function setTagname($tagname){
        return $this->tagname=$tagname;
    }

    
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
     * function getAttribute()
     * Gets the value an attribute of the element.
     *
     * @param string $aname
     * @return string
     */
    public function getAttribute($aname)
    {
        if(isset($this->attributes[$aname]))
        {
            return $this->attributes[$aname];
        }
        else
        {
            return null;
        }
    }
}