<?php

namespace SPPMod\SPPView;

use Symfony\Component\Yaml\Yaml;

/**
 * class ViewFormBuilder
 *
 * Static factory to build ViewForm instances from YAML definitions.
 * Supports SPA service integration and validation mapping.
 *
 * @author Satya Prakash Shukla
 */
class ViewFormBuilder extends \SPP\SPPObject
{
    /**
     * Build a ViewForm from a YAML file.
     *
     * @param string $yamlPath Path relative to app root or absolute.
     * @return ViewForm
     */
    public static function fromYaml(string $yamlPath): ViewForm
    {
        $config = self::loadConfig($yamlPath);
        $fConfig = $config['form'] ?? [];

        $form = new ViewForm(
            $fConfig['name'] ?? basename($yamlPath, '.yml'),
            $fConfig['method'] ?? 'post',
            $fConfig['action'] ?? '',
            $fConfig['id'] ?? null
        );

        // Metadata assignment
        if (isset($config['entity'])) $form->setEntityClass($config['entity']);
        if (isset($config['title']))  $form->setMatter($config['title']);

        // SPA Integration
        if (!empty($fConfig['service'])) {
            $form->setAttribute('data-service', $fConfig['service']);
            
            // Response handlers for SPPRouter
            $resp = $fConfig['on_response'] ?? [];
            if (isset($resp['ok']))       $form->setAttribute('data-on-ok', $resp['ok']);
            if (isset($resp['error']))    $form->setAttribute('data-on-error', $resp['error']);
            if (isset($resp['redirect'])) $form->setAttribute('data-on-redirect', $resp['redirect']);
        }

        // Build Fields - Support both 'elements' (new) and 'fields' (legacy) keys
        foreach ($config['elements'] ?? $config['fields'] ?? [] as $name => $field) {
            // Handle associative arrays where key is the name
            if (is_array($field) && !isset($field['name']) && is_string($name)) {
                $field['name'] = $name;
            }
            $elem = self::buildElement($field);
            if ($elem) {
                // Attach validations
                foreach ($field['validations'] ?? [] as $v) {
                    self::attachValidationToElement($form, $elem, $v);
                }
                $form->addElement($elem);
            }
        }

        return $form;
    }

    /**
     * Parses the YAML file and returns the config array.
     */
    public static function loadConfig(string $yamlPath): array
    {
        $fullPath = $yamlPath;
        if (!str_starts_with($yamlPath, '/') && !str_contains($yamlPath, ':')) {
            // Try relative to SPP_BASE_DIR first (framework-centric)
            $fullPath = SPP_BASE_DIR . '/' . ltrim($yamlPath, '/');
            if (!file_exists($fullPath)) {
                // Then try relative to SPP_APP_DIR (app-centric)
                $fullPath = SPP_APP_DIR . '/' . ltrim($yamlPath, '/');
            }
        }

        if (!file_exists($fullPath)) {
            throw new \SPP\SPPException("Form definition not found: " . $yamlPath);
        }

        return Yaml::parseFile($fullPath) ?? [];
    }

    /**
     * Map YAML field type to SPPViewForm element classes.
     */
    public static function buildElement(array $field): ?ViewTag
    {
        $name = $field['name'] ?? null;
        if (!$name) return null;

        $type = $field['type'] ?? 'input';
        $subType = $field['inputtype'] ?? 'text';

        // Resolve pre-populated value if source is defined
        $resolvedValue = $field['value'] ?? null;
        if (isset($field['default_source'])) {
            $resolvedValue = self::resolveDataSource($field['default_source']);
        }

        $elem = null;
        switch ($type) {
            case 'input':
                if ($subType === 'password') {
                    $elem = new SPPViewForm_Input_Password($name);
                } elseif ($subType === 'submit') {
                    $elem = new SPPViewForm_Input_Submit($name);
                } elseif ($subType === 'checkbox') {
                    $elem = new SPPViewForm_Input_Checkbox($name);
                } elseif ($subType === 'radio') {
                    $elem = new SPPViewForm_Input_Radio($name);
                } else {
                    $elem = new SPPViewForm_Input_Text($name);
                }
                break;

            case 'text':
                $elem = new SPPViewForm_Input_Text($name);
                break;

            case 'password':
                $elem = new SPPViewForm_Input_Password($name);
                break;

            case 'email':
                $elem = new SPPViewForm_Input_Text($name);
                $elem->setAttribute('type', 'email');
                break;

            case 'multiselect':
            case 'select':
                $optsSource = $field['source'] ?? $field['options_source'] ?? null;
                $elem = new SPPViewForm_Select($name);
                $options = [];

                if (isset($optsSource)) {
                    // Shorthand OR explicit SQL source
                    $options = self::resolveDataSource($optsSource);
                } else {
                    $options = $field['options'] ?? [];
                }

                if (is_array($options)) {
                    foreach ($options as $key => $opt) {
                        // Handle both [ {value:x, label:y} ] and { value: label } formats
                        if (is_array($opt)) {
                            $label = $opt['label'] ?? $opt['value'] ?? $opt['text'] ?? $key;
                            $val = $opt['value'] ?? $opt['id'] ?? $key;
                        } else {
                            $val = $key;
                            $label = $opt;
                        }
                        $elem->addOption($label, $val, !empty($opt['selected']));
                    }
                }
                
                if ($type === 'multiselect') {
                    $elem->setAttribute('multiple', 'multiple');
                }
                break;

            case 'textarea':
                $elem = new SPPViewForm_TextArea($name);
                break;
        }

        if ($elem) {
            if (isset($field['label']))       $elem->setAttribute('label', $field['label']);
            if (isset($field['placeholder'])) $elem->setAttribute('placeholder', $field['placeholder']);
            if ($resolvedValue !== null)      $elem->setAttribute('value', $resolvedValue);
            if (isset($field['class']))       $elem->addClass($field['class']);
        }

        return $elem;
    }

