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
  <td valign="top">
    <?php echo Horde::img(Trean::getFavicon($bookmark), '', 'class="favicon"', '') ?>
    <?php if ($status) echo Horde::img('http/' . $status) ?>
  </td>

  <td width="100%" valign="top">
   <div class="rating"><?php echo static_star_rating_helper($bookmark) ?></div>

   <div>
    <?php echo Horde::link($bookmark_url, '', '', $target) . htmlspecialchars($bookmark->title) ?></a>
   </div>

   <div class="small">
    <span class="small">(<?php echo $bookmark->clicks . ' ' . ($bookmark->clicks == 1 ? _("click") : _("clicks")) ?>)</span>
    <?php echo htmlspecialchars($bookmark->description) ?>
   </div>
  </td>
 </tr>
</table>
