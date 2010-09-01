<?php echo '<div class="image-tile" id="imagetile_' . (int)$image->id . '">' . $view_url->link(array_merge(array('title' => $image->filename, 'onclick.raw' => $imgOnClick)), $imgAttributes) . '<img src="' . $thumb_url  . '" alt="' . htmlspecialchars($image->filename) . '" /></a>'; ?>
 <div style="valign: bottom;">
 <div class="image-tile-caption" id="<?php echo (int)$image->id . 'caption'?>"><?php echo $imageCaption ?></div>
<?php if ($GLOBALS['registry']->getAuth() || $option_select): ?>
<div><label><input type="checkbox" class="checkbox" name="image[<?php echo (int)$image->id ?>]" /> <?php echo _("Select for Action") ?></label></div>
<?php endif; ?>
<?php if ($view != 'Gallery'): ?>
<div><?php echo _("From: ") . $gallery_url->link() . $parent->get('name') ?></a></div>
<?php endif; ?>
<?php if (($conf['comments']['allow'] == 'all' || ($conf['comments']['allow'] == 'authenticated' && $GLOBALS['registry']->getAuth())) && empty($params['hide_comments'])): ?>
 <div><?php echo $img_view_url->copy()->setAnchor('messagediv')->link(array('title' => _("View or leave comments"))) . sprintf(ngettext("%d comment", "%d comments", $image->commentCount), $image->commentCount) ?></a></div>
<?php endif;
if ($option_select): ?>
 <div>
  <span class="light">
   <?php if ($option_edit): ?>
    <?php echo $image_url->copy()->add(array('actionID' => 'modify'))->link(
                array('target' => '_blank',
                      'onclick' => Horde::popupJs(Horde::url($image_url), array('params' => array('actionID' => 'modify', 'urlencode' => true)))
            . 'return false;')) . _("Properties")?></a>
    | <?php echo $image_url->copy()->add('actionID', 'editimage')->link() . _("Edit") ?></a>
   <?php endif; ?>
  </span>
 </div>
<?php endif; ?>
</div>
</div>
