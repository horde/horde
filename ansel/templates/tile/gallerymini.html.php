<!-- Gallery Mini Tile -->
<div style="padding: 5px; background-color: <?php echo $this->background_color ?>;">
 <span style="width: 50%;"><?php echo $this->contentTag('a', $this->tag('img', array('src' => $this->gallery_image, 'alt' => '[image]')), array('href' => $this->view_link)) ?></span>
 <span style="width: 50%;"><?php echo $this->contentTag('a', $this->h($this->gallery->get('name')), array('href' => $this->view_link)) ?> (<?php echo $this->gallery_count ?>)</span>
</div>
<!-- End Gallery Mini Tile -->
