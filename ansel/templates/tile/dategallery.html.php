<div class="gallery-tile">
 <?php echo $this->contentTag('a', $this->tag('img', array('src' => $this->gallery_image,  'alt' => $this->caption)), array('href' => $this->view_link)) ?>
 <div class="gallery-tile-caption"><?php echo $this->contentTag('a', $this->h($this->caption), array('href' => $this->view_link)) ?><div class="gallery-tile-count">(<?php echo $this->gallery_count ?>)</div></div>
</div>
