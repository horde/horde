<form method="post" action="<?php echo $this->escape($this->url) ?>" id="passwd" name="passwd">
<?php echo $this->formInput ?>
<?php if (!$this->showlist): ?>
<input type="hidden" name="backend" value="<?php echo $this->backend ?>" />
<?php endif ?>
<?php if ($this->userChange): ?>
<input type="hidden" name="userid" value="<?php echo $this->escape($this->userid) ?>" />
<?php endif ?>
<input type="hidden" name="return_to" value="<?php echo $this->escape($this->url) ?>" />

<h1 class="header"><?php echo $this->header ?></h1>

<div class="horde-form">
<table>
<?php if ($this->userChange): ?>
<tr>
 <td class="horde-form-label">
  <?php echo $this->hordeLabel('userid', _("Username:")) ?>
 </td>
 <td>
  <input type="text" name="userid" value="<?php echo $this->escape($this->userid) ?>" />
  <?php echo $this->hordeHelp('passwd', 'username') ?>
 </td>
</tr>
<?php endif ?>

<tr>
 <td width="15%" class="horde-form-label">
  <?php echo $this->hordeLabel('oldpassword', _("Old password:")) ?>
 </td>
 <td>
  <input type="password" id="passwd-oldpassword" name="oldpassword" size="32" />
  <?php echo $this->hordeHelp('passwd', 'old-password') ?>
 </td>
</tr>

<tr>
 <td class="horde-form-label">
  <?php echo $this->hordeLabel('newpassword0', _("New password:")) ?>
 </td>
 <td>
  <input type="password" id="passwd-newpassword0" name="newpassword0" size="32" />
  <?php echo $this->hordeHelp('passwd', 'new-password') ?>
 </td>
</tr>

<tr>
 <td class="horde-form-label">
  <?php echo $this->hordeLabel('newpassword1', _("Confirm new password:")) ?>
 </td>
 <td>
  <input type="password" id="passwd-newpassword1" name="newpassword1" size="32" />
  <?php echo $this->hordeHelp('passwd', 'confirm-password') ?>
 </td>
</tr>

<?php if ($this->showlist): ?>
<tr>
 <td class="horde-form-label">
  <?php echo $this->hordeLabel('backend', _("Change password for:")) ?>
 </td>
 <td>
  <select name="backend">
<?php foreach ($this->backends as $key => $backend): ?>
   <?php echo $this->optionTag($key, $this->escape($backend['name']), $key == $this->backend) ?>
<?php endforeach ?>
  </select>
 </td>
 <td class="horde-form-label">
  <?php echo $this->hordeHelp('passwd', 'server') ?>
 </td>
</tr>
<?php endif; ?>
</table>
</div>

<p class="horde-form-buttons">
 <input class="horde-default" type="submit" name="submit" id="passwd-submit" value="<?php echo _("Change Password") ?>" />
 <input type="reset" name="reset" value="<?php echo _("Reset") ?>" />
</p>
</form>
