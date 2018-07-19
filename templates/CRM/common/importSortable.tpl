{literal}
  function recordImportData($i){
    if(!window.importData){
      window.importData = [];
    }
    var tds = Array.apply(null, document.querySelectorAll('#map-field>table tr>td:nth-child('+$i+')'));
    window.importData[$i] = tds.map(function(elem){ return elem.textContent})
  }
  recordImportData(1);
  recordImportData(2);

  function rearrangeImportData($i){
    var tds = Array.apply(null, document.querySelectorAll('#map-field>table tr>td:nth-child('+$i+')'));
    tds.forEach(function(elem, i){ elem.textContent = window.importData[$i][i]});
  }

  var tbody = document.getElementById('map-field').querySelector('tbody');
  var config = { attributes: true, childList: true, subtree: true };
  var observer = new MutationObserver(function(){
    rearrangeImportData(1);
    rearrangeImportData(2);
  });

  // Start observing the target node for configured mutations
  observer.observe(tbody, config);

  Sortable.create(tbody, {
    handle:'.drag-handler',  
    draggable:'tr.draggable',
    onUpdate: function(event){
      var elem = event.srcElement.querySelectorAll('tr.draggable');
      elem.forEach(function(item, i){
        var input = item.querySelector('[name^="mapper"][name$="[0]"]');
        if(input){
          var k = /^mapper\[(\d)+\]\[0\]/.exec(input.name)[1];
          var weight_item = document.querySelector('[name^="weight['+k+']"]');
          weight_item.value = i;
        }
      });
    }
  });
{/literal}