<?php

namespace SPPMod\SPPView;
//require_once 'class.spphtmlelement.php';

/**
 * class SPP_HTML_TableField
 * Represents a HTML table field.
 *
 * @author Satya Prakash Shukla
 */
class SPP_HTML_TableField extends \SPPMod\SPPView\ViewTag {
    private $content;
    public function  __construct($ename, $isheading=false) {
        parent::__construct($ename);
        $this->isemptyflag=false;
        if($isheading)
        {
            $this->tagname='th';
        }
        else
        {
            $this->tagname='td';
        }
        $this->attrlist=array('abbr', 'align','axis','bgcolor','char','charoff','colspan','headers','height','nowrap','rowspan','scope','valign','width');
    }

    public function setContent($cnt)
    {
        $this->content=$cnt;
    }

    public function getContent($cnt)
    {
        return $this->content;
    }

    public function show()
    {
        parent::show();
        echo $this->content;
        $this->endOne();
    }
}

/**
 * Description of SPP_HTML_TableRow
 *
 * @author Administrator
 */
class SPP_HTML_TableRow extends SPP_HTML_Element {
    private $fields;
    private $numfld=0;
    public function  __construct($ename) {
        parent::__construct($ename);
        $this->isemptyflag=false;
        $this->tagname='tr';
        $this->attrlist=array('align','bgcolor','char','charoff','valign');
    }

    public function addField(SPP_HTML_TableField $fld)
    {
        $this->fields[$this->numfld++]=$fld;
        return true;
    }

    public function show()
    {
        parent::show();
        foreach($this->fields as $fld)
        {
            $fld->show();
        }
        $this->endOne();
    }
}


/**
 * Description of SPP_HTML_TableSection
 *
 * @author Administrator
 */
class SPP_HTML_TableSection extends SPP_HTML_Element {
    private $rows=array();
    private $numrows=0;
    public function  __construct($ename,$stype) {
        parent::__construct($ename);
        $this->isemptyflag=false;
        if($stype=='head')
        {
            $this->tagname='thead';
        }
        elseif($stype=='body')
        {
            $this->tagname='tbody';
        }
        elseif($stype=='foot')
        {
            $this->tagname='tfoot';
        }
        else
        {
            throw new InvalidHTMLTableSectionException('Invalid Table section: '.$stype);
        }
        $this->attrlist=array('align','char','charoff','valign');
    }

    public function addRow(SPP_HTML_TableRow $row)
    {
        $this->rows[$this->numrows++]=$row;
        //$this->numrows+=1;
        return true;
    }

    public function show()
    {
        parent::show();
        foreach($this->rows as $row)
        {
            $row->show();
        }
        $this->endOne();
    }
}


/**
 * Description of SPP_HTML_Table
 *
 * @author Administrator
 */
class SPP_HTML_Table extends SPP_HTML_Element {
    private $caption;
    private $capalign=0;
    private $headrows;
    private $bodyrows;
    private $footrows;
    public function  __construct($ename) {
        parent::__construct($ename);
        $this->isemptyflag=false;
        $this->tagname='table';
        $this->attrlist=array('align','bgcolor','border','cellpadding','cellspacing','frame','rules','summary','width');
        $this->headrows=new SPP_HTML_TableSection($ename.'head','head');
        $this->bodyrows=new SPP_HTML_TableSection($ename.'body','body');
        $this->footrows=new SPP_HTML_TableSection($ename.'foot','foot');
    }

    public function setCaption($cap,$calign='center')
    {
        $this->caption=$cap;
        $this->capalign=$calign;
    }

    public function addHeaderRow(SPP_HTML_TableRow $row)
    {
        $this->headrows->addRow($row);
    }

    public function addRow(SPP_HTML_TableRow $row)
    {
        $this->bodyrows->addRow($row);
    }

    public function addFooterRow(SPP_HTML_TableRow $row)
    {
        $this->footrows->addRow($row);
    }

    public function setHeadAttribute($attname,$attval)
    {
        $this->headrows->setAttribute($attname, $attval);
    }

    public function setBodyAttribute($attname,$attval)
    {
        $this->bodyrows->setAttribute($attname, $attval);
    }

    public function setFootAttribute($attname,$attval)
    {
        $this->footrows->setAttribute($attname, $attval);
    }

    public function show()
    {
        parent::show();
        if($this->capalign!=0)
        {
            echo '<caption align="'.$this->capalign.'">'.$this->caption.'</caption>';
        }
        $this->headrows->show();
        $this->bodyrows->show();
        $this->footrows->show();
        $this->endOne();
    }
}