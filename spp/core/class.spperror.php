<?php
namespace SPP;
/**
 * class SPPError
 *
 * @author Administrator
 */
/*require_once 'class.sppobject.php';
require_once 'classes.htmlelements.php';*/
/**
 * class SPPError
 *
 * @author Administrator
 */
class SPPError extends \SPP\SPPObject
{
    //private $errordiv='';
    private $customerrhnd;
    private $appname = '';
    private static $errortype = array(
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parsing Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Runtime Notice',
        E_RECOVERABLE_ERROR => 'Catchable Fatal Error'
    );
    private $errors;

    public function __construct($handleerror = true)
    {
        $this->customerrhnd = '';
        $this->errors = array(
            E_ERROR => array(),
            E_WARNING => array(),
            E_PARSE => array(),
            E_NOTICE => array(),
            E_CORE_ERROR => array(),
            E_CORE_WARNING => array(),
            E_COMPILE_ERROR => array(),
            E_COMPILE_WARNING => array(),
            E_USER_ERROR => array(),
            E_USER_WARNING => array(),
            E_USER_NOTICE => array(),
            E_STRICT => array(),
            E_RECOVERABLE_ERROR => array(),
            E_DEPRECATED => array(),
            E_USER_DEPRECATED => array(),
            E_ALL => array()
        );
        if ($handleerror) {
            $this->init();
            set_exception_handler('SPP\SPPError::exceptionHandler');
        }
    }

    private function init()
    {
        $this->appname = \SPP\Scheduler::getContext();
        if (SPPSession::sessionVarExists('__errors__' . $this->appname)) {
            $this->errors = SPPSession::getSessionVar('__errors__' . $this->appname);
        }
        if (is_callable($this->customerrhnd) && $this->customerrhnd != '') {
            set_error_handler('SPPError::errorHandler');
        }
    }

    public function defineCustomErrorHandler($hnd)
    {
        if ($hnd != '' && $hnd != null && $hnd != false && is_callable($hnd)) {
            $this->customerrhnd = $hnd;
        }
    }

    public static function setCustomErrorHandler($hnd)
    {
        if ($hnd != '' && $hnd != null && $hnd != false && is_callable($hnd)) {
            $err = \SPP\Scheduler::getActiveErrorObj();
            if ($err != null) {
                $err->defineCustomErrorHandler($hnd);
            }
        }
    }

    public function getCustomErrorHandler()
    {
        return $this->customerrhnd;
    }

    /**
     * public static function errorHandler($errno, $errmsg, $filename, $linenum, $vars=array())
     *
     * @param int $errno
     * @param string $errmsg
     * @param string $filename
     * @param int $linenum
     * @param array $vars
     */
    public static function exceptionHandler(\Throwable $e)
    {
        if (ob_get_length()) ob_clean();
        
        $debug = defined('SPP_DEBUG') && SPP_DEBUG;
        $title = get_class($e);
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();
        $trace = $e->getTrace();

        if (php_sapi_name() === 'cli') {
            echo "\n[UNCAUGHT EXCEPTION] $title: $message in $file on line $line\n";
            exit(1);
        }

        if ($debug) {
            include __DIR__ . '/error_template.php';
        } else {
            echo "<h1>500 Internal Server Error</h1><p>Something went wrong. Please try again later.</p>";
        }
        exit(1);
    }

