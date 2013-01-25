<!-- Image Tile -->
<div class="image-tile" id="imagetile_<?php echo $this->image->id?>">
  <?php echo $this->contentTag('a', $this->tag('img', array('src' => $this->thumb_url, 'alt' => $this->h($this->image->filename))), array_merge(array('href' => $this->view_url, 'title' => $this->image->filename, 'onclick.raw' => $this->imgOnClick), $this->imgAttributes)) ?>
  <div style="valign: bottom;">
   <?php echo $this->contentTag('div', $this->imageCaption, array('class' => 'image-tile-caption',  'id' => $this->image->id . 'caption')) ?>
   <?php if ($GLOBALS['registry']->getAuth() || $option_select): ?>
     <div><label><input type="checkbox" class="checkbox" name="image[<?php echo $this->image->id ?>]" /> <?php echo _("Select for Action") ?></label></div>
   <?php endif; ?>
   <?php if ($this->view_type != 'Gallery'): ?>
     <div><?php echo _("From") . ': ' . $this->contentTag('a', $this->h($this->parent->get('name')), array('href' => strval($this->gallery_url))) ?></div>
   <?php endif; ?>
   <?php if (!empty($this->option_comments)):?>
     <div><?php echo $this->contentTag('a', sprintf(ngettext("%d comment", "%d comments", $this->image->commentCount), $this->image->commentCount), array('href' => strval($this->img_view_url->copy()->setAnchor('messagediv')), 'title' => _("View or leave comments"))) ?></div>
   <?php endif; ?>
   <?php if ($this->option_select): ?>
     <div>
        <span class="light">
        <?php if ($this->option_edit): ?>
          <?php echo $this->contentTag('a', _("Properties"), array('href' => $this->image_url->copy()->add(array('actionID' => 'modify')), 'target' => '_blank', 'onclick' => Horde::popupJs(Horde::url($this->image_url), array('height' => 360, 'width' => 500, 'params' => array('actionID' => 'modify', 'urlencode' => true))) . ' return false;')) ?>
    |     <?php echo $this->contentTag('a', _("Edit"), array('href' => $this->image_url->copy()->add('actionID', 'editimage'))) ?>
        <?php endif; ?>
        </span>
     </div>
   <?php endif; ?>
  </div>
</div>
<!-- End image tile -->