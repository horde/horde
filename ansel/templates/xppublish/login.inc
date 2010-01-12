<center>
<span class="header"><?php printf(_("Login to %s"), $registry->get('name')) ?></span>
<br />
<form method="post" action="xppublish.php" id="login" name="login">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="lcid" value="<?php echo Horde_Util::getFormData('lcid') ?>" />
<input type="hidden" name="langid" value="<?php echo Horde_Util::getFormData('langid') ?>" />
<input type="hidden" name="cmd" value="login" />
<table>
 <tr>
  <td class="light"><?php echo _("Username") ?>:</td><td><input type="text" name="username" value="" /></td>
 </tr>
 <tr>
  <td class="light"><?php echo _("Password") ?>:</td><td><input type="password" name="password" value="" /></td>
 </tr>
</table>
</form>
</center>
