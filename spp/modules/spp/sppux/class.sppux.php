<?php

namespace SPPMod\SPPUX;

/**
 * SPP-UX module facade.
 *
 * Provides shared helpers for registering the native SPP frontend runtime
 * and rendering mount points for application components.
 */
class SPPUX extends \SPP\SPPObject
{
    private static function appBaseUri(): string
    {
        return defined('APP_BASE_URI') ? APP_BASE_URI : '';
    }

    private static function toAppUri(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        if ($path === '' || preg_match('/^https?:\/\//i', $path) || str_starts_with($path, '/') || str_starts_with($path, './') || str_starts_with($path, '../')) {
            return $path;
        }

        return rtrim(self::appBaseUri(), '/') . '/' . ltrim($path, '/');
    }

    public static function runtimePath(?string $appname = null): string
    {
        $value = \SPP\Module::getConfig('runtime_path', 'sppux', $appname);
        return self::toAppUri($value ?: 'spp/modules/spp/sppux/js/sppux.js');
    }

    public static function loaderPath(?string $appname = null): string
    {
        $value = \SPP\Module::getConfig('loader_path', 'sppux', $appname);
        return self::toAppUri($value ?: 'spp/modules/spp/sppux/js/spp-loader.js');
    }

    public static function componentBase(?string $appname = null): string
    {
        $appname = $appname ?: \SPP\Scheduler::getContext();
        $value = \SPP\Module::getConfig('component_base', 'sppux', $appname);
        $value = $value ?: 'src/{app}/comp';
        return self::toAppUri(str_replace('{app}', $appname, $value));
    }

    public static function componentPath(string $name, ?string $appname = null): string
    {
        $name = trim($name);
        if ($name === '') {
            throw new \SPP\SPPException('SPP-UX component name cannot be empty.');
        }

        if (preg_match('/^https?:\/\//i', $name) || str_starts_with($name, '/') || str_starts_with($name, './') || str_starts_with($name, '../')) {
            return $name;
        }

        return rtrim(self::componentBase($appname), '/') . '/' . ltrim($name, '/') . '.js';
    }

    public static function registerAssets(?string $appname = null): void
    {
        if (!class_exists('\SPPMod\SPPView\ViewPage')) {
            return;
        }

        \SPPMod\SPPView\ViewPage::addJsIncludeFile(self::runtimePath($appname));

        $autoMount = \SPP\Module::getConfig('auto_mount', 'sppux', $appname);
        if ($autoMount === false || $autoMount === 'false' || $autoMount === '0') {
            return;
        }

        \SPPMod\SPPView\ViewPage::addJsIncludeFile(self::loaderPath($appname), ['type' => 'module']);
    }

    public static function registerBridge(?string $appname = null): void
    {
        if (!class_exists('\SPPMod\SPPView\ViewPage')) {
            return;
        }

        $exposeBridge = \SPP\Module::getConfig('expose_bridge', 'sppux', $appname);
        if ($exposeBridge === false || $exposeBridge === 'false' || $exposeBridge === '0') {
            return;
        }

        \SPPMod\SPPView\ViewPage::addJsContent(<<<'JS'
window.spp_admin = window.spp_admin || {
    api: async (action, data = {}) => {
        const params = new URLSearchParams({ action, ...data });
        const response = await fetch('api.php?' + params.toString(), { credentials: 'same-origin' });
        return response.json();
    },
    apiPost: async (actionOrFormData, data = {}) => {
        const formData = actionOrFormData instanceof FormData ? actionOrFormData : new FormData();
        if (!(actionOrFormData instanceof FormData)) {
            formData.append('action', actionOrFormData);
            Object.entries(data).forEach(([key, value]) => formData.append(key, value));
        }
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        return response.json();
    },
    callAppService: async (name, params = {}) => {
        const formData = new FormData();
        formData.append('params', JSON.stringify(params));
        const response = await fetch(`?__spa=1&__svc=${encodeURIComponent(name)}`, {
            method: 'POST',
            headers: { 'X-SPP-Ajax': '1' },
            body: formData,
            credentials: 'same-origin'
        });
        const result = await response.json();
        if (result.status === 'ok' || result.success === true) {
            return result.data !== undefined ? result.data : result;
        }
        throw new Error(result.message || result.data?.message || 'SPP service call failed.');
    }
};
JS);
    }

    public static function boot(?string $appname = null): void
    {
        self::registerAssets($appname);
        self::registerBridge($appname);
    }

    public static function component(string $name, array $props = [], ?string $appname = null): string
    {
        $path = self::componentPath($name, $appname);
        $propsJson = htmlspecialchars(json_encode($props), ENT_QUOTES, 'UTF-8');
        $pathAttr = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');

        return "<div data-spp-component=\"1\" data-spp-type=\"ux\" data-spp-path=\"{$pathAttr}\" data-spp-props=\"{$propsJson}\"></div>";
    }

    public static function render(string $name, array $props = [], ?string $appname = null): void
    {
        echo self::component($name, $props, $appname);
    }
}
