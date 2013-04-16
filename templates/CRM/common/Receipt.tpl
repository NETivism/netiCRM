<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<style>
{literal}
.row { display:table-row;}
.line { border-top: 1px dashed #aaa; padding-top: 10px;}
.receipt{ position:relative; font-size:12pt; height:480px; margin-top:20px;}
.receipt-head { width: 100%; height: 50px;}
.receipt-head .logo {position:absolute; left:0; top:0;}
.receipt-head .title {position:absolute; left:45%; top:0; font-size: 16pt; text-align:center;}
.receipt-head .serial {position:absolute; font-size: 10pt; top: -20px; right:0;}
.receipt-head .serial .type { font-weight:bold; }

.receipt-body {clear:both; position: relative; }
.receipt-body .content { float: left; maring:0; margin-left:10px; padding-left:10px;}
.receipt-body .content li { margin: 8px 0;}
.receipt-body .stamp { position: absolute; left:50%;}
.receipt-body .handle { margin-top:50px;}
.receipt-body .unit { padding:0 8px; font-size:10pt;}
.receipt-body li.amount .desc { padding-left: 5px; }
.receipt-body li.amount .second-line { padding-left: 50px; }
.receipt-body .start.unit { font-size: 12pt; padding-left:5px;}

.receipt-footer {clear:both; display:table;}
.receipt-footer .org-info { display:table-cell; font-size:9pt; width:255px; }
.receipt-footer .org-desc { display:table-cell; font-size:8pt; }
{/literal}
</style>
</head>
<body>
{$pages}
</body>
</html>
