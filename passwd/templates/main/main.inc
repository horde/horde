<script type="text/javascript">
<!--

function submit_form()
{
    if (document.passwd.oldpassword.value == "") {
        alert('<?php echo addslashes(_("Please provide your current password")); ?>');
        document.passwd.oldpassword.focus();
        return false;
    } else if (document.passwd.newpassword0.value == "") {
        alert('<?php echo addslashes(_("Please provide a new password")); ?>');
        document.passwd.newpassword0.focus();
        return false;
    } else if (document.passwd.newpassword1.value == "") {
        alert('<?php echo addslashes(_("Please verify your new password")); ?>');
        document.passwd.newpassword1.focus();
        return false;
    } else if (document.passwd.newpassword0.value != document.passwd.newpassword1.value) {
        alert('<?php echo addslashes(_("Your passwords do not match")); ?>');
        document.passwd.newpassword0.value = '';
        document.passwd.newpassword1.value = '';
        document.passwd.newpassword0.focus();
        return false;
    } else {
        return true;
    }
}

function setFocus()
{
    if (document.passwd.oldpassword.value == "") {
        document.passwd.oldpassword.focus();
    } else if (document.passwd.newpassword0.value == "") {
        document.passwd.newpassword0.focus();
    } else {
        document.passwd.newpassword1.focus();
    }
}
//-->
</script>

<div id="menu">
<?php
Horde::addScriptFile('stripe.js', 'horde', true);
require_once 'Horde/Menu.php';
$menu = &new Menu(HORDE_MENU_MASK_ALL & ~HORDE_MENU_MASK_PREFS);
echo $menu->render();
$item = 1;
?>
</div>
<?php $notification->notify(array('listeners' => 'status')) ?>

<form method="post" action="<?php echo Horde::url('main.php', false, -1, true) ?>" name="passwd">
<?php echo Util::formInput() ?>
<?php if ($conf['backend']['backend_list'] == 'hidden'): ?>
<input type="hidden" name="backend" value="<?php echo htmlspecialchars($backend_key) ?>" />
<?php endif; if ($conf['user']['change'] !== true): ?>
<input type="hidden" name="userid" value="<?php echo htmlspecialchars($userid) ?>" />
<?php endif; ?>
<input type="hidden" name="return_to" value="<?php echo htmlspecialchars(Util::getFormData('return_to')) ?>" />

<h1 class="header">
<?php
if ($conf['backend']['backend_list'] == 'hidden') {
    printf(_("Changing password for %s"), htmlspecialchars($backends[$backend_key]['name']));
} else {
    echo _("Change your password");
}
?>
</h1>

<table class="striped" style="border-collapse: collapse; width: 100%;">
<?php if ($conf['user']['change'] === true): ?>
<tr>
 <td class="rightAlign">
  <strong><?php echo Horde::label('userid', _("Username:")) ?></strong>
 </td>
 <td>
  <input type="text" id="userid" name="userid" value="<?php echo htmlspecialchars($userid) ?>" />
 </td>
 <td class="rightAlign">
  <?php echo Help::link('passwd', 'passwd-username') ?>
 </td>
</tr>
<?php endif; ?>

<tr>
 <td width="15%" class="rightAlign">
  <strong><?php echo Horde::label('oldpassword', _("Old password:")) ?></strong>
 </td>
 <td>
  <input type="password" tabindex="1" id="oldpassword" name="oldpassword" size="32" />
 </td>
 <td class="rightAlign">
  <?php echo Help::link('passwd', 'passwd-old-password') ?>
 </td>
</tr>

<tr>
 <td class="rightAlign">
  <strong><?php echo Horde::label('newpassword0', _("New password:")) ?></strong>
 </td>
 <td>
  <input type="password" tabindex="2" id="newpassword0" name="newpassword0" size="32" />
 </td>
 <td class="rightAlign">
  <?php echo Help::link('passwd', 'passwd-new-password') ?>
 </td>
</tr>

<tr>
 <td class="rightAlign">
  <strong><?php echo Horde::label('newpassword1', _("Confirm new password:")) ?></strong>
 </td>
 <td>
  <input type="password" tabindex="3" id="newpassword1" name="newpassword1" size="32" />
 </td>
 <td class="rightAlign">
  <?php echo Help::link('passwd', 'passwd-confirm-password') ?>
 </td>
</tr>

<?php if ($conf['backend']['backend_list'] == 'shown'): ?>
<tr>
 <td class="rightAlign">
  <strong><?php echo Horde::label('backend', _("Change password for:")) ?></strong>
 </td>
 <td style="direction: ltr">
  <select id="backend" name="backend" tabindex="4">
   <?php echo $backends_list ?>
  </select>
 </td>
 <td class="rightAlign">
     <?php echo Help::link('passwd', 'passwd-server') ?>
 </td>
</tr>
<?php endif; ?>

<tr class="control">
 <td colspan="3" class="control">
  <input class="button" type="submit" name="submit" value="<?php echo _("Change Password") ?>" onclick="return submit_form();" />
  <input class="button" type="reset" name="reset" value="<?php echo _("Reset") ?>" />
 </td>
</tr>
</table>
</form>
