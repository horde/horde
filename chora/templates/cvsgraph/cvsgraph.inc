<div class="options">
 <span>
  <?php echo _("Click on the revisions and branches to display the file.") ?>
  <br />
  <?php echo _("Click on the links in between revisions to get a diff between those revisions.") ?>
 </span>
</div>

<p class="history">
 <img usemap="#graphMap" alt="<?php echo htmlspecialchars($title) ?>" src="<?php echo $imgUrl ?>" />
</p>
<?php echo $map ?>
