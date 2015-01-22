<?php

namespace Foolz\FoolFuuka\Composer;

use Composer\Script\CommandEvent;
use Foolz\Foolframe\Model\Util;

class ScriptHandler
{
    private static $options = [
        'foolfuuka-app-dir' => 'app',
        'foolfuuka-web-dir' => 'public',
        'assets' => []
    ];

    protected static function getOptions(CommandEvent $event)
    {
        return array_merge(self::$options, $event->getComposer()->getPackage()->getExtra());

    }

    public static function installAssets(CommandEvent $event)
    {
        $options = self::getOptions($event);
        $rootDir = realpath(__DIR__.'/../../');

        if (!$event->getIO()->askConfirmation('Would you like to install the third-party assets? [y/N] ', false)) {
            return;
        }

        $event->getIO()->write('Installing third-party assets.');
        foreach ($options['assets'] as $package => $path) {
            $pkgDir = $rootDir.'/'.$path;
            $webDir = $rootDir.'/'.$options['foolfuuka-web-dir'].'/foolfuuka/'.$package;

            $event->getIO()->write('+ '.$package);
            if (file_exists($webDir)) {
                Util::delete($webDir);
            }

            if (is_dir($pkgDir)) {
                @mkdir($webDir, 0755, true);
            } else {
                @mkdir($webDir.'/../', 0755, true);
            }

            Util::copy($pkgDir, $webDir);
        }
        $event->getIO()->write('Finished installing third-party assets.');
    }
}
