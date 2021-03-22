<form action="{crmURL p='civicrm/contact/search/basic' h=0 }" name="autocomplete_search_block" id="autocomplete_search_block" method="post" onsubmit="getSearchURLValue();">
  <input type="text" class="form-text md-textfield" id="neticrm_sort_name_navigation" name="sort_name" value="" placeholder="{ts}Name, Phone or Email{/ts}" />
  <input type="hidden" id="sort_contact_id" value="" />
  <i class="zmdi zmdi-search zmdi-hc-flip-horizontal"></i>
</form>
{literal}
<script type="text/javascript">
cj(document).ready(function($){
  window.getSearchURLValue = function() {
    var contactId =  $( "#sort_contact_id" ).val();
    if ( ! contactId || isNaN( contactId ) ) {
      var sortValue = $( "#neticrm_sort_name_navigation" ).val();
      if ( sortValue ) {
        //using xmlhttprequest check if there is only one contact and redirect to view page
        var dataUrl = "/civicrm/ajax/contact?name=" + sortValue;
        var response = $.ajax({
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

  var contactUrl = "{/literal}{crmURL p=civicrm/ajax/rest q="className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&context=navigation" h=0}{literal}";
  $( "#neticrm_sort_name_navigation" ).autocomplete( contactUrl, {
    width: 200,
    selectFirst: false,
    minChars:1,
    matchContains: true
  })
  .result(function(event, data, formatted) {
    document.location = "{/literal}{crmURL p="civicrm/contact/view" q="reset=1&cid=" h=0}{literal}"+data[1];
    return false;
  });
});

</script>
{/literal}