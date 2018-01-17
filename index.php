<?php

/*
 * Coupons addon for Bear Framework
 * https://github.com/bearcms/coupons-bearframework-addon
 * Copyright (c) 2018 Amplilabs Ltd.
 * Free to use under the MIT license.
 */

use BearFramework\App;

$app = App::get();
$context = $app->context->get(__FILE__);

$context->classes
        ->add('BearCMS\BearFrameworkAddons\Coupons', 'classes/Coupons.php')
        ->add('BearCMS\BearFrameworkAddons\Coupons\Coupon', 'classes/Coupons/Coupon.php');

$app->shortcuts
        ->add('coupons', function() {
            return new \BearCMS\BearFrameworkAddons\Coupons();
        });
