<script language="JavaScript" type="text/javascript">
<!--
function open_user_win(args, useraction)
{
    var url = "<?php echo Horde::applicationUrl("/users/") ?>"
        + useraction + ".php";
    if (url.indexOf('?') == -1) {
        var glue = '?';
    } else {
        var glue = '&';
    }
    var name = useraction + "_user_window";
    if (args != "") {
        url = url + glue + args;
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
    param =
"toolbar=no,location=no,status=yes,scrollbars=yes,resizable=yes,width=" + width + ",height=" + height + ",left=0,top=0";
    name = window.open(url, name, param);
    if (!name) {
        alert("<?php echo addslashes(_("The compose window can't be opened.  Perhaps you have set your browser to block popup windows?")) ?>");
    } else {
        if (!eval("name.opener")) {
            name.opener = self;
        }
    }
}

// -->
</script>

<link href="/screen_002.css" rel="stylesheet" type="text/css">
<table width="95%" border="0" cellpadding="0" cellspacing="0" class="header">
  <tr valign="bottom">
    <td width="61"><div align="center"><font size="1">
      &nbsp;
    </td>
    <td width="148"><div align="center"></div></td>
    <td width="332">&nbsp;</td>
    <td width="202"><font size="3">Context: <?php echo $context; ?></font></td>
  </tr>
</table>

<table width="95%" border="0" cellpadding="0" cellspacing="0">
  <tbody>
    <tr class="class">
        <td width="7%"><strong>Ext.</strong></td>
        <td colspan="2"><strong>Name</strong></td>
        <td><strong>Email</strong></td>
    </tr>
    <?php
        $line = 0;
        $users = $shout->getUsers($context);
        ksort($users);
        foreach ($users as $extension => $user) {
            $rowcolor = $line % 2;
            $line++;
    ?>
    <tr class="item<?php echo $rowcolor; ?>">
        <td width="7%"><?php echo Horde::link("#", '', '', '',
"open_user_win('context=$context&amp;extension=$extension', 'edit')");?>
            <?php echo $extension; ?></a>
        </td>
        <td><?php echo Horde::link("#", '', '', '',
"open_user_win('context=$context&amp;extension=$extension', 'edit')");?>
            <?php echo $user['name'];?></a>
        </td>
        <td width="100">
            <div align="center">
                <?php
                echo Horde::link("#", '', '', '',
"open_user_win('context=$context&amp;extension=$extension', 'edit')");
                ?><font size="1">edit</font></a>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <?php
                echo Horde::link("#", '', '', '',

"open_user_win('context=$context&amp;extension=$extension', 'delete')");
                ?><font size="1">delete</font></a>
            </div>
            <div align="center"></div>
        </td>
    <td><?php echo $user['email']; ?></td>
    </tr>
    <?php
        }
    ?>
  </tbody>
</table>
