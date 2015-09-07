<?php


if($order)
{
  $html = "";


    $Payqr_invoice = new payqr_invoice_action();
    $invoice = $Payqr_invoice->get_invoice($order->invoice_id);
    $invoice = json_decode($invoice);

    if($invoice)
    {
          $html .= "<form method='post'>";
          $html .= "<div style='margin-bottom:20px'>";
          $html .= "<input type='hidden' name='invoice_id' value='{$invoice->id}'/>";
          $html .= "<div class='item_div'>PayQR.order_info</div>";
          $payqrFields = array(
              "id" => "ID",
              "status" => "Статус",
              "executionStatus" => "Cтатус исполнения заказа",
              "confirmStatus" => "Cтатус подтверждения заказа",
              "payqrNumber" => "Номер инвойса",
              "orderId" => "ID заказа",
              "amount" => "Сумма",
              "revertAmount" => "Сумма возврата",
          );
          $html .= "<table class='payqr'>";
          $k=0;
          foreach($payqrFields as $key=>$field)
          {
              $html .= "<tr class='".($k%2 == 0 ? "odd" : "even")."'><td>{$field}</td><td>{$invoice->$key}</td></tr>";
              $k++;
          }
          $html .= "</table>";
          $html .= "<div class='item_div'>Товары в заказе</div>";
          $html .= "<table class='payqr'><tr><th>ID</th><th>кол-во</th><th>сумма</th></tr>";
          foreach($invoice->cart as $k=>$item)
          {
              $html .= "<tr class='".($k%2 == 0 ? "odd" : "even")."'><td>{$item->article}</td><td>{$item->quantity}</td><td>{$item->amount}</td></tr>";
          }
          $html .= "</table>";
          $html .= "<div class='item_div'>Действия</div>";
          //7 cases for payqr orders
          $html .= "<div class='form-item'><label>Ничего не выполнять: <input type='radio' name='invoice_action' value='invoice_no_action' checked/></label></div>";
          if($invoice->status == "new")
          {
              $html .= "<div class='form-item'><label>Аннулировать счет на заказ: <input type='radio' name='invoice_action' value='invoice_cancel'/></label></div>";
          }
          elseif($invoice->status != "cancelled" && $invoice->status != "failed")
          {
              if($invoice->status == "paid" || $invoice->status == "revertedPartially")
              {
                  $html .= "<div class='form-item'><label>Отменить заказ после оплаты: <input class='invoice_check' text='PayQR.invoice_revert' type='radio' name='invoice_action' value='invoice_revert'/></label>";
                  $revert_amount_value = $invoice->amount - $invoice->revertAmount;
                  $html .= "<input type='hidden' name='invoice_amount' value='{$invoice->amount}'/>";
                  $html .= "<input type='hidden' name='invoice_revertAmount' value='{$invoice->revertAmount}'/>";
                  $html .= "<div><label>PayQR.invoice_revert_amount: <input type='text' name='invoice_revert_amount' value='$revert_amount_value' class='form-text'/></label><div>";
                  $html .= "</div>";
              }
              if(($invoice->status == "paid" || $invoice->status == "revertedPartially" || $invoice->status == "reverted") && $invoice->confirmStatus == "None")
              {
                  $html .= "<div class='form-item'><label>PayQR.invoice_confirm: <input class='invoice_check' text='PayQR.invoice_confirm' type='radio' name='invoice_action' value='invoice_confirm'/></label></div>";
              }
              if(($invoice->status == "paid" || $invoice->status == "revertedPartially") && $invoice->executionStatus == "None")
              {
                  $html .= "<div class='form-item'><label>Подтвердить исполнение заказа: <input class='invoice_check' text='PayQR.invoice_execution_confirm' type='radio' name='invoice_action' value='invoice_execution_confirm'/></label></div>";
              }
              $time_since_created = round((time()-strtotime($invoice->created))/60);
              if($time_since_created < 259200 && ($invoice->status == "paid" || $invoice->status == "revertedPartially" || $invoice->status == "reverted"));
              {
                  $html .= "<div class='form-item'><label>Дослать/изменить сообщение: <input class='invoice_check' text='PayQR.invoice_message' text='PayQR.invoice_message' type='radio' name='invoice_action' value='invoice_message'/></label>";
                  $html .= "<div><label>Текст сообщения к покупке: <input type='text' name='invoice_message_text' value='' class='form-text'/></label></div>";
                  $html .= "<div><label>URL изображения для сообщения к покупке: <input type='text' name='invoice_message_image_url' value='' class='form-text'/></label></div>";
                  $html .= "<div><label>URL сайта для сообщения к покупке: <input type='text' name='invoice_message_click_url' value='' class='form-text'/></label></div></div>";
              }
              $html .= "<div class='form-item'><label>Синхронизировать статус с PayQR: <input class='invoice_check' text='PayQR.invoice_sync_data' type='radio' name='invoice_action' value='invoice_sync_data'/></label></div>";
              $html .= "<div class='form-item'><label>Показать историю возвратов: <input type='radio' name='invoice_action' value='invoice_show_history'/></label></div>";
          }
          $html .= "</div>";
          $html .= "<input type='submit' value='Выполнить'>";
          $html .= "</form>";
    }
    else
    {
          $html = "<strong>Нет данных в системе PayQR</strong>";
    }

  echo $html;
}
