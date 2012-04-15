<?php
/*
 * @donor
 * @name
 * @picture
 * @amount
 */
?>
<div class="donor">
  <div class="donor-name"><?php print $name; ?></div>
  <div class="donor-picture"><?php print $picture; ?></div>
  <div class="donor-amount"><label><?php print t('Donation'); ?></label><?php print $amount; ?></div>
</div>
