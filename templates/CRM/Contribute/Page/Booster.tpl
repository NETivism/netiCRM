<div class="crm-contribute-booster">
  {foreach from=$main_categories key=main_title item=items}
  <div class="crm-section">
    <h2 class="crm-section-title">{$main_title}</h2>
    <div class="crm-section-content">
    {foreach from=$items key=weight item=card}
      <div id="{$card.id}" class="mdl-card mdl-shadow--2dp {$card.class}">
        <a href="{$card.link}">
          <div class="mdl-card__title"><h3 class="mdl-card__title-text">{$card.title}</h3></div>
          <div class="mdl-card__supporting-text">{$card.description}</div>
        </a>
      </div>
    {/foreach}{* end items*}
    </div>
  </div>
  {/foreach}{*end main_categories*}
</div>
