<?php
$baseDir = dirname(dirname(__FILE__));
return [
    'plugins' => [
        'Bake' => $baseDir . '/vendor/cakephp/bake/',
        'DebugKit' => $baseDir . '/vendor/cakephp/debug_kit/',
        'FriendsOfCake/Fixturize' => $baseDir . '/vendor/friendsofcake/fixturize/',
        'JeremyHarris/LazyLoad' => $baseDir . '/vendor/jeremyharris/cakephp-lazyload/',
        'Migrations' => $baseDir . '/vendor/cakephp/migrations/',
        'Muffin/Webservice' => $baseDir . '/vendor/muffin/webservice/',
        'Tresorg' => $baseDir . '/plugins/Tresorg/',
        'Tresorg/PaypalButtons' => $baseDir . '/vendor/tresorg/cakephp-paypalbuttons/',
        'Xety/Cake3CookieAuth' => $baseDir . '/vendor/xety/cake3-cookieauth/',
        'Xety/Cake3Upload' => $baseDir . '/vendor/xety/cake3-upload/'
    ]
];