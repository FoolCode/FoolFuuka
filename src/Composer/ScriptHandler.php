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

        $event->getIO->write('Installing assets.');
        foreach ($options['assets'] as $package => $path) {
            $pkgDir = $rootDir.'/'.$path;
            $webDir = $rootDir.'/'.$options['foolfuuka-web-dir'].'/foolfuuka/'.$package;

            if (file_exists($webDir)) {
                Util::delete($webDir);
            }

            if (is_dir($pkgDir)) {
                @mkdir($webDir, 0655, true);
            } else {
                @mkdir($webDir.'/../', 0655, true);
            }

            Util::copy($pkgDir, $webDir);
        }
    }
}
