{literal}
  window.fixedColumnsCount = document.querySelectorAll('.columnheader>*').length - 1;

  function recordFixedColumnsData($i){
    if(!window.fixedColumnsData){
      window.fixedColumnsData = [];
    }
    var tds = Array.apply(null, document.querySelectorAll('#map-field>table tr>td:nth-child('+$i+')'));
    window.fixedColumnsData[$i] = tds.map(function(elem){ return elem.textContent})
  }
  for (var i = 1; i <= window.fixedColumnsCount; i++) {
    recordFixedColumnsData(i);
  }
  
  function rearrangeFixedColumnsData($i){
    var tds = Array.apply(null, document.querySelectorAll('#map-field>table tr>td:nth-child('+$i+')'));
    tds.forEach(function(elem, i){ elem.textContent = window.fixedColumnsData[$i][i]});
  }

  // Solve arrangement when last step condition.
  var draggableElements = Array.apply(null, document.querySelectorAll('tr.draggable'));
  document.querySelectorAll('[name^="weight\["]').forEach(function(elem){
    var mapperId = /weight\[(\d+)\]/.exec(elem.name)[1];
    var order = Number(elem.value);
    var $target = document.querySelector('#mapper\\[' + mapperId + '\\]\\[0\\]').parentNode.parentNode;
    draggableElements[order].appendChild($target);
  });

  var tbody = document.getElementById('map-field').querySelector('tbody');
  var config = { attributes: true, childList: true };
  var observer = new MutationObserver(function(){
    for (var i = 1; i <= window.fixedColumnsCount; i++) {
      rearrangeFixedColumnsData(i);
    }
  });

  // Start observing the target node for configured mutations
  observer.observe(tbody, config);

  Sortable.create(tbody, {
    handle:'.drag-handler',  
    draggable:'tr.draggable',
    onUpdate: function(event){
      var elem = event.srcElement.querySelectorAll('tr.draggable');
      console.log(elem);
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