<?php
$bookmark_url = Horde_Util::addParameter(Horde::url('redirect.php'), 'b', $bookmark->id);
$target = $GLOBALS['prefs']->getValue('show_in_new_window') ? '_blank' : '';
if ($bookmark->http_status == 'error') {
    $status = 'error.png';
} elseif ($bookmark->http_status == '') {
    $status = '';
} else {
    $status = substr($bookmark->http_status, 0, 1) . 'xx.png';
}
?>
<table width="100%" cellspacing="0">
 <tr>
  <td valign="top" width="36">
   <?php echo Horde::img(Trean::getFavicon($bookmark), '', 'class="favicon"', '') ?>
   <?php if ($status) echo Horde::img('http/' . $status) ?>
  </td>

  <td valign="top">
   <div class="rating"><?php echo static_star_rating_helper($bookmark) ?></div>
   <?php echo Horde::link($bookmark_url, '', '', $target) ?><?php echo htmlspecialchars($bookmark->url) ?></a><?php echo (empty($bookmark->description) ? '' : ' - <small>' . htmlspecialchars($bookmark->description) . '</small>') ?>
   <small>(<?php echo $bookmark->clicks ?> <?php echo ($bookmark->clicks == 1 ? _("click") : _("clicks")) ?>)</small>
  </td>
 </tr>
</table>
