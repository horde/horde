<?php echo $this->renderPartial('header'); ?>

<div id="menu">
 <h1 style="text-align:center"><?php echo $this->welcome ?></h1>
</div>

<?php $this->koward->notification->notify(array('listeners' => 'status')) ?>

<form name="koward_login" method="post" action="<?php echo $this->post ?>">
<table width="100%"><tr><td align="center"><table width="300" align="center">

<tr>
    <td class="light rightAlign"><strong><?php echo Horde::label('horde_user', _("Username")) ?></strong>&nbsp;</td>
    <td class="leftAlign"><input type="text" id="horde_user" name="horde_user" value="<?php echo htmlspecialchars(Horde_Util::getFormData('horde_user')) ?>" style="direction:ltr" /></td>
</tr>

<tr>
    <td class="light rightAlign"><strong><?php echo Horde::label('horde_pass', _("Password")) ?></strong>&nbsp;</td>
    <td class="leftAlign"><input type="password" id="horde_pass" name="horde_pass" value="" style="direction:ltr" /></td>
</tr>

<tr>
    <td>&nbsp;</td>
    <td class="light leftAlign"><input name="loginButton" class="button" value="<?php echo _("Log in") ?>" type="submit" onclick="return submit_login();" /></td>
</tr>

</table></td></tr></table>
</form>
