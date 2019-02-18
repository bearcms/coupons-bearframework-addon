<?php

/*
 * Coupons addon for Bear Framework
 * https://github.com/bearcms/coupons-bearframework-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

namespace BearCMS\BearFrameworkAddons\Coupons;

/**
 * @property ?string $id
 * @property ?string $typeID
 * @property ?string $value
 * @property ?int $startDate
 * @property ?int $endDate
 * @property ?int $usageLimit
 * @property array $usage
 * @property array $data
 */
class Coupon
{

    use \IvoPetkov\DataObjectTrait;
    use \IvoPetkov\DataObjectToArrayTrait;
    use \IvoPetkov\DataObjectToJSONTrait;

    function __construct()
    {
        $this
                ->defineProperty('id', [
                    'type' => '?string'
                ])
                ->defineProperty('typeID', [
                    'type' => '?string'
                ])
                ->defineProperty('value', [
                    'type' => '?string'
                ])
                ->defineProperty('startDate', [
                    'type' => '?int'
                ])
                ->defineProperty('endDate', [
                    'type' => '?int'
                ])
                ->defineProperty('usageLimit', [
                    'type' => '?int'
                ])
                ->defineProperty('usage', [
                    'type' => 'array'
                ])
                ->defineProperty('data', [
                    'type' => 'array'
                ])
        ;
    }

}
