<div id="dialplan">
    <div id="menuInfo">
        <div id="menuWorking" class="working"></div>
        <table class="striped">
            <tr>
                <td class="metaStatName">Menu</td>
                <td class="metaStatInfo"><select id="menu.select" onchange="refreshMenu()"></select></td>
            </tr>
            <tr>
                <td class="metaStatName">Description</td>
                <td class="metaStatInfo" id="menu.description"></td>
            </tr>
            <tr>
                <td class="metaStatName">Recording</td>
                <td class="metaStatInfo"><span id="menu.recording"></span></td>
            </tr>
        </table>
        <form id="editMenu">
        <table class="striped">
            <tr>
                <td class="metaStatName">Menu Name</td>
                <td class="metaStatInfo"><input id="menu_edit.name" name="name"></td>
            </tr>
            <tr>
                <td class="metaStatName">Description</td>
                <td class="metaStatInfo"><input id="menu_edit.description" name="description"></td>
            </tr>
            <tr>
                <td class="metaStatName">Recording</td>
                <td class="metaStatInfo"><select id="menu_edit.recording" name="recording_id"></select></td>
            </tr>
            <tr>
                <td><div class="button" onclick="saveMenuInfo()"><?php echo _("Save"); ?></div></td>
                <td><div class="button" onclick="Effect.BlindUp('editMenu')"><?php echo _("Cancel"); ?></div></td>
            </tr>
        </table>
        </form>
        <br style="clear:both;">
        <ul id="dialplanControls" class="controls">
        <?php
        $addurl = Horde::url('dialplan.php');
        $addurl = Horde_Util::addParameter($addurl, 'action', 'add');
        ?>

        <li>
            <a class="button" style="display: block;" href="<?php echo $addurl; ?>">
            <?php echo Horde::img('menu-add.png'); ?>&nbsp;New Menu
            </a>
        </li>
        <li>
            <div class="button" onclick="editMenu()">
            <?php echo Horde::img('menu-edit.png'); ?>&nbsp;Edit Menu
            </div>
        </li>
        <li>
            <div class="button" onclick="deleteMenu()">
            <?php echo Horde::img('menu-delete.png');?>&nbsp;Delete Menu
            </div>
        </li>
        </ul>
    </div>

    <div id="digitpad">
      <div id="dialpadWorking" class="working"></div>
      <div id="editActionOverlay">
        <img id="digitGraphic" alt="Current Digit">
        <form id="editActionForm">
          <input name="digit" id="editActionFormDigit" type="hidden">
          <div id="selectAction"></div>
          <div id="editAction"></div>
        </form>
      </div>
      <div class="digit" id="digit_1" onClick="editAction('1');"><span class="digitLabel">1</span></div>
      <div class="digit" id="digit_2" onClick="editAction('2');"><span class="digitLabel">2</span></div>
      <div class="digit" id="digit_3" onClick="editAction('3');"><span class="digitLabel">3</span></div>
      <br style="clear:both;">
      <div class="digit" id="digit_4" onClick="editAction('4');"><span class="digitLabel">4</span></div>
      <div class="digit" id="digit_5" onClick="editAction('5');"><span class="digitLabel">5</span></div>
      <div class="digit" id="digit_6" onClick="editAction('6');"><span class="digitLabel">6</span></div>
      <br style="clear:both;">
      <div class="digit" id="digit_7" onClick="editAction('7');"><span class="digitLabel">7</span></div>
      <div class="digit" id="digit_8" onClick="editAction('8');"><span class="digitLabel">8</span></div>
      <div class="digit" id="digit_9" onClick="editAction('9');"><span class="digitLabel">9</span></div>
      <br style="clear:both;">
      <div class="digit" id="digit_star" onClick="editAction('star');"><span class="digitLabel">*</span></div>
      <div class="digit" id="digit_0" onClick="editAction('0');"><span class="digitLabel">0</span></div>
      <div class="digit" id="digit_octo" onClick="editAction('octo');"><span class="digitLabel">#</span></div>
    </div>
</div>

<script type="text/javascript">
<!--
// TODO: Convert to object
var ajax_url = '<?php echo Horde::getServiceLink('ajax', 'shout') ?>';
var curmenu = null;
var menuInfo = $H();
var menuActions = $H(<?php echo Horde_Serialize::serialize(Shout::getMenuActions(), Horde_Serialize::JSON); ?>);
var destinations = $H(<?php echo Horde_Serialize::serialize($destinations, Horde_Serialize::JSON); ?>);
var conferences = $H(<?php echo Horde_Serialize::serialize($conferences, Horde_Serialize::JSON); ?>);
var recordings = $H(<?php echo Horde_Serialize::serialize($recordings, Horde_Serialize::JSON); ?>);

