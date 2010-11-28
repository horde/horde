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
<div class="checkout">
<?php
if (strpos($mime_type, 'text/plain') !== false) {
    $data = $pretty->render('inline');
    $data = reset($data);
    echo '<div class="fixed">' . $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($data['data'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO)) . '</div>';
} elseif (strpos($mime_type, 'image/') !== false) {
    echo Horde::img(Horde_Util::addParameter(Horde::selfUrl(true), 'p', 1), '', '', '');
} elseif ($pretty->canRender('inline')) {
    $data = $pretty->render('inline');
    $data = reset($data);
    echo $data['data'];
} else {
    echo Horde::link(Horde_Util::addParameter(Horde::selfUrl(true), 'p', 1)) . Horde::img('download.png') . ' ' . sprintf(_("Download revision %s"), $r) . '</a>';
}
?>
</div>
