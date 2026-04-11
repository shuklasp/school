<?php

namespace SPPMod\SPPView;

/**
 * abstract class ViewValidator
 * Base class for all SPPView field validators.
 *
 * Concrete subclasses must implement:
 *   - getJsFunction(): returns the client-side JS function call string
 *   - validate(mixed $value): bool — server-side validation logic
 *
 * @author Satya Prakash Shukla
 */
abstract class ViewValidator extends \SPP\SPPObject
{
    /**
     * Tracks whether this subclass has already registered its CSS/JS assets.
     * Declared per-subclass via late static binding (static::$included).
     */
    protected static bool $included = false;

    /** @var mixed Callback (reserved for future use or subclass event hooks) */
    protected mixed $callback;

    /** @var string JS validation function name */
    protected string $jsfunc = '';

    /**
     * Element types that this validator may be attached to.
     * Empty array means no restriction.
     * @var array<string>
     */
    protected array $applicabletags = [];

    /** @var string ID of the DOM element that shows the error message */
    protected string $errorholder = '';

    /** @var string Default error message */
    protected string $msg = '';

    /**
     * Map of attached elements: id => ['element' => ViewTag, 'event' => string, 'msg' => string]
     * Stores per-element messages independently of the global $msg.
     * @var array<string, array{element: \SPPMod\SPPView\ViewTag, event: string, msg: string}>
     */
    protected array $attachedto = [];

    public function __construct(mixed $callback, string $errorholder, string $msg, string $jsfunc)
    {
        $this->callback = $callback;
        $this->errorholder = $errorholder;
        $this->msg = $msg;
        $this->jsfunc = $jsfunc;

        // Use late static binding so each concrete subclass tracks its own flag
        if (!static::$included) {
            static::$included = true;
            ViewPage::addCssIncludeFile(SPP_CSS_URI . SPP_US . 'sppview/sppvalidations.css');
            ViewPage::addJsIncludeFile(SPP_JS_URI . SPP_US . 'sppview/sppvalidations.js');
        }
    }

    /**
     * Changes the error holder element and re-applies event handlers on all
     * already-attached elements so they reference the new holder.
     */
    public function setErrorHolder(string $hld): void
    {
        $this->errorholder = $hld;
        foreach ($this->attachedto as $entry) {
            $entry['element']->setAttribute($entry['event'], $this->getJsFunction());
        }
    }

    /**
     * Changes the default error message.
     * Does NOT override per-element messages set via attachTo().
     */
    public function setMessage(string $msg): void
    {
        $this->msg = $msg;
    }

    /**
     * Returns the JS function call string for client-side validation.
     * Implementations must incorporate $this->errorholder and $this->msg.
     */
    abstract public function getJsFunction(): string;

    /**
     * Server-side validation of a submitted value.
     *
     * @param mixed $value The value to validate (e.g. from $_POST)
     * @return bool         True if valid, false otherwise
     */
    abstract public function validate(mixed $value): bool;

    /**
     * Attaches this validator to a ViewTag element on a given DOM event.
     *
     * Validates that the element type is in $applicabletags (if restrictions exist).
     * Stores a per-element message independently of the global $msg property.
     *
     * @param \SPPMod\SPPView\ViewTag $elem  The element to attach to
     * @param string                  $event DOM event name (e.g. 'onblur')
     * @param string                  $msg   Optional override message for this element only
     * @throws \SPP\SPPException if the element type is not in applicabletags
     */
    public function attachTo(\SPPMod\SPPView\ViewTag $elem, string $event, string $msg = ''): void
    {
        // Enforce applicable tag restrictions
        if (!empty($this->applicabletags)) {
            $tagName = strtolower($elem->getTagName());
            if (!in_array($tagName, $this->applicabletags, true)) {
                throw new \SPP\SPPException(
                    'Validator ' . static::class . ' cannot be attached to <' . $tagName . '>. '
                    . 'Applicable tags: ' . implode(', ', $this->applicabletags)
                );
            }
        }

        // Store per-element message; fall back to global msg if not provided
        $elementMsg = ($msg !== '') ? $msg : $this->msg;

        $id = $elem->getAttribute('id');
        $this->attachedto[$id] = [
            'element' => $elem,
            'event' => $event,
            'msg' => $elementMsg,
        ];

        $elem->setAttribute($event, $this->getJsFunction());
    }

    /**
     * Entry point for server-side validation.
     * Default implementation iterates over all attached elements and calls validate().
     * MultipleValidators should override this if they need to validate the set as a whole.
     *
     * @return bool
     */
    public function validateAll(): bool
    {
        $res = true;
        foreach ($this->getAttachedIds() as $id) {
            $value = $_POST[$id] ?? null;
            if (!$this->validate($value)) {
                $res = false;
            }
        }
        return $res;
    }

    /**
     * Returns an array of element IDs that this validator is attached to.
     * @return array<string>
     */
    public function getAttachedIds(): array
    {
        return array_keys($this->attachedto);
    }
}
