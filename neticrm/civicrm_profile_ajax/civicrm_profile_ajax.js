(function($){
  $.fn.orgAutocomplete = function () {
    var defaultsContact = {
        returnParam: ['sort_name','email'],
        params: {
          rowCount:35,
          json:1,
          fnName:'civicrm/contact/search'
      }
    };
    var options = {params:{contact_type:"Organization"}};

    settings = $.extend(true,{},defaultsContact, options);

    var contactUrl = "/civicrm/ajax/rest" + "?";
    // How to loop on all the attributes ??
    for  (param in settings.params) {
      contactUrl = contactUrl + param +"="+ settings.params[param] + "&"; 
    }

    //    contactUrl = contactUrl + "fnName=civicrm/contact/search&json=1&";
    for (var i=0; i < settings.returnParam.length; i++) {
      contactUrl = contactUrl + 'return['+settings.returnParam[i] + "]&"; 
    }

    //var contactUrl = "/civicrm/ajax/rest?fnName=civicrm/contact/search&json=1&return[sort_name]=1&return[email]&rowCount=25";

    return this.each(function() {
    var selector = this;
    if (typeof $.fn.autocomplete != 'function') 
        $.fn.autocomplete = cj.fn.autocomplete;//to work around the fubar cj
        $(this).autocomplete( contactUrl, {
          dataType:"json",
              extraParams:{sort_name:function () {
            return $(selector).val();}//how to fetch the val ?
          },
          formatItem: function(data,i,max,value,term){
              if (data['email'])
              return value + ' ('+ data['email'] + ")";
              else 
              return value;
          },          
          parse: function(data){
             var acd = new Array();
             for(cid in data){
               acd.push({ data:data[cid], value:data[cid].sort_name, result:data[cid].sort_name });
             }
             return acd;
          },
          
          width: 250,
          delay:100,
          max:25,
          minChars:1,
          selectFirst: true
       });
     });
  }

})(jQuery); 
