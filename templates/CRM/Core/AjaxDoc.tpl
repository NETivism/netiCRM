
<style>
{literal}
#result {background:lightgrey;}
#selector a {margin-right:10px;}
.required {font-weight:bold;}
.helpmsg {background:yellow;}
#explorer label {display:inline;}
code {line-height:1em;}
{/literal}
</style>

<script>
{literal}

cj(function($) {
  var restURL = CRMurl("civicrm/ajax/rest");

  function toggleField (name, label, type) {
    var h = '<div>\
      <label for="' + name + '">'+label+'</label>: <input name="' + name + '" data-id="'+name+ '" />\
      <a href="#" class="remove-extra" title={/literal}"{ts escape="js"}Remove Field{/ts}"{literal}>X</a>\
    </div>';
    if ( $('#extra [name=' + name + ']').length > 0) {
      $('#extra [name=' + name + ']').parent().remove();
    }
    else {
      $('#extra').append (h);
    }
  }

  function buildForm (params) {
    var h = '<label>ID</label><input data-id="id" size="3" maxlength="20" />';
    if (params.action == 'delete') {
      $('#extra').html(h);
      return;
    }
  
    CRMapi(params.entity, 'getFields', {}, {
      success:function (data) {
        h = {/literal}'<i>{ts escape="js"}Available fields (click to add/remove):{/ts}</i>'{literal};
        $.each(data.values, function(key, value) {
          var required = value.required ? " required" : "";
          h += "<a data-id='" + key + "' class='type_" + value.type + required + "'>" + value.title + "</a>";
        });
        $('#selector').html(h);
      }
    });
  }

  function generateQuery () {
    var params = {};
    $('#explorer input:checkbox:checked, #explorer select, #extra input').each(function() {
      var val = $(this).val();
      if (val) {
        params[$(this).data('id')] = val;
      }
    });
    query = CRMurl("civicrm/ajax/rest", params);
    $('#query').val(query);
    if (params.action == 'delete' && $('#selector a').length == 0) {
      buildForm (params);
      return;
    }
    if (params.action == 'create' && $('#selector a').length == 0) {
      buildForm (params);
      return;
    }
  }

  function runQuery(query) {
    var vars = [],
    hash,
    smarty = '',
    php = "$params = array(<br />&nbsp;&nbsp;'version' => 3,",
    json = "{",
    link = "",
    key,
    value,
    entity,
    action,
    query = $('#query').val();
    var hashes = query.slice(query.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++) {
      hash = hashes[i].split('=');
      key = hash[0];
      value = hash[1];

      switch (key) {
       case 'version':
       case 'debug':
       case 'json':
         break;
       case 'action':
         action = value.toLowerCase();
         $('#action').val(action);
         break;
       case 'entity':
         entity = value.charAt(0).toUpperCase() + value.substr(1);
         $('#entity').val(entity);
         break;
       default:
         if (typeof value == 'undefined') {
           break;
         }
         value = isNaN(value) ? "'" + value + "'" : value;
         smarty += ' ' + key + '=' + value;
         php += "<br />&nbsp;&nbsp'" + key +"' => " + value + ",";
         json += "'" + key + "': " + value + ", ";
      }
    }

    if (!entity) {
      $('#query').val({/literal}"{ts escape='js'}Choose an entity.{/ts}"{literal});
      $('#entity').val('');
      window.location.hash = 'explorer';
      return;
    }
    if (!action) {
      $('#query').val({/literal}"{ts escape='js'}Choose an action.{/ts}"{literal});
      $('#action').val('');
      window.location.hash = 'explorer';
      return;
    }

    window.location.hash = query;
    $('#result').html('<i>Loading...</i>');
    $.post(query,function(data) {
      $('#result').text(data);
    },'text').fail(function(){
      $('#result').text({/literal}"{ts escape='js'}You have no permission to use this feature. Please join to netiCRM.tw .{/ts}"{literal});
    });
    link="<a href='"+query+"' title='open in a new tab' target='_blank'>ajax query</a>&nbsp;";
    var RESTquery = Drupal.settings.resourceBase + "extern/rest.php?"+ query.substring(restURL.length,query.length) + "&api_key={yoursitekey}&key={yourkey}";
    $("#link").html(link+"|<a href='"+RESTquery+"' title='open in a new tab' target='_blank'>REST query</a>.");

    
    json = (json.length > 1 ? json.slice (0,-2) : '{') + '}';
    php += "<br />);<br />";
    $('#php').html(php + "$result = civicrm_api('" + entity + "', '" + action + "', $params);");
    $('#jQuery').html ("CRMapi('"+entity+"', '"+action+"', "+json+",<br />&nbsp;&nbsp;{success: function(data) {<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;cj.each(data, function(key, value) {// do something  });<br />&nbsp;&nbsp;&nbsp;&nbsp;}<br />&nbsp;&nbsp;}<br />);");

    if (action.substring(0, 3) == "get") {//using smarty only make sense for get actions
      $('#smarty').html("{crmAPI var='result' entity='" + entity + "' action='" + action + "' " + smarty + '}<br />{foreach from=$result.values item=' + entity + '}<br/>&nbsp;&nbsp;&lt;li&gt;{$' + entity +'.some_field}&lt;/li&gt;<br />{/foreach}');
    } else {
      $('#smarty').html("smarty uses only 'get' actions");
    }
    $('#generated').show();
  }

  var query = window.location.hash;
  var t = "#/civicrm/ajax/rest";
  if (query.substring(0, t.length) === t) {
    $('#query').val (query.substring(1)).focus();
  } else {
    window.location.hash="explorer"; //to be sure to display the result under the generated code in the viewport
  }
  $('#entity, #action').change (function() { $("#selector, #extra").empty(); generateQuery(); runQuery(); });
  $('#explorer input:checkbox').change(function() {generateQuery(); runQuery(); });
  $('#explorer').submit(function() {runQuery(); return false;});
  $('#extra').on('keyup', 'input', generateQuery);
  $('#extra').on('click', 'a.remove-extra', function() {
    $(this).parent().remove();
    generateQuery();
  });
  $('#selector').on('click', 'a', function() {
    toggleField($(this).data('id'), this.innerHTML, this.class);
  });

  function CRMurl(p, params) {
    var tplURL = '/civicrm/example?placeholder';
    if (p == "init") {
      tplURL = params;
      urlInitted = true;
      return;
    }
    params = params || '';
    var frag = p.split ('?');
    var url = tplURL.replace("civicrm/example", frag[0]);

    if (typeof(params) == 'string') {
      url = url.replace("placeholder", params);
    }
    else {
      url = url.replace("placeholder", $.param(params));
    }
    if (frag[1]) {
      url += (url.indexOf('?') === (url.length - 1) ? '' : '&') + frag[1];
    }
    // remove trailing "?"
    if (url.indexOf('?') === (url.length - 1)) {
      url = url.slice(0, (url.length - 1));
    }
    return url;
  };

  /**
   * AJAX api
   */
  function CRMapi(entity, action, params, options) {
    // Default settings
    var json = false,
    settings = {
      context: null,
      success: function(result, settings) {
        return true;
      },
      error: function(result, settings) {
        // $().crmError(result.error_message, {/literal}"{ts}Error{/ts}"{literal});
        return false;
      },
      callBack: function(result, settings) {
        if (result.is_error == 1) {
          return settings.error.call(this, result, settings);
        }
        return settings.success.call(this, result, settings);
      },
      ajaxURL: 'civicrm/ajax/rest'
    };
    action = action.toLowerCase();
    // Default success handler
    switch (action) {
      case "update":
      case "create":
      case "setvalue":
      case "replace":
        settings.success = function() {
          // CRM.alert('', ts('Saved'), 'success');
          return true;
        };
        break;
      case "delete":
        settings.success = function() {
          // CRM.alert('', ts('Removed'), 'success');
          return true;
        };
    }
    for (var i in params) {
      if (i.slice(0, 4) == 'api.' || typeof(params[i]) == 'Object') {
        json = true;
        break;
      }
    }
    if (json) {
      params = {
        entity: entity,
        action: action,
        json: JSON.stringify(params)
      };
    }
    else {
      params.entity = entity;
      params.action = action;
      params.json = 1;
    }
    // Pass copy of settings into closure to preserve its value during multiple requests
    (function(stg) {
      $.ajax({
        url: stg.ajaxURL.indexOf('http') === 0 ? stg.ajaxURL : CRMurl(stg.ajaxURL),
        dataType: 'json',
        data: params,
        type: action.indexOf('get') < 0 ? 'POST' : 'GET',
        success: function(result) {
          stg.callBack.call(stg.context, result, stg);
        }
      });
    })($.extend({}, settings, options));
  };
  

});
{/literal}
</script>
<body>
<form id="explorer">
<label>entity</label>
<select id="entity" data-id="entity">
  <option value="" selected="selected">Choose...</option>
{foreach from=$entities.values item=entity}
  <option value="{$entity}">{$entity}</option>
{/foreach}
</select>
&nbsp;|&nbsp;

