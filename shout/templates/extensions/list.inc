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
    Account: <?php echo $_SESSION['shout']['accounts'][$curaccount]; ?>
</div>

<div id="extensionList">
    <table width="100%" cellspacing="0" class="striped">
        <tr>
            <td class="uheader">Extension</td>
            <td class="uheader">Destinations</td>
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
            <td>
                <?php echo Horde::link($editurl); echo $info['name'] . ' (' . $extension . ')'; ?></a>
            </td>
            <td>
                <?php
                $attrs = array('onClick' => 'destinfo("' . $extension . '");',
                               'id' => 'destX' . $extension . 'toggle');
                echo Horde::img('tree/plusonly.png', _("Destinations"), $attrs,
                                $registry->getImageDir('horde'));
                ?>
                <span id="destX<?php echo $extension; ?>summary"></span>
                <div class="extensionDestinations" id="destX<?php echo $extension; ?>info">
                </div>
            </td>
        </tr>
        <?php
            }
        ?>
    </table>
</div>

<script type="text/javascript">
<!--

var destinations = [];
var ajax_url = '<?php echo Horde::getServiceLink('ajax', 'shout') ?>';

function resetExtenDest(exten)
{
    var e;

    while ((e = $('destX' + exten + 'info').childNodes[0]) != null) {
        $('destX' + exten + 'info').removeChild(e);
    }

    while ((e = $('destX' + exten + 'summary').childNodes[0]) != null) {
        $('destX' + exten + 'summary').removeChild(e);
    }

    var dest = destinations.get(exten);

    if (dest.devices == null) {
        dest.devices = [];
    }

    if (dest.numbers == null) {
        dest.numbers = [];
    }

    if (dest.devices.size() == 0 && dest.numbers.size() == 0) {
        var span = document.createElement('span');
        span.className = 'informational';
        var text = document.createTextNode("No destinations configured");
        span.appendChild(text);
        $('destX' + exten + 'summary').appendChild(span);
    }

    dest.devices.each(function (s) {
        // Fill in detail block
        var img = document.createElement('img');
        img.src = "<?php echo $registry->getImageDir() . '/shout.png'; ?>";

        var span = document.createElement('span');
        span.className = 'device';
        var text = document.createTextNode(" " + s + " ");
        span.setAttribute('onClick', 'editDest("' + exten + '", "device", "' + s + '")');
        span.appendChild(text);

        var del = document.createElement('img');
        del.id = "dest" + s + "X" + exten + "del";
        del.src = "<?php echo $registry->getImageDir('horde') . '/delete-small.png'; ?>"
        del.style.cursor = 'pointer';
        del.setAttribute('onClick', 'delDest("' + exten + '", "device", "' + s + '")');

        var br = document.createElement('br');

        var info = $('destX' + exten + 'info');
        info.appendChild(img);
        info.appendChild(span);
        info.appendChild(del);
        info.appendChild(br);

        // Create summary icons
        img = document.createElement('img');
        img.src = "<?php echo $registry->getImageDir() . '/shout.png'; ?>";
        $('destX' + exten + 'summary').appendChild(img);

    });


    dest.numbers.each(function (s) {
        // Fill in detail block
        var img = document.createElement('img');
        img.src = "<?php echo $registry->getImageDir() . '/telephone-pole.png'; ?>";

        var span = document.createElement('span');
        span.className = 'device';
        var text = document.createTextNode(" " + s + " ");
        span.setAttribute('onClick', 'editDest("' + exten + '", "number", "' + s + '")');
        span.appendChild(text);

        var del = document.createElement('img');
        del.id = "dest" + s + "X" + exten + "del";
        del.src = "<?php echo $registry->getImageDir('horde') . '/delete-small.png'; ?>";
        del.style.cursor = 'pointer';
        del.setAttribute('onclick', 'delDest("' + exten + '", "number", "' + s + '")');

        var br = document.createElement('br');

        var info = $('destX' + exten + 'info');
        info.appendChild(img);
        info.appendChild(span);
        info.appendChild(del);
        info.appendChild(br);

        // Create summary icons
        img = document.createElement('img');
        img.src = "<?php echo $registry->getImageDir() . '/telephone-pole.png'; ?>";
        $('destX' + exten + 'summary').appendChild(img);
    });

    var form = document.createElement('form');
    form.method = 'post';
    form.action = ajax_url + 'addDestination';
    form.id = 'destX' + exten + 'form';
    var hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'extension';
    hidden.value = exten;
    form.appendChild(hidden);
    $('destX' + exten + 'info').appendChild(form);

    var a = document.createElement('a');
    a.id = 'destX' + exten + 'addDest';
    a.className = 'addDest';
    a.href = '#';
    a.setAttribute('onClick', 'addDest(' + exten + ')');
    var t = document.createTextNode('Add destination...');
    a.appendChild(t);
    $('destX' + exten + 'info').appendChild(a);
}

function resetDestInfo(collapse)
{
    destinations.each(function (item) {
        var exten;
        exten = item.key;
        resetExtenDest(exten);
        if (collapse) {
            contract(exten);
        }
    });
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
    $('destX' + exten + 'summary').hide();
    $('destX' + exten + 'info').show();
    $('destX' + exten + 'toggle').src = '<?php echo $registry->getImageDir('horde') . '/tree/minusonly.png'; ?>';
}

