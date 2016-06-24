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

$.fn.crmaccordions = function(){
  $('.crm-accordion-header').each(function(){
    var accordionWrapper = $(this).parent();
    if(!accordionWrapper.hasClass('crm-accordion-processed')) {
      $(this).die('click');
      $(this).live('click', function (e) {
        accordionWrapper.toggleClass('crm-accordion-open');
        accordionWrapper.toggleClass('crm-accordion-closed');
        e.preventDefault();
      });
      $(this).live('mouseover', function() {
        $(this).addClass('crm-accordion-header-hover');
      });
      $(this).live('mouseout', function() {
        $(this).removeClass('crm-accordion-header-hover');
      });
      accordionWrapper.addClass('crm-accordion-processed');
    }
  });
};

})(jQuery);
