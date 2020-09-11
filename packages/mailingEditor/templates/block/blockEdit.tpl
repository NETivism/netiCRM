<textarea class="nme-tpl" data-template-level="block" data-template-name="edit">
{* Template Content: BEGIN *}
<div id="nme-block-[nmeBlockID]" data-id="[nmeBlockID]" class="nme-block" data-type="[nmeBlockType]" data-section="[nmeBlockSection]" data-sortable="[nmeBlockSortable]" data-block-override="[nmeBlockOverride]" data-elem-override="[nmeElemOverride]">
  <div class="nme-block-inner">
    <div class="nme-block-content">
      <!-- Block Content HERE -->
      [nmeBlockContent]
    </div>
    <div class="nme-block-control">
      <div class="nme-block-move">
        <button type="button" title="{ts}Move Up{/ts}" class="handle-prev handle-btn" data-type="prev" data-tooltip><i class="zmdi zmdi-long-arrow-up"></i></button>
        <button type="button" title="{ts}Move Down{/ts}" class="handle-next handle-btn" data-type="next" data-tooltip><i class="zmdi zmdi-long-arrow-down"></i></button>
      </div>
      <div class="nme-block-actions">
        <button type="button" title="{ts}Duplicate Block{/ts}" class="handle-clone handle-btn" data-type="clone" data-tooltip><i class="zmdi zmdi-collection-plus"></i></button>
        <button type="button" title="{ts}Delete Block{/ts}" class="handle-delete handle-btn" data-type="delete" data-tooltip><i class="zmdi zmdi-delete"></i></button>
      </div>
    </div>
  </div>
</div>
{* Template Content: END *}
</textarea>