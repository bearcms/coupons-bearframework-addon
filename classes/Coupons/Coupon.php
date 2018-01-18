<?php

/*
 * Coupons addon for Bear Framework
 * https://github.com/bearcms/coupons-bearframework-addon
 * Copyright (c) 2018 Amplilabs Ltd.
 * Free to use under the MIT license.
 */

namespace BearCMS\BearFrameworkAddons\Coupons;

/**
 * @property ?string $id
 * @property ?string $typeID
 * @property ?string $discount
 * @property ?int $startDate
 * @property ?int $endDate
 * @property ?int $usageLimit
 * @property ?int $usageCount
 */
class Coupon
{

    use \IvoPetkov\DataObjectTrait;
    use \IvoPetkov\DataObjectToArrayTrait;
    use \IvoPetkov\DataObjectToJSONTrait;

    function __construct()
    {
        $this->defineProperty('id', [
            'type' => '?string'
        ]);
        $this->defineProperty('typeID', [
            'type' => '?string'
        ]);
        $this->defineProperty('discount', [
            'type' => '?string'
        ]);
        $this->defineProperty('startDate', [
            'type' => '?int'
        ]);
        $this->defineProperty('endDate', [
            'type' => '?int'
        ]);
        $this->defineProperty('usageLimit', [
            'type' => '?int'
        ]);
        $this->defineProperty('usageCount', [
            'type' => '?int'
        ]);
    }

}
