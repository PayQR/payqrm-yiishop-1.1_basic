<?php
/**
 * Код в этом файле будет выполнен, когда интернет-сайт получит уведомление от PayQR о полной отмене счета (заказа) после его оплаты.
 * Это означает, что посредством запросов в PayQR интернет-сайт либо одной полной отменой, либо несколькими частичными отменами вернул всю сумму денежных средств по конкретному счету (заказу).
 *
 * $Payqr->objectOrder содержит объект "Счет на оплату" (подробнее об объекте "Счет на оплату" на https://payqr.ru/api/ecommerce#invoice_object)
 *
 * Ниже можно вызвать функции своей учетной системы, чтобы особым образом отреагировать на уведомление от PayQR о событии invoice.reverted.
 *
 * Получить orderId из объекта "Счет на оплату", по которому произошло событие, можно через $Payqr->objectOrder->getOrderId();
 */ 