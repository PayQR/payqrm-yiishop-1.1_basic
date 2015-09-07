<?php

class payqr_order
{
  private $Payqr;
  public function __construct($Payqr)
  {
    $this->Payqr = $Payqr;
  }

  public function saveAddress()
  {
    $payqrCustomer = $this->Payqr->objectOrder->getCustomer();
    $delivery = $this->Payqr->objectOrder->getDelivery();
    $firstName = $payqrCustomer->firstName . " " . $payqrCustomer->middleName;
    $address = Address::model()->findByAttributes(array("firstname"=>$firstName, "lastname"=>$payqrCustomer->lastName));
    if(!$address)
    {
      $address = new Address();
      $address->firstname = $firstName;
      $address->lastname = $payqrCustomer->lastName;
    }
    $home = (!empty($delivery->house) ? " Дом-" . $delivery->house : "") .
      (!empty($delivery->unit) ? " копрус-" . $delivery->unit : "") .
      (!empty($delivery->building) ? " строение-" . $delivery->building : "") .
      (!empty($delivery->flat) ? " кв-" . $delivery->flat : "") .
      (!empty($delivery->hallway) ? " подъезд-" . $delivery->hallway : "") .
      (!empty($delivery->floor) ? " этаж-" . $delivery->floor : "");

    $address->country = empty($delivery->country) ? 0 : $delivery->country;
    $address->city = empty($delivery->city) ? 0 : $delivery->city;
    $address->street = (empty($delivery->street) ? 0 : "ул. " . $delivery->street) . $home;
    $address->zipcode = empty($delivery->zip) ? 0 : $delivery->zip;
    $address->save();
    return $address;
  }

  public function checkCart()
  {
    $cartObject = $this->Payqr->objectOrder->getCart();
    foreach($cartObject as $item)
    {
      $id = $item->article;
      if(!is_numeric($id) && $obj = json_decode($id, true))
      {
        $id = $obj['id'];
      }
      if($model = Products::model()->findByPk($id))
      {
        $price = $model->price;
        if(isset($obj["variation"]))
        {
          $price = $model->getPrice($obj["variation"]);
        }
        $item->amount = $item->quantity * $price;
      }
    }
    return $cartObject;
  }
  public function calcAmount($cartObject)
  {
    $amount = 0;
    foreach($cartObject as $item)
    {
      $amount += $item->amount;
    }
    if($deliverySelected = $this->Payqr->objectOrder->getDeliveryCasesSelected())
    {
      $amount += $deliverySelected->amountFrom;
    }
    return $amount;
  }
  public function create($cartObject)
  {
    $payqrCustomer = $this->Payqr->objectOrder->getCustomer();
    $deliverySelected = $this->Payqr->objectOrder->getDeliveryCasesSelected();
    $delivery = $this->Payqr->objectOrder->getDelivery();



    //check if customer exist otherwise create new
    $customer = Customer::model()->findByAttributes(array("email"=>$payqrCustomer->email));
    if(!$customer)
    {
      $customer = new Customer();
      $customer->email = $payqrCustomer->email;
      $customer->save();
    }
    $address = $this->saveAddress();

    $order = new Order();
    $order->customer_id = $customer->customer_id;
    $order->ordering_date = time();
    $order->delivery_address_id = $address->id;
    $order->billing_address_id = $address->id;
    $order->payment_method = payqr_config::$paymentId;

    if($deliverySelected)
    {
      $order->shipping_method = $deliverySelected->article;
    }
    $order->save();

    foreach ($cartObject as $item)
    {
      $product = new OrderPosition();
      $id = $item->article;
      $specifications = 0;
      if(!is_numeric($id) && $obj = json_decode($id, true))
      {
        $id = $obj['id'];
        $specifications = json_encode($obj["variation"]);
      }
      $product->order_id = $order->order_id;
      $product->product_id = $id;
      $product->amount = $item->quantity;
      $product->specifications = $specifications;
      $product->save();
    }
    $invoice_id = $this->Payqr->objectOrder->getInvId();
    $model = new PayqrInvoice();
    $model->order_id = $order->order_id;
    $model->invoice_id = $invoice_id;
    $model->save();
    return $order->order_id;
  }
}
