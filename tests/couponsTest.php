<?php

/*
 * Coupons addon for Bear Framework
 * https://github.com/bearcms/coupons-bearframework-addon
 * Copyright (c) 2018 Amplilabs Ltd.
 * Free to use under the MIT license.
 */

/**
 * @runTestsInSeparateProcesses
 */
class SenderTest extends BearFrameworkAddonTestCase
{

    /**
     * 
     */
    public function testNewType()
    {
        $app = $this->getApp();
        $coupons = $app->coupons;

        $percentDiscountByType = function($itemType, $discount, array $items) {
            $result = [];
            foreach ($items as $itemID => $itemData) {
                if (isset($itemData['type'], $itemData['value']) && $itemData['type'] === $itemType) {
                    if (preg_match('/^[0-9\.]+\%$/', $discount) === 1) {
                        $discountPercent = substr($discount, 0, -1);
                        if (is_numeric($discountPercent)) {
                            $result[$itemID] = (float) $itemData['value'] * (float) $discountPercent / 100;
                        } else {
                            throw new Exception('');
                        }
                    } else {
                        throw new Exception('');
                    }
                }
            }
            return $result;
        };


        $coupons->addType('discountFruits', function($coupon, array $items) use ($percentDiscountByType) {
            return $percentDiscountByType('fruit', $coupon->value, $items);
        });

        $coupons->addType('discountVegetables', function($coupon, array $items) use ($percentDiscountByType) {
            return $percentDiscountByType('vegetable', $coupon->value, $items);
        });

        $couponIDs = [];

        $coupon = $coupons->make('discountFruits', '20%');
        $coupons->save($coupon);
        $couponIDs[] = $coupon->id;

        $coupon = $coupons->make('discountVegetables', '29%');
        $coupons->save($coupon);
        $couponIDs[] = $coupon->id;

        $items = [
            'item1' => ['type' => 'fruit', 'value' => 10],
            'item2' => ['type' => 'fruit', 'value' => 20],
            'item3' => ['type' => 'vegetable', 'value' => 30],
            'item4' => ['type' => 'tv', 'value' => 40]
        ];
        $discount = $coupons->getDiscount($couponIDs, $items);

        $this->assertTrue($discount == [
            "item1" => 2,
            "item2" => 4,
            "item3" => 8.7,
            "item4" => 0
        ]);
    }

    /**
     * 
     */
    public function testDescription()
    {
        $app = $this->getApp();
        $coupons = $app->coupons;

        $coupons->addType('allDiscount', function($coupon, array $items) {
            // not needed for the test
        }, [
            'description' => function($coupon) {
                return $coupon->value . ' off everything!';
            }
        ]);

        $coupon = $coupons->make('allDiscount', '20%');
        $coupons->save($coupon);
        $this->assertTrue($coupons->getDescription($coupon->id) === '20% off everything!');

        $coupon = $coupons->make('allDiscount', '20%');
        $coupon->startDate = mktime(1, 2, 3, 4, 5, 2018);
        $coupon->endDate = mktime(1, 2, 3, 6, 7, 2018);
        $coupons->save($coupon);
        $this->assertTrue($coupons->getDescription($coupon->id) === '20% off everything! Valid between April 5, 2018 and June 7, 2018.');

        $coupon = $coupons->make('allDiscount', '20%');
        $coupon->startDate = mktime(1, 2, 3, 4, 5, 2018);
        $coupons->save($coupon);
        $this->assertTrue($coupons->getDescription($coupon->id) === '20% off everything! Valid from April 5, 2018.');

        $coupon = $coupons->make('allDiscount', '20%');
        $coupon->endDate = mktime(1, 2, 3, 6, 7, 2018);
        $coupons->save($coupon);
        $this->assertTrue($coupons->getDescription($coupon->id) === '20% off everything! Valid until June 7, 2018.');

        $coupon = $coupons->make('allDiscount', '20%');
        $coupon->usageLimit = 1;
        $coupons->save($coupon);
        $this->assertTrue($coupons->getDescription($coupon->id) === '20% off everything! Can be used only once.');

        $coupon = $coupons->make('allDiscount', '20%');
        $coupon->usageLimit = 33;
        $coupons->save($coupon);
        $this->assertTrue($coupons->getDescription($coupon->id) === '20% off everything! Can be used 33 times.');
    }

    /**
     * 
     */
    public function testIsValid()
    {
        $app = $this->getApp();
        $coupons = $app->coupons;

        $coupons->addType('allDiscount', function($coupon, array $items) {
            // not needed for the test
        });

        $coupon = $coupons->make('allDiscount', '20%');
        $coupon->startDate = time() + 2;
        $coupon->endDate = time() + 5;
        $coupons->save($coupon);
        $couponID = $coupon->id;

        $this->assertFalse($coupons->isValid($couponID));
        sleep(2);
        $this->assertTrue($coupons->isValid($couponID));
        sleep(5);
        $this->assertFalse($coupons->isValid($couponID));
    }

    /**
     * 
     */
    public function testUsageLimit()
    {
        $app = $this->getApp();
        $coupons = $app->coupons;

        $coupons->addType('allDiscount', function($coupon, array $items) {
            // not needed for the test
        });

        $coupon = $coupons->make('allDiscount', '20%');
        $coupon->usageLimit = 2;
        $coupons->save($coupon);
        $couponID = $coupon->id;

        $this->assertTrue($coupons->isValid($couponID));
        $coupons->markAsUsed($couponID);
        $this->assertTrue($coupons->isValid($couponID));
        $coupons->markAsUsed($couponID);
        $this->assertFalse($coupons->isValid($couponID));
    }

    /**
     * 
     */
    public function testInvalidCoupon()
    {
        $app = $this->getApp();
        $coupons = $app->coupons;

        $coupons->addType('allDiscount', function($coupon, array $items) {
            // not needed for the test
        });

        $coupon = $coupons->make('allDiscount', '20%');
        $coupons->save($coupon);

        $this->assertFalse($coupons->isValid('INVALID'));
    }

}