    /**
     * Resolves a data source (SQL, Callback, or Expression) to a value or array of options.
     */
    public static function resolveDataSource(array $src)
    {
        $type = $src['type'] ?? 'static';
        if (isset($src['table']) || isset($src['tablename'])) {
            $type = 'sql';
        }
        
        // Resolve parameters if they use the expr: prefix
        $params = $src['params'] ?? [];
        foreach ($params as $k => $v) {
            if (is_string($v) && str_starts_with($v, 'expr:')) {
                $params[$k] = self::evaluateExpression(substr($v, 5));
            }
        }

        switch ($type) {
            case 'sql':
                $db = new \SPPMod\SPPDB\SPPDB();
                $query = $src['query'] ?? null;
                $table = $src['table'] ?? $src['tablename'] ?? null;

                if (!$query && $table) {
                    // Shorthand logic
                    if (str_starts_with(strtoupper(ltrim($table)), 'SELECT')) {
                        // Table field contains a complete query
                        $query = $table;
                    } else {
                        $valFld = $src['value_field'] ?? $src['id'] ?? $src['value'] ?? 'id';
                        $lblFld = $src['label_field'] ?? $src['name'] ?? $src['text'] ?? 'name';
                        $condition = $src['condition'] ?? $src['conditions'] ?? $src['where'] ?? '';

                        $query = "SELECT {$valFld} as value, {$lblFld} as label FROM " . \SPPMod\SPPDB\SPPDB::sppTable($table);
                        
                        if (!empty($condition)) {
                            if (is_array($condition)) {
                                $condition = implode(' AND ', $condition);
                            }
                            $query .= " WHERE " . $condition;
                        }
                    }
                }

                if (!$query) return null;

                // Execute the query
                $result = $db->execute_query($query, $params);
                
                // Format results as standard label/value pairs for the Select element
                $formatted = [];
                foreach ($result as $row) {
                    if (isset($row['value']) && isset($row['label'])) {
                        $formatted[] = ['value' => $row['value'], 'label' => $row['label']];
                    } else {
                        // Fallback: use first and second columns as value and label
                        $vals = array_values($row);
                        $formatted[] = [
                            'value' => $vals[0] ?? null,
                            'label' => $vals[1] ?? ($vals[0] ?? null)
                        ];
                    }
                }
                return $formatted;

            case 'callback':
                $method = $src['method'] ?? null;
                if ($method && is_callable($method)) {
                    return call_user_func_array($method, $params);
                }
                break;

            case 'static':
                return $src['value'] ?? null;
        }

        return null;
    }

    /**
     * Safely evaluates a class::method() expression.
     */
    private static function evaluateExpression(string $expr)
    {
        if (str_contains($expr, '::')) {
            if (is_callable($expr)) {
                return call_user_func($expr);
            }
        }
        return $expr;
    }

    /**
     * Maps YAML validation config to SPP validator instances and attaches them.
     */
    private static function attachValidationToElement(ViewForm $form, ViewTag $elem, array $vConfig): void
    {
        $type = $vConfig['type'] ?? null;
        if (!$type) return;

        $msg = $vConfig['message'] ?? 'Validation failed';
        $errHolder = $vConfig['errorholder'] ?? ($elem->getAttribute('id') . '_error');
        $event = $vConfig['event'] ?? 'onblur';

        $validator = null;
        switch ($type) {
            case 'required':
                $validator = new SPP_Validator_RequiredValidator($elem, $errHolder, $msg);
                break;
            case 'numeric':
                $validator = new SPP_Validator_NumericValidator($elem, $errHolder, $msg);
                break;
            // Add more mappings as needed...
        }

        if ($validator) {
            $form->addValidator($validator);
            $form->attachValidator($validator, $elem, $event, $errHolder, $msg);
        }
    }

    /**
     * Static utility to validate raw data against a YAML form definition.
     * Returns ['valid' => bool, 'errors' => [field => message]]
     */
    public static function validate(string $yamlPath, array $data): array
    {
        $fullPath = (str_starts_with($yamlPath, '/') || str_contains($yamlPath, ':')) 
            ? $yamlPath 
            : SPP_APP_DIR . '/' . ltrim($yamlPath, '/');

        $config = Yaml::parseFile($fullPath);
        $errors = [];
        $isValid = true;

        foreach ($config['fields'] ?? [] as $field) {
            $name = $field['name'];
            $val = $data[$name] ?? null;

            foreach ($field['validations'] ?? [] as $vCfg) {
                // We use a temporary element to satisfy validator constructor
                $tempElem = new ViewTag('input', $name);
                $validator = null;
                
                switch ($vCfg['type']) {
                    case 'required':
                        $validator = new SPP_Validator_RequiredValidator($tempElem);
                        break;
                    case 'numeric':
                        $validator = new SPP_Validator_NumericValidator($tempElem);
                        break;
                }

                if ($validator && !$validator->validate($val)) {
                    $errors[$name] = $vCfg['message'] ?? 'Invalid value';
                    $isValid = false;
                    break; // stop on first error for this field
                }
            }
        }

        return ['valid' => $isValid, 'errors' => $errors];
    }
}
