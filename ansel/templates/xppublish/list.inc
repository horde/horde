<div style="margin:5px">
<form id="folder" name="folder" method="post" action="<?php echo Horde::url('xppublish.php', true) ?>">
<input type="hidden" name="cmd" value="select" />
<?php Horde_Util::pformInput() ?>

<h1 class="header">
 <?php echo _("Publish Photos to:") ?>
</h1>

<div class="headerbox" style="padding:5px">
 <p>
  <label style="font-weight:bold" for="gallery"><?php echo _("Existing galleries:") ?></label><br />
  <select id="gallery" name="gallery">
   <?php echo Ansel::selectGalleries(array('perms' => Horde_Perms::EDIT))?>
  </select>
 </p>
 <p>
  <label style="font-weight:bold" for="create"><?php echo _("Create a new gallery:") ?></label><br />
  <input type="button" class="button" value="<?php echo _("Create") ?>" onclick="folder.cmd.value='new'; folder.submit();" />
 </p>
</div>

</form>
</div>
