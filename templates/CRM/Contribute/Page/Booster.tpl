{foreach from=$main_categories key=main_title item=items}
<h2>{$main_title}</h2>
{foreach from=$items key=weight item=card}
<div class="mdl-card">
  <a href="{$card.link}">
  <div class="mdl-card__title">{$card.title}</div>
  <div class="mdl-card__supporting-text">{$card.description}</div>
  </a>
</div>
{/foreach}{* end items*}
{/foreach}{*end main_categories*}
