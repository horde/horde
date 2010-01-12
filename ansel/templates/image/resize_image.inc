<script type="text/javascript">
document.observe(
    'dom:loaded',
    function()
    {
        document.slider = new Control.Slider(
            'handle1',
            'slider-track',
            {
                range: $R(1, <?php echo $geometry['height'] ?>),
                sliderValue: <?php echo $geometry['height'] ?>,
                axis:'horizontal',
                onChange: function(value) {resizeImage(value)},
                onSlide: function(value) {resizeImage(value)}
            }
        );
    });

function resizeImage(value)
{
    $('edit_image').height = value;
    $('width').value = $('edit_image').width;
    $('height').value = $('edit_image').height;

}

function resetImage()
{
    document.slider.setValue(<?php echo $geometry['height'] ?>);
}
</script>
<h1 class="header"><?php echo htmlspecialchars($title, ENT_COMPAT, Horde_Nls::getCharset()) ?></h1>
<div class="control anselActions" style="text-align: center">
<?php

$style = $gallery->getStyle();
$image_src = Ansel::getImageUrl($image_id, 'full');

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
if (Ansel::isAvailable('text_watermark')) {
    echo Horde::link('#', '', '', '_blank', Horde::popupJs(Horde::applicationUrl($imageurl), array('params' => array('actionID' => 'setwatermark', 'share' => $gallery->getName()), 'width' => 450, 'height' => 250, 'urlencode' => true)) . 'return false;') . Horde::img('text.png') . ' ' . _("Watermark") . '</a> ';
}

$selfUrl = Horde::selfUrl(true);
echo Horde::link(Horde::applicationUrl(Horde_Util::addParameter($selfUrl, array('actionID' => 'cropedit')))) . Horde::img('image/crop.png', '', '', $registry->getImageDir('horde')) . _("Crop") . '</a>';

echo Horde::link(Horde::applicationUrl(Horde_Util::addParameter($selfUrl, array('actionID' => 'resizeedit')))) . Horde::img('image/ratio.png', '', '', $registry->getImageDir('horde')) . _("Resize") . '</a>';
?>
</div>
<form method="post" action="<?php echo Horde::applicationUrl(Horde_Util::addParameter($imageurl, 'actionID', 'resize'));?>">
<div style="width:100%;text-align:center;margin-top:5px;margin-bottom:5px;">
  <?php echo _("width") ?>:
  <input type="text" name="width" id="width" size="5" readonly="readonly" value="<?php echo (int)$geometry['width'] ?>"/>
  <?php echo _("height") ?>:
  <input type="text" name="height" id="height" size="5" readonly="readonly" value="<?php echo (int)$geometry['height'] ?>"/>
  <div id="slider-track">
   <div id="handle1" class="sliderHandle"></div>
  </div>
   <input type="submit" name="submit" id="submit" value="<?php echo _("Save") ?>" />
  <input type="reset" name="reset" id="reset" value="<?php echo _("Reset") ?>" onclick="resetImage()"/>
</div>

<div style="width: 100%;float:left;text-align: center;padding-top:10px;">
 <?php echo '<img src="' . $image_src . '" id="edit_image" alt="[image]" />' ?>
 <br />
 <?php $caption = $image->caption ? $image->caption : $image->filename; echo Horde_Text_Filter::filter($caption, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO)) ?>
</div>
