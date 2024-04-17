<?php
namespace SPP;
//
/*require_once 'class.sppobject.php';
require_once 'class.sppevent.php';
require_once 'class.spputils.php';
require_once 'class.sppstack.php';*/
/**
 * class \SPP\Registry
 * Implements a registry system for Satya Portal Pack.
 *
 * @author Satya Prakash Shukla
 */
class Registry extends \SPP\SPP_Object {
    //private static $functions=array();
    //private static $objects=array();
//    private static $events=array();
    private static $reg=array();
    private static $values=array();
    private static $valkey=0;

    /**
     * function __construct()
     * Constructor for the \SPP\Registry class.
     *
     */
    public function __construct() {
        ;
    }

    /**
     * function register()
     * Registers a new entity and assigns a value to it.
     *
     * @param mixed $entity
     * @param mixed $value
     */
    public static function register($entity,$value)
    {
        //var_dump($entity,$value);
        if(\SPP\Scheduler::getContext()!='')
        {
            $entity='__apps=>'.\SPP\Scheduler::getContext().'=>'.$entity;
        }
         //echo '<br /><br />Registring '.$entity.' with value ';
         //print_r($value);
         //echo '<br /><br />';
        //$value=serialize($value);
        //print_r((string)$value);
        $key='';
        //var_dump($value);
        if(($key=self::getKey($entity))!==false)
        {
            self::$values[$key]=$value;
        }
        else
        {
            $ent=strtok($entity, '=>');
            $oldent=$ent;
            $arr=array();
            $stk=new \SPP\Stack();
            while($ent!==false)
            {
                $stk->push(trim($ent));
                $ent=strtok('=>');
                //var_dump($ent);
            }
            self::$values[self::$valkey]=$value;
            //var_dump($arr);
            $arr[$stk->pop()]=self::$valkey;
            self::$valkey++;
            //var_dump($arr);
            $lastval='';
            while(($val=$stk->pop())!==false)
            {
                $ar=array();
                $ar[$val]=$arr;
                $arr=$ar;
                $lastval=$val;
                //echo '
                //        Array is
                //            ';
                //print_r($arr);
                //echo $lastval;
            }
            $arr1=array();
            if(array_key_exists($lastval, self::$reg))
            {
                //self::del($entity);
                /*echo '\n\r<br /><br />Merging arrays ';
                print_r(self::$reg[$lastval]);
                echo '------------------';
                print_r($arr[$lastval]);
                echo '<br /><br />';*/

                $arr1=array_merge_recursive(self::$reg[$lastval], $arr[$lastval]);
            }
            else
            {
                $arr1=$arr[$lastval];
            }
            self::$reg[$lastval]=$arr1;
            $key=self::$valkey-1;
            //var_dump(self::$reg);
            //echo '<br /><br />';
            //var_dump(self::$values);
            //echo '<br /><br />';
        }
/*         echo '<br /><br />Registered '.$entity.' with value ';
        print_r(self::$values[$key]);
        echo '<br /><br />';
        echo '<br /><br />Getting '.$entity.' with value ';
        var_dump(self::get($entity));
        echo '<br /><br />';
        echo '<br /><br />Registry Array is now : ';
        print_r(self::$reg);
        echo '<br /><br />';
        echo '<br /><br />Values array is now ';
        print_r(self::$values);
        echo '<br /><br />';
 */    
    }

/*    public static function del($entity)
    {
        $ent=strtok($entity,'=>');
        $arr=&self::$reg;
        while($ent!==false)
        {
            $arr=&$arr[$ent];
            $ent=strtok('=>');
        }
        unset($arr);
    }*/

    /**
     * function get()
     * Gets the value of a registered entity.
     * Returns boolean false if entity is not registered.
     * 
     * @param mixed $entity
     * @return mixed
     */
    public static function get($entity)
    {
        if(\SPP\Scheduler::getContext()!='')
        {
            $entity='__apps=>'.\SPP\Scheduler::getContext().'=>'.$entity;
        }
        /*$ent=strtok($entity,'=>');
        $arr=self::$reg;
        //echo '<br />';
        //print_r($arr);
        //echo '<br />';
        while($ent!==false)
        {
            if(array_key_exists($ent, $arr))
            {
                $arr=$arr[$ent];
                $ent=strtok('=>');
            }
            else
            {
                return false;
            }
        }
        //$arr=str_replace('__regval__','',$arr);

        /*echo '----------Getting '.$entity.'-------------';
        var_dump ($arr);
        echo '-----------------Got------------------';
        $arr1=array();
        if(is_array($arr))
        {
            foreach($arr as $key=>$val)
            {
                $arr1[$key]=unserialize($val);
            }
            var_dump($arr1);
            return $arr1;
        }
        else
        {
        $val=self::$values[$arr];
        //var_dump(self::$reg);
        return $val;*/
        //}
        //var_dump(self::$values);
        $key=self::getKey($entity);
        if($key===false)
        {
            return false;
        }
        else
        {
            return self::$values[$key];
        }
    }

    
    /**
     * function isRegistered()
     * Checks if an entity is registered.
     * 
     * @param mixed $entity
     * @return boolean
     */
    public static function isRegistered($entity)
    {
        if(\SPP\Scheduler::getContext()!='')
        {
            $entity='__apps=>'.\SPP\Scheduler::getContext().'=>'.$entity;
        }
        $ent=strtok($entity,'=>');
        $arr=self::$reg;
        while($ent!==false)
        {
            if(array_key_exists($ent, $arr))
            {
                $arr=$arr[$ent];
                $ent=strtok('=>');
            }
            else
            {
                return false;
            }
        }
        return true;
    }

    //***************************************************************
    //Private functions
    //***************************************************************
    private static function getKey($entity)
    {
        /*if(\SPP\App::$AppContext!='')
        {
            $entity='__apps=>'.\SPP\App::$AppContext.'=>'.$entity;
        }*/
        $ent=strtok($entity,'=>');
        $arr=self::$reg;
        while($ent!==false)
        {
           /* echo '
                    Ent is :
                    ';
            print_r($ent);
            echo '
                    ';*/
            if(array_key_exists($ent, $arr))
            {
                $arr=$arr[$ent];
                $ent=strtok('=>');
            /*echo '
                    Value is :
                    ';
            print_r($arr);
            echo '
                    ';*/
            }
            else
            {
                return false;
            }
        }
        return $arr;
    }


/*    public static function callHandler(\SPP\SPP_Event $event) {
        self::$events[$event]();
    }

    public static function registerObject(\SPP\SPP_Object $ojb) {
        self::$objects[]=$obj;
    }*/
}
?>