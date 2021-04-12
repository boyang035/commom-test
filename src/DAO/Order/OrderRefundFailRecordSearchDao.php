<?php

namespace rrzj\commom\DAO\Order;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Query;

class OrderRefundFailRecordSearchDao extends OrderRefundFailRecordDao
{
    public $order_status;
    public $from_merch;
    public $created_by;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'status', 'order_id', 'trade_no'], 'integer'],
            [['refund_amount', 'order_status', 'created_at', 'updated_at', 'from_merch', 'app_tmp_id', 'created_by', 'fail_reason'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params, $otherParams)
    {
        $query = OrderRefundFailRecordDao::find()->select('order_refund_fail_record.*, o.order_status, o.created_by')
            ->leftJoin('v2_order o', 'o.order_id=order_refund_fail_record.order_id');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['created_at' => SORT_DESC, 'id' => SORT_DESC]]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        if ($this->created_at) {
            $createdTime = strtotime(strlen($this->created_at) > 10 ? $this->created_at : $this->created_at . ' 00:00:00');
            $createdEnd = strtotime(date("Y-m-d 23:59:59", $createdTime));
            $query->andFilterWhere(['>', 'order_refund_fail_record.created_at', $createdTime])
                ->andFilterWhere(['<=', 'order_refund_fail_record.created_at', $createdEnd]);
        }
        if ($this->updated_at) {
            $updatedTime = strtotime(strlen($this->updated_at) > 10 ? $this->updated_at : $this->updated_at . ' 00:00:00');
            $updatedEnd = strtotime(date("Y-m-d 23:59:59", $updatedTime));
            $query->andFilterWhere(['>', 'order_refund_fail_record.updated_at', $updatedTime])
                ->andFilterWhere(['<=', 'order_refund_fail_record.updated_at', $updatedEnd]);
        }

        if ($otherParams) {
            $query->andWhere($otherParams);
        }

        $query->andFilterWhere([
            'order_refund_fail_record.order_id' => $this->order_id,
            'order_refund_fail_record.trade_no' => $this->trade_no,
            'order_refund_fail_record.refund_amount' => $this->refund_amount,
            'order_refund_fail_record.status' => $this->status,
            'order_refund_fail_record.app_tmp_id' => $this->app_tmp_id,
            'order_refund_fail_record.fail_reason' => $this->fail_reason,
            'o.order_status' => $this->order_status,

        ]);

        if ($this->from_merch) {
            if (preg_match('/^\d+$/', $this->from_merch)) {
                $query->andWhere(['order_refund_fail_record.app_tmp_id' => $this->from_merch]);
            } elseif (preg_match('/^ma\_\w{25}$/', $this->from_merch)) {
                $appTmpIds = (new Query())->select('mini_app_id')->from('mini_info')
                    ->where(['merch_id' => $this->from_merch])->column();
                $query->andWhere(['order_refund_fail_record.app_tmp_id' => $appTmpIds]);
            } else {
                $merchId = (new Query())->select('id')->from('code_merch')
                    ->where(['name' => $this->from_merch])->scalar();
                $appTmpIds = $merchId ? (new Query())->select('mini_app_id')->from('mini_info')
                    ->where(['merch_id' => $merchId])->column() : [];
                $query->andWhere(['order_refund_fail_record.app_tmp_id' => $appTmpIds]);
            }
        }

        return $dataProvider;
    }
}