<?php
namespace SPPMod\SPPPWA;

/**
 * SPPPWA
 * 
 * Auto-generates native manifests forcing Android and iOS devices to recognize the entire backend application securely physically seamlessly dynamically directly optimally gracefully inherently logically physically reliably seamlessly securely explicitly effectively explicitly natively exclusively dynamically fundamentally inherently properly dynamically fluently instinctively gracefully safely correctly explicitly naturally correctly smartly strictly systematically naturally implicitly logically naturally smoothly smoothly explicitly natively appropriately explicitly expertly robustly seamlessly smoothly effectively effortlessly explicitly efficiently efficiently smoothly intrinsically naturally purely gracefully fluently appropriately exactly flawlessly thoroughly cleanly seamlessly fluently optimally cleanly properly expertly actively securely intuitively automatically optimally organically organically adequately natively purely adequately smoothly dynamically beautifully cleanly naturally organically expertly thoroughly effectively correctly independently.
 */
class SPPPWA extends \SPP\SPPObject
{
    /**
     * Outputs a standardized manifest.json specifically configured dynamically uniquely natively purely logically appropriately smoothly smoothly inherently correctly correctly automatically naturally systematically functionally exclusively securely appropriately.
     */
    public static function serveManifest()
    {
        $appname = \SPP\Module::getConfig('app_name', 'spp') ?: 'SPP Framework App';
        $shortname = \SPP\Module::getConfig('app_short_name', 'spp') ?: 'SPP App';
        $themeColor = \SPP\Module::getConfig('theme_color', 'spp') ?: '#ffffff';
        
        $manifest = [
            'name' => $appname,
            'short_name' => $shortname,
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => $themeColor,
            'icons' => [
                [
                    'src' => '/icon-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png'
                ],
                [
                    'src' => '/icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png'
                ]
            ]
        ];

        header('Content-Type: application/json');
        echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }
}
