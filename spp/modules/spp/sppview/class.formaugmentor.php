<?php

namespace SPPMod\SPPView;

/**
 * class FormAugmentor
 * 
 * Scans captured HTML for <form> tags and "augments" them with validation logic,
 * SPA routing attributes, and pre-populated data defined in YAML.
 * 
 * @author Satya Prakash Shukla
 */
class FormAugmentor extends \SPP\SPPObject
{
    /**
     * Augments HTML with YAML-defined form logic and optional script injection.
     * 
     * @param string $html Original HTML output
     * @param array $scripts Optional list of JS files to inject
     * @return string Augmented HTML
     */
    public static function augment(string $html, array $scripts = []): string
    {
        // Performance optimization: skip if no form is present AND no scripts to inject
        if (stripos($html, '<form') === false && empty($scripts)) {
            return $html;
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        // Load with UTF-8 support and handle fragments gracefully
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        $forms = $dom->getElementsByTagName('form');
        $modified = false;

        foreach ($forms as $formNode) {
            /** @var \DOMElement $formNode */
            $formId = $formNode->getAttribute('id') ?: $formNode->getAttribute('name');
            if (!$formId) continue;

            try {
                // Try to find a matching YAML definition in the root etc/forms directory
                $yamlPath = APP_ETC_DIR . SPP_DS . 'forms' . SPP_DS . "{$formId}.yml";
                if (!file_exists($yamlPath)) continue;
                
                $config = ViewFormBuilder::loadConfig($yamlPath);
                if (!empty($config)) {
                    self::augmentFormNode($dom, $formNode, $config);
                    $modified = true;
                }
            } catch (\Exception $e) {
                // Skip if YAML not found or invalid
                continue;
            }
        }

        // 2. Inject scripts if any
        if (!empty($scripts)) {
            $root = $dom->getElementsByTagName('body')->item(0) ?: $dom->documentElement;
            if ($root) {
                foreach ($scripts as $src) {
                    $sNode = $dom->createElement('script');
                    $sNode->setAttribute('type', 'text/javascript');
                    $sNode->setAttribute('src', $src);
                    // Ensure the script tag is not self-closing
                    $sNode->nodeValue = ''; 
                    $root->appendChild($sNode);
                }
                $modified = true;
            }
        }

        if (!$modified) {
            libxml_clear_errors();
            return $html;
        }

        $result = $dom->saveHTML();
        libxml_clear_errors();
        
        // Remove the XML encoding tag we added for UTF-8 support
        return str_replace('<?xml encoding="utf-8" ?>', '', $result);
    }

    /**
     * Injects attributes and data into a single form node.
     */
    private static function augmentFormNode(\DOMDocument $dom, \DOMElement $formNode, array $config): void
    {
        $fConfig = $config['form'] ?? [];
        
        // 1. Inject SPA Service logic
        if (!empty($fConfig['service'])) {
            $formNode->setAttribute('data-service', $fConfig['service']);
            $resp = $fConfig['on_response'] ?? [];
            if (isset($resp['ok']))       $formNode->setAttribute('data-on-ok', $resp['ok']);
            if (isset($resp['error']))    $formNode->setAttribute('data-on-error', $resp['error']);
            if (isset($resp['redirect'])) $formNode->setAttribute('data-on-redirect', $resp['redirect']);
        }

        // 2. Map fields for validation and pre-population
        foreach ($config['fields'] ?? [] as $field) {
            $name = $field['name'] ?? null;
            if (!$name) continue;

            // Find matching input/select/textarea
            $inputNodes = self::findFieldNodes($formNode, $name);
            if (empty($inputNodes)) continue;

            // Resolve pre-populated value
            $resolvedValue = $field['value'] ?? null;
            if (isset($field['default_source'])) {
                $resolvedValue = ViewFormBuilder::resolveDataSource($field['default_source']);
            }

            foreach ($inputNodes as $node) {
                if (!$node instanceof \DOMElement) continue;
                
                // Inject Validations
                foreach ($field['validations'] ?? [] as $v) {
                    self::injectValidation($node, $v);
                }

                // Inject Value/Pre-population
                if ($resolvedValue !== null) {
                    self::injectValue($dom, $node, $field, $resolvedValue);
                }

                // Special handling for dynamic/static Select options
                if ($node->tagName === 'select' && (isset($field['options_source']) || isset($field['options']))) {
                    self::injectOptions($dom, $node, $field);
                }
            }
        }
    }

    /**
     * Finds nodes within a form that match a field name or ID.
     */
    private static function findFieldNodes(\DOMElement $formNode, string $name): array
    {
        $matching = [];
        $tags = ['input', 'select', 'textarea'];
        foreach ($tags as $tag) {
            $nodes = $formNode->getElementsByTagName($tag);
            foreach ($nodes as $node) {
                if ($node instanceof \DOMElement && ($node->getAttribute('name') === $name || $node->getAttribute('id') === $name)) {
                    $matching[] = $node;
                }
            }
        }
        return $matching;
    }

    /**
     * Injects a JS validation call into a node's event attributes.
     */
    private static function injectValidation(\DOMElement $node, array $vConfig): void
    {
        $type = $vConfig['type'] ?? null;
        if (!$type) return;

        $event = $vConfig['event'] ?? 'onblur';
        $msg = addslashes($vConfig['message'] ?? 'Validation failed');
        $errHolder = $vConfig['errorholder'] ?? ($node->getAttribute('id') . '_error');
        $id = $node->getAttribute('id') ?: $node->getAttribute('name');

        $jsFunc = '';
        switch ($type) {
            case 'required': $jsFunc = "validateRequired('{$id}', '{$msg}', '{$errHolder}')"; break;
            case 'numeric':  $jsFunc = "validateNumeric('{$id}', '{$msg}', '{$errHolder}')"; break;
            // Add more as needed...
        }

        if ($jsFunc) {
            $existing = $node->getAttribute($event);
            if ($existing) $jsFunc = rtrim($existing, ';') . '; ' . $jsFunc;
            $node->setAttribute($event, $jsFunc);
        }
    }

    /**
     * Injects a pre-populated value into an HTML element.
     */
    private static function injectValue(\DOMDocument $dom, \DOMElement $node, array $field, $value): void
    {
        $tagName = $node->tagName;
        $type = $node->getAttribute('type');

        if ($tagName === 'textarea') {
            $node->nodeValue = htmlspecialchars((string)$value);
        } elseif ($tagName === 'select') {
            // Handle select - we'll check matching options later in injectOptions or here
            $options = $node->getElementsByTagName('option');
            foreach ($options as $opt) {
                if ($opt instanceof \DOMElement && $opt->getAttribute('value') == $value) {
                    $opt->setAttribute('selected', 'selected');
                }
            }
        } elseif ($type === 'checkbox' || $type === 'radio') {
            if ($node->getAttribute('value') == $value) {
                $node->setAttribute('checked', 'checked');
            }
        } else {
            $node->setAttribute('value', $value);
        }
    }

    /**
     * Injects dynamic options into a <select> node.
     */
    private static function injectOptions(\DOMDocument $dom, \DOMElement $selectNode, array $field): void
    {
        $options = [];
        if (isset($field['options_source'])) {
            $options = ViewFormBuilder::resolveDataSource($field['options_source']);
        } else {
            $options = $field['options'] ?? [];
        }

        if (!is_array($options)) return;

        // Clear existing options? User might want to keep some. 
        // For simplicity, we clear if options (source or static) are defined in YAML.
        while ($selectNode->hasChildNodes()) {
            $selectNode->removeChild($selectNode->firstChild);
        }

        $currentValue = $selectNode->getAttribute('value') ?: ($field['value'] ?? null);

        foreach ($options as $opt) {
            $oNode = $dom->createElement('option');
            $oNode->setAttribute('value', $opt['value']);
            $oNode->nodeValue = htmlspecialchars($opt['label'] ?? $opt['value']);
            
            if ($opt['value'] == $currentValue || (!empty($opt['selected']))) {
                $oNode->setAttribute('selected', 'selected');
            }
            $selectNode->appendChild($oNode);
        }
    }
}
