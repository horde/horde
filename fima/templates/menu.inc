<form action="<?php echo basename($_SERVER['PHP_SELF']) ?>" method="get" name="menuform">
<input type="hidden" name="actionID" value="change_ledger" />
<div id="menu">
 <div class="rightFloat">
  <label for="changeledger">
  <select id="changeledger" name="changeledger" onchange="ledgerSubmit()">
<?php
$ledgers = Fima::listLedgers();
$activeLedger = Fima::getActiveLedger();
foreach ($ledgers as $ledgerId => $ledger) {
    $selected = ($ledgerId == $activeLedger) ? ' selected="selected"' : '';
    echo '   <option value="' . $ledgerId . '"' . $selected .'>' . $ledger->get('name') . '</option>' . "\n";
}
?>
  </select>
  </label>
 </div>
 <div class="rightFloat">
  <ul><li class="rightFloat"><a href="#" onclick="ledgerSubmit(true); return false;"><?php echo Horde::img('list.png', _("Open Ledger"), '') . '<br />' . _("Open Ledger") ?></a></li></ul>
 </div>

 <?php echo Fima::getMenu()->render() ?>
 <br class="clear" />

</div>
</form>

<script type="text/javascript">
var loading;
function ledgerSubmit(clear)
{
    if (document.menuform.changeledger[document.menuform.changeledger.selectedIndex].value != '') {
        if ((loading == null) || (clear != null)) {
            loading = true;
            document.menuform.submit();
        }
    }
}
</script>
<?php require HORDE_BASE . '/services/sidebar.php'; ?>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
