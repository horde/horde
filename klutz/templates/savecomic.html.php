<form method="post" name="save_comic_dialog" action="<?php echo Horde::url('savecomic.php') ?>">
<input type="hidden" name="actionID" value="save_comic" />
<input type="hidden" name="date" value="<?php echo htmlspecialchars($date) ?>" />
<input type="hidden" name="index" value="<?php echo htmlspecialchars($index) ?>" />

<h1 class="header">
 <?php echo Horde::img('mime/image.png', _("Image"), null, $registry->getImageDir('horde')); ?>
 <?php echo _("Select the gallery to save the comic to.") ?>
</h1>

<p class="item">
 <label for="gallery" class="hidden"><?php echo _("Gallery") ?></label>
 <select id="gallery" name="gallery">
  <?php echo $gallerylist ?>
 </select>
</p>
<p class="item">
 <h1 class="smallheader"><?php echo _("Enter a description for this comic") ?></h1>
 <label for="description" class="hidden"><?php echo _("Description") ?></label>
 <textarea name="desc" rows="4" cols="40"><?php echo htmlspecialchars($comic->name . ' - ' . strftime('%B %d, %Y', $date)) ?></textarea>
</p>
<p class="item">
 <h1 class="smallheader"><?php _("Enter any tags for this image") ?></h1>
 <label for="tags" class="hidden"><?php echo _("Tags") ?></label>
 <input name="tags" type="text" />
</p>
<div class="nowrap">
 <input type="submit" name="submit" class="horde-default" value="<?php echo _("Submit") ?>" />&nbsp;
 <input type="button" class="horde-cancel" onclick="window.close();" value="<?php echo _("Cancel") ?>" />
</div>
</form>
