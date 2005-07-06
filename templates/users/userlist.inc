<table width="90%" border="0" cellspacing="0" cellpadding="0" class="item">
  <tbody>
    <tr style="">
        <td class="class">
            <input name="AddUsr" type="submit" id="AddUsr3" value="Add User" />
        </td>
        <td class="class">Click on Name to edit User information.</td>
        <td width="20" class="class"><div align="left"></div></td>
        <td class="header">V-Office</td>
    </tr>
    <tr style="">
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td colspan="2">&nbsp;</td>
    </tr>
    <tr style="">
        <td class="class"><strong>Ext.</strong></td>
        <td class="class"><strong>Name</strong></td>
        <td colspan="2" class="class"><strong>Email</strong></td>
    </tr>
    <?php
        $line = 0;
        $users = $shout->getUsers($context);
        ksort($users);
        foreach ($users as $extension => $user) {
            $rowcolor = $line % 2;
            $line++;
    ?>
    <tr style="item<?php echo $rowcolor; ?>">
        <td><?php echo $extension; ?></td>
        <td><?php echo $user['name'];
?></td>
        <td><?php echo $user['email']; ?></td>
    </tr>
    <?php
        }
    ?>
  </tbody>
</table>