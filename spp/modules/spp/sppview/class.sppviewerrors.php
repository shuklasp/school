<?php

namespace SPPMod\SPPView;

class SPP_ViewErrors{
    protected static $errorHolders = array();
    public static function addError(string $errorHolder, string $errorMessage, string $errorType= 'error')
    {
        if(!in_array($errorType, array('error', 'warning', 'info'))){
            $errorType = 'error';
        }
        if(!isset(self::$errorHolders[$errorHolder])){
            self::$errorHolders[$errorHolder] = array();
        }
        self::$errorHolders[$errorHolder][$errorType][] = $errorMessage;
    }
    public static function getErrors(string $errorHolder)
    {
        if(isset(self::$errorHolders[$errorHolder])){
            return self::$errorHolders[$errorHolder];
        }
        return array();
    }

    public static function clearErrors(string $errorHolder)
    {
        if(isset(self::$errorHolders[$errorHolder])){
            unset(self::$errorHolders[$errorHolder]);
        }
    }

    public static function clearAllErrors()
    {
        self::$errorHolders = array();
    }

    public static function displayErrors(string $errorHolder)
    {
        $errors = self::getErrors($errorHolder);
        if(count($errors) > 0){
            echo '<div class="error-holder '.$errorHolder.'">';
            foreach($errors as $errorType => $errorsMessages){
                echo '<p>'.$errorType.':</p>';
                foreach($errorsMessages as $key => $errorMessage){
                    echo '<p class="error-message '.$errorType.'">'.$errorMessage.'</p>';
                    unset($errorMessage); // free memory
                    unset(self::$errorHolders [$errorHolder][$errorType][$key]); // free memory
                }
            }
            echo '</div>';
        }
    }

    public static function displayGlobalErrors()
    {
        self::displayErrors('global');
    }
    public static function displayFormErrors(string $formName)
    {
        self::displayErrors($formName);
    }
    public static function displayBlockErrors(string $blockName)
    {
        self::displayErrors($blockName);
    }
    public static function displayModuleErrors(string $moduleName)
    {
        self::displayErrors($moduleName);
    }
    public static function displayAllErrors()
    {
        foreach(self::$errorHolders as $errorHolder => $errors){
            self::displayErrors($errorHolder);
        }
    }
}
