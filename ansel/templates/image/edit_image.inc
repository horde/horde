<h1 class="header"><?php echo htmlspecialchars($title, ENT_COMPAT, $GLOBALS['registry']->getCharset()) ?></h1>
<div class="control anselActions" style="text-align: center">
<div id="prevDiv"></div>
<?php
$style = $gallery->getStyle();
$image_src = Ansel::getImageUrl($image_id, 'screen');

echo $galleryurl->link() . Horde::img('galleries.png') . ' ' . _("Back to gallery") . '</a> ';
echo $ImageGenerator_url->link() . Horde::img('galleries.png') . ' ' . _("Cancel") . '</a> ';

if (Ansel::isAvailable('rotate')) {
    echo $imageurl->copy()->add('actionID', 'previewrotate270')->link() . Horde::img('image/rotate-270.png') . ' ' . _("Rotate Left") . '</a> ';
    echo $imageurl->copy()->add('actionID', 'previewrotate180')->link() . Horde::img('image/rotate-180.png') . ' ' . _("Rotate 180") . '</a> ';
    echo $imageurl->copy()->add('actionID', 'previewrotate90')->link() . Horde::img('image/rotate-90.png') . ' ' . _("Rotate Right") . '</a> ';
}
if (Ansel::isAvailable('flip')) {
    echo $imageurl->copy()->add('actionID', 'previewflip')->link() . Horde::img('image/flip.png') . ' ' . _("Flip") . '</a> ';
}
if (Ansel::isAvailable('mirror')) {
    echo $imageurl->copy()->add('actionID', 'previewmirror')->link() . Horde::img('image/mirror.png') . ' ' . _("Mirror") . '</a> ';
}
if (Ansel::isAvailable('grayscale')) {
    echo $imageurl->copy()->add('actionID', 'previewgrayscale')->link() . Horde::img('image/grayscale.png') . ' ' . _("Grayscale") . '</a> ';
}
if (Ansel::isAvailable('TextWatermark')) {
    echo Horde::link('#', '', '', '_blank', Horde::popupJs(Horde::applicationUrl($imageurl), array('params' => array('actionID' => 'setwatermark', 'share' => $gallery->getName()), 'width' => 450, 'height' => 250, 'urlencode' => true)) . 'return false;') . Horde::img('text.png') . ' ' . _("Watermark") . '</a> ';
}

$selfUrl = Horde::selfUrl(true);
echo $selfUrl->copy()->add('actionID', 'cropedit')->link() . Horde::img('image/crop.png') . _("Crop") . '</a>';
echo $selfUrl->copy()->add('actionID', 'resizeedit')->link() . Horde::img('image/ratio.png') . _("Resize") . '</a>';
?>
</div>
<br />
<div style="text-align: center;">
  <?php echo '<img src="' . $image_src . '" id="edit_image" alt="[image]" />' ?>
  <br />
  <?php $caption = $image->caption ? $image->caption : $image->filename; echo Horde_Text_Filter::filter($caption, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO)) ?>
</div>
