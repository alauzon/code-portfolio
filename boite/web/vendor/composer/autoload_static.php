<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitcf2a6b27d9e9e8de6232a19a8ae7c594
{
    public static $files = array (
        'decc78cc4436b1292c6c0d151b19445c' => __DIR__ . '/..' . '/phpseclib/phpseclib/phpseclib/bootstrap.php',
    );

    public static $prefixLengthsPsr4 = array (
        'p' => 
        array (
            'phpseclib3\\' => 11,
        ),
        'P' => 
        array (
            'ParagonIE\\ConstantTime\\' => 23,
        ),
        'D' => 
        array (
            'Davidearl\\WebAuthn\\' => 19,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'phpseclib3\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpseclib/phpseclib/phpseclib',
        ),
        'ParagonIE\\ConstantTime\\' => 
        array (
            0 => __DIR__ . '/..' . '/paragonie/constant_time_encoding/src',
        ),
        'Davidearl\\WebAuthn\\' => 
        array (
            0 => __DIR__ . '/..' . '/davidearl/webauthn/WebAuthn',
        ),
    );

    public static $prefixesPsr0 = array (
        'C' => 
        array (
            'CBOR' => 
            array (
                0 => __DIR__ . '/..' . '/2tvenom/cborencode/src',
            ),
        ),
    );

    public static $classMap = array (
        'Boite\\Authenticator' => __DIR__ . '/../..' . '/Boite/Authenticator.php',
        'Boite\\CSRFProtector' => __DIR__ . '/../..' . '/Boite/CSRFProtector.php',
        'Boite\\Database' => __DIR__ . '/../..' . '/Boite/Database.php',
        'Boite\\Logger' => __DIR__ . '/../..' . '/Boite/Logger.php',
        'Boite\\PasswordManager' => __DIR__ . '/../..' . '/Boite/PasswordManager.php',
        'Boite\\RateLimiter' => __DIR__ . '/../..' . '/Boite/RateLimiter.php',
        'Boite\\Registrar' => __DIR__ . '/../..' . '/Boite/Registrar.php',
        'Boite\\SessionManager' => __DIR__ . '/../..' . '/Boite/SessionManager.php',
        'Boite\\TemplateRenderer' => __DIR__ . '/../..' . '/Boite/TemplateRenderer.php',
        'Boite\\User' => __DIR__ . '/../..' . '/Boite/User.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitcf2a6b27d9e9e8de6232a19a8ae7c594::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitcf2a6b27d9e9e8de6232a19a8ae7c594::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInitcf2a6b27d9e9e8de6232a19a8ae7c594::$prefixesPsr0;
            $loader->classMap = ComposerStaticInitcf2a6b27d9e9e8de6232a19a8ae7c594::$classMap;

        }, null, ClassLoader::class);
    }
}
