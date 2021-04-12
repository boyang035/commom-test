<?php

namespace rrzj\commom\DAO\Order;

use common\models\Debug;
use common\Service\Tool\ToolBase;
use Exception;
use Yii;
use yii\db\Query;


class OrderRefundFailRecordDao extends OrderRefundFailRecord
{
    /**
     * 保存数据
     * @param $data
     * @return bool
     */
    public static function saveData($data)
    {
        if (empty($data['trade_no']) || empty($data['app_tmp_id'])) return false;

        $t = time();
        $model = OrderRefundFailRecordDao::findOne(['trade_no' => $data['trade_no']]);
        if (!$model) {
            $model = new OrderRefundFailRecordDao();
            $model->created_at = $t;
            $model->order_id = $data['order_id'];
            $model->trade_no = $data['trade_no'];
            $model->refund_amount = $data['refund_amount'];
            $model->refund_reason = $data['refund_reason'];
            $model->app_tmp_id = $data['app_tmp_id'];
        }
        $model->status = $data['status'];
        $model->fail_reason = $data['fail_reason'];
        $model->updated_at = $t;
        if (!$model->save()) {
            Debug::database($model->errors, 'Order.Refund.Fail', '订单退款异常添加记录失败');
        }
    }

    /**
     * 获取退款失败短信内容
     */
    public static function getSmsInfo($appTmpId)
    {
        if (empty($appTmpId)) return '';

        $info = (new Query())
            ->select('m.phone, t.mini_app_name')
            ->from('mini_info t')
            ->leftJoin('code_merch m', 't.merch_id=m.id')
            ->where(['t.mini_app_id' => $appTmpId])
            ->one();
        if (empty($info)) return '';

        $content = '【人人租机】您的支付宝小程序绑定的企业支付宝账户余额不足，用户未能正常执行退款操作，请及时充值，如有疑问请咨询客服 4008922580';
        return ['phone' => $info['phone'], 'content' => $content, 'tell' => '4008922580'];
    }

    /**
     * 商家小程序申请退款的收录在这里
     */
    public static function orderRefundApply($appTmpId, $orderId, $refundMoney, $remainMoney)
    {
        $t = time();
        $applyMoney = (new Query())->select('SUM(refund_amount)')
            ->from('order_refund_fail_record')->where(['order_id' => $orderId, 'status' => 1])->scalar();
        if ($applyMoney > 0 && ($applyMoney + $refundMoney > $remainMoney)) // 申请退款金额 + 已申请退款金额 > 最多可退金额
            return ['status' => 8601, 'message' => '存在已申请退款金额：' . $applyMoney . "，本次提交超出最大可退款金额"];

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $model = new OrderRefundFailRecordDao();
            $model->status = 1; // 待处理
            $model->order_id = $orderId;
            $model->app_tmp_id = $appTmpId;
            $model->trade_no = '';
            $model->created_at = $t;
            $model->refund_amount = $refundMoney;  // 本次退款金额
            $model->refund_reason = '申请退款'; // 退款原因
            $model->updated_at = $t;
            if (!$model->save())
                throw new Exception('申请退款失败' . ToolBase::strSqlError($model->errors));

            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            Debug::database(
                ['order_id' => $orderId, 'line' => $e->getLine(), 'msg' => $e->getMessage()],
                'backend.code.order.refund.apply',
                '商家小程序申请退款出错'
            );
            return ['status' => 8600, 'message' => '申请退款失败'];
        }

        return ['status' => 0];
    }

    /**
     * 获取订单流水退款数据
     */
    public static function getRefundData($model)
    {
        if ($model->trade_no) {
            // 有流水号
            $result = [['trade_no' => $model->trade_no, 'refund_money' => $model->refund_amount]];
        } elseif ($model->status == 1) {
            // 申请退款
            $result = self::getTradeNoRefundMoney($model->order_id, $model->refund_amount);
        } else {
            // 已退款
            $result = self::getRefundTradeByText($model->fail_reason);
        }
        return $result;
    }

    /**
     * 根据订单号查询每个流水需退金额
     */
    public static function getTradeNoRefundMoney($orderId, $refundMoney)
    {
        $payLog = (new Query())
            ->select('t1.trade_no, t1.money, t1.discount, t2.has_refund')
            ->from('tbl_pay_log t1')
            ->leftJoin('account_pay_log t2', 't2.pay_id=t1.auto_id')
            ->where(['t1.id' => $orderId])
            ->orderBy('t1.auto_id desc')
            ->all();
        if (!$payLog) return ['status' => 1001, 'message' => '暂无支付记录'];

        $result = [];
        $hasRefund = 0; // 已退金额累计
        foreach ($payLog as $pl) {
            $maxRefund = round($pl['money'] - $pl['has_refund'], 2); // 流水最高可退金额
            if ($maxRefund < 0.01) continue; // 不做退款处理

            if ($maxRefund + $hasRefund > $refundMoney) { // 部分退款处理
                $thisRefund = round($refundMoney - $hasRefund, 2); // 本次退款金额
            } else { // 全部退款
                $thisRefund = $maxRefund;                                       // 本次退款金额
            }
            if ($thisRefund <= 0) continue;

            $hasRefund += $thisRefund;                                          // 累加已退款金额
            $result[] = ['trade_no' => $pl['trade_no'], 'refund_money' => $thisRefund];
        }
        if (empty($result)) return ['status' => 1001, 'message' => '暂无可退款流水'];

        return $result;
    }

    /**
     * 获取退款失败流水
     */
    public static function getRefundTradeByText($failReason)
    {
        $arr = $failReason ? explode(';', $failReason) : [];
        $result = [];
        foreach ($arr as $k => $v) {
            if (strpos($v, '退款失败') !== false) {
                $arr1 = explode(' ', $v);
                $result[] = ['trade_no' => $arr1[0], 'refund_money' => $arr1[1]];
            }
        }
        return $result;
    }

    /**
     * 获取退款说明
     */
    public static function getRefundText($failReason, $text)
    {
        $arr = $failReason ? explode(';', $failReason) : [];

        foreach ($arr as $k => $v) {
            if (strpos($v, '退款失败') !== false) {
                unset($arr[$k]);
            }
        }
        $str = implode(';', $arr);
        $text = $str . $text;
        return $text;
    }
}
