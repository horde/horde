<?php
$accesskey = $prefs->getValue('widget_accesskey') ?
    Horde::getAccessKey(_("Select _Context")) : '';
$menu_view = $prefs->getValue('menu_view');
?>

<div id="menu">

 <span style="float:right">
  <form action="index.php" method="get" name="menu">
   <?php Util::pformInput() ?>
   <label for="context" accesskey="<?php echo $accesskey ?>">
    <select id="context" name="context" onchange="contextSubmit()">
     <?php
        foreach ($shout->getContexts() as $item) {
            print "<option name=\"$item\"";
            if ($item == $context) {
                print " selected";
            }
            print ">$item</option>\n";
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
  </form>
 </span>
 <span style="float:right">
  <?php
   $link = Horde::link('#', _("Select Context"), '', '', 'contextSubmit(true);
    return false;');
   printf('<ul><li>%s%s<br />%s</a></li></ul>',
    $link, Horde::img('folders/folder_open.png'),
    ($menu_view != 'icon') ?
    Horde::highlightAccessKey(_("Select _Context"), $accesskey) : '');
  ?>
 </span>

  <?php echo Shout::getMenu('string') ?>
</div>

<script language="JavaScript" type="text/javascript">
<!--
var loading;
function contextSubmit(clear)
{
    if (document.menu.context[document.menu.context.selectedIndex].value !=
'') {
        if ((loading == null) || (clear != null)) {
            loading = true;
            document.menu.submit();
        }
    }
}
// -->
</script>
