<div class="header">
    <ul id="controls">
        <?php
        $addurl = Horde::applicationUrl('extensions.php');
        $addurl = Horde_Util::addParameter($addurl, 'action', 'add');
        ?>
        <li><a href="<?php echo $addurl; ?>">
            <?php echo Horde::img('add-extension.png'); ?>&nbsp;New Extension
            </a>
        </li>
    </ul>
    Context: <?php echo $context; ?>
</div>

<div id="extensionList">
    <table width="100%" cellspacing="0" class="striped">
        <tr>
            <td class="uheader">Extension</td>
            <td class="uheader">Name</td>
            <td class="uheader">E-Mail Address</td>
        </tr>
        <?php
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
        <tr class="item" style="vertical-align: top">
            <td style="width: 20%">
                <?php echo Horde::link($editurl); echo $extension; ?></a>
            </td>
            <td style="width: 35%;">
                <?php
                $attrs = array('onclick' => 'javascript:destinations("' . $extension . '");',
                               'id' => 'destX' . $extension . 'toggle');
                echo Horde::img('tree/plusonly.png', _("Destinations"), $attrs,
                                $registry->getImageDir('horde'));
                echo Horde::link($editurl);
                    echo $info['name']; ?>
                </a>
                <span id="destX<?php echo $extension; ?>summary">
                <?php
                foreach ($info['devices'] as $device) {
                    echo Horde::img('shout.png');
                }
                foreach ($info['numbers'] as $number) {
                    echo Horde::img('telephone-pole.png');
                }
                ?>
                </span>

                <form method="post"
                      action="<?php echo Horde::applicationUrl('extensions.php'); ?>">
                <div class="extensionDestinations" id="destX<?php echo $extension; ?>">
                    <input type="hidden" name="extension" value="<?php echo $extension; ?>">
                    <?php
                    foreach ($info['devices'] as $device) {
                        echo Horde::img('shout.png') . "&nbsp;" . $device . "<br>\n";
                    }
                    foreach ($info['numbers'] as $number) {
                        echo Horde::img('telephone-pole.png') . "&nbsp;" . $number . "<br>\n";
                    }
                    ?>
                </div>
                </form>
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

<script type="text/javascript">
<!--

<?php
foreach ($extensions as $extension => $info)
{
    echo "contract('${extension}');\n";
}
?>

function destinations(exten)
{    
    // Use the summary icons span as our state key
    if ($("destX" + exten + "summary").style.display == 'none') {
        // Icons hidden, we are expanded
        contract(exten);
    } else {
        expand(exten);
    }
}

function expand(exten)
{
    $('destX'+exten+'summary').hide();
    $('destX'+exten).show();
    $("destX" + exten + "toggle").src = '<?php echo $registry->getImageDir('horde') . '/tree/minusonly.png'; ?>';
}

function contract(exten)
{
    $('destX'+exten+'summary').show();
    $('destX'+exten).hide();
    $("destX" + exten + "toggle").src = '<?php echo $registry->getImageDir('horde') . '/tree/plusonly.png'; ?>';
}
// -->
</script>
