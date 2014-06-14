$(document).ready(function() {

  if (!location.search.match('status=')) {
    google.load("visualization", "1", {
      packages: ["corechart"],
      callback: startDrawChart
    });
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
