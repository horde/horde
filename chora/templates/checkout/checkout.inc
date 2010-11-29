<div class="commit-summary">
 <ul class="revision striped">
  <?php if ($author): ?><li><?php echo _("Author:") ?> <?php echo $author ?></li><?php endif; ?>
<?php if (!empty($branch_info)): ?>
  <li><?php echo _("Branch:") ?>
<?php foreach ($branch_info as $val): ?>
   <strong><a href="<?php echo Chora::url('browsefile', $where, array('onb' => $val)) ?>"><?php echo $val ?></a></strong>
<?php endforeach; ?>
  </li>
<?php endif; ?>
  <?php if ($tags): ?><li class="tags"><?php echo _("Tags:") ?> <?php echo implode(', ', $tags) ?></li><?php endif; ?>
 </ul>
 <?php echo $log_print ?>
 <div class="clear"></div>
</div>

<h3 class="checkout"><?php echo _("Checkout") ?></h3>
<div class="checkout"><?php echo $rendered ?></div>
