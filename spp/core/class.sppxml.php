<?php
namespace SPP;
/**
 * class SPPXml
 * Does XML Handling
 *
 * @author Satya Prakash Shukla
 */

class SPPXml extends \SPP\SPPObject {

    private $xmlelement;
    
    /**
     * Constructor
     */
    public function __construct($xml)
    {
        if(is_file($xml))
        {
            $this->xmlelement=simplexml_load_file($xml);
        }
        else
        {
            $this->xmlelement=simplexml_load_string($xml);
        }
        if(!$this->xmlelement)
        {
            $errors='';
            foreach(libxml_get_errors() as $err)
            {
                $errors.=$err->message.'\n\r';
            }
            throw new \SPP\SPPException('XML Parsing error: '.$xml.'. '.$errors);
        }
    }


    public static function xml2phpArray($xml,$arr=array()) {
        $iter = 0;
        foreach($xml->children() as $b) {
            $a = $b->getName();
            if(!$b->children()) {
                $arr[$a] = trim($b[0]);
            }
            else {
                $arr[$a][$iter] = array();
                $arr[$a][$iter] = self::xml2phpArray($b,$arr[$a][$iter]);
            }
            $iter++;
        }
        return $arr;
    }
}
?>