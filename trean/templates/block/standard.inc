<?php
$target = $GLOBALS['prefs']->getValue('show_in_new_window') ? '_blank' : '';
$bookmark_url = Horde::url('redirect.php')->add('b', $bookmark->id);
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
  <td valign="top">
   <?php if ($status) echo Horde::img('http/' . $status) ?>
   <?php echo Horde::img(Trean::getFavicon($bookmark), '', 'class="trean-favicon"', '') ?>
  </td>

  <td valign="top" width="100%">
   <div class="bookmark-title">
    <?php echo Horde::link($bookmark_url, '', '', $target) . htmlspecialchars($bookmark->title) ?></a>
    <span class="small">(<?php echo $bookmark->clicks . ' ' . ($bookmark->clicks == 1 ? _("click") : _("clicks")) ?>)</span>
   </div>

   <div class="small">
    <?php echo Horde::link($bookmark_url, '', '', $target) . nl2br(htmlspecialchars(wordwrap($bookmark->url, 100, "\n", true))) ?></a>
   </div>

   <div>
    <?php echo htmlspecialchars($bookmark->description) ?>
   </div>
  </td>
 </tr>
</table>
