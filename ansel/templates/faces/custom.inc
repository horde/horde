<h1 class="header"><?php echo $title ?></h1>

<script type="text/javascript">

function onEndCrop(coords, dimensions) {
    $('x1').value = coords.x1;
    $('y1').value = coords.y1;
    $('x2').value = coords.x2;
    $('y2').value = coords.y2;
}

Event.observe(
    window,
    'load',
    function() {
        new Cropper.ImgWithPreview(
            'faceImage',
            {
                minWidth: 50,
                minHeight: 50,
                ratioDim: {
                    x: 50,
                    y: 50
                },
                onEndCrop: onEndCrop,
                <?php if ($x1): ?>
                onloadCoords: { x1: <?php echo $x1 ?>, y1: <?php echo $y1 ?>, x2: <?php echo $x2 ?>, y2: <?php echo $y2 ?> },
                <?php endif; ?>
                previewWrap: 'previewArea'
            }
        )
    }
);

</script>
<div style="float:left;width:70%;">
  <img src="<?php echo Ansel::getImageUrl($image_id, 'screen') ?>" alt="<?php echo htmlspecialchars($image->filename) ?>" id="faceImage" />
</div>
<div style="float:left;width:25%;text-align:center;">
   <form method="post" action="<?php echo $form_post ?>">
      <input type="hidden" name="image_id" id="image_id" value="<?php echo (int)$image->id ?>" />
      <input type="hidden" name="face_id" id="face_id" value="<?php echo (int)$face_id ?>" />
      <input type="hidden" name="gallery_id" id="gallery_id" value="<?php echo (int)$image->gallery ?>" />
      <input type="hidden" name="x1" id="x1" value="<?php echo $x1 ?>" />
      <input type="hidden" name="y1" id="y1" value="<?php echo $y1 ?>" />
      <input type="hidden" name="x2" id="x2" value="<?php echo $x2 ?>" />
      <input type="hidden" name="y2" id="y2" value="<?php echo $y2 ?>" />
      <div class="control">
      <div id="previewArea"></div>
        <?php echo _("Person in photo") ?>:<input type="text" name="name" id="name" value="<?php echo htmlspecialchars($name) ?>" /><br />
        <input type="submit" name="submit" class="button" value="<?php echo _("Save") ?>" />
        <input type="submit" name="submit" class="button" value="<?php echo _("Cancel") ?>" />
        <input type="reset" name="reset" class="button" value="<?php echo _("Reset") ?>" />
      </div>
   </form>
</div>
