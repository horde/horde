<div class="header">
 <span class="smallheader rightFloat"><?php echo strftime('%B %d, %Y', $date) ?></span>
<?php if ($homepage): ?>
 <a href="<?php echo $homepage ?>" target="_blank">
  <?php printf(_("%s by %s"), $name, $author) ?>
 </a>
<?php else: ?>
 <?php printf(_("%s by %s"), $name, $author) ?>
<?php endif; ?>
<?php if (!empty($imageApp)) { echo $saveImgLink; } ?>
</div>
<p><img alt="<?php echo htmlspecialchars($name) ?>" src="<?php echo $url ?>" <?php echo $size ?> /></p>
