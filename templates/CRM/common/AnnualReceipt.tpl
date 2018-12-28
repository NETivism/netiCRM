<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<style>
{literal}

table { width: 100%; border-collapse:collapse; }
table td { padding: 2px 4px; }
.align-right { text-align: right; }

.row { display:table-row;}
.line { border-top: 1px dashed #aaa; padding-top: 20px;}

.receipt{ position:relative; font-size: 11pt; min-height:490px;}
.receipt-head { position: relative; width: 100%;}
.receipt-head .logo {text-align: center;}
.receipt-head .title {font-size: 16pt; text-align:center; margin-top: 10px;}
.receipt-head .date {font-size: 10pt;}
.receipt-head .serial {position:absolute; font-size: 10pt; bottom: 0; right: 0;}
.receipt-head .serial .type { font-weight:bold; }

.receipt-body {clear:both; position: relative; }
.receipt-body table, .receipt-body td { border:0px; }
.receipt-body table .col-1 { width: 24%; }
.receipt-body table .col-1:after { content: "：";}
.receipt-body table .col-2 {width: 48.6%;}
.receipt-body .content { float: left; maring:0; margin-left:10px; padding-left:10px;}
.receipt-body .content li { margin: 8px 0;}
.receipt-body .stamp { position: absolute; left:50%;}
.receipt-body .handle { margin-top:50px;}
.receipt-body li.amount .desc { padding-left: 5px; }
.receipt-body li.amount .second-line { padding-left: 50px; }

.receipt-record { margin: 10px 0;}
.receipt-record table { font-size: 11pt; }
.receipt-record table th { border: 1px solid #777; }
.receipt-record table td { border: 1px solid #777; font-size: 10pt; padding: 1px 4px;}
.receipt-record .annual-total { text-align: right; font-size: 11pt; margin-right: 5px; }

.receipt-signature { margin: 50px 0 0 0; }
.receipt-signature table td.label { font-size: 11pt; text-align: right; }
.receipt-signature table td.value { font-size: 11pt; text-align: left; }

.receipt-footer {clear:both; position: relative; margin-top: 10px; font-size: 10pt;}
.receipt-footer table {font-size: 10pt;}
.receipt-footer table, .receipt-footer td { border:1px solid #777; }
.receipt-footer table .col-1 {text-align: center;}
.receipt-footer table .col-2 {width: 46%; vertical-align: top;}
.receipt-footer table .col-3 {text-align: center;}
.receipt-footer table .col-4 {width: 47%;}
.receipt-footer .thank {font-weight: bold;}
.receipt-footer .note {margin-top: 5px; padding-bottom: 20px;}
.receipt-footer .org-info { display:table-cell; font-size:9pt; width:255px; }
.receipt-footer .org-desc { display:table-cell; font-size:8pt; }

.single-page-header { height: 200px; position: relative; }
.single-page-header .info {position: absolute; top: 45px; left: 50px; max-width:250px; }
.single-page-header .info .address { margin-bottom: 10px;}

.stamp-wrapper{position: relative;}
.stamp-img{position: absolute;}
.big-stamp{bottom: 0;max-height: 103px;left: 1px;}
.small-stamp{top: 0;max-height: 43px;left: 70px;z-index: -1;}

{/literal}
</style>
</head>
<body>
{$pages}
</body>
</html>
