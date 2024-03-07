<?php
/**
 * class SPP_String
 * Does string handling in SPP.
 *
 * @author Satya Prakash Shukla
 */
class SPP_String extends SPP_Object {
    private $str;

    public function __toString()
    {
        return $this->str;
    }

    public function matchFileName($exp, $str)
    {
        $len=strlen($str);
        $exlen=strlen($exp);
        $match=1;
        for($i=0, $j=0;$i<$len&&$j<$exlen;$i++)
        {
            for(;$str[$i]==$exp[$j];$i++,$j++)
            {
                if($i>=$len||$j>=$exlen)
                {
                    break;
                }
            }
            if($exp[$j]=='*')
            {
                for(;$exp[$j]=='*'&&$j<$exlen;$j++);
                if($j>=$exlen)
                {
                    break;
                }
                else
                {
                    $match=0;
                    for(;$str[$i]!=$exp[$j]&&$i<$len&&$j<$exlen;$i++,$j++);
                    if($i>=$len||$j>=$exlen)
                    {
                        break;
                    }
                }
            }
        }
    }
}
?>