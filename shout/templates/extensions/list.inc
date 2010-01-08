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

var destinations = new Array();

function resetDestInfo(exten)
{
    while ((e = $('destX'+exten+'info').childNodes[0]) != null) {
        $('destX'+exten+'info').removeChild(e);
    }

    dest = destinations.get(exten);

    if (dest['devices'] == null) {
        dest['devices'] = new Array();
    }

    if (dest['numbers'] == null) {
        dest['numbers'] = new Array();
    }

    dest['devices'].each(function(s) {
        e = document.createElement('img');
        e.src = "<?php echo $registry->getImageDir() . '/shout.png'; ?>";
        t = document.createTextNode(" "+s);
        b = document.createElement('br');
        $('destX'+exten+'info').appendChild(e);
        $('destX'+exten+'info').appendChild(t);
        $('destX'+exten+'info').appendChild(b);
    });


    dest['numbers'].each(function(s) {
        e = document.createElement('img');
        e.src = "<?php echo $registry->getImageDir() . '/telephone-pole.png'; ?>";
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
    hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'extension';
    hidden.value = exten;
    form.appendChild(hidden);
    $('destX'+exten+'info').appendChild(form);

    a = document.createElement('a');
    a.id = 'destX'+exten+'addDest';
    a['className'] = 'addDest';
    a.href='javascript:addDest('+exten+')';
    //a.setAttribute('onclick', 'addDest('+exten+')');
    t = document.createTextNode('Add destination...');
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
    spinner = document.createElement('img');
    spinner.src = "<?php echo $registry->getImageDir('horde') . '/loading.gif'; ?>"
    form = event.target;
    Element.extend(form);

    exten = form.getInputs('hidden', 'extension').first().value;

    resetDestInfo(exten);

    $('destX'+exten+'addDest').show();

}

function addDest(exten)
{
    $('destX'+exten+'addDest').hide();
    select = document.createElement('select');
    select.name = 'type';

    option = document.createElement('option');
    option.name = 'number';
    text = document.createTextNode("<?php echo _("Number"); ?>");
    option.appendChild(text);
    select.appendChild(option);

    option = document.createElement('option');
    option.name = 'device';
    text = document.createTextNode("<?php echo _("Device"); ?>");
    option.appendChild(text);
    select.appendChild(option);

    input = document.createElement('input');
    input.name = 'destination';
    input.id = 'destination';
    input.type = "text";
    input.size = 12;
    input.maxlength = 15;

    save = document.createElement("input");
    save.name = "action";
    save.value = "Save";
    save.type = "submit";

    br = document.createElement('br');

    $('destX'+exten+'form').appendChild(select);
    $('destX'+exten+'form').appendChild(input);
    $('destX'+exten+'form').appendChild(save);
    $('destX'+exten+'form').appendChild(br);
    input.focus();
    Event.observe($('destX'+exten+'form'), 'submit', function(event) {processForm(event);});
}

destinations = $H({
<?php
foreach ($extensions as $extension => $info)
{
    echo "'${extension}': {\n";
    if (count($info['devices'])) {
        echo '  \'devices\': ["' . implode('","', $info['devices']) . '"],';
    }
    if (count($info['numbers'])) {
        echo '  \'numbers\': ["' . implode('","', $info['numbers']) . '"],';
    }
    echo "},\n";
}
?>
});

// Initialize the data.
destinations.each(function(item) {
    exten = item.key;
    resetDestInfo(exten);
    contract(exten);
})

// -->
</script>