function contract(exten)
{
    $('destX' + exten + 'summary').show();
    $('destX' + exten + 'info').hide();
    $('destX' + exten + 'toggle').src = '<?php echo $registry->getImageDir('horde') . '/tree/plusonly.png'; ?>';
}

function processForm(event)
{
    Event.stop(event);
    var form = event.target;
    Element.extend(form);

    var editflag = form.getInputs('hidden', 'edit').first();
    if (editflag) {
        editflag = editflag.value;
    }

    var exten = form.getInputs('hidden', 'extension').first().value;

    var spinner = document.createElement('img');
    spinner.src = "<?php echo $registry->getImageDir('horde') . '/loading.gif'; ?>"

    $('destX' + exten + 'submit').hide();
    form.insertBefore(spinner, $('destX' + exten + 'submit'))

    if (editflag) {
        var origtype = form.getInputs('hidden', 'origtype').first().value;
        var origdest = form.getInputs('hidden', 'origdest').first().value;
        var newtype = $('destX' + exten + 'type').value;
        var newdest = $('destX' + exten + 'destination').value;

        if ((origtype == newtype) && (origdest == newdest)) {
            // The user hit "save" without making any changes.
            // Do not contact AJAX, just make sure destinations is updated.
                var key = (origtype == "number") ? 'numbers' : 'devices';
                var xd = destinations.get(exten);
                xd[key].push(origdest);
                destinations.set(exten, xd);
                resetExtenDest(exten);
                return true;
        } else {
            // A change was made.  Remove the old destination first.
            delDest(exten, origtype, origdest);
        }
    }

    // FIXME: Better error handling
    new Ajax.Request(form.readAttribute('action'),
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
    $('destX' + exten + 'addDest').hide();

    var hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'action';
    hidden.value = 'addDestination';

    var select = document.createElement('select');
    select.id = 'destX' + exten + 'type';
    select.name = 'type';

    var option = document.createElement('option');
    option.value = 'number';
    var text = document.createTextNode("<?php echo _("Number"); ?>");
    option.appendChild(text);
    select.appendChild(option);

    option = document.createElement('option');
    option.value = 'device';
    text = document.createTextNode("<?php echo _("Device"); ?>");
    option.appendChild(text);
    select.appendChild(option);

    var input = document.createElement('input');
    input.id = 'destX' + exten + 'destination';
    input.name = 'destination';
    input.type = "text";
    input.size = 12;
    input.maxlength = 15;

    var save = document.createElement("input");
    save.name = "submit";
    save.value = "Save";
    save.type = "submit";
    save.id = 'destX' + exten + 'submit';

    var br = document.createElement('br');

    $('destX' + exten + 'form').appendChild(hidden);
    $('destX' + exten + 'form').appendChild(select);
    $('destX' + exten + 'form').appendChild(input);
    $('destX' + exten + 'form').appendChild(save);
    $('destX' + exten + 'form').appendChild(br);
    input.focus();
    Event.observe($('destX' + exten + 'form'), 'submit', function(event) {processForm(event);});
}

function editDest(exten, type, dest)
{
    var key = (type == "number") ? 'numbers' : 'devices';

    // Remove the current destination from the list
    var xd = destinations.get(exten);
    xd[key] = xd[key].without(dest);
    destinations.set(exten, xd);
    resetExtenDest(exten);

    addDest(exten);

    // Tell processForm() this is an edit
    var editflag = document.createElement('input');
    editflag.type = 'hidden';
    editflag.name = 'edit';
    editflag.value = 'true';
    var origtype = document.createElement('input');
    origtype.type = 'hidden';
    origtype.name = 'origtype';
    origtype.value = type;
    var origdest = document.createElement('input');
    origdest.type = 'hidden';
    origdest.name = 'origdest';
    origdest.value = dest;
    $('destX' + exten + 'form').appendChild(editflag);
    $('destX' + exten + 'form').appendChild(origtype);
    $('destX' + exten + 'form').appendChild(origdest);

    // Preserve the original values for the user.
    $('destX' + exten + 'type').value = type
    $('destX' + exten + 'destination').value = dest;
}

function delDest(exten, type, dest)
{
    var params = $H({
        'extension': exten,
        'type': type,
        'destination': dest
    });

    
    var deleteIcon = $("dest" + dest + "X" + exten + "del")
    // If null we came from an edit so the spinner is already going
    if (deleteIcon !== null) {
        // Hide the delete button and replace it with a spinner
        deleteIcon.hide();
        var spinner = document.createElement('img');
        spinner.src = "<?php echo $registry->getImageDir('horde') . '/loading.gif'; ?>"
        var parent = deleteIcon.parentNode;
        parent.insertBefore(spinner, deleteIcon);
    }

    // FIXME: Better error handling
    new Ajax.Request(ajax_url + 'deleteDestination',
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

new Ajax.Request(ajax_url + 'getDestinations',
{
    method: 'post',
    onSuccess: function(r) {
        destinations = $H(r.responseJSON.response);
        resetDestInfo(true);
    }
});

// -->
</script>
