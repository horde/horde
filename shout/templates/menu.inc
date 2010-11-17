<a name="top"></a>
<?php
$menu_view = $prefs->getValue('menu_view');
?>

<div id="menu">

<?php
// Only show the account selector if there is more than one available account
try {
    $accounts = $shout->storage->getAccounts();
} catch (Exception $e) {
    $accounts = array();
}
if (count($accounts) > 1) { ?>
<script language="JavaScript" type="text/javascript">
<!--
var loading;
function accountSubmit(clear)
{

    if (document.accountMenu.account[document.accountMenu.account.selectedIndex].name != '') {
        if ((loading == null) || (clear != null)) {
            loading = true;
            document.accountMenu.submit();
        }
    }
}
// -->
</script>
 <form action="" method="get" name="accountMenu">
  <span style="float:right">
   <?php Horde_Util::pformInput() ?>
    <select id="account" name="account" onchange="accountSubmit()">
     <?php
        $cur_code = $GLOBALS['session']->get('shout', 'curaccount_code');

        foreach ($accounts as $account => $info) {
            print "<option value=\"$account\"";
            if ($account == $cur_code) {
                print " selected";
            }
            print ">${info['name']}</option>\n";
        }
     ?>
    </select>
   <?php
     if (isset($section)) {
         ?>
         <input type="hidden" name="section" value="<?php echo $section; ?>" />
         <?php
     }
    ?>
  </span>
 </form>
<?php } // if (count(accounts) > 1) ?>

  <?php echo Shout::getMenu('string') ?>
</div>
<?php require HORDE_BASE . '/services/sidebar.php'; ?>
