<div id="shorten-url" title="netiCRM Short URL Builder">
  <form>
    <div id="crm-container" class="crm-container">
      <div class="shorten-url-form-block crm-section">
        <div class="label">
          <label for="utm-source">{ts}UTM Source{/ts}</label>
        </div>
        <div class="edit-value content">
        <div class="crm-form-elem crm-form-textfield">
          <input type="text" id="utm-source" name="utm-source" size="45" placeholder="e.g. google, newsletter, facebook, twitter">
        </div>
        </div>
      </div>
      <div class="shorten-url-form-block crm-section">
        <div class="label">
          <label for="utm-medium">{ts}UTM Medium{/ts}</label>
        </div>
        <div class="edit-value content">
        <div class="crm-form-elem crm-form-textfield">
          <input type="text" id="utm-medium" name="utm-medium" size="45" placeholder="e.g. cpc, banner, email, QR">
        </div>
        </div>
      </div>
      <div class="shorten-url-form-block crm-section">
        <div class="label">
          <label for="utm-term">{ts}UTM Term{/ts}</label>
        </div>
        <div class="edit-value content">
        <div class="crm-form-elem crm-form-textfield">
          <input type="text" id="utm-term" name="utm-term" size="45" placeholder="Identify the paid keywords or other value">
        </div>
        </div>
      </div>
      <div class="shorten-url-form-block crm-section">
        <div class="label">
          <label for="utm-content">{ts}UTM Content{/ts}</label>
        </div>
        <div class="edit-value content">
        <div class="crm-form-elem crm-form-textfield">
          <input type="text" id="utm-content" name="utm-content" size="45">
        </div>
        </div>
      </div>
      <div class="shorten-url-form-block crm-section">
        <div class="label">
          <label for="utm-campaign">{ts}UTM Campaign{/ts}</label>
        </div>
        <div class="edit-value content">
        <div class="crm-form-elem crm-form-textfield">
          <input type="text" id="utm-campaign" name="utm-campaign" size="45" placeholder="Promo Code">
        </div>
        </div>
      </div>
      <div class="shorten-url-form-block crm-section">
        <div class="label">
          <label for="result_url">{ts}Final URL{/ts}</label>
        </div>
        <div class="edit-value content">
        <div class="crm-form-elem crm-form-textfield">
          <textarea type="text" id="result_url" name="result_url" cols="50" rows="4"></textarea>
        </div>
        </div>
      </div>
      <div class="shorten-url-form-block crm-section">
        <div class="label">
          <label for="shorten_url">{ts}Shorten URL{/ts}</label>
        </div>
        <div class="edit-value content">
        <div class="crm-form-elem crm-form-textfield">
          <input type="text" id="shorten_url" name="shorten_url" size="45">
        </div>
        </div>
      </div>
      <div class="crm-submit-buttons" align="center">
        <a href="#" class="button full-url-copy">{ts}Copy Full URL{/ts}</a>
        <a href="#" class="button shorten-url-copy">{ts}Copy Short Link{/ts}</a>
      </div>
    </div>  
  </form> 
</div>
<script>{literal}
  cj(document).ready( function($) {
    //popup window
    $("#shorten-url").dialog({
      modal: true,
      width: "680px",
      autoOpen: false,
    });

    var name;
    $(".url-shorten").click(function(){
      $("#shorten-url").dialog("open");
      name = $('.url-shorten').data("url-shorten");
      addOriginurl();
      return false;
    });

  function addOriginurl(){
    //utm input default value
    var previousSeting = JSON.parse(localStorage.getItem('netShortenTool'));
    $('#utm-source').val(previousSeting.utmSource);
    $('#utm-medium').val(previousSeting.utmMedium);
    $('#utm-term').val(previousSeting.utmTerm);
    $('#utm-content').val(previousSeting.utmCotent);
    $('#utm-campaign').val(previousSeting.utmCampaign);

    $('#utm-source, #utm-medium, #utm-term, #utm-content, #utm-campaign').on('keyup', function() {
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
    var urlOriginal = $('.url_to_copy[name ="'+ name +'"]').data("url-original");
    var utmResult = "";
    $.each(utm , function(index, val) {
      var utmInput = document.getElementById(val); 
      if (utmInput && utmInput.value) {
        utmResult = utmResult + '&' + val.replace('-', '_') + '=' + utmInput.value;
      }
    });
    $('#result_url').val(urlOriginal + utmResult);

    //Final URL change
    $('#utm-source, #utm-medium, #utm-term, #utm-content, #utm-campaign').on('keyup', function() {
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
            $('#shorten_url').val('https://neti.cc/' + data.short);
            $("#shorten_url").select();
            document.execCommand("copy");
            $(".shorten-url-copy").after("<span>Copied</span>");
            $(".url_to_copy").val('https://neti.cc/' + data.short);
            $('.url_to_copy').attr('data-url-shorten', 'https://neti.cc/' + data.short);
          }
      });
    });

    //Shorten URL btn
    $('#utm-source, #utm-medium, #utm-term, #utm-content, #utm-campaign').on('keyup', function() {
      if($('.shorten-url-copy').css("pointer-events") == "none") {
        $(".shorten-url-copy").css({ 
          "pointer-events":"initial",
          "background": "#333030"});
      }
    });

    //Full URL btn
    $(".full-url-copy").click(function() {
      $("#result_url").select();
      document.execCommand("copy");
      $(".full-url-copy").after("<span>Copied</span>");
    });
  }
  });
{/literal}</script>