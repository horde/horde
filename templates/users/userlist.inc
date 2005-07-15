<table width="95%" border="0" cellpadding="0" cellspacing="0" class="header">
  <tr valign="bottom">
    <td width="8%"><div align="center"><font size="1">
      &nbsp;
    </td>
    <td width="20%"><div align="center"></div></td>
    <td width="45%">&nbsp;</td>
    <td width="27%"><font size="3">Context: <?php echo $context; ?></font></td>
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
        <td width="7%"><?php
                $url = Horde::applicationUrl("users/edit.php");
                $url = Util::addParameter($url, "context=$context");
                $url = Util::addParameter($url, "extension=$extension");
                echo Horde::link($url);
            ?>
            <?php echo $extension; ?></a>
        </td>
        <td><?php
                $url = Horde::applicationUrl("users/edit.php");
                $url = Util::addParameter($url, "context=$context");
                $url = Util::addParameter($url, "extension=$extension");
                echo Horde::link($url);?>
            <?php echo $user['name'];?></a>
        </td>
        <td width="100">
            <div align="center">
                <?php
                $url = Horde::applicationUrl("users/edit.php");
                $url = Util::addParameter($url, "context=$context");
                $url = Util::addParameter($url, "extension=$extension");
                echo Horde::link($url); ?>
                <font size="1">edit</font></a>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <?php
$url = Horde::applicationUrl("users/delete.php");
                $url = Util::addParameter($url, "context=$context");
                $url = Util::addParameter($url, "extension=$extension");
                echo Horde::link($url); ?>
                <font size="1">delete</font></a>
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