<label>action</label>
<select id="action" data-id="action">
  <option value="" selected="selected">Choose...</option>
  <option value="get">get</option>
  <option value="create" title="used to update as well, if id is set">create</option>
  <option value="delete">delete</option>
  <option value="getfields">getfields</option>
  <option value="getactions">getactions</option>
  <option value="getcount">getcount</option>
  <option value="getsingle">getsingle</option>
  <option value="getvalue">getvalue</option>
  <option value="getoptions">getoptions</option>
</select>
&nbsp;|&nbsp;

<label for="debug-checkbox">
  <input type="checkbox" id="debug-checkbox" data-id="debug" checked="checked" value="1">debug
</label>
&nbsp;|&nbsp;

<label for="sequential-checkbox" title="{ts}sequential is a more compact format, that is nicer and general and easier to use for json and smarty.{/ts}">
  <input type="checkbox" id="sequential-checkbox" data-id="sequential" checked="checked" value="1">sequential
</label>
&nbsp;|&nbsp;

<label for="json-checkbox">
  <input type="checkbox" id="json-checkbox" data-id="json" checked="checked" value="1">json
</label>

<div id="selector"></div>
<div id="extra"></div>
<input size="90" maxsize=300 id="query" value="{crmURL p="civicrm/ajax/rest" q="json=1&debug=on&entity=Contact&action=get&sequential=1&return=display_name,email,phone"}"/>
<input type="submit" value="GO" title="press to run the API query"/>
<table id="generated" border=1 style="display:none;">
  <caption>Generated codes for this api call</caption>
  <tr><td>URL</td><td><div id="link"></div></td></tr>
  <tr><td>smarty</td><td><code id="smarty" title='smarty syntax (mostly works for get actions)'></code></td></tr>
  <tr><td>php</td><td><code id="php" title='php syntax'></code></td></tr>
  <tr><td>javascript</td><td><code id="jQuery" title='javascript syntax'></code></td></tr>
</table>
<pre id="result">
You can choose an entity and an action (eg Tag Get to retrieve a list of the tags)
Or your can directly modify the url in the field above and press enter.

When you use the create method, it displays the list of existing fields for this entity.
click on the name of the fields you want to populate, fill the value(s) and press enter

The result of the ajax calls are displayed in this grey area.
</pre>