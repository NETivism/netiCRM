{*
Template name: blockFooter.tpl
Base on: example/templates/block/block--text--mailchimp.html

===================================
Class name mapping of nmeEditor and mailchimp:
===================================
nmeb-footer nmeb | mcnTextBlock
nmeb-outer | mcnTextBlockOuter
nmeb-inner | mcnTextBlockInner
nmeb-content-container | mcnTextContentContainer
nmeb-content | mcnTextContent
nmee-footer nme-elem | none

===================================
Setting mapping
===================================
block (nmeb-inner): block padding
elemContainer (nmeb-content-container): none
elemContainerInner (nmeb-content): none
elem (nme-elem): none
*}

<textarea class="nme-tpl" data-template-level="block" data-template-name="footer">
{* Template Content: BEGIN *}
<table data-id="[nmeBlockID]" data-type="[nmeBlockType]" border="0" cellpadding="0" cellspacing="0" width="100%" class="nmeb-footer nmeb" style="min-width: 100%;">
  <tbody class="nmeb-outer">
    <tr>
      <td valign="top" class="nmeb-inner" data-settings-target="block">
        <!--[if mso]>
            <table align="left" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100%;">
            <tr>
            <![endif]-->
        <!--[if mso]>
            <td valign="top" width="600" style="width:600px;">
            <![endif]-->
        <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width: 100%; min-width: 100%;" width="100%" class="nmeb-content-container" data-settings-target="elemContainer">
          <tbody>
            <tr>
              <td valign="top" class="nmeb-content" data-settings-target="elemContainerInner">
                <div class="nmee-footer nme-elem" data-settings-target="elem" style="margin: 0; text-align: center;">
                  <p>{domain.name}<br>{domain.address}</p><p><a href="%7Bmailing.viewUrl%7D" target="_blank">在瀏覽器中觀看</a></webversion> | <unsubscribe style="text-decoration:underline;"><a href="%7Baction.unsubscribeUrl%7D">退訂電子報</a></unsubscribe><br><a href="%7Baction.subscribeUrl%7D">訂閱電子報</a> | <a href="%7Baction.forward%7D">轉寄給朋友</a> | <a href="%7Baction.optOutUrl%7D">不再收到本站訊息</a></p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <!--[if mso]>
          </td>
          <![endif]-->
        <!--[if mso]>
          </tr>
          </table>
          <![endif]-->
      </td>
    </tr>
  </tbody>
</table>
{* Template Content: END *}
</textarea>