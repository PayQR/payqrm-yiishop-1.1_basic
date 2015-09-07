<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PayqrSettings
 *
 * @author 1
 */
class PayqrSettingsHtml
{
  public function getHtml()
  {
    $html = "<H1>Настройки PayQR</H1>";
    $html .= "<div class='form'><form method='post'>";
    $html .= $this->getHtmlRec(0);
    $html .= "<div class='row'><input type='submit' value='Сохранить'/></form></div>";
    $html .= "<div><span style='color:red'>*</span>Высота и Ширина кнопки указываются в px или %, например 10px или 20%</div>";
    return $html;
  }
  private function getHtmlRec($id)
  {
    $html = "";
    $criteria = new CDbCriteria();
    $criteria->condition = "parent_id={$id} and published=1";
    $criteria->order = "sort_order";
    $data = PayqrSettings::model()->findAll($criteria);
    foreach($data as $item)
    {
      $html .= "<li class='row' id='{$item->id}'>";
      $html .= $this->getRow($item);
      $html .= "<ul id='child_{$item->id}' class='$item->key children'>";
      $html .= $this->getHtmlRec($item->id);
      $html .= "</ul>";
      $html .= "</li>";
    }
    return $html;
  }
  private function getRow($item)
  {
    $html = "";
    if($item->parent_id == 0)
    {
      $html .= "<a href='javascript:void(0)'>$item->name</a>";
    }
    else
    {
      $html .= "<label for='{$item->key}'>{$item->name}</label>";

      $text_attr = $this->get_attr_str($item, "text");
      $select_attr = $this->get_attr_str($item, "select");

      if(!empty($item->value_list))
      {
        $html .= "<select $select_attr>";
        foreach(json_decode($item->value_list) as $key=>$val)
        {
          $s = "";
          if($key == $item->value){
            $s = "selected='selected'";
          }
          $html .= "<option value='$key' $s>$val</option>";
        }
        $html .= "</select>";
      }
      elseif(substr($item->key, 0, 12) == "order_status")
      {

      }
      else{
        $html .= "<input type='text' $text_attr/>";
      }
    }
    return $html;
  }

  private function get_attr_str($item, $type)
  {
    $text = "text";
    $select = "select";

    $attr = array();
    if($item->static == 1){
      $attr["readonly"] = "readonly";
      $attr["style"] = "background-color: #eee;";
    }
    $attr["id"] = $item->key;
    $attr["name"] = "PayqrSettings[{$item->key}]";
    $attr["value"] = $item->value;
    if($type == $text)
    {
      $attr["size"] = strlen($item->value);
    }
    $attr_str = "";
    foreach($attr as $key=>$val)
    {
      $attr_str .= "$key='$val' ";
    }
    $attr_str = trim($attr_str);
    return $attr_str;
  }
}
