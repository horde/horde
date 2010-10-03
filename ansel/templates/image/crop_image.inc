<script type="text/javascript">

var imageDimensions = new Array({x:0, y:0}, {x:4, y:6}, {x:5, y:7}, {x:8, y:10});

var imageCropper = {

    // The current cropper object
    currentCropper: null,

    // Original Coords in case we request a reset
    x1: <?php echo $x1 ?>,
    x2: <?php echo $x2 ?>,
    y1: <?php echo $y1 ?>,
    y2: <?php echo $y2 ?>,
    ratioX: 0,
    ratioY: 0,

    // Initialize the object
    init: function()
    {
        this.attachCropper();
        $('reset').observe('click', this.reset.bindAsEventListener(imageCropper));
        changeRatio();
    },

    // Resets cropper to the original coords
    reset: function()
    {
         $('x1').value = this.x1;
         $('x2').value = this.x2;
         $('y1').value = this.y1;
         $('y2').value = this.y2;
         this.attachCropper();
    },

    // Restrict to a particular aspect ratio
    setAspectRatio: function(x, y)
    {
        this.ratioX = x;
        this.ratioY = y;
        this.attachCropper();
    },

    // Initialize the Cropper object
    attachCropper: function() {
        if (this.currentCropper != null) {
            this.currentCropper.remove();
        }
        this.currentCropper = new Cropper.Img(
            'edit_image',
            {
                onloadCoords: { x1: $F('x1'), y1: $F('y1'), x2: $F('x2'), y2: $F('y2') },
                displayOnInit: true,
                onEndCrop: function(coords, dimensions) {
                    $('x1').value = coords.x1;
                    $('y1').value = coords.y1;
                    $('x2').value = coords.x2;
                    $('y2').value = coords.y2;
                    $('width').value = dimensions.width;
                    $('height').value = dimensions.height;
                },
                ratioDim: {x: this.ratioX, y:this.ratioY}
            });
     }
}

document.observe('dom:loaded', function() { imageCropper.init()});

function changeRatio()
{
    // First check if we are manual, then disable the orientation selector
    if ($F('ratio') > 0) {
        $('orientation').enable();
    } else {
        $('orientation').disable();
    }

    if ($F('orientation') == 'portrait') {
        imageCropper.setAspectRatio(imageDimensions[$F('ratio')].x, imageDimensions[$F('ratio')].y);
    } else {
        imageCropper.setAspectRatio(imageDimensions[$F('ratio')].y, imageDimensions[$F('ratio')].x);
    }
}
</script>

<h1 class="header"><?php echo htmlspecialchars($title, ENT_COMPAT, 'UTF-8') ?></h1>
<div class="control anselActions" style="text-align: center">
<?php
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
    $url = new Horde_Url('#');
    echo $url->link(array('target' => '_blank', 'onclick.raw' => Horde::popupJs($imageurl, array('params' => array('actionID' => 'setwatermark', 'share' => $gallery->getName()), 'width' => 450, 'height' => 250, 'urlencode' => true)) . 'return false;')) . Horde::img('text.png') . ' ' . _("Watermark") . '</a> ';
}
$selfUrl = Horde::selfUrl(true);
echo $selfUrl->copy()->add('actionID','cropedit')->link() . Horde::img('image/crop.png') . _("Crop") . '</a>';
echo $selfUrl->copy()->add('actionID', 'resizeedit')->link() . Horde::img('image/ratio.png') . _("Resize") . '</a>';
?>
</div>
<br />
<div style="width:70%;float:left;">
  <div>
    <?php echo '<img src="' . $image_src . '" id="edit_image" alt="[image]" />' ?>
  </div>
  <br />
    <?php $caption = $image->caption ? $image->caption : $image->filename; echo $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($caption, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO)) ?>
</div>
<div style="text-align:center;width:25%;float:left;position:relative;">
<form method="post" action="<?php echo $imageurl->copy()->add('actionID', 'previewcrop');?>">
  <input type="hidden" name="image_id" id="image_id" value="<?php echo (int)$image_id ?>" />
  <input type="hidden" name="x1" id="x1" value="<?php echo $x1 ?>" />
  <input type="hidden" name="y1" id="y1" value="<?php echo $y1 ?>" />
  <input type="hidden" name="x2" id="x2" value="<?php echo $x2 ?>" />
  <input type="hidden" name="y2" id="y2" value="<?php echo $y2 ?>" />
   <div style="width:50%;float:left;text-align:right;">
    <?php echo _("Desired dimensions:")?>
   </div>
   <div style="width:50%;float:left;text-align:left;">
    <select name="ratio" style="width:100px;" id="ratio" onchange="changeRatio()">
     <option value="0"><?php echo _("Manual"); ?></option>
     <option value="1">2:3</option>
     <option value="2">5:7</option>
     <option value="3">8:10</option>
    </select><br />
   </div>
   <div style="width:50%;float:left;text-align:right;">
    <?php echo _("Orientation:") ?>
   </div>
   <div style="width:50%;float:left;text-align:left;">
    <select name="orientation" style="width:100px;" id="orientation" onchange="changeRatio()">
     <option value="landscape"><?php echo _("Landscape") ?></option>
     <option value="portrait"><?php echo _("Portrait") ?></option>
    </select><br />
   </div>
   <div style="width:50%;float:left;text-align:right;">
      <?php echo _("width") ?>:
     </div>
     <div style="width:50%;float:left;text-align:left;">
      <input type="text" name="width" id="width" readonly="readonly" style="width:100px;" value="<?php echo (int)$geometry['width'] ?>"/><br />
     </div>
     <div style="width:50%;float:left;text-align:right;">
      <?php echo _("height") ?>:
      </div>
      <div style="width:50%;float:left;text-align:left;">
      <input type="text" name="height" id="height" readonly="readonly" style="width:100px;" value="<?php echo (int)$geometry['height'] ?>"/><br />
     </div>
      <input type="submit" name="submit" id="submit" value="<?php echo _("Preview") ?>" />
      <input type="reset" name="reset" id="reset" value="<?php echo _("Reset") ?>" />
    </form>
 </div>
</div>
