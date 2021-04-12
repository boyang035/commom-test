<?php

namespace rrzj\commom\DAO\Order;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "order_refund_fail_record".
 *
 * @property string $id
 * @property int $status 状态（10:成功,11:失败）
 * @property string $order_id 订单ID
 * @property string $trade_no 流水号
 * @property string $refund_amount 退款金额
 * @property string $refund_reason 退款说明
 * @property string $app_tmp_id 小程序ID
 * @property string $fail_reason 退款失败原因
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class OrderRefundFailRecord extends ActiveRecord
{
    public $order_status;
    public $from_merch;
    public $created_by;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_refund_fail_record';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'order_id', 'created_at', 'updated_at'], 'integer'],
            [['order_id', 'refund_amount'], 'required'],
            [['refund_amount'], 'number'],
            [['trade_no'], 'string', 'max' => 64],
            [['refund_reason'], 'string', 'max' => 255],
            [['fail_reason'], 'string', 'max' => 2000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'status' => '状态',
            'order_id' => '订单ID',
            'trade_no' => '流水号',
            'refund_amount' => '退款金额',
            'refund_reason' => '退款说明',
            'app_tmp_id' => '小程序ID',
            'fail_reason' => '退款失败原因',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    public static $status = [
        1 => '申请退款',
        10 => '退款成功',
        11 => '退款失败',
        12 => '驳回退款',
    ];
}
