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
            foreach ($extensions as $extension => $info) {

                $url = Horde::applicationUrl("extensions.php");
                $url = Horde_Util::addParameter($url,
                    array(
                        'extension' => $extension,
                    )
                );
                $editurl = Horde_Util::addParameter($url, 'action', 'edit');
                $deleteurl = Horde_Util::addParameter($url, 'action', 'delete');
        ?>
        <tr class="item">
            <td style="width: 20%">
                <?php echo Horde::link($editurl); echo $extension; ?></a>
            </td>
            <td style="width: 35%;">
                <?php echo Horde::link($editurl); echo $info['name']; ?></a>
                <?php
                foreach ($info['devices'] as $device) {
                    echo Horde::img('shout.png');
                }
                foreach ($info['numbers'] as $number) {
                    echo Horde::img('telephone-pole.png');
                }
                ?>
            </td>
            <td style="width: 45%">
                <?php echo $info['email']; ?>
            </td>
        </tr>
        <?php
            }
        ?>
    </table>
</div>