    public static function errorHandler($errno, $errmsg, $filename, $linenum, $vars = array())
    {
        // ... existing logic
        $proc = \SPP\Scheduler::getActiveProc();
        $pname = $proc->getName();
        $err = $proc->getErrorObj();
        if ($err === null) {
            return;
        }
        $err->errors[$errno][] = array('errno' => $errno, 'errmsg' => $errmsg, 'filename' => $filename, 'linenum' => $linenum);
        
        // If it's a fatal error, treat it like an exception
        if (in_array($errno, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            self::exceptionHandler(new \ErrorException($errmsg, $errno, 0, $filename, $linenum));
        }

        if ($err->customerrhnd != '') {
            if (is_callable($err->customerrhnd))
                call_user_func($err->customerrhnd);
        }
        SPPSession::setSessionVar('__errors__' . $pname, $err->errors);
    }


    public function getErrorTypes()
    {
        return self::$errortype;
    }

    /***
     * public static function triggerUserError($err)
     * Triggers a user error
     *
     * @param string $err
     */
    public static function triggerUserError($err)
    {
        trigger_error($err, E_USER_NOTICE);
    }

    public static function triggerDevError($err)
    {
        trigger_error($err, E_USER_ERROR);
    }

    public static function triggerAdminError($err)
    {
        trigger_error($err, E_USER_WARNING);
    }

    /***
     * public function getErrors()
     * Returns all errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    public function getDevErrors()
    {
        return $this->errors[E_USER_ERROR];
    }

    public function getAdminErrors()
    {
        return $this->errors[E_USER_WARNING];
    }

    public function getUserErrors()
    {
        return $this->errors[E_USER_NOTICE];
    }

    /**
     * public static function getUlErrors($errformat='',$errno=0)
     * Returns HTML unordered list of errors
     *
     * @param string $errformat
     * @param int $errno
     * @return string
     * @return string[html]ml]
     */
    public static function getUlErrors($errformat = '', $errno = 0)
    {
        $errobj = \SPP\Scheduler::getActiveErrorObj();
        if ($errobj === null) {
            return '';
        }
        $htm = '<ul>';
        //$ul=new SPP_HTML_Ul('errors');
        $err = array();
        $errors = $errobj->getErrors();
        if ($errno == 0) {
            $err = $errors;
        } else {
            $err[$errno] = $errors[$errno];
        }
        foreach ($err as $errno => $errors) {
            foreach ($errors as $error) {
                //print_r($error);
                //echo $error['errmsg'];
                if ($errformat == '') {
                    //$ul->addItem($error['errmsg']);
                    $htm .= '<li>' . htmlspecialchars($error['errmsg'], ENT_QUOTES, 'UTF-8') . '</li>';
                } else {
                    $errstr = str_replace('!errmsg!', htmlspecialchars((string) $error['errmsg'], ENT_QUOTES, 'UTF-8'), $errformat);
                    $errstr = str_replace('!errno!', htmlspecialchars((string) $error['errno'], ENT_QUOTES, 'UTF-8'), $errstr);
                    $errstr = str_replace('!filename!', htmlspecialchars((string) $error['filename'], ENT_QUOTES, 'UTF-8'), $errstr);
                    $errstr = str_replace('!linenum!', htmlspecialchars((string) $error['linenum'], ENT_QUOTES, 'UTF-8'), $errstr);
                    //$ul->addItem($errstr);
                    $htm .= '<li>' . $errstr . '</li>';
                }
            }
        }
        $htm .= '</ul>';
        //return $ul->getHtml();
        return $htm;
    }

    /***
     * public static function getOlErrors($errformat='',$errno=0)
     * Returns a HTML ordered list of errors
     *
     * @param string $errformat
     * @param int $errno
     */
    public static function getOlErrors($errformat = '', $errno = 0)
    {
        $errobj = \SPP\Scheduler::getActiveErrorObj();
        if ($errobj === null) {
            return '';
        }
        $htm = '<ol>';
        //$ul=new SPP_HTML_Ol('errors');
        $err = array();
        $errors = $errobj->getErrors();
        if ($errno == 0) {
            $err = $errors;
        } else {
            $err[$errno] = $errors[$errno];
        }
        foreach ($err as $errno => $errors) {
            foreach ($errors as $error) {
                //print_r($error);
                //echo $error['errmsg'];
                if ($errformat == '') {
                    //$ul->addItem($error['errmsg']);
                    $htm .= '<li>' . htmlspecialchars($error['errmsg'], ENT_QUOTES, 'UTF-8') . '</li>';
                } else {
                    $errstr = str_replace('!errmsg!', htmlspecialchars((string) $error['errmsg'], ENT_QUOTES, 'UTF-8'), $errformat);
                    $errstr = str_replace('!errno!', htmlspecialchars((string) $error['errno'], ENT_QUOTES, 'UTF-8'), $errstr);
                    $errstr = str_replace('!filename!', htmlspecialchars((string) $error['filename'], ENT_QUOTES, 'UTF-8'), $errstr);
                    $errstr = str_replace('!linenum!', htmlspecialchars((string) $error['linenum'], ENT_QUOTES, 'UTF-8'), $errstr);
                    //$ul->addItem($errstr);
                    $htm .= '<li>' . $errstr . '</li>';
                }
            }
        }
        $htm .= '</ol>';
        //return $ul->getHtml();
        return $htm;
    }

    public static function destroyErrors($errnum = 0)
    {
        $errobj = \SPP\Scheduler::getActiveErrorObj();
        if ($errobj instanceof SPPError) {
            $errobj->destroySelfErrors($errnum);
        }
    }

    public function destroySelfErrors($errnum = 0)
    {
        if ($errnum == 0) {
            foreach ($this->errors as $errno => $errors) {
                $this->errors[$errno] = array();
            }
        } else {
            $this->errors[$errnum] = array();
        }
        SPPSession::setSessionVar('__errors__' . $this->appname, $this->errors);
    }

    public static function getErrorDetails($errno)
    {
        return self::$errortype[$errno];
    }
}
