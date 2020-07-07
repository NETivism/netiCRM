<div id="shorten-url" title="netiCRM Short URL Builder">
  <form>
    <div id="crm-container" class="crm-container">
      <div class="shorten-url-form-block crm-section">
        <div class="label">
          <label for="utm-source">{ts}UTM Source{/ts}</label>
        </div>
        <div class="edit-value content">
        <div class="crm-form-elem crm-form-textfield">
          <input type="text" id="utm-source" name="utm-source" size="30" placeholder="e.g. google, newsletter, facebook, twitter">
          <i class="zmdi zmdi-close-circle-o clear-input"></i>
        </div>
        </div>
      </div>
      <div class="shorten-url-form-block crm-section">
        <div class="label">
          <label for="utm-medium">{ts}UTM Medium{/ts}</label>
        </div>
        <div class="edit-value content">
        <div class="crm-form-elem crm-form-textfield">
          <input type="text" id="utm-medium" name="utm-medium" size="30" placeholder="e.g. cpc, banner, email, QR">
          <i class="zmdi zmdi-close-circle-o clear-input"></i>
        </div>
        </div>
      </div>
      <div class="shorten-url-form-block crm-section">
        <div class="label">
          <label for="utm-term">{ts}UTM Term{/ts}</label>
        </div>
        <div class="edit-value content">
        <div class="crm-form-elem crm-form-textfield">
          <input type="text" id="utm-term" name="utm-term" size="30" placeholder="Identify the paid keywords or other value">
          <i class="zmdi zmdi-close-circle-o clear-input"></i>
        </div>
        </div>
      </div>
      <div class="shorten-url-form-block crm-section">
        <div class="label">
          <label for="utm-content">{ts}UTM Content{/ts}</label>
        </div>
        <div class="edit-value content">
        <div class="crm-form-elem crm-form-textfield">
          <input type="text" id="utm-content" name="utm-content" size="30">
          <i class="zmdi zmdi-close-circle-o clear-input"></i>
        </div>
        </div>
      </div>
      <div class="shorten-url-form-block crm-section">
        <div class="label">
          <label for="utm-campaign">{ts}UTM Campaign{/ts}</label>
        </div>
        <div class="edit-value content">
        <div class="crm-form-elem crm-form-textfield">
          <input type="text" id="utm-campaign" name="utm-campaign" size="30" placeholder="Promo Code">
          <i class="zmdi zmdi-close-circle-o clear-input"></i>
        </div>
        </div>
      </div>
      <div class="shorten-url-form-block crm-section">
        <div class="label">
          <label for="result_url">{ts}Final URL{/ts}</label>
        </div>
        <div class="edit-value content">
        <div class="crm-form-elem crm-form-textfield">
          <textarea type="text" id="result_url" name="result_url" cols="40" rows="2"></textarea>
          <a href="#" class="button shorten-url-copy" style="float:right;margin-left:10px;">{ts}Shorten URL{/ts}</a>
        </div>
        </div>
      </div>
      <div class="shorten-url-form-block crm-section">
        <div class="label">
          <label for="shorten_url">{ts}Short URL{/ts}</label>
        </div>
        <div class="edit-value content">
        <div class="crm-form-elem crm-form-textfield">
          <input type="text" id="shorten_url" name="shorten_url" size="30">
        </div>
        </div>
      </div>
      <div class="crm-submit-buttons" align="center">
        <a href="#" class="button full-url-copy">{ts}Copy Full URL{/ts}</a>
        <a href="#" class="button shorten-url-copy">{ts}Copy Short URL{/ts}</a>
      </div>
    </div>  
  </form> 
