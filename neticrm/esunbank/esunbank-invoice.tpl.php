<?php
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="zh-hant" lang="zh-hant" dir="ltr">
  <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?php print $vars['site_name']; ?>繳款單</title>
  <link type="text/css" rel="stylesheet" media="all" href="<?php print $vars['css']; ?>" />
  <link type="text/css" rel="stylesheet" media="print" href="<?php print $vars['css']; ?>" />
  <style>
    @media print {
      img {
        border: 0;
      }
      button.print {
        display: none;
      }
    }
  </style>
  <script type="text/javascript">
    window.print();
  </script>
  </head>
<body>
<div id="wrap">
<?php if($logo){ ?>
  <img src="<?php print $vars['logo']; ?>" id="logo" />
<?php } ?>
<h1><span>超商/郵局/ATM/玉山銀行代收</span>繳款單</h1>
<div class="section1 clear-block">
<h2>第一聯  繳款人收執聯</h2>
  <div class="info-right">
    <?php if($vars['created_date']){ ?><div class="create-date">取單日期：<?php print $vars['created_date']; ?></div><?php } ?>
    <?php if($vars['due_date']){ ?><div class="due-date">繳費期限：<?php print $vars['due_date']; ?></div><?php } ?>
    <div class="stamp">收迄戳記</div>
  </div>
  <div class="info">
    <p><label>訂單編號：</label><?php print $vars['order_number']; ?></p>
    <p><label>繳款人：</label><?php print $vars['user']; ?></p>
    <p><label>繳款金額：</label><?php print $vars['price']; ?><br /></p>
    <div><img src="<?php print $vars['path'].'images/icons.png'; ?>" border="0" /></div>
    <div class="receipt-info"><?php print $receipt_info; ?></div>
  </div>
  <div class="contact-info"><?php print $contact_info; ?></div>
</div><!-- section1 -->
<div align="center"><img src="<?php print $vars['path'].'images/line.png'; ?>" border="0" /></div>
<div class="section2 clear-block">
<div class="head"><?php print $vars['site_name']; ?><span>超商/郵局/ATM/玉山銀行代收</span>繳款單</div>
<h2>第二聯  代理收款傳票</h2>
<div class="payment-info payment-info-store">
<h3>便利商店專用（上限2萬元）- 可至統一超商/全家/OK/萊爾富便利商店繳納</h3>
<?php
foreach($vars['barcode_store'] as $k => $v){
  $c++;
  print "<div class=\"bar\">".$v.'<div class="code"><label class="b">條碼'.$c.'：</label>*'.$vars['serial_store'][$k].'*</div></div>';
}
?>
</div>
<div class="payment-info payment-info-atm">
<h3>ATM轉帳/玉山銀行繳款 - 可用網路ATM或實體ATM或至玉山銀行臨櫃繳納</h3>
<div class="bank-right">
  <p><label>繳款人：</label><?php print $vars['user']; ?></p>
  <p><label>繳款日：</label>中華民國&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;年&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;月&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;日</p>
  <div class="stamp">收迄戳記</div>
</div>
<table class="bank" cellpadding="0" cellspacing="0">
<tr>
  <td class="col1">戶名</td>
  <td class="col2"><?php print $account_name; ?></td>
</tr>
<tr>
  <td>銀行代碼</td>
  <td>808</td>
</tr>
<tr>
  <td>帳號</td>
  <td><?php print $vars['serial']; ?></td>
</tr>
<tr>
  <td>繳款金額</td>
  <td><?php print $vars['price']; ?></td>
</tr>
<tr>
  <td colspan="2"><div class="blank">認證欄</div></td>
</tr>
</table>
</div>
<div class="payment-info payment-info-post-office">
<h3>郵局專用 - 可至各郵局櫃臺繳納</h3>
<?php if($postoffice_account){ ?><div class="postoffice-account"><?php print $postoffice_account; ?></div><?php } ?>
<div class="bar"><?php print $vars['barcode_postoffice']['a']; ?><div class="code"><label class="b">郵政劃撥：</label>*<?php print $vars['serial_postoffice']['a']; ?>*</div></div>
<div class="bar"><?php print $vars['barcode_postoffice']['b']; ?><div class="code"><label class="b">繳款帳號：</label>*<?php print $vars['serial_postoffice']['b']; ?>*</div></div>
<div class="bar"><?php print $vars['barcode_postoffice']['c']; ?><div class="code"><label class="b">繳款金額：</label>*<?php print $vars['serial_postoffice']['c']; ?>*</div></div>
</div>
</div><!-- section2 -->
<div class="footer"><?php print $contact_info; ?></div>
</div><!-- wrap -->
</body>
</html>
