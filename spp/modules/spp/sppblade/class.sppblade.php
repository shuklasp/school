<?php

namespace SPPMod\SPPBlade;

use eftec\bladeone\BladeOne;

/**
 * Class SPPBlade
 * Wrapper for BladeOne engine in SPP.
 */
class SPPBlade extends \SPP\SPPObject
{
    protected ?BladeOne $engine = null;
    protected string $viewsPath;
    protected string $cachePath;

    public function __construct()
    {
        $app = \SPP\App::getApp();
        $appName = $app->getName();

        // Resolve paths relative to app directory
        $this->viewsPath = SPP_APP_DIR . '/resources/' . $appName . '/views';
        $this->cachePath = SPP_APP_DIR . '/var/cache/' . $appName . '/blade';

        $this->ensureDirectories();

        $mode = \SPP\Module::getConfig('mode', 'sppblade') ?: BladeOne::MODE_AUTO;
        
        $this->engine = new BladeOne($this->viewsPath, $this->cachePath, (int)$mode);

        $this->registerDirectives();
    }

    /**
     * Register custom SPP Blade directives.
     */
    protected function registerDirectives(): void
    {
        // @sppform('login')
        // Loads form from XML/YAML in the app's forms directory
        $this->engine->directive('sppform', function ($expression) {
            if (empty($expression)) return "";
            return "<?php 
                \$appName = \SPP\Scheduler::getContext();
                \$fname = str_replace(['\'', '\"'], '', $expression);
                \$baseDir = SPP_APP_DIR . '/etc/apps/' . \$appName . '/forms/';
                
                \$formFile = null;
                foreach (['yml', 'yaml', 'xml'] as \$ext) {
                    if (file_exists(\$baseDir . \$fname . '.' . \$ext)) {
                        \$formFile = \$baseDir . \$fname . '.' . \$ext;
                        break;
                    }
                }

                if (\$formFile) {
                    \SPPMod\SPPView\ViewPage::readFormFile(\$formFile);
                }
            ?>";
        });

        // @sppform_start('login')
        $this->engine->directive('sppform_start', function ($expression) {
            if (empty($expression)) return "";
            return "<?php 
                \$forms = \SPPMod\SPPView\ViewPage::getFormsList();
                \$fname = str_replace(['\'', '\"'], '', $expression);
                if (isset(\$forms[\$fname])) {
                    \$forms[\$fname]->startForm();
                }
            ?>";
        });

        // @sppform_end
        $this->engine->directive('sppform_end', function () {
            return "<?php 
                \$forms = \SPPMod\SPPView\ViewPage::getFormsList();
                \$activeForm = end(\$forms); // Simplification: get last registered form
                if (\$activeForm) \$activeForm->endForm();
            ?>";
        });

        // @sppelement('username', ['class' => 'form-control'])
        $this->engine->directive('sppelement', function ($expression) {
            return "<?php 
                \$args = [$expression];
                \$elemId = \$args[0];
                \$attrs = \$args[1] ?? [];
                
                \$forms = \SPPMod\SPPView\ViewPage::getFormsList();
                foreach (\$forms as \$form) {
                    \$elements = \$form->get('element');
                    if (isset(\$elements[\$elemId])) {
                        \$el = \$elements[\$elemId];
                        // If attributes are passed, try to apply them if the element supports it
                        if (!empty(\$attrs) && method_exists(\$el, 'setAttributes')) {
                            \$el->setAttributes(\$attrs);
                        }
                        echo \$el->getHTML();
                        break;
                    }
                }
            ?>";
        });
        // @sppauth
        $this->engine->directive('sppauth', function () {
            return "<?php if (\SPPMod\SPPAuth\SPPAuth::authSessionExists()): ?>";
        });

        // @sppendsppauth
        $this->engine->directive('endsppauth', function () {
            return "<?php endif; ?>";
        });

        // @sppguest
        $this->engine->directive('sppguest', function () {
            return "<?php if (!\SPPMod\SPPAuth\SPPAuth::authSessionExists()): ?>";
        });

        // @sppendsppguest
        $this->engine->directive('endsppguest', function () {
            return "<?php endif; ?>";
        });
        // @sppbind($entity)
        $this->engine->directive('sppbind', function ($expression) {
            if (empty($expression)) return "";
            return "<?php 
                \$forms = \SPPMod\SPPView\ViewPage::getFormsList();
                \$activeForm = end(\$forms);
                if (\$activeForm && isset($expression)) {
                    \$activeForm->bind($expression);
                }
            ?>";
        });
        // @react('MyComponent', ['prop' => 'value'])
        $this->engine->directive('react', function ($expression) {
            if (empty($expression)) return "";
            return "<?php 
                \$args = [$expression];
                \$name = \$args[0];
                \$props = json_encode(\$args[1] ?? []);
                \$context = \SPP\Scheduler::getContext();
                \$path = \"/resources/{\$context}/js/react/{\$name}.js\";
                echo \"<div data-spp-component='1' data-spp-type='react' data-spp-path='{\$path}' data-spp-props='{\$props}'></div>\";
            ?>";
        });

        // @vue('MyComponent', ['prop' => 'value'])
        $this->engine->directive('vue', function ($expression) {
            if (empty($expression)) return "";
            return "<?php 
                \$args = [$expression];
                \$name = \$args[0];
                \$props = json_encode(\$args[1] ?? []);
                \$context = \SPP\Scheduler::getContext();
                \$path = \"/resources/{\$context}/js/vue/{\$name}.js\";
                echo \"<div data-spp-component='1' data-spp-type='vue' data-spp-path='{\$path}' data-spp-props='{\$props}'></div>\";
            ?>";
        });

        // @sppux('ComponentName', ['prop' => 'value'])
        $this->engine->directive('sppux', function ($expression) {
            if (empty($expression)) return "";
            return "<?php 
                \$args = [$expression];
                \$name = \$args[0];
                \$props = \$args[1] ?? [];
                if (class_exists('\\\\SPPMod\\\\SPPUX\\\\SPPUX')) {
                    echo \SPPMod\SPPUX\SPPUX::component(\$name, \$props);
                } else {
                    \$propsJson = htmlspecialchars(json_encode(\$props), ENT_QUOTES, 'UTF-8');
                    \$context = \SPP\Scheduler::getContext();
                    \$appBaseUri = defined('APP_BASE_URI') ? APP_BASE_URI : '';
                    \$path = rtrim(\$appBaseUri, '/') . \"/src/{\$context}/comp/{\$name}.js\";
                    echo \"<div data-spp-component='1' data-spp-type='ux' data-spp-path='{\$path}' data-spp-props='{\$propsJson}'></div>\";
                }
            ?>";
        });

        // @drupal_node(123)
        $this->engine->directive('drupal_node', function ($expression) {
            return "<?php 
                \$drupal = \SPP\App::getApp()->make('drupal');
                \$node = \$drupal->getNode((int)$expression);
                if (\$node) {
                    echo \"<div class='drupal-node'>\";
                    echo \"<h3>\" . \$node->getTitle() . \"</h3>\";
                    echo \$node->get('body')->view('full');
                    echo \"</div>\";
                }
            ?>";
        });

        // @drupal_view('recent_news', 'block_1')
        $this->engine->directive('drupal_view', function ($expression) {
            return "<?php 
                \$drupal = \SPP\App::getApp()->make('drupal');
                if (\$drupal->bootstrap()) {
                    \$args = [$expression];
                    \$view = \Drupal\views\Views::getView(\$args[0]);
                    if (\$view) {
                        \$view->setDisplay(\$args[1] ?? 'default');
                        echo \$view->render();
                    }
                }
            ?>";
        });
    }

    /**
     * Render a blade template.
     * 
     * @param string $view
     * @param array $data
     * @return string
     */
    public function render(string $view, array $data = []): string
    {
        return $this->engine->run($view, $data);
    }

    /**
     * Ensure views and cache directories exist.
     */
    protected function ensureDirectories(): void
    {
        if (!is_dir($this->viewsPath)) {
            mkdir($this->viewsPath, 0777, true);
        }
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }
    }

    /**
     * Get the underlying engine instance.
     */
    public function getEngine(): BladeOne
    {
        return $this->engine;
    }
}
