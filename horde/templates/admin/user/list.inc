<h1 class="header">
 <?php echo _("Users in the system:") ?>
 <?php if ($numitem): ?>
  <small>[<?php printf(_("%d to %d of %d"), $start, $end, $numitem) ?>]</small>
 <?php endif; ?>
</h1>

<div class="headerbox">

<div class="control">
 <form method="post" name="search" action="<?php echo Horde::selfUrl() ?>">
 <?php echo Horde_Util::formInput() ?>
 <input type="hidden" name="page" value="0" />
 <strong><?php echo Horde::label('search_pattern', _("Search:")) ?></strong>
 <input id="search_pattern" name="search_pattern" type="text" value="" size="50" maxlength="100" />
 <input type="submit" class="button" value="<?php echo _("Search") ?>" />
 </form>
</div>

<table class="striped" width="100%" cellspacing="0">
<?php
$remove = $auth->hasCapability('remove');
$total = 0;
$admin_url = Horde::url('admin/user.php');

foreach ($users as $user):
    if ($total++ < $min || $total > $max) {
        continue;
    }
    $user_url = $admin_url->copy()->add('user_name', $user);
?>
 <tr>
<?php if ($remove): ?>
  <td width="1%"><?php echo Horde::link($user_url->add('form', 'remove_f'), _("Delete")) . Horde::img('delete.png', _("Delete")) ?></a></td>
<?php endif; ?>
  <td width="1%"><?php echo Horde::link($user_url->add('form', 'update_f'), _("Update")) .  Horde::img('edit.png', _("Update")) ?></a></td>
  <td width="1%"><?php echo Horde::link($user_url->add('form', 'clear_f'), _("Clear user data")) .  Horde::img('reload.png', _("Clear user data")) ?></a></td>
  <td class="leftAlign"><?php echo $user ?></td>
 </tr>
<?php endforeach; ?>
</table>
<?php
if ($numitem) {
    $pager = new Horde_Core_Ui_Pager('page', $vars, array('num' => $total, 'url' => $viewurl, 'page_count' => 10, 'perpage' => $perpage));
    echo $pager->render($page, $total, $viewurl);
}
?>
</div>
