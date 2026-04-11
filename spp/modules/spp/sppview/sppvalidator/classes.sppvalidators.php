<?php

namespace SPPMod\SPPView;

/**
 * Concrete SPPView validators.
 *
 * Each validator must implement:
 *   - validate(mixed $value): bool  (Single Validators)
 *   - validateAll(): bool           (Multiple Validators)
 */

/**
 * Required field validator.
 */
class SPP_Validator_RequiredValidator extends SPP_Single_validator {
    public function __construct(\SPPMod\SPPView\ViewTag $elem, $errorholder = 'nameerror', $msg = 'A required field is left blank!') {
        parent::__construct($elem, $errorholder, $msg, 'validateRequired');
        $this->applicabletags = ['input', 'select', 'textarea'];
    }

    public function validate(mixed $value): bool {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            ViewPage::addClass($this->element->getAttribute('id'), 'errorclass');
            \SPP\SPPError::triggerUserError($this->msg);
            return false;
        }
        return true;
    }
}

/**
 * Numeric field validator.
 */
class SPP_Validator_NumericValidator extends SPP_Single_validator {
    public function __construct(\SPPMod\SPPView\ViewTag $elem, $errorholder = 'nameerror', $msg = 'The field should be numeric!') {
        parent::__construct($elem, $errorholder, $msg, 'validateNumeric');
        $this->applicabletags = ['input'];
    }

    public function validate(mixed $value): bool {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            // Optional field logic if needed, but usually Numeric is paired with Required
            return true; 
        }
        if (!is_numeric($value)) {
            ViewPage::addClass($this->element->getAttribute('id'), 'errorclass');
            \SPP\SPPError::triggerUserError($this->msg);
            return false;
        }
        return true;
    }
}

/**
 * Multiple-element validator: at least one of the fields in the set must be filled.
 */
class SPP_Validator_OneRequiredValidator extends SPP_Multiple_Validator {
    public function __construct(array $elems, $errorholder = 'nameerror', $msg = 'At least one of these fields must be filled') {
        parent::__construct($elems, $errorholder, $msg, 'validateOneRequired');
        $this->applicabletags = ['input'];
    }

    /**
     * Multiple validators must override validateAll() to check the set instead of individual values.
     */
    public function validateAll(): bool {
        $flag = false;
        foreach ($this->elements as $elem) {
            $id = $elem->getAttribute('id');
            if (isset($_POST[$id]) && trim($_POST[$id]) !== '') {
                $flag = true;
                break;
            }
        }

        if ($flag) {
            return true;
        }

        // None filled — mark all with errorclass
        foreach ($this->elements as $elem) {
            ViewPage::addClass($elem->getAttribute('id'), 'errorclass');
        }
        \SPP\SPPError::triggerUserError($this->msg);
        return false;
    }

    /**
     * Stub implementation to satisfy abstract base contract.
     */
    public function validate(mixed $value): bool {
        return true;
    }
}