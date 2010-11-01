<div class="header">
    Account: <?php echo $_SESSION['shout']['curaccount']['name']; ?>
</div>

<div id="extensionList">
    <table width="100%" cellspacing="0" class="striped">
        <tr>
            <td class="uheader">Extension</td>
            <td id ="destinationsCol" class="uheader">Destinations</td>
        </tr>
        <?php
            foreach ($extensions as $extension => $info) {

                $url = Horde::url("extensions.php");
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
                <?php echo Horde::link($editurl);
                      echo $info['name'] . ' (' . $extension . ')';
                      echo '</a>';
                 ?>
            </td>
            <td>
                <?php
                $attrs = array('onClick' => 'showDetail("' . $extension . '");',
                               'id' => 'destX' . $extension . 'toggle');
                echo Horde::img('tree/plusonly.png', _("Destinations"), $attrs);
                ?>
                <span id="destX<?php echo $extension; ?>"></span>
            </td>
        </tr>
        <?php
            }
        ?>
    </table>
</div>
<br />
<ul id="extensionsControls" class="controls">
    <?php
    $addurl = Horde::url('extensions.php');
    $addurl = Horde_Util::addParameter($addurl, 'action', 'add');
    ?>
    <li><span class="button" onclick="showExtensionForm()">
        <?php echo Horde::img('extension-add.png'); ?>&nbsp;New Extension
        </span>
    </li>
</ul>

<div id="addExtension" class="form">
    <div id="addExtensionWorking" class="working"></div>
    <form id="addExtensionForm" action="#" name="addExtension">
        <div class="header">Add Extension</div>
        <table cellspacing="0" class="striped">
            <tr valign="top" class="rowOdd">
                <!-- FIXME: Make these error fields dynamic -->
                <td align="right" width="15%">
                    <span class="form-error">*</span>&nbsp;Full Name
                </td>
                <td>
                    <input type="text" value="" size="40" id="name" name="name">
                </td>
            </tr>
            <tr valign="top" class="rowEven">
                <td align="right" width="15%">
                    <span class="form-error">*</span>&nbsp;Extension
                </td>
                <td>
                    <input type="text" value="" id="extension" name="extension" size="5">
                </td>
            </tr>
            <tr valign="top" class="rowOdd">
                <td align="right" width="15%">
                    <span class="form-error">*</span>&nbsp;E-Mail Address
                </td>
                <td>
                    <input type="text" value="" id="email" name="email">
                </td>
            </tr>
            <tr valign="top" class="rowEven">
                <td align="right" width="15%">
                    <span class="form-error">*</span>&nbsp;PIN
                </td>
                <td>
                    <input type="text" value="" id="mailboxpin" name="mailboxpin" size="5">
                </td>
            </tr>
        </table>
        <div class="control">
            <input type="submit" value="Save" name="addExtension" class="button">
        </div>
    </form>
</div>

<script type="text/javascript">
<!--

var destinations = $H();
var devices = $H();
var ajax_url = '<?php echo Horde::getServiceLink('ajax', 'shout') ?>';
var curexten = null;

$('addExtensionWorking').hide();
$('addExtension').hide();
Event.observe('addExtensionForm', 'submit', function(event) {saveExtension(event);});

function empty(p)
{
    var e;

    while ($(p) && (e = $(p).childNodes[0]) != null) {
        $(p).removeChild(e);
    }
}

