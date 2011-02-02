  <tr>
   <td align="right" valign="top"><strong><?php echo _("Thumbnail Style"); ?></strong></td>
   <td>
    <select name="thumbnail_style">
      <?php foreach ($this->availableThumbs as $type => $title): ?>
        <option value="<?php echo $type ?>" <?php if ($this->properties['style']->thumbstyle == $type): ?>selected="selected"<?php endif; ?>><?php echo $title ?></option>
      <?php endforeach;?>
    </select>
   </td>
  </tr>
  <tr>
   <td align="right" valign="top"><strong><?php echo _("Background Color"); ?></strong></td>
   <td>
    <select name="background_color">
      <option value="none" selected="selected">None</option>
      <option value="white">White</option>
    </select>
   </td>
  </tr>
  <tr>
   <td align="right" valign="top"><strong><?php echo _("View Style"); ?></strong></td>
   <td>
    <select name="gallery_view">
     <?php foreach ($this->galleryViews as $type => $title): ?>
       <option value="<?php echo $type ?>"<?php if ($this->properties['style']->gallery_view == $type): ?>selected="selected"<?php endif; ?>><?php echo $title ?></option>
     <?php endforeach; ?>
    </select>
   </td>
  </tr>