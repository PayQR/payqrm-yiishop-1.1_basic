<?php


class PayqrController extends Controller
{
  public function filters()
  {
    return array(
      'accessControl',
    );
  }

  public function accessRules() {
    return array(
        array('allow',
          'actions'=>array('install', 'handler', "showlog", "updatecart", "clearcart"),
          'users' => array('*'),
          ),
        array('allow',
          'actions'=>array('admin', 'orderlist', 'order'),
          'users' => array('admin'),
          ),
        array('deny',  // deny all other users
            'users'=>array('*'),
            ),
        );
  }

  public function actionInstall()
  {
    if($db = Yii::app()->db) {

      $sql = "SHOW TABLES LIKE 'payqr_settings'";
      if($res = $db->createCommand($sql)->queryRow())
      {
        $msg = "Модуль уже установлен";
      }
      else
      {        
        $config = Yii::app()->basepath . "/extensions/payqr/payqr_config.php";
        require_once $config;

        //установка платёжной системы, по умолчанию id=999, если занято то измените значение в классе payqr_config
        $sql = "INSERT INTO `shop_payment_method` (`id`, `title`, `description`, `tax_id`, `price`) VALUES (".payqr_config::$paymentId.", 'Оплата PayQR', NULL, 0, 0);";
        $db->createCommand($sql)->execute();

        //установка таблицы payqr_invoice
        $sql = "CREATE TABLE IF NOT EXISTS `payqr_invoice` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                  `order_id` int(11) NOT NULL,
                  `invoice_id` varchar(255) NOT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";
        $db->createCommand($sql)->execute();

        //установка таблички с настройками кнопки
        $log_key = md5(uniqid());
        $sql = "CREATE TABLE IF NOT EXISTS `payqr_settings` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                    `key` varchar(255) NOT NULL,
                    `name` varchar(255) NOT NULL,
                    `value` varchar(255) DEFAULT NULL,
                    `value_list` text,
                    `parent_id` int(11) NOT NULL DEFAULT '0',
                    `static` int(11) NOT NULL DEFAULT '0',
                    `published` int(11) NOT NULL DEFAULT '1',
                    `sort_order` int(11) NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`)
                  ) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8;

                INSERT INTO `payqr_settings` (`id`, `key`, `name`, `value`, `value_list`, `parent_id`, `static`, `published`, `sort_order`) VALUES
                  (1, 'base_options', 'Базовые настройки', NULL, NULL, 0, 0, 1, 10),
                  (2, 'button-options', 'Настройки кнопки', NULL, NULL, 0, 0, 1, 20),
                  (3, 'order-options', 'Статусы заказов', NULL, NULL, 0, 0, 0, 40),
                  (4, 'required-options', 'Запрашиваемые поля', NULL, NULL, 0, 0, 1, 30),
                  (6, 'handle_url', 'URL PayQR обработчика', 'http://{$_SERVER['SERVER_NAME']}/shop/payqr/handler', '', 1, 1, 1, 10),
                  (8, 'log_url', 'URL PayQR логов', 'http://{$_SERVER['SERVER_NAME']}/shop/payqr/showlog?key={$log_key}', '', 1, 1, 1, 30),
                  (9, 'merch_id', 'PayQR merchant ID', '', '', 1, 0, 1, 40),
                  (10, 'sercer_key_in', 'PayQR SecretKeyIn', '', '', 1, 0, 1, 50),
                  (11, 'secret_key_out', 'PayQR SecretKeyOut', '', '', 1, 0, 1, 60),
                  (14, 'data-middlename-required', 'Запрашивать отчество покупателя', 'deny', '{\"deny\":\"Нет\",\"required\":\"Да\"}', 4, 0, 1, 30),
                  (15, 'data-phone-required', 'Запрашивать номер телефона покупателя', 'deny', '{\"deny\":\"Нет\",\"required\":\"Да\"}', 4, 0, 1, 40),
                  (17, 'data-delivery-required', 'Запрашивать адрес доставки', 'deny', '{\"deny\":\"Нет\",\"required\":\"Да\",\"notrequired\":\"Не обязательно\"}', 4, 0, 1, 120),
                  (18, 'data-deliverycases-required', 'Могут ли быть в магазине способы доставки', 'deny', '{\"deny\":\"Нет\",\"required\":\"Да\"}', 4, 0, 1, 130),
                  (19, 'data-pickpoints-required', 'Могут ли быть в магазине точки самовывоза', 'deny', '{\"deny\":\"Нет\",\"required\":\"Да\"}', 4, 0, 1, 140),
                  (20, 'data-promo-required', 'Предлагать ввести промо-идентификатор', 'deny', '{\"deny\":\"Нет\",\"required\":\"Да\"}', 4, 0, 1, 150),
                  (21, 'data-promo-description', 'Текстовое название промо-идентификатора', '', '', 4, 0, 1, 160),
                  (22, 'data-message-text', 'Сообщение в покупке после ее совершения', '', NULL, 4, 0, 1, 170),
                  (23, 'data-message-imageurl', 'URL изображения в покупке после ее совершения', '', NULL, 4, 0, 1, 180),
                  (24, 'data-message-url', 'URL ссылка на сайт продавца в покупке после ее совершения', '', NULL, 4, 0, 1, 190),
                  (25, 'button-show-on-cart', 'Показывать кнопку PayQR на страничке корзины', '0', '[\"Нет\",\"Да\"]', 2, 0, 1, 200),
                  (26, 'button-show-on-product', 'Показывать кнопку PayQR на страничке карточки товара', '0', '[\"Нет\",\"Да\"]', 2, 0, 1, 210),
                  (27, 'button-show-on-category', 'Показывать кнопку PayQR на страничке категории товаров', '0', '[\"Нет\",\"Да\"]', 2, 0, 1, 220),
                  (28, 'cart_button_color', 'Цвет кнопки (корзина)', 'payqr-button_red', '{\"default\":\"По умолчанию\",\"payqr-button_green\":\"Зелёный\",\"payqr-button_blue\":\"Синий\",\"payqr-button_orange\":\"Оранжевый\",\"payqr-button_red\":\"Красный\"}', 25, 0, 1, 230),
                  (29, 'cart_button_form', 'Округление краев кнопки (корзина)', 'default', '{\"default\":\"По умолчанию\",\"payqr-button_sharp\":\"без округления\",\"payqr-button_rude\":\"минимальное округление\",\"payqr-button_soft\":\"мягкое округление\",\"payqr-button_sleek\":\"значительное округление\",\"payqr-button_oval\":\"максимальное округление\"}', 25, 0, 1, 240),
                  (30, 'cart_button_shadow', 'Тень кнопки (корзина)', 'default', '{\"default\":\"По умолчанию\",\"payqr-button_shadow\":\"включена\",\"payqr-button_noshadow\":\"отключена\"}', 25, 0, 1, 250),
                  (31, 'cart_button_gradient', 'Градиент кнопки (корзина)', 'payqr-button_flat', '{\"default\":\"По умолчанию\",\"payqr-button_flat\":\"отключен\",\"payqr-button_gradient\":\"включен\"}', 25, 0, 1, 260),
                  (32, 'cart_button_text_size', 'Размер текста кнопки (корзина)', 'default', '{\"default\":\"По умолчанию\",\"payqr-button_text-small\":\"мелко\",\"payqr-button_text-medium\":\"средне\",\"payqr-button_text-large\":\"крупно\"}', 25, 0, 1, 270),
                  (33, 'cart_button_text_width', 'Текст кнопки жирным (корзина)', 'default', '{\"default\":\"По умолчанию\",\"payqr-button_text-normal\":\"отключен\",\"payqr-button_text-bold\":\"включен\"}', 25, 0, 1, 280),
                  (34, 'cart_button_text_case', 'Регистр текста кнопки (корзина)', 'default', '{\"default\":\"По умолчанию\",\"payqr-button_text-lowercase\":\"нижний\",\"payqr-button_text-standartcase\":\"стандартный\",\"payqr-button_text-uppercase\":\"верхний\"}', 25, 0, 1, 290),
                  (35, 'cart_button_height', 'Высота кнопки (корзина)', 'auto', NULL, 25, 0, 1, 300),
                  (36, 'cart_button_width', 'Ширина кнопки (корзина)', 'auto', NULL, 25, 0, 1, 310),
                  (37, 'product_button_color', 'Цвет кнопки (карточка товара)', 'default', '{\"default\":\"По умолчанию\",\"payqr-button_green\":\"Зелёный\",\"payqr-button_blue\":\"Синий\",\"payqr-button_orange\":\"Оранжевый\",\"payqr-button_red\":\"Красный\"}', 26, 0, 1, 320),
                  (38, 'product_button_form', 'Округление краев кнопки (карточка товара)', 'default', '{\"default\":\"По умолчанию\",\"payqr-button_sharp\":\"без округления\",\"payqr-button_rude\":\"минимальное округление\",\"payqr-button_soft\":\"мягкое округление\",\"payqr-button_sleek\":\"Sleek\",\"payqr-button_oval\":\"Oval\"}', 26, 0, 1, 330),
                  (39, 'product_button_shadow', 'Тень кнопки (карточка товара)', 'default', '{\"default\":\"По умолчанию\",\"payqr-button_shadow\":\"включена\",\"payqr-button_noshadow\":\"отключена\"}', 26, 0, 1, 340),
                  (40, 'product_button_gradient', 'Градиент кнопки (карточка товара)', 'default', '{\"default\":\"По умолчанию\",\"payqr-button_flat\":\"отключен\",\"payqr-button_gradient\":\"включен\"}', 26, 0, 1, 350),
                  (41, 'product_button_text_size', 'Размер текста кнопки (карточка товара)', 'default', '{\"default\":\"По умолчанию\",\"payqr-button_text-small\":\"мелко\",\"payqr-button_text-medium\":\"средне\",\"payqr-button_text-large\":\"крупно\"}', 26, 0, 1, 360),
                  (42, 'product_button_text_width', 'Текст кнопки жирным (карточка товара)', 'default', '{\"default\":\"По умолчанию\",\"payqr-button_text-normal\":\"отключен\",\"payqr-button_text-bold\":\"включен\"}', 26, 0, 1, 370),
                  (43, 'product_button_text_case', 'Регистр текста кнопки (карточка товара)', 'default', '{\"default\":\"По умолчанию\",\"payqr-button_text-lowercase\":\"нижний\",\"payqr-button_text-standartcase\":\"стандартный\",\"payqr-button_text-uppercase\":\"верхний\"}', 26, 0, 1, 380),
                  (44, 'product_button_height', 'Высота кнопки (карточка товара)', 'auto', NULL, 26, 0, 1, 390),
                  (45, 'product_button_width', 'Ширина кнопки (карточка товара)', 'auto', NULL, 26, 0, 1, 400),
                  (46, 'category_button_color', 'Цвет кнопки (категория товаров)', 'default', '{\"default\":\"По умолчанию\",\"payqr-button_green\":\"Зелёный\",\"payqr-button_blue\":\"Синий\",\"payqr-button_orange\":\"Оранжевый\",\"payqr-button_red\":\"Красный\"}', 27, 0, 1, 410),
                  (47, 'category_button_form', 'Округление краев кнопки (категория товаров)', 'default', '{\"default\":\"По умолчанию\",\"payqr-button_sharp\":\"без округления\",\"payqr-button_rude\":\"минимальное округление\",\"payqr-button_soft\":\"мягкое округление\",\"payqr-button_sleek\":\"Sleek\",\"payqr-button_oval\":\"Oval\"}', 27, 0, 1, 420),
                  (48, 'category_button_shadow', 'Тень кнопки (категория товаров)', 'default', '{\"default\":\"По умолчанию\",\"payqr-button_shadow\":\"включена\",\"payqr-button_noshadow\":\"отключена\"}', 27, 0, 1, 430),
                  (49, 'category_button_gradient', 'Градиент кнопки (категория товаров)', 'default', '{\"default\":\"По умолчанию\",\"payqr-button_flat\":\"отключен\",\"payqr-button_gradient\":\"включен\"}', 27, 0, 1, 440),
                  (50, 'category_button_text_size', 'Размер текста кнопки (категория товаров)', 'default', '{\"default\":\"По умолчанию\",\"payqr-button_text-small\":\"мелко\",\"payqr-button_text-medium\":\"средне\",\"payqr-button_text-large\":\"крупно\"}', 27, 0, 1, 450),
                  (51, 'category_button_text_width', 'Текст кнопки жирным (категория товаров)', 'default', '{\"default\":\"По умолчанию\",\"payqr-button_text-normal\":\"отключен\",\"payqr-button_text-bold\":\"включен\"}', 27, 0, 1, 460),
                  (52, 'category_button_text_case', 'Регистр текста кнопки (категория товаров)', 'default', '{\"default\":\"По умолчанию\",\"payqr-button_text-lowercase\":\"нижний\",\"payqr-button_text-standartcase\":\"стандартный\",\"payqr-button_text-uppercase\":\"верхний\"}', 27, 0, 1, 470),
                  (53, 'category_button_height', 'Высота кнопки (категория товаров)', 'auto', NULL, 27, 0, 1, 480),
                  (54, 'category_button_width', 'Ширина кнопки (категория товаров)', 'auto', NULL, 27, 0, 1, 490),
                  (55, 'order_status_created', 'Заказ создан но не оплачен (invoice.order.creating)', '2', '', 3, 0, 1, 500),
                  (56, 'order_status_paid', 'Заказ оплачен (invoice.paid)', '2', '', 3, 0, 1, 510),
                  (57, 'order_status_canceled', 'Заказ отменён', '1', '', 3, 0, 1, 520),
                  (59, 'log_path', 'Путь к файлу логов', 'protected/extensions/payqr/logs/payqr.log', NULL, 1, 0, 1, 11),
                  (60, 'log_key', 'Ключ доступа к логам', '{$log_key}', NULL, 1, 0, 1, 12),
                  (61, 'data-firstname-required', 'Запрашивать имя покупателя', NULL, '{\"deny\":\"Нет\",\"required\":\"Да\"}', 4, 0, 0, 10),
                  (62, 'data-lastname-required', 'Запрашивать фамилию покупателя', NULL, '{\"deny\":\"Нет\",\"required\":\"Да\"}', 4, 0, 0, 20),
                  (63, 'data-email-required', 'Запрашивать адрес электронной почты', NULL, '{\"deny\":\"Нет\",\"required\":\"Да\"}', 4, 0, 0, 50);";

        $db->createCommand($sql)->execute();
        $msg = "Установка прошла успешно";
      }
      $this->render("install", array("msg"=>$msg));
    }
  }

  /*
  * Настройки кнопки в админке
  */
  public function actionAdmin()
  {
    if(isset($_POST["PayqrSettings"]))
    {
      foreach($_POST["PayqrSettings"] as $key=>$value)
      {
        $model = PayqrSettings::model()->findByAttributes(array("key"=>$key));
        if($model){
          $model->value = $value;
          if($model->key == "log_url")
          {
            $token = "?key=";
            $value = explode($token, $value);
            $model->value = $value[0] . $token . $_POST["PayqrSettings"]["log_key"];
          }
          $model->save();
        }
      }
    }
    $settings = PayqrSettings::model()->findAll(array("order"=>"sort_order"));
    $this->render('admin', array("settings"=>$settings));
  }
  /*
  * Действия PayQR с заказами (реверт) в админке
  */
  public function actionOrderList()
  {
    $this->render('orderlist');
  }
  public function actionUpdateCart()
  {
    $amount = 0;
    $button = new PayqrButton();
    $products = $button->getCartProducts();
    foreach($products as $item){
      $amount += $item["amount"];
    }
    $res = array(
      "amount" => $amount,
      "cart" => json_encode($products)
    );
    echo json_encode($res);
  }
  public function validate_payqr_input()
  {
    $action = $_POST["invoice_action"];
    switch($action)
    {
        case "invoice_revert":
            $revert_amount = $_POST["invoice_revert_amount"];
            $invoice_amount = $_POST["invoice_amount"];
            $invoice_revertAmount = $_POST["invoice_revertAmount"];
            if($revert_amount > $invoice_amount-$invoice_revertAmount)
            {
              $message = "PayQR.revert_should_be_less_then";
              echo "<strong class='error'>$message</strong>";
              return false;
            }
            break;
        case "invoice_message":
            $text = $_POST["invoice_message_text"];
            $image_url = $_POST["invoice_message_image_url"];
            $click_url = $_POST["invoice_message_click_url"];
            if(empty($text) || empty($image_url) || empty($click_url))
            {
                $message = "PayQR.message_all_field_required";
                echo "<strong class='error'>$message</strong>";
                return false;
            }
            break;
    }
    return true;
  }
  public function actionOrder($id)
  {
    $this->setUpConfig();
    if(isset($_POST["invoice_action"]))
    {
      $order_id = $_POST["order_id"];
      $action = $_POST["invoice_action"];
      $invoice_id = $_POST["invoice_id"];
      if($this->validate_payqr_input())
      {
        switch($action)
        {
            case "invoice_cancel":
                $Payqr_invoice->invoice_cancel($invoice_id);
                $model = Core_Entity::factory("Shop_Order")->find($order_id);
                $status = Core_Entity::factory('PayQR')->getByKey("order_status_canceled");
                $model->shop_order_status_id = $status->value;
                $model->paid = 0;
                $model->save();
                break;
            case "invoice_revert":
                $revert_amount = $_POST["invoice_revert_amount"];
                $Payqr_invoice->invoice_revert($invoice_id, $revert_amount);
                break;
            case "invoice_confirm":
                $Payqr_invoice->invoice_confirm($invoice_id);
                break;
            case "invoice_execution_confirm":
                $Payqr_invoice->invoice_execution_confirm($invoice_id);
                break;
            case "invoice_execution_confirm":
                $text = $_POST["invoice_message_text"];
                $image_url = $_POST["invoice_message_image_url"];
                $click_url = $_POST["invoice_message_click_url"];
                $Payqr_invoice->invoice_message($invoice_id, $text, $image_url, $click_url);
                break;
            case "invoice_sync_data":
                if($invoice)
                {
                  //удаляем элементы которых нет во втором массиве
                  $items = OrderPosition::model()->findAllbyAttributes($order_id);
                  foreach($items as $item)
                  {
                    $exist = false;
                    foreach($invoice->cart as $cartItem)
                    {
                      if($cartItem->article == $item->shop_item_id){
                        $exist = true;
                      }
                    }
                    if($exist == false)
                    {
                      $item->delete();
                    }
                  }

                  //Добавляем элементы которых нет в первом массиве

                  foreach($invoice->cart as $cartItem)
                  {
                    $exist = false;
                    foreach($items as $item)
                    {
                      if($cartItem->article == $item->shop_item_id){
                        $exist = true;
                      }
                    }
                    if($exist == false)
                    {
                      $product = new OrderPosition();
                      $product->order_id = $order_id;
                      $product->product_id = $cartItem->article;
                      $product->amount = $cartItem->quantity;
                      $product->specifications = 0;
                      $product->save();
                    }
                  }
                }
                break;
        }
      }
    }
    $order = PayqrInvoice::model()->findByAttributes(array("order_id"=>$id));
    $this->render('order', array("order"=>$order));
  }

  public function actionShowlog()
  {
    $button = new PayqrButton();
    if(isset($_GET["key"]) && $_GET["key"] == $button->getOption("log_key"))
    {
        $file = Yii::app()->basepath . "/../" . $button->getOption("log_path");
        if(file_exists($file))
        {
          $log = file_get_contents($file);
          echo nl2br($log);
        }
    }
  }
  private function setUpConfig()
  {
    $button = new PayqrButton();
    $config = Yii::app()->basepath . "/extensions/payqr/payqr_config.php";
    require_once $config;
    payqr_config::$merchantID = $button->getOption("merch_id");
    payqr_config::$secretKeyIn = $button->getOption("sercer_key_in");
    payqr_config::$secretKeyOut = $button->getOption("secret_key_out");
    payqr_config::$logFile = Yii::app()->basepath . "/../" . $button->getOption("log_path");
  }

  public function actionHandler()
  {
    $this->setUpConfig();
    $receiver = Yii::app()->basepath . "/extensions/payqr/payqr_receiver.php";
    require_once $receiver;
  }

  public function actionClearCart()
  {
    Yii::app()->user->setState("cart", "");
  }
}