function empty(p)
{
    while ($(p) && (e = $(p).childNodes[0]) != null) {
        $(p).removeChild(e);
    }
}

function editAction(digit)
{
    try {
        $('dialpadWorking').show();
        empty('editAction');
        $('editActionFormDigit').value = digit;

        if ($('selectAction').firstChild == null) {
            $('digitGraphic').src = '<?php echo Horde_Themes::img(null, array('theme' => null, 'nohorde' => true)); ?>/digit-'+digit+'.png';

            // Draw the selectActionForm
            $('editActionOverlay').show();
            empty('selectAction');
            empty('editAction');
            var p = document.createElement('p');
            p.id = 'actionPrompt';
            var text = document.createTextNode('<?php echo _("Action:"); ?>');
            p.appendChild(text);
            $('selectAction').appendChild(p);
            var select = document.createElement('select');
            select.name = 'action';
            select.setAttribute('onChange', 'editAction("'+digit+'")');
            var option = document.createElement('option');
            option.value = '';
            var text = document.createTextNode('<?php echo _("-- Select Action --"); ?>');
            option.appendChild(text);
            select.appendChild(option);
            menuActions.each(function (item) {
                option = document.createElement('option');
                option.value = item.key;
                var text = document.createTextNode(item.value.description);
                option.appendChild(text);
                select.appendChild(option);
            });
            if (menuInfo.get(curmenu).actions[digit] != null) {
                select.value = menuInfo.get(curmenu).actions[digit].action
            }
            $('selectAction').appendChild(select);

            // Cancel button
            var cancel = document.createElement('input');
            cancel.setAttribute('onclick', 'cancelEdit()');
            cancel.className = 'button';
            cancel.type = "reset";
            cancel.value = '<?php echo _("Cancel"); ?>';
            $('editAction').appendChild(cancel);
        }

        if ($('editActionForm').down('select').getValue() != '') {
            empty('editAction');
            var action = $('editActionForm').down('select').getValue();
            var div = document.createElement('div');
            div.id = 'editActionArgs';
            switch(action) {
            case 'jump':
                var text = document.createTextNode(menuActions.get(action).args.menuName.name);
                div.appendChild(text);

                var select = document.createElement('select');
                select.name = 'menuName';
                menuInfo.each(function (item) {
                    var option = document.createElement('option');
                    option.value = item.key;
                    var text = document.createTextNode(item.value.name);
                    option.appendChild(text);
                    select.appendChild(option);
                })
                if (menuInfo.get(curmenu).actions[digit] != null) {
                    select.value = menuInfo.get(curmenu).actions[digit].args.menuName;
                }
                div.appendChild(select);
                break;

            case 'ringexten':
            case 'leave_message':
                var text = document.createTextNode(menuActions.get(action).args.exten.name);
                div.appendChild(text);

                var select = document.createElement('select');
                select.name = 'exten';
                destinations.each(function (item) {
                    var option = document.createElement('option');
                    option.value = item.key;
                    var text = document.createTextNode(item.value.name);
                    option.appendChild(text);
                    select.appendChild(option);
                })

                if (menuInfo.get(curmenu).actions[digit] != null) {
                    select.value = menuInfo.get(curmenu).actions[digit].exten;
                }
                div.appendChild(select);
                break;

            case 'conference':
                var text = document.createTextNode(menuActions.get(action).args.roomno.name);
                div.appendChild(text);

                var select = document.createElement('select');
                select.name = 'roomno';
                conferences.each(function (item) {
                    var option = document.createElement('option');
                    option.value = item.key;
                    var text = document.createTextNode(item.value.name);
                    option.appendChild(text);
                    select.appendChild(option);
                })

                if (menuInfo.get(curmenu).actions[digit] != null) {
                    select.value = menuInfo.get(curmenu).actions[digit].roomno;
                }
                div.appendChild(select);
                break;

            case 'dial':
                var text = document.createTextNode(menuActions.get(action).args.numbers.name);
                div.appendChild(text);
                var br = document.createElement('br');
                div.appendChild(br);
                if (menuInfo.get(curmenu).actions[digit] != null) {
                    menuInfo.get(curmenu).actions[digit].args.numbers.each(function (s) {
                        var input = document.createElement('input');
                        input.name = 'numbers[]';
                        input.value = s;
                        input.size = "15";
                        div.appendChild(input);
                    })
                } else {
                    var input = document.createElement('input');
                    input.name = 'numbers[]';
                    input.size = "15";
                    div.appendChild(input);

                }
                var img = document.createElement('img');
                img.alt = '<?php echo _("Add a number"); ?>';
                img.id = 'addDialNumberButton';
                img.src = '<?php echo Horde_Themes::img('plus.png'); ?>';
                img.setAttribute('onclick', 'addDialNumber()');
                div.appendChild(img);
                break;

            case 'admin_login':
            case 'directory':
            case 'rewind':
            case 'none':
                break;

            default:
                alert(action);
                break;
            }
            $('editAction').appendChild(div);

            // Save and Cancel buttons
            var div = document.createElement('div');
            var submit = document.createElement('input');
            submit.type = 'submit';
            submit.className = 'button';
            submit.value = '<?php echo _("Save"); ?>';
            div.appendChild(submit);

            var cancel = document.createElement('input');
            cancel.setAttribute('onclick', 'cancelEdit()');
            cancel.className = 'button';
            cancel.type = 'reset';
            cancel.value = '<?php echo _("Cancel"); ?>';
            div.appendChild(cancel);

            $('editAction').appendChild(div);
        }
        $('dialpadWorking').hide();
    } catch (e) {
        logException(e);
    }
}

