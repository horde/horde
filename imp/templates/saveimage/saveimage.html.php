<form method="post" name="save_image_dialog" action="<?php echo $this->action ?>">
 <input type="hidden" name="actionID" value="save_image" />
 <input type="hidden" name="id" value="<?php echo $this->h($this->id) ?>" />
 <input type="hidden" name="uid" value="<?php echo $this->h($this->uid) ?>" />
 <input type="hidden" name="mbox" value="<?php echo $this->h($this->mbox) ?>" />

 <h1 class="header">
  <?php echo $this->hordeImage('mime/image.png', _("Image")) ?>
  <?php echo _("Select the gallery to save the image in.") ?>
 </h1>

 <p class="item">
  <label for="gallery" class="hidden"><?php echo _("Gallery") ?></label>
  <select id="gallery" name="gallery">
   <?php echo $this->gallerylist ?>
  </select>
 </p>

 <div class="nowrap">
  <input type="submit" name="submit" class="horde-default" value="<?php echo _("Submit") ?>" />&nbsp;
  <input type="button" class="horde-cancel" value="<?php echo _("Cancel") ?>" />
 </div>
</form>
