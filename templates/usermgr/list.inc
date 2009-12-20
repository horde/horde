<div class="header">
    Context: <?php echo $context; ?>
</div>

<div id="userList" class="userList">
    <table width="100%" cellspacing="0">
        <tr>
            <td class="uheader">Extension</td>
            <td class="uheader">Name</td>
            <td class="uheader">E-Mail Address</td>
        </tr>
        <?php
            $line = 0;
            foreach ($users as $extension => $user) {
                $rowcolor = $line % 2;
                $line++;
                $url = Horde::applicationUrl("index.php");
                $url = Horde_Util::addParameter($url,
                    array(
                        'context' => $context,
                        'extension' => $extension,
                        'section' => $section,
                    )
                );
                $editurl = Horde_Util::addParameter($url, "action=edit");
                $deleteurl = Horde_Util::addParameter($url, "action=delete");
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