function addDialNumber()
{
    $('editActionArgs').removeChild($('addDialNumberButton'));
    var br = document.createElement('br');
    $('editActionArgs').appendChild(br);
    var input = document.createElement('input');
    input.name = 'numbers[]';
    input.size = "15";
    $('editActionArgs').appendChild(input);
    var img = document.createElement('img');
    img.alt = '<?php echo _("Add a number"); ?>';
    img.id = 'addDialNumberButton';
    img.src = '<?php echo Horde_Themes::img('plus.png'); ?>';
    img.setAttribute('onclick', 'addDialNumber()');
    $('editActionArgs').appendChild(img);
}

function saveAction(event)
{
    $('dialpadWorking').show();
    Event.stop(event);
    var form = event.target;
    Element.extend(form);

    var params = form.serialize(true);
    params.menu = curmenu;
    new Ajax.Request(ajax_url + 'saveAction',
    {
        method: 'post',
        parameters: params,
        onSuccess: function(r) {
            empty('selectAction');
            empty('editAction');
            $('editActionOverlay').hide();
            new Ajax.Request(ajax_url + 'getMenus',
            {
                method: 'get',
                parameters: $H({
                    'menu': curmenu
                }),
                onSuccess: function(r) {
                    menuInfo = $H(r.responseJSON.response);
                    refreshMenu();
                    $('dialpadWorking').hide();
                }
            });
        }
    });
}

function cancelEdit()
{
    empty($('selectAction'));
    empty($('editAction'));
    $('editActionOverlay').hide();
}

function playRecording()
{
    // TODO
    alert("Playing recording");
}

