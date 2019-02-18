<?php

/*
 * Coupons addon for Bear Framework
 * https://github.com/bearcms/coupons-bearframework-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

use BearFramework\App;

$app = App::get();
$context = $app->contexts->get(__FILE__);

$context->classes
        ->add('BearCMS\BearFrameworkAddons\Coupons', 'classes/Coupons.php')
        ->add('BearCMS\BearFrameworkAddons\Coupons\*', 'classes/Coupons/*.php');

$app->shortcuts
        ->add('coupons', function() {
            return new \BearCMS\BearFrameworkAddons\Coupons();
        });

$app->localization
        ->addDictionary('en', function() use ($context) {
            return include $context->dir . '/locales/en.php';
        })
        ->addDictionary('bg', function() use ($context) {
            return include $context->dir . '/locales/bg.php';
        });
