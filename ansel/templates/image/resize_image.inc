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
<h1 class="header"><?php echo htmlspecialchars($title, ENT_COMPAT, $GLOBALS['registry']->getCharset()) ?></h1>
<div class="control anselActions" style="text-align: center">
<?php

$style = $gallery->getStyle();
$image_src = Ansel::getImageUrl($image_id, 'full');

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
if (Ansel::isAvailable('text_watermark')) {
    echo Horde::link('#', '', '', '_blank', Horde::popupJs(Horde::applicationUrl($imageurl), array('params' => array('actionID' => 'setwatermark', 'share' => $gallery->getName()), 'width' => 450, 'height' => 250, 'urlencode' => true)) . 'return false;') . Horde::img('text.png') . ' ' . _("Watermark") . '</a> ';
}

$selfUrl = Horde::selfUrl(true);
echo $selfUrl->copy()->add(array('actionID' => 'cropedit'))->link() . Horde::img('image/crop.png') . _("Crop") . '</a>';

echo $selfUrl->copy()->add(array('actionID' => 'resizeedit'))->link() . Horde::img('image/ratio.png') . _("Resize") . '</a>';
?>
</div>
<form method="post" action="<?php echo $imageurl->copy()->add('actionID', 'resize');?>">
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
 <?php $caption = $image->caption ? $image->caption : $image->filename; echo $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($caption, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO)) ?>
</div>
