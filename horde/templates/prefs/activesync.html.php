<?php if (!empty($this->identities)): ?>
<?php echo _("Identity to use when sending email via ActiveSync."); ?>
<div>
 <select id="activesync_identity" name="activesync_identity">
  <?php foreach($this->identities as $id => $desc): ?>
    <option value="<?php echo $id ?>" <?php echo $id == $this->default ? 'selected="selected"' : '' ?>><?php echo $desc ?></option>
  <?php endforeach ?>
 </select>
</div>
<?php endif ?>

<br class="spacer" />
<h2 class="smallheader"><?php echo _("State Management") ?></h2>
<p>
 <input class="horde-delete" type="submit" value="<?php echo _("Reset all device state") ?>" name="reset" />
 <?php echo _("This will cause your devices to resyncronize all items.") ?>
</p>

<br class="spacer" />
<h2 class="smallheader"><?php echo _("Device Management") ?></h2>
<?php if ($this->devices): ?>
<input type="hidden" id="removedevice" name="removedevice" />
<input type="hidden" name="wipeid" id="wipeid" />
<input type="hidden" name="cancelwipe" id="cancelwipe" />

<?php echo $this->render('device_table');?>

<?php else: ?>
<p>
 <em><?php echo _("None") ?></em>
</p>
<?php endif; ?>

<p>
 <strong>
  <?php echo _("NOTE: WIPING A DEVICE MAY RESET IT TO FACTORY DEFAULTS. PLEASE MAKE SURE YOU REALLY WANT TO DO THIS BEFORE REQUESTING A WIPE") ?>
 </strong>
</p>
