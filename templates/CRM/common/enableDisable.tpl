{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                        |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                  |
 |                                  |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License       |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                  |
 | CiviCRM is distributed in the hope that it will be useful, but   |
 | WITHOUT ANY WARRANTY; without even the implied warranty of     |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.         |
 | See the GNU Affero General Public License for more details.    |
 |                                  |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along          |
 | with this program; if not, contact CiviCRM LLC           |
 | at info[AT]civicrm[DOT]org. If you have questions about the    |
 | GNU Affero General Public License or the licensing of CiviCRM,   |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing    |
 +--------------------------------------------------------------------+
*}
{* handle common enable/disable actions *}
<div id="enableDisableStatusMsg" class="success-status" style="display:none;"></div>
{literal}
<script type="text/javascript">
function modifyLinkAttributes( recordID, op, recordBAO ) {
  //we changed record from enable to disable
  if ( op == 'enable-disable' ) {
    var fieldID   = "#row_"+ recordID + " a." + "disable-action";
    var operation   = "disable-enable";
    var htmlContent = {/literal}'{ts escape="js"}Enable{/ts}'{literal};
    var newClass  = 'action-item enable-action';
    var newTitle  = {/literal}'{ts escape="js"}Enable{/ts}'{literal};
    var newText   = {/literal}' {ts escape="js"}No{/ts} '{literal};
  } else if ( op == 'disable-enable' ) {
    var fieldID   = "#row_"+ recordID + " a." + "enable-action";
    var operation   = "enable-disable";
    var htmlContent = {/literal}'{ts escape="js"}Disable{/ts}'{literal};
    var newClass  = 'action-item disable-action';
    var newTitle  = {/literal}'{ts escape="js"}Disable{/ts}'{literal};
    var newText   = {/literal}' {ts escape="js"}Yes{/ts} '{literal};
  }

  //change html
  cj( fieldID ).html( htmlContent );   

  //change title
  cj( fieldID ).attr( 'title', newTitle );

  //need to update js - change op from js to new allow operation. 
  //set updated js
  var newAction = 'enableDisable( ' + recordID + ',"' + recordBAO + '","' + operation + '" );';
  cj( fieldID ).attr("onClick", newAction );
  
  //set the updated status
  var fieldStatus = "#row_"+ recordID + "_status";
  cj( fieldStatus ).text( newText );

  //finally change class to enable-action.
  cj( fieldID ).attr( 'class', newClass );
}

function removeLinkAttributes( recordID, op, rowId ) {
  if ( op == 'enable-disable' ) {
    var fieldID   = '#' + rowId + '_' + recordID + " a." + "disable-action";
  }
  else if ( op == 'disable-enable' ) {
    var fieldID   = '#' + rowId + '_' + recordID + " a." + "enable-action";
  }

  cj( fieldID ).html( '' );
}

function modifySelectorRow( recordID, op ) {
  var elementID = "#row_" + recordID;
  if ( op == "disable-enable" ) {
    cj( elementID ).removeClass("disabled");
  }
  else if ( op == "enable-disable" )  {
    //we are disabling record.
    cj( elementID ).addClass("disabled");
  }
}

function hideEnableDisableStatusMsg( ) {
  cj( '#enableDisableStatusMsg' ).hide( );
}

cj( '#enableDisableStatusMsg' ).hide( );
function enableDisable( recordID, recordBAO, op, reloadPage ) {
  if ( op == 'enable-disable' ) {
    var st = {/literal}'{ts escape="js"}Disable Record{/ts}'{literal};
  }
  else if ( op == 'disable-enable' ) {
    var st = {/literal}'{ts escape="js"}Enable Record{/ts}'{literal};
  }

  cj("#enableDisableStatusMsg").show( );
  cj("#enableDisableStatusMsg").dialog({
    title: st,
    modal: true,
    bgiframe: true,
    position: { my: "center", at: "center", of: window },
    overlay: { 
      opacity: 0.5, 
      background: "white" 
    },
    open:function() {
      var postUrl = {/literal}"{crmURL p='civicrm/ajax/statusmsg' h=0 }"{literal};
      cj.post( postUrl, { recordID: recordID, recordBAO: recordBAO, op: op  }, function( statusMessage ) {
        if ( statusMessage.status ) {
          cj( '#enableDisableStatusMsg' ).show( ).html( statusMessage.status );
        }
        if ( statusMessage.show == "noButton" ) {
          cj('#enableDisableStatusMsg').dialog('option', 'position', "centre");
          cj('#enableDisableStatusMsg').data("width.dialog", 630);
          cj.extend( cj.ui.dialog.prototype, {
            'removebutton': function(buttonName) {
              var buttons = this.element.dialog('option', 'buttons');
              delete buttons[buttonName];
              this.element.dialog('option', 'buttons', buttons);
            }
          });
          cj('#enableDisableStatusMsg').dialog('removebutton', "{/literal}{ts}Cancel{/ts}{literal}"); 
          cj('#enableDisableStatusMsg').dialog('removebutton', "{/literal}{ts}OK{/ts}{literal}"); 
        }  
      }, 'json' );
    },
  
    buttons: { 
      "{/literal}{ts}OK{/ts}{literal}": function() {     
        saveEnableDisable( recordID, recordBAO, op, reloadPage );
        cj("#enableDisableStatusMsg").dialog("close");          
      },
      "{/literal}{ts}Cancel{/ts}{literal}": function() { 
        cj("#enableDisableStatusMsg").dialog("close"); 
      }
    } 
  });
}

//check is server properly processed post.
var responseFromServer = false; 

function noServerResponse( ) {
  if ( !responseFromServer ) { 
    var serverError =  '{/literal}{ts escape="js"}There is no response from server therefore selected record is not updated.{/ts}{literal}'  + '&nbsp;&nbsp;<a href="javascript:hideEnableDisableStatusMsg();"><img title="{/literal}{ts escape="js"}close{/ts}{literal}" src="' +resourceBase+'i/close.png"/></a>';
    cj( '#enableDisableStatusMsg' ).show( ).html( serverError ); 
  }
}

function saveEnableDisable( recordID, recordBAO, op, reloadPage ) {
  cj( '#enableDisableStatusMsg' ).hide( );
  var postUrl = {/literal}"{crmURL p='civicrm/ajax/ed' h=0 }"{literal};

  //post request and get response
  cj.post( postUrl, { recordID: recordID, recordBAO: recordBAO, op:op, key: {/literal}"{crmKey name='civicrm/ajax/ed'}"{literal}  }, function( html ){
    responseFromServer = true;    
     
    //this is custom status set when record update success.
    if ( html.status == 'record-updated-success' ) {
       
      if ( reloadPage ) {
        document.location.reload( );
      }
      //change row class and show/hide action links.
      modifySelectorRow( recordID, op );

      //modify action link html
      if ( recordBAO == 'CRM_Contribute_BAO_ContributionRecur' ) {
        let rowId = "row_" + recordID;
        removeLinkAttributes( recordID, op, rowId );
      } else {
        modifyLinkAttributes( recordID, op, recordBAO);
      }
    } 
    //cj( '#enableDisableStatusMsg' ).show( ).html( successMsg );
  }, 'json' );

  //if no response from server give message to user.
  setTimeout( "noServerResponse( )", 1500 ); 
}
</script>
{/literal}
