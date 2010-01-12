<div class="gallery-tile">
 <?php echo $image_link ?>
 <div class="gallery-tile-caption"><?php echo $text_link ?> (<?php echo (int)$gallery_count ?>)</div>
 <div class="gallery-tile-stats">
<?php if (isset($properties_link)): ?>
  <?php echo $properties_link ?><br />
<?php endif; ?>
  <?php echo $created ?><br />
  <?php echo $modified ?><br />
<?php if (isset($owner_link)): ?>
  <?php echo _("Owner") ?>: <?php echo $owner_link ?> <br />
<?php endif; ?>
 </div>
</div>
