<form method="post" action="<?php echo $this->h($this->url) ?>" name="passwd" id="passwd">
<?php echo $this->formInput ?>
<?php if (!$this->showlist): ?>
<input type="hidden" name="backend" value="<?php echo $this->backend ?>" />
<?php endif ?>
<?php if ($this->userChange): ?>
<input type="hidden" name="userid" value="<?php echo $this->h($this->userid) ?>" />
<?php endif ?>
<input type="hidden" name="return_to" value="<?php echo $this->h($this->url) ?>" />

<h1 class="header"><?php echo $this->header ?></h1>

<table class="striped" style="border-collapse: collapse; width: 100%;">
<?php if ($this->userChange): ?>
<tr>
 <td class="rightAlign">
  <strong><?php echo $this->label->userid ?></strong>
 </td>
 <td>
  <input type="text" id="userid" name="userid" value="<?php echo $this->h($this->userid) ?>" />
 </td>
 <td class="rightAlign">
  <?php echo $this->help->username ?>
 </td>
</tr>
<?php endif ?>

<tr>
 <td width="15%" class="rightAlign">
  <strong><?php echo $this->label->oldpassword ?></strong>
 </td>
 <td>
  <input type="password" id="oldpassword" name="oldpassword" size="32" />
 </td>
 <td class="rightAlign">
  <?php echo $this->help->oldpassword ?>
 </td>
</tr>

<tr>
 <td class="rightAlign">
  <strong><?php echo $this->label->newpassword0 ?></strong>
 </td>
 <td>
  <input type="password" id="newpassword0" name="newpassword0" size="32" />
 </td>
 <td class="rightAlign">
  <?php echo $this->help->newpassword ?>
 </td>
</tr>

<tr>
 <td class="rightAlign">
  <strong><?php echo $this->label->newpassword1 ?></strong>
 </td>
 <td>
  <input type="password" id="newpassword1" name="newpassword1" size="32" />
 </td>
 <td class="rightAlign">
  <?php echo $this->help->confirmpassword ?>
 </td>
</tr>

<?php if ($this->showlist): ?>
<tr>
 <td class="rightAlign">
  <strong><?php echo $this->label->backend ?></strong>
 </td>
 <td style="direction: ltr">
  <select id="backend" name="backend">
   <?php foreach ($this->backends as $key => $backend): ?>
   <option value="<?php echo $key ?>"<?php echo $backend['selected'] ?>><?php echo $this->h($backend['name']) ?></option>
   <?php endforeach ?>
  </select>
 </td>
 <td class="rightAlign">
     <?php echo $this->help->server ?>
 </td>
</tr>
<?php endif; ?>

<tr class="control">
 <td colspan="3" class="control">
  <input class="button" type="submit" name="submit" id="submit" value="<?php echo _("Change Password") ?>" />
  <input class="button" type="reset" name="reset" value="<?php echo _("Reset") ?>" />
 </td>
</tr>
</table>
</form>
