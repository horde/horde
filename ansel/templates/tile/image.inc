<?php echo '<div class="image-tile" id="imagetile_' . (int)$image->id . '">' . Horde::link($view_url, $image->filename, '', '', $imgOnClick, $title, '', $imgAttributes) . '<img src="' . $thumb_url  . '" alt="' . htmlspecialchars($image->filename) . '" /></a>'; ?>
 <div style="valign: bottom;">
 <div class="image-tile-caption" id="<?php echo (int)$image->id . 'caption'?>"><?php echo $imageCaption ?></div>
<?php if (Horde_Auth::getAuth() || $option_select): ?>
<div><label><input type="checkbox" class="checkbox" name="image[<?php echo (int)$image->id ?>]" /> <?php echo _("Select for Action") ?></label></div>
<?php endif; ?>
<?php if ($view != 'Gallery'): ?>
<div><?php echo _("From: ") . Horde::link($gallery_url) . $parent->get('name') ?></a></div>
<?php endif; ?>
<?php if (($conf['comments']['allow'] == 'all' || ($conf['comments']['allow'] == 'authenticated' && Horde_Auth::getAuth())) && empty($params['hide_comments'])): ?>
 <div><?php echo Horde::link($img_view_url . '#messagediv', _("View or leave comments")) ?> <?php printf(ngettext("%d comment", "%d comments", $image->commentCount), $image->commentCount) ?></a></div>
<?php endif;
if ($option_select): ?>
 <div>
  <span class="light">
   <?php if ($option_edit): ?>
    <?php echo Horde::link(Horde::applicationUrl(Horde_Util::addParameter($image_url, array('actionID' => 'modify'))), '', '', '_blank', 'popup(this.href); return false;') . _("Properties") ?></a>
    | <?php echo Horde::link(Horde::applicationUrl(Horde_Util::addParameter($image_url, 'actionID', 'editimage'))) . _("Edit") ?></a>
   <?php endif; ?>
  </span>
 </div>
<?php endif; ?>
</div>
</div>
