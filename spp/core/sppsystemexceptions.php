<?php
namespace SPP\Exceptions;
//require_once 'class.sppexception.php';

class UserAuthenticationException extends \SPP\SPPException
{
    public function __construct($message,$code=1000) {
        parent::__construct($message,$code);
    }
}

class UserNotFoundException extends \SPP\SPPException
{
    public function __construct($message,$code=1001) {
        parent::__construct($message,$code);
    }
}

class UnknownPropertyException extends \SPP\SPPException
{
    public function __construct($message,$code=1002) {
        parent::__construct($message,$code);
    }
}

class RightViolationException extends \SPP\SPPException
{
    public function __construct($message,$code=1003) {
        parent::__construct($message,$code);
    }
}

class UnknownConfigVarException extends \SPP\SPPException
{
    public function __construct($message,$code=1004) {
        parent::__construct($message,$code);
    }
}

class UnknownConfigTabVarException extends \SPP\SPPException
{
    public function __construct($message,$code=1005) {
        parent::__construct($message,$code);
    }
}

class ReadonlyConfigVarException extends \SPP\SPPException
{
    public function __construct($message,$code=1006) {
        parent::__construct($message,$code);
    }
}

class UnknownSessionVarException extends \SPP\SPPException
{
    public function __construct($message,$code=1007) {
        parent::__construct($message,$code);
    }
}

class NoAuthSessionException extends \SPP\SPPException
{
    public function __construct($message,$code=1008) {
        parent::__construct($message,$code);
    }
}

class VarNotFoundException extends \SPP\SPPException
{
    public function __construct($message,$code=1009) {
        parent::__construct($message,$code);
    }
}

class InvalidHTMLAttributeException extends \SPP\SPPException
{
    public function __construct($message,$code=1010) {
        parent::__construct($message,$code);
    }
}

class InvalidHTMLTableSectionException extends \SPP\SPPException
{
    public function __construct($message,$code=1011) {
        parent::__construct($message,$code);
    }
}

class SequenceExistsException extends \SPP\SPPException
{
    public function __construct($message,$code=1012) {
        parent::__construct($message,$code);
    }
}

class SequenceDoesNotExistException extends \SPP\SPPException
{
    public function __construct($message,$code=1013) {
        parent::__construct($message,$code);
    }
}

class ConfigVarExistsException extends \SPP\SPPException
{
    public function __construct($message,$code=1014) {
        parent::__construct($message,$code);
    }
}

class UnknownRoleException extends \SPP\SPPException
{
    public function __construct($message,$code=1015) {
        parent::__construct($message,$code);
    }
}

class InvalidUserSessionException extends \SPP\SPPException
{
    public function __construct($message,$code=1016) {
        parent::__construct($message,$code);
    }
}

class ProfileAlreadyExistsException extends \SPP\SPPException
{
    public function __construct($message,$code=1017) {
        parent::__construct($message,$code);
    }
}

class ProfileDoesNotExistException extends \SPP\SPPException
{
    public function __construct($message,$code=1018) {
        parent::__construct($message,$code);
    }
}
class NotAssociativeArrayException extends \SPP\SPPException
{
    public function __construct($message,$code=1019) {
        parent::__construct($message,$code);
    }
}

class NotIntegerException extends \SPP\SPPException
{
    public function __construct($message,$code=1017) {
        parent::__construct($message,$code);
    }
}

class NoProfileSelectedException extends \SPP\SPPException
{
    public function __construct($message,$code=1018) {
        parent::__construct($message,$code);
    }
}

class UnknownProfileFieldException extends \SPP\SPPException
{
    public function __construct($message,$code=1017) {
        parent::__construct($message,$code);
    }
}

class UnknownEventException extends \SPP\SPPException
{
    public function __construct($message,$code=1019) {
        parent::__construct($message, $code);
    }
}

class UnknownWizardException extends \SPP\SPPException
{
    public function __construct($message,$code=1020) {
        parent::__construct($message, $code);
    }
}

class UnknownWizardVarException extends \SPP\SPPException
{
    public function __construct($message,$code=1021) {
        parent::__construct($message, $code);
    }
}

class UnknownRequestTypeException extends \SPP\SPPException
{
    public function __construct($message,$code=1022) {
        parent::__construct($message, $code);
    }
}

class SessionDoesNotExistException extends \SPP\SPPException
{
    public function __construct($message,$code=1023) {
        parent::__construct($message, $code);
    }
}

class DuplicateModuleException extends \SPP\SPPException
{
    public function __construct($message,$code=1024) {
        parent::__construct($message, $code);
    }
}

class UserBannedException extends \SPP\SPPException
{
    public function __construct($message,$code=1025) {
        parent::__construct($message, $code);
    }
}
?>