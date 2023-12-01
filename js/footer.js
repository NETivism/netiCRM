(function ($) {
$(document).ready(function(){
  let urlParams = new URLSearchParams(window.location.search);

  /**
   * This is a straightforward JavaScript workaround when 'templates/CRM/common/Embed.tpl' is not used.
   * Advantages:
   * - 1. Ensures consistent embedded content styles, especially in scenarios with different templates.
   * - 2. Simple and convenient to use.
   * Disadvantages:
   * - 1. Loads the complete HTML.
   * - 2. Sometimes might not be the best practice.
   */
  if (urlParams.get('embed') === '1' && !$('.embed-preview-mode').length) {
    let crmContainerHtml = $('.crm-container').clone().wrap('<div>').parent().html();
    $('body').html(crmContainerHtml).addClass('embed-preview-mode');
  }

  if (document.querySelector('.crm-container select[data-parent]')) {
    new crmDependentSelect('.crm-container select[data-parent]');
  }
  $().crmtooltip();
});
}(cj));
