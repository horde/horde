<a name="top"></a>
<?php
$accesskey = $prefs->getValue('widget_accesskey') ?
    Horde::getAccessKey(_("Select _Account")) : '';
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
   <label for="account" accesskey="<?php echo $accesskey ?>">
    <select id="account" name="account" onchange="accountSubmit()">
     <?php
        foreach ($accounts as $account => $name) {
            print "<option value=\"$account\"";
            if ($account == $curaccount) {
                print " selected";
            }
            print ">$name</option>\n";
        }
     ?>
    </select>
   </label>
   <?php
     if (isset($section)) {
         ?>
         <input type="hidden" name="section" value="<?php echo $section; ?>" />
         <?php
     }
    ?>
  </span>
 </form>

 <div style="float:right">
  <?php
   $link = Horde::link('#', _("Select Account"), '', '', 'accountSubmit(true);
    return false;');
   printf('<ul><li>%s%s<br />%s</a></li></ul>',
    $link, Horde::img('folders/folder_open.png'),
    ($menu_view != 'icon') ?
    Horde::highlightAccessKey(_("Select _Account"), $accesskey) : '');
  ?>
 </div>

<?php } // if (count(accounts) > 1) ?>

  <?php echo Shout::getMenu('string') ?>
</div>
