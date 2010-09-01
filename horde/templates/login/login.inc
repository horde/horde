<div id="menu">
<?php if ($hmenu): ?>
 <span class="leftFloat"><?php echo $hmenu ?></span>
<?php endif; ?>
 <h1 class="loginmenu"><?php printf(_("Welcome to %s"), $registry->get('name')) ?></h1>
</div>

<form name="horde_login" id="horde_login" method="post" action="<?php echo Horde::selfUrl(false, true, false, true) ?>"<?php echo $conf['menu']['always'] ? ' target="_parent"' : '' ?>>
 <input type="hidden" name="app" id="app" value="<?php echo htmlspecialchars($vars->app) ?>" />
 <input type="hidden" name="login_post" id="login_post" value="0" />
 <input type="hidden" name="url" value="<?php echo htmlspecialchars($vars->url) ?>" />
 <input type="hidden" name="anchor_string" id="anchor_string" value="<?php echo htmlspecialchars($vars->anchor_string) ?>" />
 <input type="hidden" name="ie_version" value="" />

<?php $notification->notify(array('listeners' => 'status')) ?>

 <table width="100%">
  <tr>
   <td align="center">
    <table width="300" align="center">
<?php foreach ($loginparams as $key => $val): ?>
<?php if ($val['type'] == 'hidden'): ?>
<input type="hidden" id="<?php echo $key ?>" name="<?php echo $key ?>" value="<?php echo isset($val['value']) ? htmlspecialchars($val['value']) : '' ?>" />
<?php else: ?>
     <tr>
      <td class="light rightAlign">
       <strong><?php echo Horde::label($key, $val['label']) ?></strong>
      </td>
      <td class="leftAlign">
<?php if ($val['type'] == 'text'): ?>
       <input type="text" id="<?php echo $key ?>" name="<?php echo $key ?>" value="<?php echo isset($val['value']) ? htmlspecialchars($val['value']) : '' ?>" style="direction:ltr" />
<?php elseif ($val['type'] == 'password'): ?>
       <input type="password" id="<?php echo $key ?>" name="<?php echo $key ?>" value="" style="direction:ltr" />
<?php elseif ($val['type'] == 'select'): ?>
       <select id="<?php echo $key ?>" name="<?php echo $key ?>">
<?php foreach ($val['value'] as $k2 => $v2): ?>
        <option value="<?php echo $k2 ?>"<?php echo !empty($v2['selected']) ?' selected="selected"' : '' ?><?php echo !empty($v2['hidden']) ? ' style="display:none"' : '' ?>><?php echo htmlspecialchars($v2['name']) ?></option>
<?php endforeach ?>
       </select>
<?php endif; ?>
      </td>
     </tr>
<?php endif; ?>
<?php endforeach; ?>

<?php if (!$is_auth && !$prefs->isLocked('language')): ?>
     <tr>
      <td class="light rightAlign">
       <strong><?php echo Horde::label('new_lang', _("Language")) ?></strong>
      </td>
      <td class="light leftAlign">
       <select id="new_lang" name="new_lang">
<?php foreach ($langs as $val): ?>
        <option value="<?php echo $val['val'] ?>"<?php echo ($val['sel'] ? ' selected="selected"' : '') ?>><?php echo $val['name'] ?></option>
<?php endforeach; ?>
       </select>
      </td>
     </tr>
<?php endif; ?>

     <tr>
      <td>&nbsp;</td>
      <td class="light leftAlign">
       <input id="login_button" name="login_button" class="button" value="<?php echo _("Log in") ?>" type="submit" />
      </td>
     </tr>

<?php if (!empty($conf['signup']['allow']) && $auth->hasCapability('add')): ?>
     <tr>
      <td>&nbsp;</td>
      <td class="light leftAlign">
       <?php echo Horde::link(Horde::url('signup.php', false, array('app' => 'horde'))->add('url', $vars->url), _("Don't have an account? Sign up."), 'light') . _("Don't have an account? Sign up.") ?></a>
      </td>
     </tr>
<?php endif; ?>

<?php if ($auth->hasCapability('resetpassword')): ?>
     <tr>
      <td>&nbsp;</td>
      <td class="light">
       <?php echo Horde::link(Horde::url('services/resetpassword.php', false, array('app' => 'horde'))->add('url', $vars->url), _("Forgot your password?"), 'light') . _("Forgot your password?") ?></a>
      </td>
     </tr>
<?php endif; ?>

    </table>
   </td>
  </tr>
 </table>
</form>

<?php
try {
    Horde::loadConfiguration('motd.php', null, null, true);
} catch (Horde_Exception $e) {}
