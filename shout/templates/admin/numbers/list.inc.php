<div id="adminNumberList">
    <table width="100%" cellspacing="0" class="striped">
        <tr>
            <td class="uheader">Telephone Number</td>
            <td class="uheader">Account Name</td>
            <td class="uheader">Menu Name</td>
        </tr>
        <?php
            $url = Horde::url("admin/numbers.php");
            $editurl = $url->copy()->add('action', 'edit');
            $deleteurl = $url->copy()->add('action', 'delete');
            foreach ($numbers as $numberinfo) {
                $accountcode = $numberinfo['accountcode'];
                ?>
                <tr class="item">
                    <td>
                        <?php echo Horde::link($editurl->add(array('number' => $numberinfo['number'])));
                              echo $numberinfo['number']; echo '</a>'; ?>
                    </td>
                    <td>
                        <?php echo $accounts[$accountcode]['name']; ?>
                    </td>
                    <td>
                        <?php echo $numberinfo['menuName']; ?>
                    </td>
                </tr>
                <?php
            }
            ?>
    </table>
</div>
<ul id="controls">
    <?php
    $addurl = $url->add('action', 'add');
    ?>
    <li><a class="horde-create" href="<?php echo $addurl; ?>">
        <?php echo Horde::img('extension-add.png'); ?>&nbsp;New Number
        </a>
    </li>
</ul>
