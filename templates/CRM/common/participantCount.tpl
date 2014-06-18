<div>
  <div id="stat_ps">
    <span class="fa fa-plus-square expand-icon"></span>
    <div class="stat_ps_graph" id="stat_ps_graph1"></div>
    <div class="stat_ps_graph" id="stat_ps_graph2"></div>
    <div class="stat_ps_label" id="stat_ps_label1"></div>
  </div>
</div>

{literal}
<script>
color = [
  '#3F3F3F', // dark black
  '#335f94', // blue
  '#88add9',
  '#27476d',
  '#6292cb',
  '#3f78bb',
  '#893135', // red
  '#c7595e',
  '#d57e82',
  '#b03d42',
  '#e2a4a7',
  '#BFBFBF' // white grey
];

cj(document).ready(function(){
  /**
   * participants status
   */

  var p_status = {/literal}{$participantSummary}{literal};
  var event_id = {/literal}{$id}{literal};

  //人數
  var part_finished = 0;
  $.each(p_status.finished, function(index, val) {
     part_finished += parseInt(val);
  });

  var part_unfinished = 0;
  $.each(p_status.unfinished, function(index, val) {
     part_unfinished += parseInt(val);
  });

  var part_participants = part_finished;

  var part_blank = p_status.space > 0 ? p_status.space - part_participants : 0;
  var p_max = 0;
  if (part_finished > 0) {
    p_max += part_finished;
  }

  if (part_blank > 0) {
    p_max += part_blank;
  } else {
    p_max += part_unfinished;
  }


  // Count ratio.
  var perc_finished = part_finished / p_max;
  var perc_unfinished = part_unfinished / p_max;
  var perc_Blank = part_blank / p_max;

  /**
   * Graph
   */
  $('#stat_ps_graph1').append(getJqGraphBlock(part_finished, '#111', p_max));
  if (part_blank > 0) {
    $('#stat_ps_graph1').append(getJqGraphBlock(part_blank, '#ddd', p_max));
  } else {
    $('#stat_ps_graph1').append(getJqGraphBlock(part_unfinished, '#777', p_max));
  }

  /**
   * Graph part2
   */
  var block = [];
  var i = 0;
  $.each(p_status.finished, function(index, val) {
    i++;
    block.push(getJqGraphBlock(p_status['finished'][index], color[i], p_max));
  });
  if (!part_blank > 0) {
    i=6;
    $.each(p_status.unfinished, function(index, val) {
      i++;
      block.push(getJqGraphBlock(p_status['unfinished'][index], color[i], p_max));
    });
  }
  $('#stat_ps_graph2').append(block.join(''));

  /**
   * Text Label
   */
  $('#stat_ps_label1').append($('<ol>'));
  $('<li class="finished-label">').append(
    getJqLabelBlock(t('Counted'), part_finished, '#111111', 'div', 'finished-block')
  ).appendTo($('#stat_ps_label1 ol'));


  if (part_blank > 0) {
    $('<li>').append(
      getJqLabelBlock(t('Place Available'), part_blank, '#dddddd', 'div')
    ).appendTo($('#stat_ps_label1 ol'));
  } else {
    $('<li class="unfinished-label">').append(
      getJqLabelBlock(t('Not Counted'), part_unfinished, '#777777', 'div', 'finished-block')
    ).appendTo($('#stat_ps_label1 ol'));

  }

  /**
   * Text Label 2
   */
  var $li = {
    'finished': $('<div class="substate-div">'),
    'unfinished': $('<div class="substate-div">')
  };
  
  i=0;
  $.each(p_status.finished, function(index, val) {
    i++;
    if (typeof p_status.finished[index] !== 'undefined') {
      getJqLabelBlock(index, p_status.finished[index], color[i], 'div')
        .appendTo($li['finished']);
    }
  });
  i=6;
  $.each(p_status.unfinished, function(index, val) {
    i++;
    if (typeof p_status.unfinished[index] !== 'undefined') {
      getJqLabelBlock(index, p_status.unfinished[index], color[i], 'div')
        .appendTo($li['unfinished']);
    }
  });
  $li['finished'].appendTo('.finished-label');
  $li['unfinished'].appendTo('.unfinished-label');

  if (part_blank > 0) {
    $('<li>').appendTo($('#stat_ps_label2 ol'));
  }

  /**
   * Event
   */
  $('#stat_ps_graph2').hide(); //.css('height', 0);
  $('.substate-div').css('height', 0).hide();
  $('#stat_ps').hover(function() {
    $('#stat_ps_graph2').show();
    $('#stat_ps .expand-icon').removeClass('fa-plus-square').addClass('fa-minus-square-o');
    $('#stat_ps_graph1').animate({'opacity': 0.3}, 'fast');
    $('.substate-div').animate({
        'height': function() {
          var $lis = $('.substate-div').show();
          var arr = [];
          $.each($lis, function(index, val) {
            arr.push($(val).children('div').length);
            /* iterate through array or object */
          });
          return Math.max.apply(null, arr) * 20 + 10;
        }()
      },'fast');
  }, function() {
    $('#stat_ps_graph2').hide();
    $('#stat_ps .expand-icon').addClass('fa-plus-square').removeClass('fa-minus-square-o');
    $('#stat_ps_graph1').animate({'opacity': 1}, 'fast');
    $('.substate-div').animate({
        'height': 0
      },'fast', function() {
        $('.substate-div').hide();
      });
  });


  /**
   * Get jquery Status Label Object (<span>)
   * @param  {String} state If it is number. Output will return a link.
   * @param  {number} people  The counts of people.
   * @param  {String of Hash} color  Like '#159c93'
   * @param  {String} htmlTag  Like 'div', default is 'li'
   * @param  {String} name  Class name
   * @return {jQuery object}        Like $('<span>...</span>')
   */
  function getJqLabelBlock(state, people, color, htmlTag, name) {
    htmlTag = typeof htmlTag !== "undefined" ? htmlTag : "li";
    if(p_status.status.hasOwnProperty(state)){
      var label = '<a href="'+document.URL.replace(/&status=\d+/, '')+'&status='+p_status.status[state]+'">'+state+'</a>';
    }
    else{
      var label = state;
    }
    
    return $('<' + htmlTag + '>')
      .append($('<span>').css({
        'backgroundColor': color,
        'margin-right': '10px'
      }))
      .append('<span class="label-title">'+label+'</span>')
      .append($('<span class="people-count">').text(people))
      .toggleClass(name);
  }

  /**
   * Get jquery Rectangle Objects (<span>)
   * @param  {} name  No used.
   * @param  {Number} state The counts of this status.
   * @param  {String of Hash} color Like '#19f88a'
   * @param  {Number} max   Max value
   * @return {jQuery object}       Like $(<span>...</span>)
   */
  function getJqGraphBlock(state, color, max) {
    var $block = $('<span>').addClass('part').css({
      'backgroundColor': color,
      'width': state / max * 100 + "%"
    });
    return $block[0].outerHTML;
  }

  /**
   * get translate string
   * @param  {[type]} str [description]
   * @return {[type]}          [description]
   */
  function t(str) {
    return typeof Drupal.settings.neticrm_event_stat.translate[str] !== 'undefined' ? Drupal.settings.neticrm_event_stat.translate[str] : str;
  }
  
});
</script>
{/literal}
