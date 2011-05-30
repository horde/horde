<form method="post" action="groups.php">
<input type="hidden" name="actionID" value="addform" />
<?php Horde_Util::pformInput() ?>

<h1 class="header">
 <?php echo Horde::img('group.png') ?>&nbsp;<?php echo _("Add a group") ?>
</h1>

<label for="name" class="hidden"><?php echo _("Group name") ?></label>
<input type="text" id="name" name="name" />
<input type="submit" class="button" value="<?php echo _("Add") ?>" />
</form>
<br />
