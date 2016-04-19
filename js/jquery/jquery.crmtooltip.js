/*
* +--------------------------------------------------------------------+
* | CiviCRM version 3.3                                                |
* +--------------------------------------------------------------------+
* | Copyright CiviCRM LLC (c) 2004-2010                                |
* +--------------------------------------------------------------------+
* | This file is a part of CiviCRM.                                    |
* |                                                                    |
* | CiviCRM is free software; you can copy, modify, and distribute it  |
* | under the terms of the GNU Affero General Public License           |
* | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
* |                                                                    |
* | CiviCRM is distributed in the hope that it will be useful, but     |
* | WITHOUT ANY WARRANTY; without even the implied warranty of         |
* | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
* | See the GNU Affero General Public License for more details.        |
* |                                                                    |
* | You should have received a copy of the GNU Affero General Public   |
* | License and the CiviCRM Licensing Exception along                  |
* | with this program; if not, contact CiviCRM LLC                     |
* | at info[AT]civicrm[DOT]org. If you have questions about the        |
* | GNU Affero General Public License or the licensing of CiviCRM,     |
* | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
* +--------------------------------------------------------------------+
*/
(function($){

$.fn.crmtooltip = function(){
  $('a.crm-summary-link').each(function(){
    $(this).replaceWith($('<div data="'+this.href+'" class="'+ $(this).attr('class')+'">' + this.innerHTML + '</div>'));
  });
  var timeout;
  $('.crm-summary-link')
    .addClass('crm-processed')
    .hover(function(e) {
      var $container = $(this);
      $container.css('cursor', 'wait');
      timeout = setTimeout(function(){
        $container.css('cursor', 'text');
        $('.crm-tooltip-wrapper').hide();
        $('.crm-summary-link').removeClass('crm-tooltip-active');
        $container.addClass('crm-tooltip-active');
        if ($container.parent().find('.crm-tooltip-wrapper').length == '') {
          var $tooltip = $('<div class="crm-tooltip-wrapper"><div class="crm-tooltip"></div></div>');
          var $close = $('<div class="zmdi zmdi-close-circle-o close"></div>');
          $tooltip.appendTo($container);
          $tooltip.children('.crm-tooltip')
            .html('<div class="crm-loading-element"></div>')
            .load($container.attr('data'), function(){
              $close.click(function(){
                $('.crm-tooltip-wrapper').remove();
                $('.crm-summary-link').removeClass('crm-tooltip-active');
                $('.crm-summary-link').removeClass('crm-tooltip-down');
              });
              $close.appendTo($(this)); 
            });
        }
        else{
          $container.parent().find('.crm-tooltip-wrapper').show();
        }
        clearTimeout(timeout);
      }, 450);
    }, function(e){
      $(this).css('cursor', 'pointer');
      clearTimeout(timeout);
    });
};

})(jQuery);

