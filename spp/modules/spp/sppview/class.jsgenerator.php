<?php

namespace SPPMod\SPPView;

/**
 * class JSGenerator
 * 
 * Transpiles PHPComponents into SPP-UX JavaScript components.
 * 
 * @author Satya Prakash Shukla
 */
class JSGenerator extends \SPP\SPPObject
{
    /**
     * Generates JavaScript code for a given PHPComponent class.
     * 
     * @param string $className Full class name including namespace
     * @return string JavaScript component definition
     */
    public static function generate(string $className): string
    {
        if (!class_exists($className)) {
            throw new \SPP\SPPException("Class '{$className}' not found for JS generation.");
        }

        $reflect = new \ReflectionClass($className);
        if (!$reflect->isSubclassOf(PHPComponent::class)) {
            throw new \SPP\SPPException("'{$className}' must extend SPPMod\SPPView\PHPComponent.");
        }

        $shortName = $reflect->getShortName();
        $instance = $reflect->newInstanceWithoutConstructor();

        // 1. Extract Initial State
        $stateProp = $reflect->getProperty('state');
        $stateProp->setAccessible(true);
        $initialState = $stateProp->getValue($instance);

        // 2. Extract Render Template
        // We call a temporary instance to get the template
        // Note: In a real transpiler, we might parse the AST, but for this framework
        // we'll execute a dry-run or provide a static template property.
        $template = $instance->render();

        // 3. Extract Actions (Methods)
        $actions = [];
        foreach ($reflect->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() === $className) {
                $actions[] = $method->getName();
            }
        }

        // 4. Build JS class
        $js = "/**\n * Generated SPP-UX Component: {$shortName}\n";
        $js .= " * Source: {$className}\n */\n\n";
        $js .= "export default class {$shortName} extends BaseComponent {\n";
        
        // Initial State
        $js .= "    async onInit() {\n";
        $js .= "        this.state = " . json_encode($initialState, JSON_PRETTY_PRINT) . ";\n";
        $js .= "    }\n\n";

        // Methods / Actions
        foreach ($actions as $action) {
            if ($action === 'render' || $action === 'onInit') continue;
            
            $js .= "    async {$action}(data = {}) {\n";
            $js .= "        return await this.callServer('{$action}', data);\n";
            $js .= "    }\n\n";
        }

        // Render Method
        // We convert PHP template variables or logic here if needed,
        // but for now, we'll assume the PHP render returns a template string.
        $js .= "    render() {\n";
        $js .= "        const { " . implode(', ', array_keys($initialState)) . " } = this.state;\n";
        $js .= "        return html`" . self::escapeTemplate($template) . "`;\n";
        $js .= "    }\n";
        $js .= "}\n";

        return $js;
    }

    /**
     * Escapes backticks and handles basic variable replacement in templates.
     */
    private static function escapeTemplate(string $tpl): string
    {
        $tpl = str_replace('`', '\`', $tpl);
        // Replace {$var} or {var} with ${var} for lit-html compatibility
        return preg_replace('/\{(\$?[a-zA-Z0-9_]+)\}/', '${\1}', $tpl);
    }
}
