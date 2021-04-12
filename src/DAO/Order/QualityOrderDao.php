<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/1/5
 * Time: 17:11
 */

namespace rrzj\commom\DAO\Order;


class QualityOrderDao extends QualityOrder
{
    /**
     * 判断是否严选商品订单
     */
    public static function judgeQualityOrder($orderId)
    {
        $orderArr = self::find()->select('order_id')->where(['order_id' => $orderId])->one();
        if (!$orderArr) {
            return false;
        }
        return true;
    }
}