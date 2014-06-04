$(document).ready(function() {

  color = {
    '已登記': '#621478', //暗紫
    '出席': '#700000', //暗紅
    '待處理-後續付款': '#0B2770', //暗藍
    '等待審核': '#0C4018', //暗綠
    '待處理-在候補名單中': '#70330B', //暗橘
    '待處理-已審核通過': '#156970 ', //暗水藍
    '失約': '#D881F0 ', //淺紫
    '已取消': '#D47F7F ', //淺紅
    '待處理-未完成交易': '#7C8FBF ', //淺藍
    '在候補名單': '#C9B974 ', //淺黃
    '已拒絕': '#BFBFBF ', //淺灰
    '已過期': '#82BA81 ', //淺綠
  };

  $('form#Search').prepend(' <div style = "width:100%; margin:0 auto;"><div id = "stat_ps"><div class="stat_ps_graph" id="stat_ps_graph1"></div><div class = "stat_ps_graph" id = "stat_ps_graph2"></div><div class="stat_ps_label" id="stat_ps_label1"></div></div></div>');

  if (!location.search.match('status=')) {
    google.load("visualization", "1", {
      packages: ["corechart"],
      callback: startDrawChart,
    });
  }
  //google.setOnLoadCallback(startDrawChart);
  // 
  /**
   * 弄 participants status 的部份
   */

  p_status = Drupal.settings.neticrm_event_stat.summary;
  event_id = Drupal.settings.neticrm_event_stat.event;
  arr_translate = Drupal.settings.neticrm_event_stat.translate;
  arr_states = Drupal.settings.neticrm_event_stat.state;

  //人數
  part_finished = Object.extended(p_status.finished).values().sum(function(n) {
    return parseInt(n);
  });
  part_unfinished = Object.extended(p_status.unfinished).values().sum(function(n) {
    return parseInt(n);
  });
  part_Participants = part_finished;

  part_Blank = p_status.space > 0 ? p_status.space - part_Participants : 0;


  // part_Positive = (p_status.Positive.length > 0) ? p_status.Positive[0].count : 0;
  // part_Pending = (p_status.Pending.length > 0) ? p_status.Pending[0].count : 0;
  // part_Participants = Drupal.settings.neticrm_event_stat.eventSummary.maxParticipants;
  // part_Blank = part_Participants - part_Positive - part_Pending;


  //
  p_max = 0;
  if (part_finished > 0) {
    p_max += part_finished;
  }

  if (part_Blank > 0) {
    p_max += part_Blank;
  } else {
    p_max += part_unfinished;
  }


  //佔比
  perc_finished = part_finished / p_max;
  perc_unfinished = part_unfinished / p_max;
  perc_Blank = part_Blank / p_max;

  /**
   * 處理圖的部份
   */
  $pos = getJqGraphBlock(arr_translate['Counted'], part_finished, '#111', p_max).appendTo($('#stat_ps_graph1'));

  if (part_Blank > 0) {
    $bla = getJqGraphBlock('可報名人數', part_Blank, '#ddd', p_max).appendTo($('#stat_ps_graph1'));
  } else {
    $pen = getJqGraphBlock(arr_translate['Not Counted'], part_unfinished, '#777', p_max).appendTo($('#stat_ps_graph1'));
  }

  /**
   * 第二個圖
   */
  $block = [];
  $.each(color, function(index, val) {
    if (p_status['finished'][index]) {
      $block.push(getJqGraphBlock(index, p_status['finished'][index], color[index], p_max));
    }
  });
  if (!part_Blank > 0) {
    $.each(color, function(index, val) {
      if (p_status['unfinished'][index]) {
        $block.push(getJqGraphBlock(index, p_status['unfinished'][index], color[index], p_max));
      }
    });
  }
  for (var i = 0; i < $block.length; i++) {
    $('#stat_ps_graph2').append($block[i]);
  };

  /**
   * 處理文字說明標籤的部份
   */
  $('#stat_ps_label1').append($('<ol>'));
  $('<li class="finished-label">').append(
    getJqLabelBlock(arr_translate['Counted'], part_finished, '#111111', 'div', 'finished-block')
  ).appendTo($('#stat_ps_label1 ol'));


  if (part_Blank > 0) {
    $('<li>').append(
      getJqLabelBlock('可報名人數', part_Blank, '#dddddd', 'div')
    ).appendTo($('#stat_ps_label1 ol'));
  } else {
    $('<li class="unfinished-label">').append(
      getJqLabelBlock(arr_translate['Not Counted'], part_unfinished, '#777777', 'div', 'finished-block')
    ).appendTo($('#stat_ps_label1 ol'));

  }

  /**
   * 第二個標籤
   */
  //$('#stat_ps_label2').append($('<ol>'));
  $li = {
    'finished': $('<div class="substate-div">'),
    'unfinished': $('<div class="substate-div">'),
  };

  $.each(arr_states, function(index, val) {
    if (typeof p_status[val.isfinish][val.name] !== 'undefined') {
      getJqLabelBlock(index, p_status[val.isfinish][val.name], color[val.name], 'div')
        .appendTo($li[val.isfinish]);
    }
  });
  $li['finished'].appendTo('.finished-label');
  $li['unfinished'].appendTo('.unfinished-label');

  if (part_Blank > 0) {
    $('<li>').appendTo($('#stat_ps_label2 ol'));
  }


  /**
   * 動作
   */
  $('#stat_ps_graph2').hide(); //.css('height', 0);
  $('.substate-div').css('height', 0).hide();
  $('#stat_ps').hover(function() {
    $('#stat_ps_graph1').hide();
    $('#stat_ps_graph2').show();
    // $('#stat_ps_graph1').animate({
    //     'height': 0,
    //   },
    //   'fast');

    // $('#stat_ps_graph2').animate({
    //     'height': 18,
    //   },
    //   'fast');
    $('.substate-div').animate({
        'height': function() {
          var $lis = $('.substate-div').show();
          var arr = [];
          $.each($lis, function(index, val) {
            arr.push($(val).children('div').length);
            /* iterate through array or object */
          });
          return arr.max() * 20 + 10;
        }(),
      },
      'fast');
  }, function() {
    $('#stat_ps_graph1').show();
    $('#stat_ps_graph2').hide();
    // $('#stat_ps_graph1').animate({
    //     'height': 18,
    //   },
    //   'fast');
    // $('#stat_ps_graph2').animate({
    //     'height': 0,
    //   },
    //   'fast');
    $('.substate-div').animate({
        'height': 0,
      },
      'fast', function() {
        $('.substate-div').hide();
      });
  });


  /**
   * 傳回 jquery 的標籤、人數物件 (<span>)
   * @param  {[type]} title  [description]
   * @param  {[type]} people [description]
   * @param  {[type]} color  [description]
   * @return {[type]}        [description]
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
              'backgroundColor': color,
            }))
          .append($('<span class="label-title">').text(arr_states[stateNumber]['name']))
          .append($('<span class="people-count">').text(people + '人'))
      ).toggleClass(name);
    } else {
      return $('<' + htmlTag + '>')
        .append($('<span>').css({
          'backgroundColor': color,
        }))
        .append($('<span class="label-title">').text(stateNumber))
        .append($('<span class="people-count">').text(people + '人'))
        .toggleClass(name);
    }
  }

  /**
   * 傳回 jquery 的有顏色長條物件 (<span>)
   * @param  {[type]} name  [description]
   * @param  {[type]} state [description]
   * @param  {[type]} color [description]
   * @param  {[type]} max   [description]
   * @return {[type]}       [description]
   */
  function getJqGraphBlock(name, state, color, max) {
    return $('<span>').addClass('part').css({
      'backgroundColor': color,
      'width': state / max * 100 + "%",
    });
  }

  /**
   * google 表單開始執行的函式
   * @return {[type]} [description]
   */
  function startDrawChart() {
    $('form#Search').parent().prepend($('<div id="stat_bc">')).prepend($('<div id="stat_dc">'));

    var data = google.visualization.arrayToDataTable(Drupal.settings.neticrm_event_stat.pie);
    gPie = new google.visualization.PieChart(document.getElementById('stat_dc'));
    gPie.draw(data, {
      title: '參加者回流比例',
      piehole: 0.3,
      backgroundColor: {
        fill: 'transparent'
      },
    });
    console.log(gPie);

    data = google.visualization.arrayToDataTable(Drupal.settings.neticrm_event_stat.bar);
    gBar = new google.visualization.BarChart(document.getElementById('stat_bc'));
    gBar.draw(data, {
      title: '最活躍參加者（根據以前活動）',
      backgroundColor: {
        fill: 'transparent'
      },
    });

    //點選圓餅圖區塊的事件
    google.visualization.events.addListener(gPie, 'select', function() {
      switch (gPie.getSelection()[0].row) {
        case 0:
          console.log(gPie.getSelection()[0].row);
          location.href = "https://dev.neticrm.tw/civicrm/event/search?reset=1&force=1&status=true&event=2";
          //選到第一次參加的人
          break;
        case 1:
          //選到以前參加過的人
          console.log(gPie.getSelection()[0].row);
          location.href = "https://dev.neticrm.tw/civicrm/event/search?reset=1&force=1&status=false&event=2";
          break;
      }
    });
    google.visualization.events.addListener(gBar, 'select', function() {
      console.log(gBar.getSelection());
    });


  }

});