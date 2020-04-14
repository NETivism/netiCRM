{*
Template name: blockTitle.tpl
Base on: example/templates/block/block--text--mailchimp.html

===================================
Class name mapping of nmeEditor and mailchimp:
===================================
nmeb-title nmeb | mcnTextBlock
nmeb-outer | mcnTextBlockOuter
nmeb-inner | mcnTextBlockInner
nmeb-content-container | mcnTextContentContainer
nmeb-content | mcnTextContent
nmee-title nme-elem | none

===================================
Setting mapping
===================================
block (nmeb-inner): block padding
elemContainer (nmeb-content-container): none
elemContainerInner (nmeb-content): none
elem (nme-elem): title font, text, color
*}

<div class="nme-tpl" data-template-level="block" data-template-name="title">
{* Template Content: BEGIN *}
<table data-id="[nmeBlockID]" data-type="[nmeBlockType]" border="0" cellpadding="0" cellspacing="0" width="100%" class="nmeb-title nmeb" style="min-width: 100%;">
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
                <h3 class="nmee-title nme-elem" data-settings-target="elem">[Title HERE]</h3>
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
</div>