$(document).ready(function() {

  color = {
    1: '#621478', //Dark Purple
    2: '#700000', //Dark Red
    5: '#0B2770', //Dark Blue
    8: '#0C4018', //Dark Green
    9: '#70330B', //Dark Orange
    10: '#156970 ', //Dark Water Blue
    3: '#D881F0 ', //Bright Purple
    4: '#D47F7F ', //Bright Red
    6: '#7C8FBF ', //Bright Blue
    7: '#C9B974 ', //Bright Yellow
    11: '#BFBFBF ', //Bright Gray
    12: '#82BA81 ', //Bright Green
  };

  $('form#Search').prepend(' <div style="width:100%; margin:0 auto;"><div id="stat_ps"><div class="stat_ps_graph" id="stat_ps_graph1"></div><div class="stat_ps_graph" id="stat_ps_graph2"></div><div class="stat_ps_label" id="stat_ps_label1"></div></div></div>');

  if (!location.search.match('status=')) {
    google.load("visualization", "1", {
      packages: ["corechart"],
      callback: startDrawChart
    });
  }
  //google.setOnLoadCallback(startDrawChart);
  // 
  /**
   * participants status
   */

  p_status = Drupal.settings.neticrm_event_stat.summary;
  event_id = Drupal.settings.neticrm_event_stat.event;
  //arr_translate = Drupal.settings.neticrm_event_stat.translate;
  arr_states = Drupal.settings.neticrm_event_stat.state;

  //人數
  var part_finished = 0;
  $.each(p_status.finished, function(index, val) {
     part_finished += parseInt(val);
  });

  var part_unfinished = 0;
  $.each(p_status.unfinished, function(index, val) {
     part_unfinished += parseInt(val);
  });

  part_participants = part_finished;

  part_blank = p_status.space > 0 ? p_status.space - part_participants : 0;


  // part_Positive = (p_status.Positive.length > 0) ? p_status.Positive[0].count : 0;
  // part_Pending = (p_status.Pending.length > 0) ? p_status.Pending[0].count : 0;
  // part_participants = Drupal.settings.neticrm_event_stat.eventSummary.maxParticipants;
  // part_blank = part_participants - part_Positive - part_Pending;


  //
  p_max = 0;
  if (part_finished > 0) {
    p_max += part_finished;
  }

  if (part_blank > 0) {
    p_max += part_blank;
  } else {
    p_max += part_unfinished;
  }


  // Count ratio.
  perc_finished = part_finished / p_max;
  perc_unfinished = part_unfinished / p_max;
  perc_Blank = part_blank / p_max;

  /**
   * Graph
   */
  $pos = getJqGraphBlock(part_finished, '#111', p_max).appendTo($('#stat_ps_graph1'));

  if (part_blank > 0) {
    $bla = getJqGraphBlock(part_blank, '#ddd', p_max).appendTo($('#stat_ps_graph1'));
  } else {
    $pen = getJqGraphBlock(part_unfinished, '#777', p_max).appendTo($('#stat_ps_graph1'));
  }

  /**
   * Graph part2
   */
  $block = [];
  $.each(color, function(index, val) {
    if (p_status['finished'][arr_states[index]['name']]) {
      $block.push(getJqGraphBlock(p_status['finished'][arr_states[index]['name']], color[index], p_max));
    }
  });
  if (!part_blank > 0) {
    $.each(color, function(index, val) {
      if (p_status['unfinished'][arr_states[index]['name']]) {
        $block.push(getJqGraphBlock(p_status['unfinished'][arr_states[index]['name']], color[index], p_max));
      }
    });
  }
  for (var i = 0; i < $block.length; i++) {
    $('#stat_ps_graph2').append($block[i]);
  };

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
  $li = {
    'finished': $('<div class="substate-div">'),
    'unfinished': $('<div class="substate-div">')
  };

  $.each(arr_states, function(index, val) {
    if (typeof p_status[val.isfinish][val.name] !== 'undefined') {
      getJqLabelBlock(index, p_status[val.isfinish][val.name], color[index], 'div')
        .appendTo($li[val.isfinish]);
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
    $('#stat_ps_graph1').hide();
    $('#stat_ps_graph2').show();
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
    $('#stat_ps_graph1').show();
    $('#stat_ps_graph2').hide();
    $('.substate-div').animate({
        'height': 0
      },'fast', function() {
        $('.substate-div').hide();
      });
  });


  /**
   * Get jquery Status Label Object (<span>)
   * @param  {number or String} stateNumber  If it is number. Output will return a link.
   * @param  {number} people  The counts of people.
   * @param  {String of Hash} color  Like '#159c93'
   * @param  {String} htmlTag  Like 'div', default is 'li'
   * @param  {String} name  Class name
   * @return {jQuery object}        Like $('<span>...</span>')
   */
  function getJqLabelBlock(stateNumber, people, color, htmlTag, name) {
    htmlTag = typeof htmlTag !== "undefined" ? htmlTag : "li";
    if (!isNaN(stateNumber)) {
      return $('<' + htmlTag + '>')
        .toggleClass(name)
        .append(
          $('<a>')
          .attr('href', '/civicrm/event/search?reset=1&force=1&status=' + stateNumber + '&event=' + event_id)
          .append(
            $('<span>').css({
              'backgroundColor': color
            }))
          .append($('<span class="label-title">').text(arr_states[stateNumber]['name']))
          .append($('<span class="people-count">').text(people))
      ).toggleClass(name);
    } else {
      return $('<' + htmlTag + '>')
        .append($('<span>').css({
          'backgroundColor': color
        }))
        .append($('<span class="label-title">').text(stateNumber))
        .append($('<span class="people-count">').text(people))
        .toggleClass(name);
    }
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
    return $('<span>').addClass('part').css({
      'backgroundColor': color,
      'width': state / max * 100 + "%"
    });
  }

  /**
   * get translate string
   * @param  {[type]} str [description]
   * @return {[type]}          [description]
   */
  function t(str) {
    return typeof Drupal.settings.neticrm_event_stat.translate[str] !== 'undefined' ? Drupal.settings.neticrm_event_stat.translate[str] : str;
  }

  /**
   * execute google visualization
   * @return {[type]} [description]
   */
  function startDrawChart() {
    var data_pie = Drupal.settings.neticrm_event_stat.pie;
    var data_bar = Drupal.settings.neticrm_event_stat.bar;

    is_gPie = !(data_pie[1][1] == 0 && data_pie[2][1] == null);
    is_gBar = data_bar.length !== 1;

    if (is_gBar) $('form#Search').parent().prepend($('<div id="stat_bc">'));

    if (is_gPie) $('form#Search').parent().prepend($('<div id="stat_dc">'));

    if (is_gPie) {
      data = google.visualization.arrayToDataTable(data_pie);
      gPie = new google.visualization.PieChart(document.getElementById('stat_dc'));
      gPie.draw(data, {
        title: 'Ratio of participants has attended before.',
        piehole: 0.3,
        backgroundColor: {
          fill: 'transparent'
        }
      });

      //The Event click Pie Chart
      google.visualization.events.addListener(gPie, 'select', function() {
        switch (gPie.getSelection()[0].row) {
          case 0:
            // Click First time part
            break;
          case 1:
            // Click Another Part
            break;
        }
      });
    }

    if (is_gBar) {
      data = google.visualization.arrayToDataTable(data_bar);
      gBar = new google.visualization.BarChart(document.getElementById('stat_bc'));
      gBar.draw(data, {
        title: 'Most active participants.',
        backgroundColor: {
          fill: 'transparent'
        }
      });

      google.visualization.events.addListener(gBar, 'select', function() {
        //Event when gBar is selected.
      });
    }

  }

});