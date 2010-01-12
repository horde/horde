<a name="top"></a>
<?php
$accesskey = $prefs->getValue('widget_accesskey') ?
    Horde::getAccessKey(_("Select _Context")) : '';
$menu_view = $prefs->getValue('menu_view');
?>

<div id="menu">

<?php
// Only show the context selector if there is more than one available context
if (count($contexts) > 1) { ?>
<script language="JavaScript" type="text/javascript">
<!--
var loading;
function contextSubmit(clear)
{

    if (document.contextMenu.context[document.contextMenu.context.selectedIndex].name != '') {
        if ((loading == null) || (clear != null)) {
            loading = true;
            document.contextMenu.submit();
        }
    }
}
// -->
</script>
 <form action="index.php" method="get" name="contextMenu">
  <span style="float:right">
   <?php Horde_Util::pformInput() ?>
   <label for="context" accesskey="<?php echo $accesskey ?>">
    <select id="context" name="context" onchange="contextSubmit()">
     <?php
        foreach ($contexts as $c) {
            print "<option value=\"$c\"";
            if ($c == $context) {
                print " selected";
            }
            print ">$c</option>\n";
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
   $link = Horde::link('#', _("Select Context"), '', '', 'contextSubmit(true);
    return false;');
   printf('<ul><li>%s%s<br />%s</a></li></ul>',
    $link, Horde::img('folders/folder_open.png'),
    ($menu_view != 'icon') ?
    Horde::highlightAccessKey(_("Select _Context"), $accesskey) : '');
  ?>
 </div>

<?php } // if (count(contexts) > 1) ?>

  <?php echo Shout::getMenu('string') ?>
</div>
