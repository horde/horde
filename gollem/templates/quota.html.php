<h1 class="header">
 <strong><?php echo _("Quota Display") ?></strong>
</h1>

<?php if ($this->quotaerror): ?>
<div class="text">
 <em><?php echo _("ERROR:") ?> <?php echo $this->quotaerrormsg ?></em>
</div>
<?php endif ?>
<?php if ($this->noquota): ?>
<div class="text">
 <em><?php echo _("No quota found.") ?></em>
</div>
<?php endif ?>
<?php if ($this->quotadisplay): ?>
 <?php echo $this->quotastyle ?>
  <center><?php echo $this->quotadisplay ?></center>
 </div>
<?php endif ?>

<?php if ($this->closebutton): ?>
<center>
 <input type="button" class="button" id="closebutton" value="<?php echo $this->closebutton ?>" />
</center>
<?php endif ?>
