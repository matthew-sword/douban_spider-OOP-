<?php

class GetHtml
{
   private $ch;
   public $res;

   function get_html($url)
   {
       $this->ch = curl_init();

       curl_setopt($this->ch, CURLOPT_URL, $url);  // 设置要抓取的页面地址

       curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1); // 抓取结果直接返回（如果为0，则直接输出内容到页面）

       //关闭SSL证书验证
       curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);

       $this->res = curl_exec($this->ch);   //返回html字符串

       return($this->res);
   }
}

$get_douban = new GetHtml();

?>