</div>
<script>{literal}
  cj(document).ready( function($) {
    var pageId = '';
    var pageType = '';
    var name = '';

    //popup window
    $("#shorten-url").dialog({
      modal: true,
      width: "780px",
      position: { my: "center", at: "center", of: window },
      autoOpen: false
    });

    $(".url-shorten").click(function(){
      pageId = $(this).data('page-id');
      pageType = $(this).data('page-type');
      $("#shorten-url").dialog("open");
      name = $(this).data("url-shorten");
      $('#shorten_url').val('');
      $(".shorten-url-copy").css({ 
        "pointer-events":"initial",
        "background": "#333030"
      });
      $("#shorten_url+span").remove();
      addOriginurl();
      return false;
    });

  function addOriginurl(){
    //utm input default value
    var previousSetting = JSON.parse(localStorage.getItem('netShortenTool'));
    if (previousSetting) {
      if ('utmSource' in previousSetting) $('#utm-source').val(previousSetting.utmSource);
      if ('utmMedium' in previousSetting) $('#utm-medium').val(previousSetting.utmMedium);
      if ('utmTerm' in previousSetting) $('#utm-term').val(previousSetting.utmTerm);
      if ('utmContent' in previousSetting) $('#utm-content').val(previousSetting.utmCotent);
      if ('utmCampaign' in previousSetting) $('#utm-campaign').val(previousSetting.utmCampaign);
    }

    $('#utm-source, #utm-medium, #utm-term, #utm-content, #utm-campaign').on('change', function() {
      var utmParameters = {
        utmSource : $('#utm-source').val(),
        utmMedium : $('#utm-medium').val(),
        utmTerm : $('#utm-term').val(),
        utmCotent : $('#utm-content').val(),
        utmCampaign : $('#utm-campaign').val()
      }
      localStorage.setItem('netShortenTool', JSON.stringify(utmParameters));
    });

    //Final URL default
    var utm = ["utm-source", "utm-medium", "utm-term", "utm-content", "utm-campaign"];
    var urlOriginal = $('.url_to_copy[name="'+ name +'"]').data("url-original");
    var utmResult = "";
    $.each(utm , function(index, val) {
      var utmInput = document.getElementById(val); 
      if (utmInput && utmInput.value) {
        utmResult = utmResult + '&' + val.replace('-', '_') + '=' + utmInput.value;
      }
    });
    $('#result_url').val(urlOriginal + utmResult);

    //Final URL change
    $('#utm-source, #utm-medium, #utm-term, #utm-content, #utm-campaign').on('change', function() {
      var changeutm = $(this).attr('id');
      var href = new URL($('#result_url').val());
      //href.searchParams.set(changeutm.replace('-', '_'), $(this).val());
      var params = new URLSearchParams(href.search);
      if($(this).val().length > 0 ){
        params.set(changeutm.replace('-', '_'), $(this).val());
      } else {
        params.delete(changeutm.replace('-', '_'));
      }
      href.search = params.toString();
      $('#result_url').val(href.href);
      $('#result_url').scrollTop($('#result_url')[0].scrollHeight);
    });

    //Shorten URL
    $('.shorten-url-copy').click(function(){
      var sendUrl = $('#result_url').val();
      console.log(JSON.stringify({"redirect": sendUrl}));
      $(".shorten-url-copy").css({"pointer-events":"none","background": "#808080"});
      $.ajax({
          url: 'https://neti.cc/handle/create-entry',
          type: 'PUT',
          contentType: 'application/json',
          dataType: 'json',
          data: JSON.stringify({"redirect": sendUrl}),
          success: function (data) {
            var shortUrl = 'https://neti.cc/' + data.short;
            $('#shorten_url').val(shortUrl);
            $("#shorten_url").select();
            document.execCommand("copy");
            $("#shorten_url").after(" <span>{/literal}{ts}Copied{/ts}{literal}</span>");
            $('.url_to_copy[name="'+ name +'"]').val(shortUrl);
            $('.url_to_copy[name="'+ name +'"]').attr('data-url-shorten', shortUrl);

            // save to database
            if (pageId && pageType) {
              $.ajax({
                url: '/civicrm/ajax/saveshortenurl',
                type: 'POST',
                data: {
                  'page_id': pageId,
                  'page_type': pageType,
                  'shorten': shortUrl,
                }
              });
            }
          }
      });
    });

    //Shorten URL btn
    $('#utm-source, #utm-medium, #utm-term, #utm-content, #utm-campaign').on('change', function() {
      if($('.shorten-url-copy').css("pointer-events") == "none") {
        $(".shorten-url-copy").css({ 
          "pointer-events":"initial",
          "background": "#333030"
        });
      }
    });

    //Full URL btn
    $(".full-url-copy").click(function() {
      $("#result_url").select();
      document.execCommand("copy");
      $(".full-url-copy").after("<span>{/literal}{ts}Copied{/ts}{literal}</span>");
    });

    // clear input button
    $(".zmdi.clear-input").css('cursor', 'pointer');
    $(".zmdi.clear-input").click(function(){
      $(this).prev('input').val('');
      $(this).prev('input').trigger('change');
    });
  }
  });
{/literal}</script>