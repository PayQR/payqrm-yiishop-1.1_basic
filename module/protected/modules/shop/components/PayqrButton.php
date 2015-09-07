<?php


class PayqrButton
{
  const cart = "cart_";
  const product = "product_";
  const category = "category_";
  const scenario = "buy";

  public function __construct($showScript = false, $is_cart = false)
  {
    if($showScript)
    {
      $script = $this->getScript();
      $clear_cart_script = $is_cart ? "$.get('/shop/payqr/clearcart');" : "";
      $script .= "<script type='text/javascript'>
      payQR.onPaid(function(data) {
            $clear_cart_script
            var msg = 'Ваш заказ # ' + data.orderId + ' успешно создан и оплачен';
            alert(msg);
            window.location.href = '/';
      });
      </script>";
      echo $script;
    }
  }

  private function getScript()
  {
    $url = "https://payqr.ru/popup.js?merchId=" . $this->getOption("merch_id");
    return "<script type='text/javascript' src='$url'></script>";
  }
  private function getPrice($price)
  {
    $price = sprintf('%.2f', $price);
    return $price;
  }
  private function loadValidator()
  {
      $file = Yii::app()->basepath . "/extensions/payqr/classes/payqr_json_validator.php";
      require_once $file;
  }
  public function getCartProducts()
  {
    $this->loadValidator();
    $cart = Shop::getCartContent();
    $products = array();
    foreach($cart as $item)
    {
      if($model = Products::model()->findByPk($item["product_id"]))
      {
        $products[] = array(
                "article" => json_encode(array("id"=>$model->product_id, "variation"=>@$item['Variations'])),
                "name" => payqr_json_validator::escape_quotes($model->title),
                "imageUrl" => $this->getImageUrl($model),
                "amount"=> $model->getPrice(@$item['Variations']) * $item["amount"],
                "quantity" => $item["amount"],
          );
      }
    }
    return $products;
  }
  public function showCartButton()
  {
    if($this->getOption("button-show-on-cart") == 1)
    {
      Yii::app()->clientScript->registerScript("",
        "$('input').blur(function(){
          $.ajax({
            url:'/shop/payqr/updateCart',
            success: function(result) {
              result = JSON.parse(result);
              console.log(result);
              $('.payqr-button').attr('data-amount', result.amount);
              $('.payqr-button').attr('data-cart', result.cart);
            }
          });
        });
        ");
      $products = $this->getCartProducts();
      return $this->get_button_html(self::scenario, $products, self::cart);
    }
  }

  public function showProductButton()
  {
    if($this->getOption("button-show-on-product") == 1)
    {
      $this->loadValidator();
      $products = array();
      if($model = Products::model()->findByPk($_GET["id"]))
      {
        $products[] = array(
                "article" => $model->product_id,
                "name" => payqr_json_validator::escape_quotes($model->title),
                "imageUrl" => $this->getImageUrl($model),
                "amount"=> $this->getPrice($model->price),
                "quantity" => 1,
          );
      }
      return $this->get_button_html(self::scenario, $products, self::product);
    }
  }

  public function showCategoryButton($model)
  {
    if($this->getOption("button-show-on-product") == 1)
    {
      $this->loadValidator();
      $products[] = array(
              "article" => $model->product_id,
              "name" => payqr_json_validator::escape_quotes($model->title),
              "imageUrl" => $this->getImageUrl($model),
              "amount"=> $this->getPrice($model->price),
              "quantity" => 1,
        );
      return $this->get_button_html(self::scenario, $products, self::category);
    }
  }


  private function getImageUrl($model)
  {
    $url = "";
    $folder = Shop::module()->productImagesFolder;
    if(isset($model->images[0]->filename)){
      $url = "http://{$_SERVER["HTTP_HOST"]}/{$folder}/{$model->images[0]->filename}";
    }
    return $url;
  }

  private function get_button_html($scenario, $products, $type)
  {
    $data = $this->get_data($scenario, $products, $type);
    $html = "<button";
    foreach($data as $attr=>$value)
    {
      if(is_array($value))
      {
        $value = implode(" ", $value);
      }
      if(!empty($value))
      {
        $html .= " $attr='$value'";
      }
    }
    $html .= ">buy</button>";
    return $html;
  }
  /**
   * @param $scenario
   * @param array $data
   * @return array|bool
   */
  private function get_data($scenario, $products, $type) {
    $allowed_scenario = array('buy', 'pay');
    if (!in_array($scenario, $allowed_scenario)) {
      watchdog('commerce PayQR', 'Unallowed scenario');
      return FALSE;
    }
    $data = array();
    $data['data-scenario'] = $scenario;


    $cart_data = $products;
    $data_amount = 0;
    foreach ($cart_data as $item) {
      $data_amount += $item['amount'];
    }
    $data['data-amount'] = $data_amount;
    $data['data-cart'] = json_encode($cart_data);
    $data['data-firstname-required'] = "required";
    $data['data-lastname-required'] = "required";
    $data['data-middlename-required'] = $this->getOption('data-middlename-required');
    $data['data-phone-required'] = $this->getOption('data-phone-required');
    $data['data-email-required'] = "required";
    $data['data-delivery-required'] = $this->getOption('data-delivery-required');
    $data['data-deliverycases-required'] = $this->getOption('data-deliverycases-required');
    $data['data-pickpoints-required'] = $this->getOption('data-pickpoints-required');
    $data['data-promo-required'] = $this->getOption('data-promo-required');
    $data['data-promo-description'] = $this->getOption('data-promo-description');
    $data['data-message-text'] = $this->getOption('data-message-text');
    $data['data-message-imageurl'] = $this->getOption('data-message-imageurl');
    $data['data-message-url'] = $this->getOption('data-message-url');
    $data['data-userdata'] = json_encode(array());
    $data['data-commissionpercent'] = $this->getOption('data-commissionpercent');
    $button_style = $this->get_button_style($type);
    $data['class'] = $button_style['class'];
    $data['style'] = $button_style['style'];

    return $data;
  }
  /**
  * Get PayQR button style.
  *
  * @return array
  */
  private function get_button_style($type){
    $style = array();
    $style['class'][] = 'payqr-button';
    $style['class'][] = $this->getOption($type . 'button_color');
    $style['class'][] = $this->getOption($type . 'button_form');
    $style['class'][] = $this->getOption($type . 'button_gradient');
    $style['class'][] = $this->getOption($type . 'button_text_case');
    $style['class'][] = $this->getOption($type . 'button_text_width');
    $style['class'][] = $this->getOption($type . 'button_text_size');
    $style['class'][] = $this->getOption($type . 'button_shadow');
    $style['style'][] = 'height:' . $this->getOption($type . 'button_height') . ';';
    $style['style'][] = 'width:' . $this->getOption($type . 'button_width') . ';';
  return $style;
  }

  private $_options = array();
  public function getOption($key)
  {
    if(empty($this->_options))
    {
      $options = PayqrSettings::model()->findAll();
      foreach($options as $item){
        $this->_options[$item->key] = $item->value;
      }
    }
    $return = false;
    if(isset($this->_options[$key])){
      $return = $this->_options[$key];
    }
    return $return;
  }

  public static function log($text, $type="")
  {
    $model = new PayqrLog();
    $model->text = $text;
    $model->type = $type;
    $model->date = date("Y-m-d H:i:s");
    $model->save();
  }
}