function refreshMenu()
{
    try {
        $('menuWorking').show();
        $('dialpadWorking').show();
        curmenu = $('menu.select').value;

        empty('menu.select');
        menuInfo.each(function (item) {
            var option = document.createElement('option');
            option.value = item.key;
            var text = document.createTextNode(item.value.name);
            option.appendChild(text);
            $('menu.select').appendChild(option);
        })

        $A(['1','2','3','4','5','6','7','8','9','0','star','octo']).each(function (digit){
            empty('digit_' + digit);
            var span = document.createElement('span');
            span.className = 'digitLabel';
            var text;
            switch(digit) {
            case 'star':
                text = document.createTextNode('*');
                break;
            case 'octo':
                text = document.createTextNode('#');
                break;
            default:
                text = document.createTextNode(digit);
                break;
            }
            span.appendChild(text);
            $('digit_' + digit).appendChild(span);
        })

        if (!(menuInfo.size())) {
            curmenu = null;
            $('menuWorking').hide();
            $('dialpadWorking').hide();
            return true;
        }

        if (!menuInfo.get(curmenu)) {
            curmenu = menuInfo.keys().first();
        }
        $('menu.select').value = curmenu;

        var text = document.createTextNode(menuInfo.get(curmenu).description);
        empty('menu.description');
        $('menu.description').appendChild(text);

        if (recordings.get(menuInfo.get(curmenu).recording_id)) {
            var text = document.createTextNode(recordings.get(menuInfo.get(curmenu).recording_id).filename);
        } else {
            // We have an invalid recording id.  It either was never set
            // or it references a deleted recording.
            var text = document.createTextNode('<?php echo _("Invalid Filename"); ?>');
        }
        empty('menu.recording');
        $('menu.recording').appendChild(text);

        $('menu_edit.name').value = menuInfo.get(curmenu).name;
        $('menu_edit.description').value = menuInfo.get(curmenu).description;

        var select = $('menu_edit.recording');
        empty(select);
        recordings.each(function (item) {
            var option = document.createElement('option');
            option.value = item.key;
            var text = document.createTextNode(item.value.filename);
            option.appendChild(text);
            select.appendChild(option);
        })
        select.value = menuInfo.get(curmenu).recording_id;

        // Fill in the actions
        if (menuInfo.get(curmenu).actions.length == 0) {
            // No actions configured yet.
            $('menuWorking').hide();
            $('dialpadWorking').hide();
            return true;
        }

        $H(menuInfo.get(curmenu).actions).each(function (pair) {
            var text;
            var digit = pair.key;
            var action = pair.value.action;
            var button = $('digit_' + digit);
            var p = document.createElement('p');
            p.className = 'buttonActionLabel';
            text = document.createTextNode(menuActions.get(action).description);
            p.appendChild(text);
            button.appendChild(p);

            p = document.createElement('p');
            p.className = 'buttonDetail';

            switch(action) {
            case 'jump':
                var menu = pair.value.args['menuName'];
                text = document.createTextNode(menu);
                break;
            case 'ringexten':
            case 'leave_message':
                var exten = pair.value.args['exten'];
                text = document.createTextNode(destinations.get(exten).name);
                break;
            case 'conference':
                var roomno = pair.value.args['roomno'];
                if (roomno != null) {
                    text = document.createTextNode(conferences.get(roomno).name);
                } else {
                    text = document.createTextNode('');
                }
                break;
            case 'dial':
                if (pair.value.args.numbers.length == 1) {
                    text = document.createTextNode(pair.value.args.numbers.first());
                } else if (pair.value.args.numbers.length > 1) {
                    text = document.createTextNode(pair.value.args.numbers.length +
                                                   ' <?php echo _("numbers"); ?>');
                } else {
                    // Technically, this is an error.
                    // There should always be a number to dial.
                    text = document.createTextNode('');
                }
                break;
            default:
                text = document.createTextNode('');
                break;
            }

            p.appendChild(text);
            button.appendChild(p);
        });
        $('menuWorking').hide();
        $('dialpadWorking').hide();
    } catch (e) {
        logException(e);
    }
}

function editMenu()
{
    Effect.BlindDown('editMenu');
}

function deleteMenu()
{
    $('menuWorking').show();
    var msg = '<?php echo _("You are about to delete the \"%menu%\" menu.  Click \"OK\" to delete or \"Cancel\" to keep the menu."); ?>';
    msg = msg.replace('%menu%', menuInfo.get(curmenu).name);
    if (confirm(msg)) {
        var params = {'menu': curmenu};
        new Ajax.Request(ajax_url + 'deleteMenu',
        {
            method: 'post',
            parameters: params,
            onSuccess: function(r) {
                new Ajax.Request(ajax_url + 'getMenus',
                {
                    method: 'get',
                    onSuccess: function(r) {
                        menuInfo = $H(r.responseJSON.response);
                        refreshMenu();
                    }
                });
            }
        });
    } else {
        $('menuWorking').hide();
    }
}

function saveMenuInfo()
{
    $('menuWorking').show();
    var params = $('editMenu').serialize(true);
    params.oldname = curmenu;
    new Ajax.Request(ajax_url + 'saveMenuInfo',
    {
        method: 'post',
        parameters: params,
        onSuccess: function(r) {
            Effect.BlindUp('editMenu');
            new Ajax.Request(ajax_url + 'getMenus',
            {
                method: 'get',
                onSuccess: function(r) {
                    menuInfo = $H(r.responseJSON.response);
                    refreshMenu();
                }
            });
        }
    });
}

function logException(e)
{
    new Ajax.Request(ajax_url + 'logException',
    {
        method: 'post',
        parameters: e
    });
}

$('editActionOverlay').hide();
$('editMenu').hide();
Event.observe($('editActionForm'), 'submit', function(event) {saveAction(event);});
new Ajax.Request(ajax_url + 'getMenus',
{
    method: 'get',
    onSuccess: function(r) {
        menuInfo = $H(r.responseJSON.response);
        refreshMenu();
    }
});
// -->
</script>
