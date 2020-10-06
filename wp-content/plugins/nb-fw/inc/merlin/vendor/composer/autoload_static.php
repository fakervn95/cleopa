<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb9b342a141fe10ab8e45e713a491bb68
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'ProteusThemes\\WPContentImporter2\\' => 33,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ProteusThemes\\WPContentImporter2\\' => 
        array (
            0 => __DIR__ . '/..' . '/proteusthemes/wp-content-importer-v2/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb9b342a141fe10ab8e45e713a491bb68::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb9b342a141fe10ab8e45e713a491bb68::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}