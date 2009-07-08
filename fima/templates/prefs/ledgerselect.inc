<?php
if (!$prefs->isLocked('activet_ledger')):
    $ledgers = Fima::listLedgers();
    if (($active_ledger = $prefs->getValue('active_ledger')) == null ||
        !isset($ledgers[$active_ledger])) {
        $active_ledger = Horde_Auth::getAuth();
    }
?>

<?php echo _("Your active ledger:") ?><br />
<select name="active_ledger">
<?php foreach ($ledgers as $id => $ledger): ?>
    <option value="<?php echo htmlspecialchars($id) ?>"<?php if ($id == $active_ledger) echo ' selected="selected"' ?>><?php echo htmlspecialchars($ledger->get('name')) ?></option>
<?php endforeach; ?>
</select><br /><br />
<?php endif; ?>
