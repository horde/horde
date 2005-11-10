<div class="header" style="text-align: right">
    <span class="contextHeader" style="padding-right: 20px">Context: <?php echo $context; ?></span>
</div>

<div id="userlist">
    <table width="100%" cellspacing="0">
        <tr class="header">
            <td>Extension</td>
            <td>Name</td>
            <td>E-Mail Address</td>
        </tr>
        <?php
            $line = 0;
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
            <td style="width: 20%">
                <?php echo Horde::link($editurl); echo $extension; ?></a>
            </td>
            <td style="width: 35%;">
                <?php echo Horde::link($editurl); echo $user['name']; ?></a>
            </td>
            <td style="width: 45%">
                <?php echo $user['email']; ?>
            </td>
        </tr>
        <?php
            }
        ?>
    </table>
</div>