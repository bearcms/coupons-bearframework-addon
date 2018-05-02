<?php

/*
 * Coupons addon for Bear Framework
 * https://github.com/bearcms/coupons-bearframework-addon
 * Copyright (c) 2018 Amplilabs Ltd.
 * Free to use under the MIT license.
 */

namespace BearCMS\BearFrameworkAddons;

use BearFramework\App;

/**
 * 
 */
class Coupons
{

    private $cache = [];
    private $types = [];

    /**
     * 
     * @param string $id
     * @param \BearCMS\BearFrameworkAddons\callable $calculator
     * @param array $options
     */
    public function addType(string $id, callable $calculator, array $options = []): void
    {
        $this->types[$id] = [$calculator, $options];
    }

    /**
     * 
     * @param string $typeID
     * @param string $value
     * @return \BearCMS\BearFrameworkAddons\Coupons\Coupon
     */
    public function make(string $typeID = null, string $value = null): \BearCMS\BearFrameworkAddons\Coupons\Coupon
    {
        if (!isset($this->cache['coupon'])) {
            $this->cache['coupon'] = new \BearCMS\BearFrameworkAddons\Coupons\Coupon();
        }
        $coupon = clone($this->cache['coupon']);
        if ($typeID !== null) {
            $coupon->typeID = $typeID;
        }
        if ($value !== null) {
            $coupon->value = $value;
        }
        return $coupon;
    }

    /**
     * 
     * @param \BearCMS\BearFrameworkAddons\Coupons\Coupon $coupon
     */
    public function save(\BearCMS\BearFrameworkAddons\Coupons\Coupon $coupon): void
    {
        $app = App::get();
        if (strlen($coupon->id) === 0) {
            $id = base_convert(microtime(true) * 10000, 10, 36) . 'x';
            for ($i = 0; $i < 10; $i++) {
                $id .= str_replace(['x', 'o'], [rand(0, 9), rand(0, 9)], base_convert(rand(1000000, 9999999), 10, 36));
            }
            $id = strrev(implode('-', array_chunk(str_split($id, 6), 5)[0]));
            $coupon->id = $id;
        }
        $coupon->id = strtolower($coupon->id);
        $app->data->set($app->data->make($this->getDataKey($coupon->id), $coupon->toJSON()));
    }

    /**
     * 
     * @param string $id
     * @return bool
     */
    public function exists(string $id): bool
    {
        $id = strtolower($id);
        $app = App::get();
        return $app->data->exists($this->getDataKey($id));
    }

    /**
     * 
     * @param string $id
     * @return null|\BearCMS\BearFrameworkAddons\Coupons\Coupon
     */
    public function get(string $id): ?\BearCMS\BearFrameworkAddons\Coupons\Coupon
    {
        $id = strtolower($id);
        $app = App::get();
        $rawData = $app->data->getValue($this->getDataKey($id));
        if (strlen($rawData) > 0) {
            $rawData = json_decode($rawData, true);
            $coupon = $this->make();
            foreach ($rawData as $name => $value) {
                $coupon->$name = $value;
            }
            return $coupon;
        }
        return null;
    }

    /**
     * 
     * @param type $id
     * @param type $context
     */
    public function markAsUsed($id, $context = null)
    {
        $id = strtolower($id);
        $coupon = $this->get($id);
        if ($coupon instanceof \BearCMS\BearFrameworkAddons\Coupons\Coupon) {
            $usage = $coupon->usage;
            $usage[] = [
                'date' => time(),
                'context' => $context
            ];
            $coupon->usage = $usage;
            $this->save($coupon);
        }
    }

