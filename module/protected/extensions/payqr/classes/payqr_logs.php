<?php
/**
 * Ведение отладочных логов
 */

class payqr_logs
{

  /**
   * Добавление записи в лог файл
   *
   * @param $file
   * @param $message
   */
  public static function log($message)
  {
    if(!payqr_config::$enabledLog)
      return;
    $message = "\n" . str_repeat("-", 200). "\n" . $message . "\n";
    file_put_contents(payqr_config::$logFile, $message, FILE_APPEND);
  }
}
