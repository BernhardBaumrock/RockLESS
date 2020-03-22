<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit1f72a053e23dd9595ce8be831d0a4ccd
{
    public static $prefixesPsr0 = array (
        'L' => 
        array (
            'Less' => 
            array (
                0 => __DIR__ . '/..' . '/wikimedia/less.php/lib',
            ),
        ),
    );

    public static $classMap = array (
        'lessc' => __DIR__ . '/..' . '/wikimedia/less.php/lessc.inc.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInit1f72a053e23dd9595ce8be831d0a4ccd::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit1f72a053e23dd9595ce8be831d0a4ccd::$classMap;

        }, null, ClassLoader::class);
    }
}
