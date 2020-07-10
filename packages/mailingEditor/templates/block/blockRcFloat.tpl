{*
Template name: blockRcFloat.tpl

===================================
Class name mapping of nmeEditor and mailchimp:
===================================
nmeb-rc-float nmeb | mcnTextBlock
nmeb-outer | mcnTextBlockOuter
nmeb-inner | mcnTextBlockInner
nmeb-content-container | mcnTextContentContainer
nmeb-content | mcnTextContent
nmee-rc-float nme-elem | none

===================================
Setting mapping
===================================
block (nmeb-inner): block padding
elemContainer (nmeb-content-container): none
elemContainerInner (nmeb-content): none
elem (nme-elem): none
*}

<textarea class="nme-tpl" data-template-level="block" data-template-name="rc-float">
{* Template Content: BEGIN *}
<table data-id="[nmeBlockID]" data-type="[nmeBlockType]" border="0" cellpadding="0" cellspacing="0" width="100%" class="nmeb-rc-float nmeb" style="min-width: 100%;">
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
                <table class="nmee-rc-float nme-elem" data-settings-target="elem" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0;">
                  <tr>
                    <td style="width: 35%;" class="img-col">[Rich Content HERE]</td>
                    <td style="width: 65%;" class="text-col">[Rich Content HERE]</td>
                  </tr>
                </table>
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