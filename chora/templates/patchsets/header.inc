<?php if ($ps_id): ?>
<h1 class="header"><?php echo htmlspecialchars($title) ?></h1>
<br />
<?php else: ?>
<div class="options">
 <span>
  <?php echo _("Search Patchsets:") ?>
  <input type="text" for="patchsets_body" size="20" />
 </span>
</div>
<?php endif; ?>
