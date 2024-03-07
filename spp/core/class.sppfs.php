<?php
/**
 * class SPP_FS
 * Handles File system in SPP.
 *
 * @author Satya Prakash Shukla
 */
class SPP_FS extends SPP_Object {
    public static function findFile($file,$root=SPP_BASE_DIR) {
        $filearray=array();
        $stack=new SPP_Stack();
        do {
            $dir=opendir($root);
            //echo $root.'<br />';
            if($dir===false) {
                return false;
            }
            while(($fl=readdir($dir))!==false) {
                if($fl!='.'&&$fl!='..') {
                    if(is_dir($root.SPP_DS.$fl)) {
                        $stack->push($root.SPP_DS.$fl);
                        //echo '<br />Stack: '.$stack.'<br />';
                    }
                    else {
                            //echo '<br />File: '.$fl.'<br />';
                        if($file==$fl) {
                            $filearray[]=$root.SPP_DS.$fl;
                        }
                    }
                }
            }
        }while(($root=$stack->pop())!==false);
        return $filearray;
    }
}
?>