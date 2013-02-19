<form method="post" action="<?php echo $this->h($this->url) ?>" id="passwd" name="passwd">
<?php echo $this->formInput ?>
<?php if (!$this->showlist): ?>
<input type="hidden" name="backend" value="<?php echo $this->backend ?>" />
<?php endif ?>
<?php if ($this->userChange): ?>
<input type="hidden" name="userid" value="<?php echo $this->h($this->userid) ?>" />
<?php endif ?>
<input type="hidden" name="return_to" value="<?php echo $this->h($this->url) ?>" />

<h1 class="header"><?php echo $this->header ?></h1>

<div class="horde-form">
<table>
<?php if ($this->userChange): ?>
<tr>
 <td class="horde-form-label">
  <?php echo $this->label->userid ?>
 </td>
 <td>
  <input type="text" name="userid" value="<?php echo $this->h($this->userid) ?>" />
  <?php echo $this->help->username ?>
 </td>
</tr>
<?php endif ?>

<tr>
 <td width="15%" class="horde-form-label">
  <?php echo $this->label->oldpassword ?>
 </td>
 <td>
  <input type="password" id="passwd-oldpassword" name="oldpassword" size="32" />
  <?php echo $this->help->oldpassword ?>
 </td>
</tr>

<tr>
 <td class="horde-form-label">
  <?php echo $this->label->newpassword0 ?>
 </td>
 <td>
  <input type="password" id="passwd-newpassword0" name="newpassword0" size="32" />
  <?php echo $this->help->newpassword ?>
 </td>
</tr>

<tr>
 <td class="horde-form-label">
  <?php echo $this->label->newpassword1 ?>
 </td>
 <td>
  <input type="password" id="passwd-newpassword1" name="newpassword1" size="32" />
  <?php echo $this->help->confirmpassword ?>
 </td>
</tr>

<?php if ($this->showlist): ?>
<tr>
 <td class="horde-form-label">
  <?php echo $this->label->backend ?>
 </td>
 <td>
  <select name="backend">
   <?php foreach ($this->backends as $key => $backend): ?>
   <option value="<?php echo $key ?>"<?php echo $backend['selected'] ?>><?php echo $this->h($backend['name']) ?></option>
   <?php endforeach ?>
  </select>
 </td>
 <td class="horde-form-label">
     <?php echo $this->help->server ?>
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
