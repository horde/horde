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


var last_adduser_win;

function open_adduser_win(args)
{
    var url = "<?php echo Horde::url($GLOBALS['registry']->applicationWebPath('%application%/users/add.php' , 'shout')) ?>";
    if (url.indexOf('?') == -1) {
        var glue = '?';
    } else {
        var glue = '&';
    }
    var now = new Date();
    var name = "adduser_windows_" + now.getTime();
    if (args != "") {
        url = url + glue + args + "&uniq=" + now.getTime();
    } else {
        url = url + glue + "uniq=" + now.getTime();
    }
    var width = screen.width;
    if (width > 775) {
        width = 700;
    } else {
        width -= 75;
    }
    var height = screen.height;
    if (height > 725) {
        height = 650;
    } else {
        height -= 75;
    }
    param = "toolbar=no,location=no,status=yes,scrollbars=yes,resizable=yes,width=" + width + ",height=" + height + ",left=0,top=0";
    name = window.open(url, name, param);
    if (!name) {
        alert("<?php echo addslashes(_("The compose window can't be opened.  Perhaps you have set your browser to block popup windows?")) ?>");
    } else {
        if (!eval("name.opener")) {
            name.opener = self;
        }

        last_adduser_win = name;
    }
}

function focus_adduser_win(adduser_win)
{
    if (!adduser_win) {
        adduser_win = last_adduser_win;
    }

    if (!adduser_win) {
        return;
    }

    adduser_win.focus();
}

// -->
</script>
