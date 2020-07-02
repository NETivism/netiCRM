{*
Template name: blockRcCol1.tpl

===================================
Class name mapping of nmeEditor and mailchimp:
===================================
nmeb-rc-col-1 nmeb | mcnTextBlock
nmeb-outer | mcnTextBlockOuter
nmeb-inner | mcnTextBlockInner
nmeb-content-container | mcnTextContentContainer
nmeb-content | mcnTextContent
nmee-rc-col-1 nme-elem | none

===================================
Setting mapping
===================================
block (nmeb-inner): block padding
elemContainer (nmeb-content-container): none
elemContainerInner (nmeb-content): none
elem (nme-elem): none
*}

<textarea class="nme-tpl" data-template-level="block" data-template-name="rc-col-1">
{* Template Content: BEGIN *}
<table data-id="[nmeBlockID]" data-type="[nmeBlockType]" border="0" cellpadding="0" cellspacing="0" width="100%" class="nmeb-rc-col-1 nmeb" style="min-width: 100%;">
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
                <table class="nmee-rc-col-1 nme-elem" data-settings-target="elem" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0;">
                  <tr>
                    <td style="width: 100%;" class="col-1">[Rich Content HERE]</td>
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