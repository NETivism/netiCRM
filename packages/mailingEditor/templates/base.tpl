<textarea class="nme-tpl" data-template-level="base" data-template-name="base">
{* Template Content: BEGIN *}
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
  <!-- neticrm-mailing-editor -->
  <meta charset="utf-8"><!-- utf-8 works for most cases -->
  <meta name="viewport" content="width=device-width"><!-- Forcing initial-scale shouldn't be necessary -->
  <meta http-equiv="X-UA-Compatible" content="IE=edge"><!-- Use the latest (edge) version of IE rendering engine -->
  <meta name="x-apple-disable-message-reformatting"><!-- Disable auto-scale in iOS 10 Mail entirely -->
	<title></title>
	<!-- The title tag shows in email notifications, like Android 4.4. --><!-- Web Font / @font-face : BEGIN --><!-- NOTE: If web fonts are not required, lines 10 - 27 can be safely removed. --><!-- Desktop Outlook chokes on web font references and defaults to Times New Roman, so we force a safe fallback font. --><!--[if mso]>
    <style>
      * {
        font-family: sans-serif !important;
      }
    </style>
  <![endif]--><!-- All other clients get the webfont reference; some will render the font and others will silently fail to the fallbacks. More on that here: http://stylecampaign.com/blog/2015/02/webfont-support-in-email/ --><!--[if !mso]><!--><!-- insert web font reference, eg: <link href='https://fonts.googleapis.com/css?family=Roboto:400,700' rel='stylesheet' type='text/css'> --><!--<![endif]--><!-- Web Font / @font-face : END --><!-- CSS Reset -->
  <style type="text/css">
    {literal}
    /* What it does: Remove spaces around the email design added by some email clients. */
    /* Beware: It can remove the padding / margin and add a background color to the compose a reply window. */
    html,
    body {
      margin: 0 auto !important;
      padding: 0 !important;
      height: 100% !important;
      width: 100% !important;
    }

    /* What it does: Stops email clients resizing small text. */
    * {
      -ms-text-size-adjust: 100%;
      -webkit-text-size-adjust: 100%;
    }

    /* What it does: Centers email on Android 4.4 */
    div[style*="margin: 16px 0"] {
      margin:0 !important;
    }

    /* What it does: Stops Outlook from adding extra spacing to tables. */
    table,
    td {
      mso-table-lspace: 0pt !important;
      mso-table-rspace: 0pt !important;
    }

    /* What it does: Fixes webkit padding issue. Fix for Yahoo mail table alignment bug. Applies table-layout to the first 2 tables then removes for anything nested deeper. */
    table {
      border-spacing: 0 !important;
      border-collapse: collapse !important;
      table-layout: fixed !important;
      margin: 0 auto;
    }
    table table table {
      table-layout: auto;
    }

    /* What it does: Uses a better rendering method when resizing images in IE. */
    img {
      -ms-interpolation-mode: bicubic;
    }

    /* What it does: A work-around for email clients meddling in triggered links. */
    *[x-apple-data-detectors],  /* iOS */
    .x-gmail-data-detectors,    /* Gmail */
    .x-gmail-data-detectors *,
    .aBn {
      border-bottom: 0 !important;
      cursor: default !important;
      /* color: inherit !important; */
      text-decoration: none !important;
      font-size: inherit !important;
      font-family: inherit !important;
      font-weight: inherit !important;
      line-height: inherit !important;
    }

    /* Media Queries */
    @media screen and (max-width: 480px) {
      /* What it does: Forces elements to resize to the full width of their container. Useful for resizing images beyond their max-width. */
      .fluid,
      .nmee-image {
        /* width: 100% !important; */
        max-width: 100% !important;
        height: auto !important;
        margin-left: auto !important;
        margin-right: auto !important;
      }
      .col,
      .col-1,
      .col-2,
      .img-col,
      .text-col {
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        direction: ltr !important;
      }
      .nmeb-inner {
        padding-left: 0 !important;
        padding-right: 0 !important;
      }
      .nmeb-title .nmeb-inner,
      .nmeb-paragraph .nmeb-inner,
      .nmeb-button .nmeb-inner {
        padding-left: 20px !important;
        padding-right: 20px !important;
      }
      .nmeb-rc-col-2 .nmeb-inner,
      .nmeb-rc-float .nmeb-inner {
        padding: 0 !important;
      }
      .nmeb-rc-col-2 .nmeb-paragraph .nmeb-inner,
      .nmeb-rc-float .nmeb-paragraph .nmeb-inner {
        padding: 20px !important;
      }
    }
    {/literal}
	</style>
	<!-- What it does: Makes background images in 72ppi Outlook render at correct size. --><!--[if gte mso 9]>
    <xml>
      <o:OfficeDocumentSettings>
        <o:AllowPNG/>
        <o:PixelsPerInch>96</o:PixelsPerInch>
      </o:OfficeDocumentSettings>
    </xml>
  <![endif]-->
</head>
<body style="margin: 0; mso-line-height-rule: exactly;" width="100%">
</body>
</html>
</textarea>
{* Template Content: END *}