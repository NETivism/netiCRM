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

$.fn.crmaccordions = function() {
  $(".crm-accordion-wrapper").each(function() {
    var $this = $(this);
    
    if (!$this.hasClass("crm-accordion-processed")) {
      $this.on("click mouseenter mouseleave", ".crm-accordion-header", function(e) {
        var $header = $(this);
        var $wrapper = $header.parent();

        switch (e.type) {
          case "click":
              $wrapper.toggleClass("crm-accordion-open");
              $wrapper.toggleClass("crm-accordion-closed");

              if ($wrapper.hasClass("crm-accordion-open")) {
                $wrapper.trigger("crmaccordion:open");
              } else {
                $wrapper.trigger("crmaccordion:close");
              }
              return false; 
            break;

          case "mouseenter":
            $header.addClass("crm-accordion-header-hover");
            break;

          case "mouseleave":
            $header.removeClass("crm-accordion-header-hover");
            break;
        }
      });
    
      $this.addClass("crm-accordion-processed");
    }
  });
};

})(jQuery);
