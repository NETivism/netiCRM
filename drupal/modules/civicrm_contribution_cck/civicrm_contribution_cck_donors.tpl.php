<?php
/**
 * @donors
 * @node
 * @type
 * @delta
 * @content
 */
?>
<div class="civicc-donation civicc-type-<?php print $delta; ?>">
  <?php if($type){ ?><h3><?php print $type; ?></h3><?php } ?>
  <div class=donors>
  <?php print $content; ?>
  </div>
</div>
