<?php
namespace SPP;
/**
 * class SPPUtils
 * Provides some utility functions for SPP.
 *
 * @author Satya Prakash Shukla
 */
// require_once 'class.sppobject.php';
class SPPUtils extends \SPP\SPPObject {
    public static function valueIn(array $array, $value) {
        return in_array($value,$array);
    }

    public static function valueNotIn(array $array, $value) {
        return !in_array($value,$array);
    }

    /**
     * public static function strleft(string $s1, string $s2)
     * 
     */
    static function strleft($s1, $s2)
    {
        return substr($s1, 0, strpos($s1, $s2));
    }

    static function selfURL() {
        $s = empty($_SERVER["HTTPS"]) ? ''
                : (($_SERVER["HTTPS"] == "on") ? "s"
                : "");
        $protocol = self::strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
        $port = ($_SERVER["SERVER_PORT"] == "80") ? ""
                : (":".$_SERVER["SERVER_PORT"]);
        return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];
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

    public static function str_replace_count($search,$replace,$subject,$times) {
        $subject_original=$subject;
        $len=strlen($search);
        $pos=0;
        for ($i=1;$i<=$times;$i++) {
            $pos=strpos($subject,$search,$pos);
            if($pos!==false) {
                $subject=substr($subject_original,0,$pos);
                $subject.=$replace;
                $subject.=substr($subject_original,$pos+$len);
                $subject_original=$subject;
            } else {
                break;
            }
        }
        return($subject);
    }
}