    /**
     * 
     * @param type $id
     * @return bool
     */
    public function isValid($id): bool
    {
        $id = strtolower($id);
        $coupon = $this->get($id);
        if ($coupon instanceof \BearCMS\BearFrameworkAddons\Coupons\Coupon) {
            if (is_int($coupon->usageLimit)) {
                if (sizeof($coupon->usage) >= $coupon->usageLimit) {
                    return false;
                }
            }
            if (strlen($coupon->startDate) > 0) {
                if (time() < (int) $coupon->startDate) {
                    return false;
                }
            }
            if (strlen($coupon->endDate) > 0) {
                if (time() > (int) $coupon->endDate) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 
     * @param string $couponID
     * @return string
     */
    public function getDescription(string $couponID): string
    {
        $couponID = strtolower($couponID);
        $app = App::get();
        $description = '';
        $coupon = $this->get($couponID);
        if ($coupon !== null) {
            $typeID = $coupon->typeID;
            if (isset($this->types[$typeID], $this->types[$typeID][1]['description'])) {
                $description = $this->types[$typeID][1]['description'];
                if (is_callable($description)) {
                    $description = call_user_func($description, $coupon);
                }
            }
            $description = trim((string) $description);
            $description = strlen($description) > 0 ? [$description] : [];

            if ($coupon->usageLimit > 0) {
                if ($coupon->usageLimit === 1) {
                    $description[] = __('bearcms.bearframeworkaddons.coupons.UsageLimitOne');
                } else {
                    $description[] = sprintf(__('bearcms.bearframeworkaddons.coupons.UsageLimitMoreThanOne'), $coupon->usageLimit);
                }
            }

            if (strlen($coupon->startDate) > 0 && strlen($coupon->endDate) > 0) {
                $description[] = sprintf(__('bearcms.bearframeworkaddons.coupons.Period'), $app->localization->formatDate($coupon->startDate, ['date']), $app->localization->formatDate($coupon->endDate, ['date']));
            } else {
                if (strlen($coupon->startDate) > 0) {
                    $description[] = sprintf(__('bearcms.bearframeworkaddons.coupons.StartDate'), $app->localization->formatDate($coupon->startDate, ['date']));
                }
                if (strlen($coupon->endDate) > 0) {
                    $description[] = sprintf(__('bearcms.bearframeworkaddons.coupons.EndDate'), $app->localization->formatDate($coupon->endDate, ['date']));
                }
            }
            $description = implode(' ', $description);
        }
        return $description;
    }

    /**
     * 
     * @param array $couponIDs
     * @param array $items Format: [['id'=>'item1', 'target'=>'target1', 'value'=>10], ...]
     * @return array Format: ['item1'=>DISCOUNT_VALUE, 'item2'=>DISCOUNT_VALUE]
     */
    public function getDiscount(array $couponIDs, array $items): array
    {
        $coupons = [];
        $calculators = [];
        foreach ($couponIDs as $couponID) {
            $couponID = strtolower($couponID);
            if ($this->exists($couponID) && $this->isValid($couponID)) {
                $coupon = $this->get($couponID);
                if ($coupon !== null) {
                    $typeID = $coupon->typeID;
                    if (isset($this->types[$typeID])) {
                        $calculators[] = [$coupon, $this->types[$typeID][0]];
                    }
                    $coupons[] = $coupon;
                }
            }
        }
        $discounts = [];
        foreach ($items as $itemID => $itemData) {
            $discounts[$itemID] = 0;
        }
        foreach ($calculators as $calculatorData) {
            $typeDiscounts = call_user_func($calculatorData[1], $calculatorData[0], $items);
            if (is_array($typeDiscounts)) {
                foreach ($typeDiscounts as $itemID => $discountValue) {
                    if (isset($discounts[$itemID]) && $discounts[$itemID] < $discountValue) {
                        $discounts[$itemID] = (float) $discountValue;
                    }
                }
            }
        }
        return $discounts;
    }

//    public function getList()
//    {
//        //todo
//    }

    /**
     * 
     * @param string $id
     * @return string
     */
    private function getDataKey(string $id): string
    {
        $idMD5 = md5($id);
        return 'bearcms-coupons/coupon/' . substr($idMD5, 0, 2) . '/' . substr($idMD5, 2, 2) . '/' . $idMD5;
    }

}
