<?php
//require_once 'class.spphtmlelement.php';

class SPP_HTML_Anchor extends SPP_HTML_Element{
    public function  __construct($ename) {
        parent::__construct($ename);
        $this->isemptyflag=false;
        $this->tagname='a';
        $this->attrlist=array('charset','coords','href','hreflang','rel','rev','shape','target','type');
    }
}


class SPP_HTML_Abbr extends SPP_HTML_Element{
    public function  __construct($ename) {
        parent::__construct($ename);
        $this->isemptyflag=false;
        $this->tagname='abbr';
        $this->attrlist=array();
    }
}


class SPP_HTML_Acronym extends SPP_HTML_Element{
    public function  __construct($ename) {
        parent::__construct($ename);
        $this->isemptyflag=false;
        $this->tagname='acronym';
        $this->attrlist=array();
    }
}

class SPP_HTML_Ul extends SPP_HTML_Element{
    private $list=array();
    public function  __construct($ename) {
        parent::__construct($ename);
        $this->isemptyflag=false;
        $this->tagname='ul';
        $this->attrlist=array();
    }

    public function addItem($item)
    {
        $this->list[]=new SPP_HTML_Li($this->attributes['name'].'li'.sizeof($this->list),$item);
    }

    public function getHTML()
    {
        $htm=parent::getHTML();
        foreach($this->list as $lst)
        {
            $htm.=$lst->getHTML();
        }
        $htm.='</ul>';
        return $htm;
    }
}


class SPP_HTML_Ol extends SPP_HTML_Element{
    private $list=array();
    public function  __construct($ename) {
        parent::__construct($ename);
        $this->isemptyflag=false;
        $this->tagname='ol';
        $this->attrlist=array();
    }

    public function addItem($item)
    {
        $this->list[]=new SPP_HTML_Li($this->attributes['name'].'li'.sizeof($this->list),$item);
    }

    public function getHTML()
    {
        $htm=parent::getHTML();
        foreach($this->list as $lst)
        {
            $htm.=$lst->getHTML();
        }
        $htm.='</ol>';
        return $htm;
    }
}

class SPP_HTML_Li extends SPP_HTML_Element{
    private $content;
    public function  __construct($ename,$content) {
        parent::__construct($ename);
        $this->isemptyflag=false;
        $this->tagname='li';
        $this->content=$content;
        $this->attrlist=array();
    }

    public function getHTML()
    {
        $htm=parent::getHTML();
        $htm.=$this->content;
        $htm.='</li>';
        return $htm;
    }
}