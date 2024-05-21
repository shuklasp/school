<?php
namespace SPP;
/**
 * class \SPP\Registry
 * Implements a registry system for Satya Portal Pack.
 *
 * @author Satya Prakash Shukla
 */
class Registry extends \SPP\SPPObject {
    public static $reg=array();
    public static $values=array();
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
        if(\SPP\Scheduler::getContext()!='')
        {
            $entity='__apps=>'.\SPP\Scheduler::getContext().'=>'.$entity;
        }
        $key='';
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
            }
            self::$values[self::$valkey]=$value;
            $arr[$stk->pop()]=self::$valkey;
            self::$valkey++;
            $lastval='';
            while(($val=$stk->pop())!==false)
            {
                $ar=array();
                $ar[$val]=$arr;
                $arr=$ar;
                $lastval=$val;
            }
            $arr1=array();
            if(array_key_exists($lastval, self::$reg))
            {
                $arr1=array_merge_recursive(self::$reg[$lastval], $arr[$lastval]);
            }
            else
            {
                $arr1=$arr[$lastval];
            }
            self::$reg[$lastval]=$arr1;
            $key=self::$valkey-1;
        }
    }

    /**
     * function registerDir()
     * Registers a directory for a category.
     *
     * @param mixed $category
     * @param mixed $dir
     */
    public static function registerDir($category, $dir)
    {
        $dirs=self::get('__apps=>'.\SPP\Scheduler::getContext().'=>__dirs=>'.$category);
        $dir=str_replace('\\', '/', $dir);
        $dirs[]=$dir;
        self::register('__apps=>'. \SPP\Scheduler::getContext() . '=>__dirs=>'.$category, $dirs);
    }

    /**
     * function registerClass()
     * Registers a class for a category.
     *
     * @param mixed $category
     * @param mixed $class
     */
    public static function registerClass($category, $class)
    {
        $classes=self::get('__apps=>'. \SPP\Scheduler::getContext() . '=>__classes=>'.$category);
        $classes[]=$class;
        self::register('__apps=>'. \SPP\Scheduler::getContext() . '=>__classes=>'.$category, $classes);
    }

    /**
     * function registerFunction()
     * Registers a function for a category.
     *
     * @param mixed $category
     * @param mixed $function
     */
    public static function registerFunction($category, $function)
    {
        $functions=self::get('__apps=>'. \SPP\Scheduler::getContext() . '=>__functions=>'.$category);
        $functions[]=$function;
        self::register('__apps=>'. \SPP\Scheduler::getContext() . '=>__functions=>'.$category, $functions);
    }

    /**
     * function getDirs()
     * Gets the directories for a category.
     *
     * @param mixed $category
     * @return mixed
     */
    public static function getDirs($category)
    {
        return self::get('__apps=>'. \SPP\Scheduler::getContext() . '=>__dirs=>'.$category);
    }

    /**
     * function getValue()
     * Gets value of an entity.
     *
     * @param mixed $entity
     * @return mixed
     */
    public static function getValue($entity)
    {
        if(($key=self::getKey($entity))!==false)
        {
            return self::$values[$key];
        }
        else
        {
            return false;
        }
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
        return $arr;
    }


/*    public static function callHandler(\SPP\SPPEvent $event) {
        self::$events[$event]();
    }

    public static function registerObject(\SPP\SPPObject $ojb) {
        self::$objects[]=$obj;
    }*/
}
?>