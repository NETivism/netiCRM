{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
<div id="help">
    {ts}If you are sending emails to contacts using CiviCRM - then you need to enter settings for your SMTP/Sendmail server. You can send a test email to check your SMTP/Sendmail settings by clicking "Save and Send Test Email". If you're unsure of the correct values, check with your system administrator, ISP or hosting provider. If you do not want users to send outbound mail from CiviCRM, select "Disable Outbound Email". NOTE: If you disable outbound email, and you are using Online Contribution pages or online Event Registration - you will need to disable automated receipts and registration confirmations.{/ts}
</div>
<div class="form-item">
    <fieldset>
        <legend>{ts}Settings - Outbound Email{/ts}</legend>
        <dl>
            <dt>{$form.outBound_option.label}</dt>
            <dd>{$form.outBound_option.html}</dd>
        </dl>
        <div id="bySMTP">
            <fieldset>
                <legend>{ts}SMTP Configuration{/ts}</legend>
                <dl>
                    <dt>{$form.smtpServer.label}</dt>
                    <dd>{$form.smtpServer.html}</dd>
                    
                    <dt>&nbsp;</dt>
                    <dd class="description">{ts}Enter the SMTP server (machine) name. EXAMPLE: smtp.example.com{/ts}</dd>
                    
                    <dt>{$form.smtpPort.label}</dt>
                    <dd>{$form.smtpPort.html}</dd>
                    
                    <dt>&nbsp;</dt>
                    <dd class="description">{ts}The standard SMTP port is 25. You should only change that value if your SMTP server is running on a non-standard port.{/ts}</dd>
                    
                    <dt>{$form.smtpAuth.label}</dt>
                    <dd>{$form.smtpAuth.html}</dd>
                    
                    <dt>&nbsp;</dt>
                    <dd class="description">{ts}Does your SMTP server require authentication (user name + password)?{/ts}</dd>
                    
                    <dt>{$form.smtpUsername.label}</dt>
                    <dd>{$form.smtpUsername.html}</dd>
                    
                    <dt>{$form.smtpPassword.label}</dt>
                    <dd>{$form.smtpPassword.html}</dd>
                    
                    <dt>&nbsp;</dt>
                    <dd class="description">{ts}If your SMTP server requires authentication, enter your Username and Password here.{/ts}</dd>
                </dl>
                <div class="spacer"></div>
            </fieldset>
        </div>
        <div id="bySendmail">
            <fieldset>
                <legend>{ts}Sendmail Configuration{/ts}</legend>
                <dl>
                    <dt>{$form.sendmail_path.label}</dt>
                    <dd>{$form.sendmail_path.html}</dd>
                    
                    <dt>&nbsp;</dt>
                    <dd class="description">{ts}Enter the Sendmail Path. EXAMPLE: /usr/sbin/sendmail{/ts}</dd>
                    
                    <dt>{$form.sendmail_args.label}</dt>
                    <dd>{$form.sendmail_args.html}</dd>
                </dl>
            <div class="spacer"></div>
            </fieldset>
        </div>
        <dl>
            <dt></dt>
            <dd>{$form.buttons.html}&nbsp;&nbsp;&nbsp;&nbsp;{$form._qf_Smtp_refresh_test.html}</dd>
        </dl>    
    </fieldset>
</div>    

{literal}
<script type="text/javascript">
    showHideMailOptions();
    cj( function( ) {
        cj("input[name='outBound_option']").click( function( ) {
            showHideMailOptions();
        });
    });
    
    function showHideMailOptions()
    {   
        if (document.getElementsByName("outBound_option")[0].checked) {
            show("bySMTP");
            hide("bySendmail");
            cj("#_qf_Smtp_refresh_test").show( );
        } else if (document.getElementsByName("outBound_option")[1].checked) {
            hide("bySMTP");
            show("bySendmail");
            cj("#_qf_Smtp_refresh_test").show( );
        } else {
            hide("bySMTP");
            hide("bySendmail");
            cj("#_qf_Smtp_refresh_test").hide( );
        }
    }
</script>
{/literal}
