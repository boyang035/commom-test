<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/1/5
 * Time: 17:11
 */

namespace rrzj\commom\DAO\Order;


use yii\db\ActiveRecord;

/**
 * @desc 质检商品订单
 *
 * @property int $id int(10)
 * @property int $order_id bigint(18) 订单ID
 * @property int $order_from tinyint(2) 10：个人小程序，后续加端再补充
 * @property int $created_at int(10)
 */
class QualityOrder extends ActiveRecord
{
    public static function tableName()
    {
        return 'quality_order';
    }

}