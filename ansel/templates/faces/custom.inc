<h1 class="header"><?php echo $title ?></h1>
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
      <input type="submit" name="submit" class="horde-default" value="<?php echo _("Save") ?>" />
      <input type="reset" name="reset" value="<?php echo _("Reset") ?>" />
      <input type="submit" name="submit" class="horde-cancel" value="<?php echo _("Cancel") ?>" />
    </div>
  </form>
</div>
