<div class="pcplist-item pcplist-item-<?php print $pcp_id ?>">
  <div class="pcplist-title"><a href="<?php print $view_url ?>"><?php print $title ?></a></div>
  <div class="pcplist-image"><img src="<?php print $image_src ?>" /></div>
  <div class="pcplist-count"><label><?php print t("Contribution count") ?>:</label> <?php print $contribution_count ?></div>
  <div class="pcplist-intro"><?php print $intro_text ?>  <span class="more"><a href="<?php print $view_url ?>"><?php print t('more') ?></a></span></div>
  <div class="pcplist-contrib"><?php print $contribution_link ?></div>
  <div class="pcplist-achievement"><label><?php print t('Achieved contribution') ?>:</label> <?php print $achieved_amount ?>(<?php print t('Achieved') ?>) / <?php print $goal_amount ?>(<?php print t('goal') ?>)</div>
</div>
