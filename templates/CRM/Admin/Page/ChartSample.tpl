<div class="chartist-test">
{php}
  /*
  id：設定此元素的 id
  classes：設定此元素的 class，資料格式為陣列，可多值，例如 array('ct-chart-pie', 'ct-chart-pie-medium')
  selector：要產生圖表的元素的選擇器，預設為「.chartist-chart」
  type：chartist 圖表的類型，預設為「Line」，可使用的類型：Line、Bar、Pie
  labels：chartist 圖表的標籤，資料格式為陣列，可多值，PHP 丟資料時記得加上 json_encode，讓 js 能夠讀取
  series：chartist 圖表的值，資料格式為陣列，第一個值為資料值，第二個值為總數，PHP 丟資料時記得加上 json_encode，讓 js 能夠讀取
  withToolTip：是否有提示，資料格式為布林值，預設為「false」
  isDonut：是否為甜甜圈，圖表類型必須要是「Pie」才有作用，資料格式為布林值，預設為「false」
  isFillDonut：是否為填充式甜甜圈（以百分比顯示），圖表類型必須要是「Pie」才有作用，資料格式為布林值，預設為「false」
  animation：是否有動畫效果，資料格式為布林值，預設為「false」
  */

  $chart = array(
    'id' => 'chart-donut-fill',
    'classes' => array('ct-chart-pie', 'ct-chart-fill-donut'),
    'selector' => '#chart-donut-fill',
    'type' => 'Pie',
    'series' => json_encode(array(160, 200)),
    'isFillDonut' => true,
    'animation' => true
  );
  $this->assign('chart', $chart);
{/php}
{include file="CRM/common/chartist.tpl" chartist=$chart}
</div>

<div class="chartist-test">
{php}
  /*
  id：設定此元素的 id
  classes：設定此元素的 class，資料格式為陣列，可多值，例如 array('ct-chart-pie', 'ct-chart-pie-medium')
  selector：要產生圖表的元素的選擇器，預設為「.chartist-chart」
  type：chartist 圖表的類型，預設為「Line」，可使用的類型：Line、Bar、Pie
  labels：chartist 圖表的標籤，資料格式為陣列，可多值，PHP 丟資料時記得加上 json_encode，讓 js 能夠讀取
  series：chartist 圖表的值，資料格式為陣列，可多值，PHP 丟資料時記得加上 json_encode，讓 js 能夠讀取
  seriesUnit：圖表值的單位，資料格式為字串，預設是沒有單位，可自由填寫
  labelType：圖表標籤的類型，預設為「label」，可使用的類型：label、percent 
  labelOffset：圖表標籤的位置，預設為 0，如果有圖例，預設值為 65
  withLegend：是否有圖例，資料格式為布林值，預設為「false」
  withToolTip：是否有提示，資料格式為布林值，預設為「false」
  */

  $chart = array(
    'id' => 'chart-pie-with-legend-demo',
    'classes' => array('ct-chart-pie'),
    'selector' => '#chart-pie-with-legend-demo',
    'type' => 'Pie',
    //'labels' => json_encode(array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O')),
    'labels' => json_encode(array('夏天的香蕉是白色的', '我沒有任何筆數', '活動報名', '募款餐會餐券', '春天的蘋果還沒成熟', '超商代碼', '我沒有任何筆數再續前緣2', '回收再生正夯，廢棄啤酒麥粕變身環保建材', '電子報', '秋天的葡萄釀成酒剛剛好', '熱門文章', '聯絡人匯入', '機器人將取代人類工作？美國非營利組織積極培訓勞工，開拓未來就業之路', '冬天的橘子不用烤也好吃', '行動菜車 農村到都市的任意門', '網絡行動科技', '海洋吸塵器')),
    'series' => json_encode(array(24, 0, 15, 27, 1, 39, 0, 60, 29, 3, 34, 20, 49, 2, 23, 16, 23)),
    'seriesUnit' => '筆',
    'labelType' => 'percent', 
    'withLegend' => true,
    'withToolTip' => true
  );
  $this->assign('chart', $chart);
{/php}
{include file="CRM/common/chartist.tpl" chartist=$chart}
</div>

<div class="chartist-test">
{php}
  $chart = array(
    'id' => 'chart-pie-simple-demo',
    'classes' => array('ct-chart-pie'),
    'selector' => '#chart-pie-simple-demo',
    'type' => 'Pie',
    'series' => json_encode(array(10, 2, 4, 3)),
    'seriesUnit' => '筆',
    'labelType' => 'percent',
    'withToolTip' => true
  );
  $this->assign('chart', $chart);
{/php}
{include file="CRM/common/chartist.tpl" chartist=$chart}
</div>

<div class="chartist-test">
{php}
  $chart = array(
    'id' => 'chart-line',
    'classes' => array('ct-chart-line'),
    'selector' => '#chart-line',
    'type' => 'Line',
    'labels' => json_encode(array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday')),
    'series' => json_encode(array(array(12, 9, 7, 8, 5), array(2, 1, 3.5, 7, 3), array(1, 3, 4, 5, 6))), 
    'seriesUnit' => '$ ',
    'seriesUnitPosition'=> 'prefix',
    'withToolTip' => true
  );
  $this->assign('chart', $chart);
{/php}
{include file="CRM/common/chartist.tpl" chartist=$chart}
</div>

<div class="chartist-test">
{php}
  $chart = array(
    'id' => 'chart-bar',
    'classes' => array('ct-chart-bar'),
    'selector' => '#chart-bar',
    'type' => 'Bar',
    'labels' => json_encode(array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday')),
    'series' => json_encode(array(array(5, 2, 4, 2, 0))),
    'seriesUnit' => '$', 
    'withToolTip' => true
  );
  $this->assign('chart', $chart);
{/php}
{include file="CRM/common/chartist.tpl" chartist=$chart}
</div>

<div class="chartist-test">
{php}
  $chart = array(
    'id' => 'chart-overlapping-bars',
    'classes' => array('ct-chart-bar'),
    'selector' => '#chart-overlapping-bars',
    'type' => 'Bar',
    'labels' => json_encode(array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec')),
    'series' => json_encode(array(array(5, 4, 3, 7, 5, 10, 3, 4, 8, 10, 6, 8), array(3, 2, 9, 5, 4, 6, 4, 6, 7, 8, 7, 4))), 
    'withToolTip' => true
  );
  $this->assign('chart', $chart);
{/php}
{include file="CRM/common/chartist.tpl" chartist=$chart}
</div>

