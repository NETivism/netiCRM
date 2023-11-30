(function ($) {
$(document).ready(function(){
  let urlParams = new URLSearchParams(window.location.search);

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
