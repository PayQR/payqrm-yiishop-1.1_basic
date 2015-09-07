<?php
/**
 * Код в этом файле будет выполнен, когда интернет-сайт получит уведомление от PayQR о необходимости предоставить покупателю способы доставки конкретного заказа.
 * Это означает, что интернет-сайт на уровне кнопки PayQR активировал этап выбора способа доставки покупателем, и сейчас покупатель дошел до этого этапа.
 *
 * $Payqr->objectOrder содержит объект "Счет на оплату" (подробнее об объекте "Счет на оплату" на https://payqr.ru/api/ecommerce#invoice_object)
 *
 * Ниже можно вызвать функции своей учетной системы, чтобы особым образом отреагировать на уведомление от PayQR о событии invoice.deliverycases.updating.
 *
 * Важно: на уведомление от PayQR о событии invoice.deliverycases.updating нельзя реагировать как на уведомление о создании заказа, так как иногда оно будет поступать не от покупателей, а от PayQR для тестирования доступности функционала у конкретного интернет-сайта, т.е. оно никак не связано с реальным формированием заказов. Также важно, что в ответ на invoice.deliverycases.updating интернет-сайт может передать в PayQR только содержимое параметра deliveryCases объекта "Счет на оплату". Передаваемый в PayQR от интернет-сайта список способов доставки может быть многоуровневым.
 *
 * Пример массива способов доставки:
 * $delivery_cases = array(
 *          array(
 *              'article' => '2001',
 *               'number' => '1.1',
 *               'name' => 'DHL',
 *               'description' => '1-2 дня',
 *               'amountFrom' => '0',
 *               'amountTo' => '70',
 *              ),
 *          .....
 *  );
 * $Payqr->objectOrder->setDeliveryCases($delivery_cases);
 */


$order = new payqr_order($Payqr);
$order->saveAddress();

$delivery_cases = array();
$cases = ShippingMethod::model()->findAll();
$i = 1;
foreach($cases as $item)
{
  $delivery_cases[] = array(
    'article' => $item->id,
    'number' => $i++,
    'name' => $item->title,
    'description' => $item->description,
    'amountFrom' => $item->price,
    'amountTo' => $item->price,
  );
}

$Payqr->objectOrder->setDeliveryCases($delivery_cases);
