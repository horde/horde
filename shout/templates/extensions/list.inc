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
                $attrs = array('onclick' => 'javascript:destinfo("' . $extension . '");',
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
                <div class="extensionDestinations" id="destX<?php echo $extension; ?>info">
                    <?php
                    foreach ($info['devices'] as $device) {
                        echo Horde::img('shout.png') .
                             "&nbsp;" . $device . "<br>\n";
                    }
                    foreach ($info['numbers'] as $number) {
                        echo Horde::img('telephone-pole.png') .
                             "&nbsp;" . $number . "<br>\n";
                    }
                    ?>
                    <form method="post"
                      action="<?php echo Horde::applicationUrl('extensions.php'); ?>"
                      id="destX<?php echo $extension; ?>form">
                    </form>
                    <a class="addDest"
                       id="destX<?php echo $extension; ?>addDest"
                       href="#" onclick="javascript:addDest('<?php echo $extension; ?>')">
                       Add more destinations...
                    </a>
                </div>
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

var device_image = "<?php echo $registry->getImageDir() . '/shout.png'; ?>";
var number_image = "<?php echo $registry->getImageDir() . '/telephone-pole.png'; ?>";

var destinations = new Array();

function resetDestInfo(exten)
{
    while ((e = $('destX'+exten+'info').childNodes[0]) != null) {
        $('destX'+exten+'info').removeChild(e);
    }

    destinations[exten]['devices'].each(function(s) {
        e = document.createElement('img');
        e.src = device_image;
        t = document.createTextNode(" "+s);
        b = document.createElement('br');
        $('destX'+exten+'info').appendChild(e);
        $('destX'+exten+'info').appendChild(t);
        $('destX'+exten+'info').appendChild(b);
    });


    destinations[exten]['numbers'].each(function(s) {
        e = document.createElement('img');
        e.src = number_image;
        t = document.createTextNode(" "+s);
        b = document.createElement('br');
        $('destX'+exten+'info').appendChild(e);
        $('destX'+exten+'info').appendChild(t);
        $('destX'+exten+'info').appendChild(b);
    });

    form = document.createElement('form');
    form.method = 'post';
    form.action = '#';
    form.id = 'destX'+exten+'form';
    $('destX'+exten+'info').appendChild(form);

    a = document.createElement('a');
    a.id = 'destX'+exten+'addDest';
    a['className'] = 'addDest';
    a.href='#';
    a.setAttribute('onclick', 'addDest('+exten+')');
    t = document.createTextNode('Add more destinations...');
    a.appendChild(t);
    $('destX'+exten+'info').appendChild(a);
}

function destinfo(exten)
{
    // Use the summary icons span as our state key
    if ($('destX' + exten + 'summary').style.display == 'none') {
        // Icons hidden, we are expanded
        contract(exten);
    } else {
        expand(exten);
    }
}

function expand(exten)
{
    $('destX'+exten+'summary').hide();
    $('destX'+exten+'info').show();
    $('destX' + exten + 'toggle').src = '<?php echo $registry->getImageDir('horde') . '/tree/minusonly.png'; ?>';
}

function contract(exten)
{
    $('destX'+exten+'summary').show();
    $('destX'+exten+'info').hide();
    $('destX' + exten + 'toggle').src = '<?php echo $registry->getImageDir('horde') . '/tree/plusonly.png'; ?>';
}

function processForm(event)
{
    Event.stop(event);
    alert(event.target.childNodes);
}

function addDest(exten)
{
    //$('destX'+exten+'addDest').hide();
    e = document.createElement('input');
    e.type = "text";
    e.length = 10;
    $('destX'+exten+'form').appendChild(e);
    $('destX'+exten+'form').focusFirstElement();
    Event.observe($('destX'+exten+'form'), 'submit', function(event) {processForm(event);});
}

<?php
foreach ($extensions as $extension => $info)
{
    echo "destinations[${extension}] = {";
    if (count($info['devices'])) {
        echo 'devices: ["' . implode('","', $info['devices']) . '"],';
    }
    if (count($info['numbers'])) {
        echo 'numbers: ["' . implode('","', $info['numbers']) . '"],';
    }
    echo "};\n";
}
?>

// Initialize the data.
for (var i in destinations) {
    resetDestInfo(i);
    contract(i);
}

// -->
</script>
