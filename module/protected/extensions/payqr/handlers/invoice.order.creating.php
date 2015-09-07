<?php



$order = new payqr_order($Payqr);

$cartObject = $order->checkCart();
$Payqr->objectOrder->setCart($cartObject);

$amount = $order->calcAmount($cartObject);
$Payqr->objectOrder->setAmount($amount);

$orderId = $order->create($cartObject);
$Payqr->objectOrder->setOrderId($orderId);
