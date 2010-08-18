<?php

$perm_edit = $share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT);
$perm_delete = $share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE);

?>
<script type="text/javascript">
<!--
function Submit(action)
{
    if (action == "delete_all") {
        if (!confirm("<?php echo _("Are you sure you wish to PERMANENTLY delete all accounts and all postings?") ?>")) {
            return;
        }
    }

    document.accounts.actionID.value = action;
    document.accounts.submit();
}
//-->
</script>

<h1 class="header"><?php echo htmlspecialchars($share->get('name')) ?></h1>

<form method="post" name="accounts" action="accounts.php">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="actionID" value="" />

<div id="accountList">
<?php

$tree = $GLOBALS['injector']->getInstance('Horde_Tree')->getTree('account_tree', 'Javascript');

foreach ($accounts as $accountId => $account) {
    $params = array('icon' => Horde_Themes::img($account['icon']));
    $accountLabel = htmlspecialchars(trim($account['number'] . ' ' . $account['name']));
    if ($account['closed']) {
        $accountLabel = '<span class="closed">' . $accountLabel . '</span>';
    }
    $accountExtra = '';
    if (!$print_view) {
        if ($perm_delete && $accountId == 'root')           { $accountExtra .= '&nbsp;'.Horde::link('#', _("Delete all"), '', '', "Submit('delete_all'); return false;")                    . Horde::img('delete-small.png', _("Delete")) . '</a>'; }
        if ($perm_edit   && isset($account['edit_link']))   { $accountExtra .= '&nbsp;'.Horde::link(Horde::applicationUrl($account['edit_link']),   sprintf(_("Edit %s"), $accountLabel))   . Horde::img('edit-small.png', _("Edit"))     . '</a>'; }
        if ($perm_delete && isset($account['delete_link'])) { $accountExtra .= '&nbsp;'.Horde::link(Horde::applicationUrl($account['delete_link']), sprintf(_("Delete %s"), $accountLabel)) . Horde::img('delete-small.png', _("Delete")) . '</a>'; }
        if ($perm_edit   && isset($account['add_link']))    { $accountExtra .= '&nbsp;'.Horde::link(Horde::applicationUrl($account['add_link']), _("Create a New Account"))                 . Horde::img('new-small.png', '+')                                                            . '</a>'; }
        if (                isset($account['view_link']))   { $accountExtra .= '&nbsp;'.Horde::link(Horde::applicationUrl($account['view_link']), sprintf(_("View %s"), $accountLabel))     . Horde::img('search-small.png', _("Search"))                                                 . '</a>'; }
    }
    $tree->addNode($accountId, $account['parent_id'], $accountLabel, 0, $print_view ? true : $account['expanded'], $params, $accountExtra);
}

echo $tree->renderTree();

?>
</div>
</form>
