{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
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
<div id="pricesetTotal" class="crm-section section-pricesetTotal">
	<div class="label" id="pricelabel"><label>{ts}Total Fee(s){/ts}</label></div>
	<div class="content view-value" id="pricevalue" ></div>
</div>

<script type="text/javascript">
{literal}

var totalfee       = 0;
var thousandMarker = '{/literal}{$config->monetaryThousandSeparator}{literal}';
var seperator      = '{/literal}{$config->monetaryDecimalPoint}{literal}';
var symbol         = '{/literal}{$currencySymbol}{literal}';
var optionSep      = '|';
var priceSet = price = [];
cj("#priceset select, #priceset input").each(function () {
  var addprice, singlePrice, countEleName, $countEle;
  if ( cj(this).attr('price') ) {
    var thetype = cj(this)[0].tagName == "INPUT" ? cj(this).attr('type') : cj(this)[0].tagName.toLowerCase();
    switch( thetype ) {
      case 'checkbox':
        //default calcution of element. 
        eval( 'var option = ' + cj(this).attr('price') ) ;
        ele        = option[0];
        optionPart = String(option[1]).split(optionSep);
        singlePrice   = parseFloat( optionPart[0] );
        fdName = cj(this).attr('name').replace(/\[\d+\]/,'');
        countEleName = fdName+'_'+ele+'_count';

        var $countEle = cj('#'+countEleName);
        if($countEle.val() == '' || $countEle.val() == 0){
          $countEle.hide();
        }
        
        if( cj(this).attr('checked') ) {
          cj('#'+countEleName).show();
          addPrice = calculateCheckbox(fdName, ele);
          if(!price[ele]){
            price[ele] = 0;
          }
          totalfee   += addPrice;
          price[ele] += addPrice;
        }

        //event driven calculation of element.
        cj(this).click( function(){
          $countEle = cj('#'+countEleName);
          if ( cj(this).attr('checked') )  {
            $countEle.show();
            if($countEle.length > 0){
              if(!$countEle.val() || $countEle.val() == 0){
                $countEle.val(1);
              }
            }
          } else {
            $countEle.val("0").hide();
          }
          addPrice = calculateCheckbox(fdName, ele);
          if(!price[ele]){
            price[ele] = 0;
          }
          subtractPrice = addPrice - price[ele];
          totalfee   += subtractPrice;
          price[ele] += subtractPrice;
          display( totalfee );
        });
        display( totalfee );
        break;
        
      case 'radio':
        //default calcution of element. 
        eval( 'var option = ' + cj(this).attr('price') ); 
        ele        = option[0];
        optionPart = String(option[1]).split(optionSep);
        singlePrice   = parseFloat( optionPart[0] );
        countEleName = ele+'_'+cj(this).attr('value')+'_count';
        if ( ! price[ele] ) {
          price[ele] = 0;
        }

        $countEle = cj('#'+countEleName);
        if($countEle.val() == '' || $countEle.val() == 0){
          $countEle.hide();
        }
        
        if( cj(this).attr('checked') ) {
          $countEle = cj('#'+countEleName);
          if($countEle.length > 0){
            $countEle.show();
            if(!$countEle.val()){
              $countEle.val(1);
            }
            count = $countEle.val();
          }else{
            count = 1;
          }
          addprice = singlePrice * count;
          totalfee   = parseFloat(totalfee) + addprice - parseFloat(price[ele]);
          price[ele] = addprice;
        }
        
        //event driven calculation of element.
        cj(this).click( function(){ 
          cj('[id^='+ele+'][id$="_count"]').not('#'+countEleName).val("").hide();
          $countEle = cj('#'+countEleName);
          if($countEle.length > 0){
            $countEle.show();
            if(!$countEle.val()){
              $countEle.val(1);
            }
            count = $countEle.val();
          }else{
            count = 1;
          }
          addprice = singlePrice * count;
          totalfee   = parseFloat(totalfee) + addprice - parseFloat(price[ele]);
          price[ele] = addprice;
          
          display( totalfee );
        });
        display( totalfee );
        break;
        
      case 'text':
      case 'number':
        //default calcution of element. 
        var textval = parseFloat( cj(this).val() );
        if ( textval ) {
          eval( 'var option = '+ cj(this).attr('price') );
          ele         = option[0];
          if ( ! price[ele] ) {
           price[ele] = 0;
          }
          optionPart = String(option[1]).split(optionSep);
          addprice   = parseFloat( optionPart[0] );
          var curval  = textval * addprice;
          if ( textval >= 0 ) {
            totalfee   = parseFloat(totalfee) + curval - parseFloat(price[ele]);
            price[ele] = curval;
          }
        }
        
        //event driven calculation of element.
        cj(this)
          .bind( 'keyup', function() { calculateText( this ); })
          .bind( 'blur' , function() { calculateText( this ); })
          .bind( 'change' , function() { calculateText( this ); });
        display( totalfee );
        break;

      case 'select':
        //default calcution of element. 
        var ele = cj(this).attr('id');
          if ( ! price[ele] ) {
            price[ele] = 0;
          }
          eval( 'var selectedText = ' + cj(this).attr('price') );
          var addprice = 0;
          if ( cj(this).val( ) ) {
            optionPart = selectedText[cj(this).val( )].split(optionSep);
            addprice   = parseFloat( optionPart[0] );
          } 

        if ( addprice ) {
          totalfee   = parseFloat(totalfee) + addprice - parseFloat(price[ele]);
          price[ele] = addprice;
        }
        
        //event driven calculation of element.
        cj(this).change( function() {
          var ele = cj(this).attr('id');
          if ( ! price[ele] ) {
            price[ele] = 0;
          }
          eval( 'var selectedText = ' + cj(this).attr('price') );

          var addprice = 0;
          if ( cj(this).val( ) ) {
            optionPart = selectedText[cj(this).val( )].split(optionSep);
            addprice   = parseFloat( optionPart[0] );
          }

          if ( addprice ) {
            totalfee   = parseFloat(totalfee) + addprice - parseFloat(price[ele]);
            price[ele] = addprice;
          } else {
            totalfee   = parseFloat(totalfee) - parseFloat(price[ele]);
            price[ele] = parseFloat('0');
          }
          display( totalfee );
        });
        display( totalfee );
        break;
    }
  }
});

//calculation for text box.
function calculateText( object ) {
  eval( 'var option = ' + cj(object).attr('price') );
  ele = option[0];
  if(cj('[name='+ele+']').is('input.form-radio')){
    opid = option[1];
    cj('[name='+ele+'][value='+opid+']').trigger('click');
  }
  else if(cj('[name^='+ele+'][type="checkbox"]').length > 0){
    fdName = ele;
    ele = option[1];
    addPrice = calculateCheckbox(fdName, ele);
    subtractPrice = addPrice - price[ele];
    totalfee   += subtractPrice;
    price[ele] += subtractPrice;
    display( totalfee );

  }
  else{
    if ( ! price[ele] ) {
      price[ele] = 0;
    }
    var optionPart = String(option[1]).split(optionSep);
    addprice    = parseFloat( optionPart[0] );
    var textval = parseFloat( cj(object).attr('value') );
    var curval  = textval * addprice;
    if ( textval >= 0 ) {
      totalfee   = parseFloat(totalfee) + curval - parseFloat(price[ele]);
      price[ele] = curval;
    } else {
      totalfee   = parseFloat(totalfee) - parseFloat(price[ele]);	
      price[ele] = parseFloat('0');
    }
    display( totalfee );  
  }
}

function calculateCheckbox( fdName , ele){
  var $fd = cj('[id="'+fdName+'['+ele+']"]');
  eval( 'var option = ' + $fd.attr('price') );
  var optionPart = String(option[1]).split(optionSep);
  var singlePrice = optionPart[0];
  var countEleName = fdName+'_'+ele+'_count';
  var count = cj('#'+countEleName).val();
  return parseFloat(singlePrice) * parseInt(count);
}

//display calculated amount
function display( totalfee ) {
    var totalEventFee  = formatMoney( totalfee, 2, seperator, thousandMarker);
    document.getElementById('pricevalue').innerHTML = "<b>"+symbol+"</b> "+totalEventFee;
    scriptfee   = totalfee;
    scriptarray = price;
    cj('#total_amount').val( totalfee );
    
    ( totalfee < 0 ) ? cj('table#pricelabel').addClass('disabled') : cj('table#pricelabel').removeClass('disabled');
    
}

//money formatting/localization
function formatMoney (amount, c, d, t) {
var n = amount, 
    c = isNaN(c = Math.abs(c)) ? 2 : c, 
    d = d == undefined ? "," : d, 
    t = t == undefined ? "." : t, s = n < 0 ? "-" : "", 
    i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", 
    j = (j = i.length) > 3 ? j % 3 : 0;
	return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}

{/literal}
</script>