function showDetail(exten)
{
    // Hide any currently expanded extension info
    if (curexten) {
        showSummary(curexten);
    }

    $('destX' + exten + 'toggle').src = '<?php echo Horde_Themes::img('tree/minusonly.png') ?>';
    $('destX' + exten + 'toggle').setAttribute('onclick', 'showSummary('+exten+')');
    curexten = exten;

    var e = $('destX'+exten);
    empty(e);

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
        var text = document.createTextNode('<?php echo _("No destinations configured"); ?>');
        span.appendChild(text);
        e.appendChild(span);
    } else {
        var span = document.createElement('span');
        span.className = 'informational';
        var text = document.createTextNode('<?php echo _("Configured Destinations:"); ?>');
        span.appendChild(text);
        e.appendChild(span);
    }

    var div = document.createElement('div');
    div.className = 'extensionDestinations';
    dest.devices.each(function (s) {
        // Fill in detail block
        var img = document.createElement('img');
        img.src = "<?php echo Horde_Themes::img('shout.png') ?>";

        var span = document.createElement('span');
        span.className = 'device';
        var text = document.createTextNode(" " + devices.get(s).name + " ");
        span.setAttribute('onClick', 'editDest("' + exten + '", "device", "' + s + '")');
        span.appendChild(text);

        var del = document.createElement('img');
        del.id = "dest" + s + "X" + exten + "del";
        del.src = "<?php echo Horde_Themes::img('delete-small.png') ?>"
        del.style.cursor = 'pointer';
        del.setAttribute('onClick', 'delDest("' + exten + '", "device", "' + s + '")');

        var br = document.createElement('br');

        div.appendChild(img);
        div.appendChild(span);
        div.appendChild(del);
        div.appendChild(br);
    });


    dest.numbers.each(function (s) {
        // Fill in detail block
        var img = document.createElement('img');
        img.src = "<?php echo Horde_Themes::img('telephone-pole.png') ?>";

        var span = document.createElement('span');
        span.className = 'device';
        var text = document.createTextNode(" " + s + " ");
        span.setAttribute('onClick', 'editDest("' + exten + '", "number", "' + s + '")');
        span.appendChild(text);

        var del = document.createElement('img');
        del.id = "dest" + s + "X" + exten + "del";
        del.src = "<?php echo Horde_Themes::img('delete-small.png') ?>";
        del.style.cursor = 'pointer';
        del.setAttribute('onclick', 'delDest("' + exten + '", "number", "' + s + '")');

        var br = document.createElement('br');

        div.appendChild(img);
        div.appendChild(span);
        div.appendChild(del);
        div.appendChild(br);
    });

    var form = document.createElement('form');
    form.method = 'post';
    form.action = ajax_url + 'addDestination';
    form.id = 'destEditForm';
    var hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'extension';
    hidden.value = exten;
    form.appendChild(hidden);
    div.appendChild(form);

    var a = document.createElement('a');
    a.id = 'addDest';
    a.href = '#';
    a.setAttribute('onClick', 'addDest(' + exten + ')');
    var t = document.createTextNode('Add destination...');
    a.appendChild(t);
    div.appendChild(a);

    e.appendChild(div);
}

function showSummary(exten)
{
    var e = $('destX' + exten);
    empty(e);

    $('destX' + exten + 'toggle').src = '<?php echo Horde_Themes::img('tree/plusonly.png') ?>';
    $('destX' + exten + 'toggle').setAttribute('onclick', 'showDetail('+exten+')');

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
        e.appendChild(span);
    } else {
        dest.devices.each(function (s) {
            // Fill in detail block
            var img = document.createElement('img');
            img.src = "<?php echo Horde_Themes::img('shout.png') ?>";
            e.appendChild(img);
        })
        dest.numbers.each(function (s) {
            // Fill in detail block
            var img = document.createElement('img');
            img.src = "<?php echo Horde_Themes::img('telephone-pole.png') ?>";
            e.appendChild(img);
        })
    }
}

