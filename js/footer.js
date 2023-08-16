(function ($) {
$(document).ready(function(){
// start here
if (document.querySelector('.crm-container select[data-parent]')) {
  new crmDependentSelect('.crm-container select[data-parent]');
}
$().crmtooltip();
// end here
});
}(cj));
