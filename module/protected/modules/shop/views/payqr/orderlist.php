<?php

$model = new PayqrInvoice();

$this->widget('zii.widgets.grid.CGridView', array(
	'id'=>'payqr-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'columns'=>array(
		'invoice_id',
    'order_id',
    array(
       'class'=>'CLinkColumn',
       'label'=>'Перейти в заказ',
       'urlExpression'=>'Yii::app()->createUrl("shop/order/view",array("id"=>$data->order_id))',
     ),
     array(
        'class'=>'CLinkColumn',
        'label'=>'Редактировать',
        'urlExpression'=>'Yii::app()->createUrl("shop/payqr/order",array("id"=>$data->order_id))',
      ),
	),
)); ?>