function resetDestInfo()
{
    destinations.each(function (item) {
        showSummary(item.key)
    })
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
    spinner.src = "<?php echo Horde_Themes::img('loading.gif') ?>"

    $('destSave').hide();
    form.insertBefore(spinner, $('destSave'))

    if (editflag) {
        var origtype = form.getInputs('hidden', 'origtype').first().value;
        var origdest = form.getInputs('hidden', 'origdest').first().value;
        var newtype = $('destType').value;
        var newdest = $('destValue').value;

        if ((origtype == newtype) && (origdest == newdest)) {
            // The user hit "save" without making any changes.
            // Do not contact AJAX, just make sure destinations is updated.
                var key = (origtype == "number") ? 'numbers' : 'devices';
                var xd = destinations.get(exten);
                xd[key].push(origdest);
                destinations.set(exten, xd);
                alert("FIXME");
                resetDestInfo();
                showDetail(exten);
                return false;
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
    $('addDest').hide();

    var hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'action';
    hidden.value = 'addDestination';
    $('destEditForm').appendChild(hidden);
    showDestForm();
}

function editDest(exten, type, dest)
{
    // Reset the screen just in case we already have an edit in progress
    // FIXME: Use the following lines with a unchanged copy of "destinations"
    // to allow transitioning between active edits
    //resetDestInfo();
    //showDetail(exten);

    var key = (type == "number") ? 'numbers' : 'devices';

    // Remove the current destination from the list
    var xd = destinations.get(exten);
    xd[key] = xd[key].without(dest);
    destinations.set(exten, xd);
    showDetail(exten);

    // Fill in the new destination and tell processForm() this is an edit
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

    $('destEditForm').appendChild(editflag);
    $('destEditForm').appendChild(origtype);
    $('destEditForm').appendChild(origdest);

    showDestForm();

    // Preserve the original values for the user.
    $('destType').value = type

    // Refresh the value part of the form
    showDestType(dest);
}

function showDestForm()
{
    var spacer = document.createTextNode(' ');

    var select = document.createElement('select');
    select.id = 'destType';
    select.name = 'type';
    select.setAttribute('onchange', 'showDestType()');

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

    $('destEditForm').appendChild(select);

    $('destEditForm').appendChild(spacer.clone(true));

    var span = document.createElement('span');
    span.id = 'destValueContainer';
    $('destEditForm').appendChild(span);


    $('destEditForm').appendChild(spacer.clone(true));

    var save = document.createElement("input");
    save.id = 'destSave';
    save.name = "submit";
    save.value = "Save";
    save.type = "submit";
    var br = document.createElement('br');
    $('destEditForm').appendChild(save);
    $('destEditForm').appendChild(br);

    showDestType();
}

function showDestType(value)
{
    empty('destValueContainer');
    var type = $('destType').value;
    switch(type) {
    case 'number':
        var input = document.createElement('input');
        input.id = 'destValue';
        input.name = 'destination';
        input.type = "text";
        input.size = 12;
        input.maxlength = 15;
        if (value) {
            input.value = value;
        }

        $('destValueContainer').appendChild(input);
        input.focus();
        break;

    case 'device':
        var select = document.createElement('select');
        select.id = 'destValue';
        select.name = 'destination';

        devices.each(function(pair) {
            var option = document.createElement('option');
            option.value = pair.key;
            var text = document.createTextNode(pair.value.name);
            option.appendChild(text);
            select.appendChild(option);
        })

        if (value) {
            select.value = value;
        }

        $('destValueContainer').appendChild(select);
        break;
    }

    Event.observe($('destEditForm'), 'submit', function(event) {processForm(event);});
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
        spinner.src = "<?php echo Horde_Themes::img('loading.gif') ?>"
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

function showExtensionForm()
{
    $('addExtensionWorking').hide();
    $('extensionsControls').hide();
    Effect.BlindDown('addExtension');
}

function saveExtension(event)
{
    event.stop();
    $('addExtensionWorking').show();
    var params = Element.extend(event.target).serialize(true);

    new Ajax.Request(ajax_url + 'saveExtension',
    {
        method: 'post',
        parameters: params,
        onSuccess: function(r) {
            new Ajax.Request(ajax_url + 'getDestinations',
            {
                method: 'get',
                onSuccess: function(r) {
                    destinations = $H(r.responseJSON.response);
                    addExtension(params);
                    resetDestInfo();
                    Effect.BlindUp('addExtension', {
                        afterFinish: function() { $('extensionsControls').show() }
                    });
                }
            });
        }
    });
   
}

function addExtension(params)
{
    var tr = document.createElement('tr');
    tr.className = 'item';
    var td = document.createElement('td');
    var text = document.createTextNode(params.name + " (" + params.extension + ")");
    td.appendChild(text);
    tr.appendChild(td);

    var td = document.createElement('td');
    var img = document.createElement('img');
    img.src = '<?php echo Horde_Themes::img('tree/plusonly.png'); ?>';
    img.setAttribute('onclick', 'showDetail("'+params.extension+'");');
    img.id = "destX" + params.extension + "toggle";
    var span = document.createElement('span');
    span.id = "destX" + params.extension;
    td.appendChild(img);
    td.appendChild(span);
    tr.appendChild(td);

    $('extensionList').down('tbody').appendChild(tr);
    Horde.stripeAllElements.bind(Horde)();
}

new Ajax.Request(ajax_url + 'getDevices',
{
    method: 'post',
    onSuccess: function(r) {
        devices = $H(r.responseJSON.response);
    }
});


new Ajax.Request(ajax_url + 'getDestinations',
{
    method: 'get',
    onSuccess: function(r) {
        destinations = $H(r.responseJSON.response);
        resetDestInfo();
    }
});

// -->
</script>