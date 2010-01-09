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
                // FIXME: Convert to <a href=javascript>
                $attrs = array('onclick' => 'javascript:destinfo("' . $extension . '");',
                               'id' => 'destX' . $extension . 'toggle');
                echo Horde::img('tree/plusonly.png', _("Destinations"), $attrs,
                                $registry->getImageDir('horde'));
                echo Horde::link($editurl);
                    echo $info['name']; ?>
                </a>
                <span id="destX<?php echo $extension; ?>summary"></span>
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

function resetDestInfo(collapse)
{
    destinations.each(function(item) {
        exten = item.key;
        _resetExtenDest(exten);
        if (collapse) {
            contract(exten);
        }
    });
}

function _resetExtenDest(exten)
{

    while ((e = $('destX'+exten+'info').childNodes[0]) != null) {
        $('destX'+exten+'info').removeChild(e);
    }

    while ((e = $('destX'+exten+'summary').childNodes[0]) != null) {
        $('destX'+exten+'summary').removeChild(e);
    }

    dest = destinations.get(exten);

    if (dest['devices'] == null) {
        dest['devices'] = new Array();
    }

    if (dest['numbers'] == null) {
        dest['numbers'] = new Array();
    }

    dest['devices'].each(function(s) {
        // Fill in detail block
        img = document.createElement('img');
        img.src = "<?php echo $registry->getImageDir() . '/shout.png'; ?>";
        text = document.createTextNode(" "+s+" ");
        del = document.createElement('img');
        del.id = "dest"+s+"X"+exten+"del";
        del.src = "<?php echo $registry->getImageDir('horde') . '/delete-small.png'; ?>"
        del.style.cursor = 'pointer';
        del.setAttribute('onclick', 'delDest("'+exten+'", "device", "'+s+'")');
        br = document.createElement('br');
        $('destX'+exten+'info').appendChild(img);
        $('destX'+exten+'info').appendChild(text);
        $('destX'+exten+'info').appendChild(del);
        $('destX'+exten+'info').appendChild(br);

        // Create summary icons
        img = document.createElement('img');
        img.src = "<?php echo $registry->getImageDir() . '/shout.png'; ?>";
        $('destX'+exten+'summary').appendChild(img);

    });


    dest['numbers'].each(function(s) {
        // Fill in detail block
        img = document.createElement('img');
        img.src = "<?php echo $registry->getImageDir() . '/telephone-pole.png'; ?>";
        text = document.createTextNode(" "+s+" ");
        del = document.createElement('img');
        del.id = "dest"+s+"X"+exten+"del";
        del.src = "<?php echo $registry->getImageDir('horde') . '/delete-small.png'; ?>"
        del.style.cursor = 'pointer';
        del.setAttribute('onclick', 'delDest("'+exten+'", "number", "'+s+'")');
        br = document.createElement('br');
        $('destX'+exten+'info').appendChild(img);
        $('destX'+exten+'info').appendChild(text);
        $('destX'+exten+'info').appendChild(del);
        $('destX'+exten+'info').appendChild(br);

        // Create summary icons
        img = document.createElement('img');
        img.src = "<?php echo $registry->getImageDir() . '/telephone-pole.png'; ?>";
        $('destX'+exten+'summary').appendChild(img);
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
    form = event.target;
    Element.extend(form);

    exten = form.getInputs('hidden', 'extension').first().value;

    spinner = document.createElement('img');
    spinner.src = "<?php echo $registry->getImageDir('horde') . '/loading.gif'; ?>"

    $('destX'+exten+'submit').hide();
    form.insertBefore(spinner, $('destX'+exten+'submit'))

    // FIXME: Better error handling
    new Ajax.Request('<?php echo Horde::applicationUrl('ajax.php'); ?>',
    {
        method: 'post',
        parameters: form.serialize(true),
        onSuccess: function(r) {
            //alert(json ? Object.inspect(json) : "no JSON object")
            destinations = $H(r.responseJSON.response);
            resetDestInfo();
        },
        onFailure: function(){ alert('Something went wrong...') }
    });
}

function addDest(exten)
{
    // Hide the link to create the form below.
    // We only want one active at a time.
    $('destX'+exten+'addDest').hide();

    hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'action';
    hidden.value = 'addDestination';

    select = document.createElement('select');
    select.name = 'type';

    option = document.createElement('option');
    option.value = 'number';
    text = document.createTextNode("<?php echo _("Number"); ?>");
    option.appendChild(text);
    select.appendChild(option);

    option = document.createElement('option');
    option.value = 'device';
    text = document.createTextNode("<?php echo _("Device"); ?>");
    option.appendChild(text);
    select.appendChild(option);

    input = document.createElement('input');
    input.name = 'destination';
    input.type = "text";
    input.size = 12;
    input.maxlength = 15;

    save = document.createElement("input");
    save.name = "submit";
    save.value = "Save";
    save.type = "submit";
    save.id = 'destX'+exten+'submit';

    br = document.createElement('br');

    $('destX'+exten+'form').appendChild(hidden);
    $('destX'+exten+'form').appendChild(select);
    $('destX'+exten+'form').appendChild(input);
    $('destX'+exten+'form').appendChild(save);
    $('destX'+exten+'form').appendChild(br);
    input.focus();
    Event.observe($('destX'+exten+'form'), 'submit', function(event) {processForm(event);});
}

function delDest(exten, type, dest)
{
    params = $H({
        'extension': exten,
        'type': type,
        'destination': dest,
        'action': 'deleteDestination'
    });

    // Hide the delete button and replace it with a spinner
    $("dest"+dest+"X"+exten+"del").hide();
    spinner = document.createElement('img');
    spinner.src = "<?php echo $registry->getImageDir('horde') . '/loading.gif'; ?>"
    parent = $("dest"+dest+"X"+exten+"del").parentNode;
    parent.insertBefore(spinner, $("dest"+dest+"X"+exten+"del"));

    // FIXME: Better error handling
    new Ajax.Request('<?php echo Horde::applicationUrl('ajax.php'); ?>',
    {
        method: 'post',
        parameters: params,
        onSuccess: function(r) {
            //alert(json ? Object.inspect(json) : "no JSON object")
            destinations = $H(r.responseJSON.response);
            resetDestInfo();
        },
        onFailure: function(){ alert('Something went wrong...') }
    });
}

destinations = $H();

new Ajax.Request('<?php echo Horde::applicationUrl('ajax.php'); ?>',
{
    method: 'post',
    parameters: $H({'action': 'getDestinations'}),
    onSuccess: function(r) {
        destinations = $H(r.responseJSON.response);
        resetDestInfo(true);
    }
});

// -->
</script>
