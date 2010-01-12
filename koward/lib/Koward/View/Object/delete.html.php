<?= $this->renderPartial('header'); ?>
<?= $this->renderPartial('menu'); ?>

<form action="<?= $this->submit_url ?>" method="post" name="delete">
<?php echo Horde_Util::formInput() ?>

<div class="headerbox">

 <p><?php echo _("Permanently delete this object?") ?></p>

 <input type="submit" class="button" name="delete" value="<?php echo _("Delete") ?>" />
 <a class="button" href="<?= $this->return_url ?>"><?php echo _("Cancel") ?></a>
</div>

</form>
