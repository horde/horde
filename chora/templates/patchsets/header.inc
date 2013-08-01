<?php if ($ps_id): ?>
<h1 class="header"><?php echo htmlspecialchars($title) ?></h1>
<br />
<?php else: ?>
<p class="horde-content">
 <?php echo _("Search Patchsets:") ?>
 <input type="text" for="patchsets_body" size="20" />
</p>
<?php endif; ?>
