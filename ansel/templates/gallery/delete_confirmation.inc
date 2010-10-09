<h1 class="header">
 <?php $gallery = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getGallery($galleryId);
       printf($actionID == 'delete' ? _("Really delete %s and all of its subgalleries?") : _("Really empty %s?"), htmlspecialchars($gallery->get('name')))
 ?>
</h1>
<div class="headerbox" style="padding:10px">
 <p><?php echo $actionID == 'delete' ? _("Really delete this gallery and all of the photos and subgalleries in it? This action cannot be undone.") : _("Really empty this gallery and all of the photos in it? This action cannot be undone.") ?></p>
<form method="post" action="gallery/delete.php">
<?php echo Horde_Util::formInput() ?>
<input type="hidden" name="action" value="<?php echo $actionID ?>" />
<input type="hidden" name="gallery" value="<?php echo htmlspecialchars($galleryId) ?>" />
<input type="submit" class="button" value="<?php echo $actionID == 'delete' ? _("Really Delete") : _("Really Empty") ?>" />
<input type="button" class="button" value="<?php echo _("Cancel") ?>" onclick="history.back()" />
</form>
</div>
