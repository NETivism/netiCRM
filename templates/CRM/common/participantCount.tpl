<div>
  <div id="stat_ps">
    <span class="fa fa-minus-square-o expand-icon"></span>
    <div class="stat_ps_graph" id="stat_ps_graph1"></div>
    <div class="stat_ps_graph" id="stat_ps_graph2"></div>
    <div class="stat_ps_label" id="stat_ps_label1"></div>
  </div>
</div>

{literal}
<script>
color = [
  '#333333', // dark black
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
  '#cecece' // white grey
];

cj(document).ready(function(){
  /**
   * participants status
   */

  var p_status = {/literal}{$participantSummary|@json_encode}{literal};
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
  $('#stat_ps_graph1').append(getJqGraphBlock(part_finished, color[0], p_max));
  if (part_blank > 0) {
    $('#stat_ps_graph1').append(getJqGraphBlock(part_blank, color[color.length-1], p_max));
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
  $('<li class="finished-label">')
    .append(getJqLabelBlock('{/literal}{ts}Counted{/ts}{literal}', part_finished, color[0], 'div', 'status-block'))
    .appendTo($('#stat_ps_label1 ol'));


  if (part_blank > 0) {
    $('<li class="unfinished-label">')
      .append(getJqLabelBlock('{/literal}{ts}Place Available{/ts}{literal}', part_blank, color[color.length-1], 'div', 'status-block'))
      .appendTo($('#stat_ps_label1 ol'));
  }
  else {
    $('<li class="unfinished-label">')
      .append(getJqLabelBlock('{/literal}{ts}Not Counted{/ts}{literal}', part_unfinished, '#777777', 'div', 'status-block'))
      .appendTo($('#stat_ps_label1 ol'));
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
   * Expand, collapse
   */
  var toggleStatus = function(type){
    if(type === 'slide'){
      $('#stat_ps_graph2').slideToggle();
      $('.substate-div').slideToggle();
    }
    else{
      $('#stat_ps_graph2').toggle();
      $('.substate-div').toggle();
    }
    if($('#stat_ps .expand-icon').hasClass('fa-plus-square')){
      setCookie('collapseParticipantCount', 0);
      $("#stat_ps_graph1").animate({'opacity':0.3});
      $('#stat_ps .expand-icon').removeClass('fa-plus-square').addClass('fa-minus-square-o');
    }
    else{
      setCookie('collapseParticipantCount', 1);
      $("#stat_ps_graph1").animate({'opacity':1});
      $('#stat_ps .expand-icon').addClass('fa-plus-square').removeClass('fa-minus-square-o');
    }
  }
  $('#stat_ps .expand-icon, #stat_ps .stat_ps_graph').click(function() {
    toggleStatus('slide');
  });
  var collapse = getCookie('collapseParticipantCount');
  if(collapse == 1){
    toggleStatus();
  }
  else{
    $("#stat_ps_graph1").animate({'opacity':0.5});
  }

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

    var icon = name ? 'fa-users' : 'fa-square';
    
    return $('<' + htmlTag + '>')
      .append($('<span class="fa '+icon+'">').css({
        'color': color,
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
});
</script>
{/literal}
