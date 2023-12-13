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
    let pageTitle = $('.page-title').length ? $('.page-title').text().trim() : $('title').text().split('|')[0].trim();

    $('body').html(crmContainerHtml).addClass('embed-preview-mode');

    if (pageTitle) {
      let pageTitleHTML = `<div class="preview-page-title-block"><div class="inner"><h1 class="page-title">${pageTitle}</h1></div></div>`;

      $('.crm-container').prepend(pageTitleHTML);
    }
  }

  if (document.querySelector('.crm-container select[data-parent]')) {
    new crmDependentSelect('.crm-container select[data-parent]');
  }
  $().crmtooltip();
});
}(cj));
