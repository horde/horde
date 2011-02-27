<form name="captions" action="captions.php" method="post">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="gallery" value="<?php echo htmlspecialchars($galleryId) ?>" />
<input type="hidden" name="do" value="save" />
<input type="hidden" name="year" value="<?php echo (empty($date['year']) ? 0 : $date['year']) ?>" />
<input type="hidden" name="month" value="<?php echo (empty($date['month']) ? 0 : $date['month']) ?>" />
<input type="hidden" name="day" value="<?php echo (empty($date['day']) ? 0 : $date['day']) ?>" />

<h1 class="header">
 <?php echo htmlspecialchars($title) ?>
</h1>

<?php if ($gallery->get('desc')): ?>
<p class="box">
 <?php echo $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($gallery->get('desc'), 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO)) ?>
</p>
<?php endif; ?>

<table cellspacing="0" width="100%">
 <tr>
  <td width="100%" class="text">
<?php
if ($gallery->countImages()) {
    echo '<table cellpadding="5" cellspacing="0" class="item" width="100%"><tr class="text">';

    $count = 0;
    $images = $gallery->getImages();
    $style = $gallery->getStyle();

    foreach ($images as $image) {
        $thumb_url = Ansel::getImageUrl($image->id, $style->thumbstyle, false, $style);

        echo '<td width="10%" align="right" valign="top">';
        echo '<img src="' . $thumb_url . '" alt="' . htmlspecialchars($image->filename) . '" />' .
            '</td><td width="40%" valign="top"><textarea name="img' . $image->id . '" rows="5" cols="25">' .
            htmlspecialchars($image->caption) . '</textarea></td>';

        $count++;
        if (($count % 2) == 0) {
            echo '</tr><tr class="' . (($count % 4 == 0) ? 'text' : 'item0') . '">';
        }
    }

    $remainder = count($images) % 2;
    for ($i = 0; $i < $remainder; $i++) {
        $count++;
        echo '<td>&nbsp;</td><td>&nbsp;</td>';
    }

    echo '</tr><tr class="' . (($count % 4 == 0) ? 'text' : 'item0') . '"><td align="center" colspan="4"><input type="submit" value="' . _("Save Captions") . '" class="button" /></tr></table>';
} else {
    echo '<em>' . _("There are no photos in this gallery.") . '</em>';
} ?>
  </td>
 </tr>
</table>
</form>
