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

    public function addType(string $id, callable $calculator, array $options = [])
    {
        $this->types[$id] = [$calculator, $options];
    }

    public function make(string $typeID = null, string $discount = null): \BearCMS\BearFrameworkAddons\Coupons\Coupon
    {
        if (!isset($this->cache['coupon'])) {
            $this->cache['coupon'] = new \BearCMS\BearFrameworkAddons\Coupons\Coupon();
        }
        $coupon = clone($this->cache['coupon']);
        if ($typeID !== null) {
            $coupon->typeID = $typeID;
        }
        if ($discount !== null) {
            $coupon->discount = $discount;
        }
        return $coupon;
    }

    private function getDataKey(string $id): string
    {
        $idMD5 = md5($id);
        return 'bearcms-coupons/coupon/' . substr($idMD5, 0, 2) . '/' . substr($idMD5, 2, 2) . '/' . $idMD5;
    }

    public function save(\BearCMS\BearFrameworkAddons\Coupons\Coupon $coupon): void
    {
        $app = App::get();
        if (strlen($coupon->id) === 0) {
            $id = base_convert(microtime(true) * 10000, 10, 36) . 'x';
            for ($i = 0; $i < 10; $i++) {
                $id .= str_replace(['x', 'o'], [rand(0, 9), rand(0, 9)], base_convert(rand(1000000, 9999999), 10, 36));
            }
            $id = strrev(implode('-', array_chunk(str_split(strtoupper($id), 6), 5)[0]));
            $coupon->id = $id;
        }
        $app->data->set($app->data->make($this->getDataKey($coupon->id), $coupon->toJSON()));
    }

    public function exists(string $id): bool
    {
        $app = App::get();
        return $app->data->exists($this->getDataKey($id));
    }

    public function get(string $id): ?\BearCMS\BearFrameworkAddons\Coupons\Coupon
    {
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

    public function markAsUsed($id)
    {
        $coupon = $this->get($id);
        if ($coupon instanceof \BearCMS\BearFrameworkAddons\Coupons\Coupon) {
            $coupon->usageCount = (int) $coupon->usageCount + 1;
            $this->save($coupon);
        }
    }

    public function isValid($id): bool
    {
        $coupon = $this->get($id);
        if ($coupon instanceof \BearCMS\BearFrameworkAddons\Coupons\Coupon) {
            if (is_int($coupon->usageCount) && is_int($coupon->usageLimit)) {
                if ($coupon->usageCount >= $coupon->usageLimit) {
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
     */
    public function getDescription(string $couponID): string
    {
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
            if (strlen($coupon->startDate) > 0) {
                $description[] = sprintf(__('coupons.Starts at'), $app->localization->formatDate($coupon->startDate, ['date']));
            }
            if (strlen($coupon->endDate) > 0) {
                $description[] = sprintf(__('coupons.Ends at'), $app->localization->formatDate($coupon->endDate, ['date']));
            }
            $description = implode(' ', $description);
        }
        return $description;
    }

    /**
     * 
     * @param array $items Format: [['id'=>'item1', 'target'=>'target1', 'value'=>10], ...]
     */
    public function getDiscount(array $couponIDs, array $items): array
    {
        $coupons = [];
        $calculators = [];
        foreach ($couponIDs as $couponID) {
            if ($this->exists($couponID) && $this->isValid($couponID)) {
                $coupon = $this->get($couponID);
                if ($coupon !== null) {
                    $typeID = $coupon->typeID;
                    if (isset($this->types[$typeID])) {
                        $calculators[] = [$coupon->discount, $this->types[$typeID][0]];
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

}
