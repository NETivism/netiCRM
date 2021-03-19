  <form action="{crmURL p='civicrm/contact/search/basic' h=0 }" name="search_block" id="id_search_block" method="post" onsubmit="getSearchURLValue();">
    <div id="neticrm-quickSearch">
      <input type="text" class="form-text md-textfield" id="neticrm_sort_name_navigation" name="sort_name" value="" placeholder="{ts}Name, Phone or Email{/ts}" />
      <input type="hidden" id="sort_contact_id" value="" />
      <button type="submit" name="_qf_Basic_refresh" class="form-submit default"><i class="zmdi zmdi-search zmdi-hc-flip-horizontal"></i></button>
    </div>
  </form>

{literal}
<script type="text/javascript">
(function($){

function getSearchURLValue() {
  var contactId =  cj( "#sort_contact_id" ).val();
  if ( ! contactId || isNaN( contactId ) ) {
    var sortValue = cj( "#neticrm_sort_name_navigation" ).val();
    if ( sortValue ) {
      //using xmlhttprequest check if there is only one contact and redirect to view page
      var dataUrl = "/civicrm/ajax/contact?name=" + sortValue;
      var response = cj.ajax({
        url: dataUrl,
        async: false
      }).responseText;
      contactId = response;
    }
  }
  if ( contactId ) {
    var url = "/civicrm/contact/view?reset=1&cid=" + contactId;
    document.getElementById("id_search_block").action = url;
  }
}

var contactUrl = drupalSettings.basePath + "civicrm/ajax/rest?className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&context=navigation";
cj( "#neticrm_sort_name_navigation" ).autocomplete( contactUrl, {
  width: 200,
  selectFirst: false,
  minChars:1,
  matchContains: true
})
.result(function(event, data, formatted) {
  document.location = Drupal.settings.basePath + "civicrm/contact/view?reset=1&cid="+data[1];
  return false;
});

})(cj)

</script>
{/literal}