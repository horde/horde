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
            $url = Horde::applicationUrl("users.php");
            $url = Util::addParameter($url, array('context' => $context,
                                                  'extension' => $extension));
            $editurl = Util::addParameter($url, "action=edit");
            $deleteurl = Util::addParameter($url, "action=delete");
    ?>
    <tr class="item<?php echo $rowcolor; ?>">
        <td width="7%"><?php echo Horde::link($editurl); echo $extension; ?></a>
        </td>
        <td><?php echo Horde::link($editurl); echo $user['name']; ?></a>
        </td>
        <td width="100">
            <div align="center">
                <?php echo Horde::link($editurl, '', 'darkhint'); ?>edit</a>
                &nbsp;|&nbsp;
                <?php echo Horde::link($deleteurl, '', 'darkhint'); ?>delete</a>
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
