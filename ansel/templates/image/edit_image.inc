<h1 class="header"><?php echo htmlspecialchars($title, ENT_COMPAT, Horde_Nls::getCharset()) ?></h1>
<div class="control anselActions" style="text-align: center">
<div id="prevDiv"></div>
<?php
$style = $gallery->getStyle();
$image_src = Ansel::getImageUrl($image_id, 'screen');

echo Horde::link($galleryurl) . Horde::img('galleries.png') . ' ' . _("Back to gallery") . '</a> ';
echo Horde::link($imageview_url) . Horde::img('galleries.png') . ' ' . _("Cancel") . '</a> ';

if (Ansel::isAvailable('rotate')) {
    echo Horde::link(Horde_Util::addParameter($imageurl, 'actionID', 'previewrotate270')) . Horde::img('image/rotate-270.png', '', '', $registry->getImageDir('horde')) . ' ' . _("Rotate Left") . '</a> ';
    echo Horde::link(Horde_Util::addParameter($imageurl, 'actionID', 'previewrotate180')) . Horde::img('image/rotate-180.png', '', '', $registry->getImageDir('horde')) . ' ' . _("Rotate 180") . '</a> ';
    echo Horde::link(Horde_Util::addParameter($imageurl, 'actionID', 'previewrotate90')) . Horde::img('image/rotate-90.png', '', '', $registry->getImageDir('horde')) . ' ' . _("Rotate Right") . '</a> ';
}
if (Ansel::isAvailable('flip')) {
    echo Horde::link(Horde_Util::addParameter($imageurl, 'actionID', 'previewflip')) . Horde::img('image/flip.png', '', '', $registry->getImageDir('horde')) . ' ' . _("Flip") . '</a> ';
}
if (Ansel::isAvailable('mirror')) {
    echo Horde::link(Horde_Util::addParameter($imageurl, 'actionID', 'previewmirror')) . Horde::img('image/mirror.png', '', '', $registry->getImageDir('horde')) . ' ' . _("Mirror") . '</a> ';
}
if (Ansel::isAvailable('grayscale')) {
    echo Horde::link(Horde_Util::addParameter($imageurl, 'actionID', 'previewgrayscale')) . Horde::img('image/grayscale.png', '', '', $registry->getImageDir('horde')) . ' ' . _("Grayscale") . '</a> ';
}
if (Ansel::isAvailable('TextWatermark')) {
    echo Horde::link('#', '', '', '_blank', Horde::popupJs(Horde::applicationUrl($imageurl), array('params' => array('actionID' => 'setwatermark', 'share' => $gallery->getName()), 'width' => 450, 'height' => 250, 'urlencode' => true)) . 'return false;') . Horde::img('text.png') . ' ' . _("Watermark") . '</a> ';
}

$selfUrl = Horde::selfUrl(true);
echo Horde::link(Horde::applicationUrl(Horde_Util::addParameter($selfUrl, array('actionID' => 'cropedit')))) . Horde::img('image/crop.png', '', '', $registry->getImageDir('horde')) . _("Crop") . '</a>';
echo Horde::link(Horde::applicationUrl(Horde_Util::addParameter($selfUrl, array('actionID' => 'resizeedit')))) . Horde::img('image/ratio.png', '', '', $registry->getImageDir('horde')) . _("Resize") . '</a>';

?>
</div>
<br />
<div style="text-align: center;">
  <?php echo '<img src="' . $image_src . '" id="edit_image" alt="[image]" />' ?>
  <br />
  <?php $caption = $image->caption ? $image->caption : $image->filename; echo Horde_Text_Filter::filter($caption, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO)) ?>
</div>
