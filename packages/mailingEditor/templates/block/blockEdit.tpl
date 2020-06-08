<textarea class="nme-tpl" data-template-level="block" data-template-name="edit">
{* Template Content: BEGIN *}
<div id="nme-block-[nmeBlockID]" data-id="[nmeBlockID]" class="nme-block" data-type="[nmeBlockType]" data-section="[nmeBlockSection]" data-sortable="[nmeBlockSortable]">
  <div class="nme-block-inner">
    <div class="nme-block-content">
      <!-- Block Content HERE -->
      [nmeBlockContent]
    </div>	
    <div class="nme-block-control">
      <div class="nme-block-move">
        <button type="button" class="handle-drag handle-btn" data-type="drag"><i class="zmdi zmdi-arrows"></i></button>
        <button type="button" class="handle-prev handle-btn" data-type="prev"><i class="zmdi zmdi-long-arrow-up"></i></button>
        <button type="button" class="handle-next handle-btn" data-type="next"><i class="zmdi zmdi-long-arrow-down"></i></button>
      </div>
      <div class="nme-block-actions">
        <button type="button" class="handle-clone handle-btn" data-type="clone"><i class="zmdi zmdi-collection-plus"></i></button>
        <button type="button" class="handle-delete handle-btn" data-type="delete"><i class="zmdi zmdi-delete"></i></button>
      </div>
    </div>	
  </div>
</div>
{* Template Content: END *}
</textarea>