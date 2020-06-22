<div id="shorten-url" title="netiCRM Short URL Builder">
  <form>
    <div id="crm-container" class="crm-container">
      <div class="shorten-url-form-block crm-section">
        <div class="label">
          <label for="put_url">Website URL</label>
        </div>
        <div class="edit-value content">
        <div class="crm-form-elem crm-form-textfield">
          <input type="text" id="put_url" name="put_url" size="45">
        </div>
        </div>
      </div>
      <fieldset class="crm-group shorten-url-parameters">
        <legend>Fill UTM Parameters</legend>
        <div class="shorten-url-form-block crm-section">
          <div class="label">
            <label for="utm-source">UTM Source</label>
          </div>
          <div class="edit-value content">
          <div class="crm-form-elem crm-form-textfield">
            <input type="text" id="utm-source" name="utm-source" size="45" placeholder="e.g. google, newsletter, facebook, twitter">
          </div>
          </div>
        </div>
        <div class="shorten-url-form-block crm-section">
          <div class="label">
            <label for="utm-medium">UTM Medium</label>
          </div>
          <div class="edit-value content">
          <div class="crm-form-elem crm-form-textfield">
            <input type="text" id="utm-medium" name="utm-medium" size="45" placeholder="e.g. cpc, banner, email, QR">
          </div>
          </div>
        </div>
        <div class="shorten-url-form-block crm-section">
          <div class="label">
            <label for="utm-term">UTM Term</label>
          </div>
          <div class="edit-value content">
          <div class="crm-form-elem crm-form-textfield">
            <input type="text" id="utm-term" name="utm-term" size="45" placeholder="Identify the paid keywords or other value">
          </div>
          </div>
        </div>
        <div class="shorten-url-form-block crm-section">
          <div class="label">
            <label for="utm-content">UTM Content</label>
          </div>
          <div class="edit-value content">
          <div class="crm-form-elem crm-form-textfield">
            <input type="text" id="utm-content" name="utm-content" size="45">
          </div>
          </div>
        </div>
        <div class="shorten-url-form-block crm-section">
          <div class="label">
            <label for="utm-campaign">UTM Campaign</label>
          </div>
          <div class="edit-value content">
          <div class="crm-form-elem crm-form-textfield">
            <input type="text" id="utm-campaign" name="utm-campaign" size="45" placeholder="Promo Code">
          </div>
          </div>
        </div>
      </fieldset>
      <div class="shorten-url-form-block crm-section">
        <div class="label">
          <label for="result_url">Final URL</label>
        </div>
        <div class="edit-value content">
        <div class="crm-form-elem crm-form-textfield">
          <input type="text" id="result_url" name="result_url" size="45">
        </div>
        </div>
      </div>
      <div class="shorten-url-form-block crm-section">
        <div class="label">
          <label for="shorten_url">Shorten URL</label>
        </div>
        <div class="edit-value content">
        <div class="crm-form-elem crm-form-textfield">
          <input type="text" id="shorten_url" name="shorten_url" size="45">
        </div>
        </div>
      </div>
      <div class="crm-submit-buttons">
        <a href="#" class="button full-url-copy">Copy Full URL</a>
        <a href="#" class="button shorten-url-copy">Copy Short Link</a>
      </div>
    </div>  
  </form> 
</div>
<script>{literal}
  cj(document).ready( function($) {
    //Website URL
    var url_to_copy = document.querySelectorAll('.url_to_copy');
    $('#put_url').val(url_to_copy[0].dataset.urlOriginal);

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
    $('#result_url').val(url_to_copy[0].dataset.urlOriginal);
    var utmResult = "";
    $.each(utm , function(index, val) {
      var utmInput = document.getElementById(val); 
      if (utmInput && utmInput.value) {
        utmResult = utmResult + '&' + val.replace('-', '_') + '=' + utmInput.value;
      }
    });
    $('#result_url').val(url_to_copy[0].dataset.urlOriginal + utmResult);

    //Final URL change
    $('#utm-source, #utm-medium, #utm-term, #utm-content, #utm-campaign').on('keyup', function() {
      var changeutm = $(this).attr('id');
      var href = new URL($('#result_url').val());
      href.searchParams.set(changeutm.replace('-', '_'), $(this).val());
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
          data: {
              contentType: 'application/json',
              data: JSON.stringify({"redirect": sendUrl})
          },
          success: function () {
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

  });
{/literal}</script>