<?php $folderId = $folder->getId() ?>
<input type="hidden" name="folder[<?php echo (int)$folderId ?>]" />
<h1 class="header">
 <?php echo Horde::img('folder.png', '', '', $registry->getImageDir('horde') . '/tree') ?>
 <?php echo htmlspecialchars($folder->get('name')) ?>
</h1>
<p>
 <label>
  <strong><?php echo _("Name") ?></strong>
  <input type="text" name="name[<?php echo (int)$folderId ?>]" size="40" value="<?php echo htmlspecialchars($folder->get('name')) ?>" />
 </label>
</p>
