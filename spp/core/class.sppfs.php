<?php
namespace SPP;
/**
 * class SPPFS
 * Handles File system in SPP.
 *
 * @author Satya Prakash Shukla
 */
class SPPFS extends \SPP\SPPObject {
    public static function findFile($file, $root = SPP_BASE_DIR)
    {
        $filearray = array();
        $stack = new \SPP\Stack();
        do {
            if (!is_dir($root)) continue;
            
            $dir = @opendir($root);
            if ($dir === false) {
                // Log failure but continue searching other branches in the stack
                continue;
            }
            
            while (($fl = readdir($dir)) !== false) {
                if ($fl != '.' && $fl != '..') {
                    $path = $root . SPP_DS . $fl;
                    if (is_dir($path)) {
                        $stack->push($path);
                    } else {
                        if ($file == $fl) {
                            $filearray[] = $path;
                        }
                    }
                }
            }
            closedir($dir);
        } while (($root = $stack->pop()) !== false);
        
        return $filearray;
    }
}
?>
