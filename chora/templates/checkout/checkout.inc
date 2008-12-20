<h3 class="revision_log"><?php echo _("Log Message") ?></h3>
<div class="revision_log">
 <ul class="revision striped">
  <?php if ($author): ?><li><?php echo _("Author:") ?> <?php echo $author ?></li><?php endif; ?>
  <?php if ($branchName): ?><li><?php echo _("Branch:") ?> <strong><a href="<?php echo Chora::url('', $where, array('onb' => $branchRev)) ?>"><?php echo $branchName ?></a></strong></li><?php endif; ?>
  <?php if ($tags): ?><li class="tags"><?php echo _("Tags:") ?> <?php echo implode(', ', $tags) ?></li><?php endif; ?>
 </ul>
 <?php echo $log_print ?>
 <div class="clear"></div>
</div>

<h3 class="checkout"><?php echo _("Checkout") ?></h3>
<div class="checkout">
<?php
$type = $pretty->getType();
if (strpos($type, 'text/plain') !== false) {
    echo '<div class="fixed">' . Text_Filter::filter($pretty->render(), 'text2html', array('parselevel' => TEXT_HTML_MICRO)) . '</div>';
} elseif (strpos($type, 'image/') !== false) {
    echo Horde::img(Util::addParameter(Horde::selfUrl(true), 'p', 1), '', '', '');
} elseif ($pretty->canDisplayInline()) {
    echo $pretty->render();
} else {
    echo Horde::link(Util::addParameter(Horde::selfUrl(true), 'p', 1)) . Horde::img('download.png', '', '', $registry->getImageDir('horde')) . ' ' . sprintf(_("Download revision %s"), $r) . '</a>';
}
?>
</div>
