<div class="header">
    Account: <?php echo $GLOBALS['session']->get('shout', 'curaccount_name'); ?>
</div>

<div id="extensionList">
    <table width="100%" cellspacing="0" class="striped">
        <tr>
            <td class="uheader">Name</td>
            <td class="uheader">Mailbox</td>
            <td class="uheader">Device ID</td>
        </tr>
        <?php
            $line = 0;
            foreach ($devices as $devid => $info) {

                $url = Horde::url("devices.php")->add('devid', $devid);
                $editurl = $url->copy()->add('action', 'edit');
                $deleteurl = $url->copy()->add('action', 'delete');
        ?>
        <tr class="item">
            <td>
                <?php echo Horde::link($editurl); echo $info['name']; ?></a>
            </td>
            <td>
                <?php echo $info['mailbox']; ?>
            </td>
            <td>
                <?php echo Horde::link($editurl); echo $devid; ?></a>
            </td>
        </tr>
        <?php
            }
        ?>
    </table>
</div>
<ul id="controls">
    <?php
    $addurl = Horde::url('devices.php')->add('action', 'add');
    ?>
    <li><a class="horde-create" href="<?php echo $addurl; ?>">
        <?php echo Horde::img('device-add.png'); ?>&nbsp;New Device
        </a>
    </li>
</ul>
