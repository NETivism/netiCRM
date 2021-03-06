{*
Template name: blockHeader.tpl
Base on: example/templates/block/block--image--mailchimp.html

===================================
Class name mapping of nmeEditor and mailchimp:
===================================
nmeb-image nmeb | mcnImageBlock
nmeb-outer | mcnImageBlockOuter
nmeb-inner | mcnImageBlockInner
nmeb-content-container | mcnImageContentContainer
nmeb-content | mcnImageContent
nmee-image nme-elem | mcnImage

===================================
Setting mapping
===================================
block (nmeb-inner): block padding
elemContainer (nmeb-content-container): none
elemContainerInner (nmeb-content): image align
elem (nme-elem): none
*}

<textarea class="nme-tpl" data-template-level="block" data-template-name="header">
{* Template Content: BEGIN *}
<table data-id="[nmeBlockID]" data-type="[nmeBlockType]" border="0" cellpadding="0" cellspacing="0" width="100%" class="nmeb-header nmeb" style="min-width: 100%;">
  <tbody class="nmeb-outer">
    <tr>
      <td valign="top" style="padding: 20px 0;" class="nmeb-inner" data-settings-target="block">
        <!--[if mso]>
          <table align="left" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100%;">
          <tr>
        <![endif]-->
        <!--[if mso]>
          <td valign="top" width="600" style="width:600px;">
        <![endif]-->
        <table align="left" width="100%" border="0" cellpadding="0" cellspacing="0" class="nmeb-content-container" data-settings-target="elemContainer" style="min-width: 100%;">
          <tbody>
            <tr>
              <td class="nmeb-content" valign="top" data-settings-target="elemContainerInner" style="text-align: center;">
                <img align="center" alt="" src="{$config->resourceBase}packages/mailingEditor/images/mail-default-logo@2x.png" width="192" style="max-width: 680px; padding-bottom: 0; display: inline !important; vertical-align: bottom;" class="nmee-image nme-elem" data-settings-target="elem">
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