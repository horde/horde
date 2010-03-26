<div id="dialplan">
    <div id="menuInfo">
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
                <td class="metaStatName">Sound File</td>
                <td class="metaStatInfo"><span id="menu.soundfile"></span></td>
            </tr>
        </table>
        <form id="editMenu">
        <table class="striped">
            <tr>
                <td class="metaStatName">Menu Name</td>
                <td class="metaStatInfo"><input name="name"></td>
            </tr>
            <tr>
                <td class="metaStatName">Description</td>
                <td class="metaStatInfo"><input name="description"></td>
            </tr>
            <tr>
                <td class="metaStatName">Sound File</td>
                <td class="metaStatInfo"><select name="soundfile"></select></td>
            </tr>
            <tr>
                <td><div class="button" onclick="saveMenu()"><?php echo _("Save"); ?></div></td>
                <td><div class="button" onclick="$('editMenu').hide()"><?php echo _("Cancel"); ?></div></td>
            </tr>
        </table>
        </form>
        <br style="clear:both;">
        <ul id="controls">
        <?php
        $addurl = Horde::applicationUrl('dialplan.php');
        $addurl = Horde_Util::addParameter($addurl, 'action', 'add');
        ?>
        <a href="<?php echo $addurl; ?>">
        <li class="button">
            <?php echo Horde::img('menu-add.png'); ?>&nbsp;New Menu
        </li>
        </a>
        <li class="button" onclick="editMenu()"><?php echo Horde::img('menu-edit.png'); ?>&nbsp;Edit Menu</li>
        <li class="button" onclick="deleteMenu()"><?php echo Horde::img('menu-delete.png');?>&nbsp;Delete Menu</li>
        </ul>
    </div>

    <div id="digitpad">
      <div id="editActionOverlay">
          <img id="digitGraphic" alt="Current Digit">
          <form id="editActionForm">
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
      <div class="digit" id="digit_*" onClick="editAction('star');"><span class="digitLabel">*</span></div>
      <div class="digit" id="digit_0" onClick="editAction('0');"><span class="digitLabel">0</span></div>
      <div class="digit" id="digit_#" onClick="editAction('octothorpe');"><span class="digitLabel">#</span></div>
    </div>
</div>

<script type="text/javascript">
<!--
// TODO: Convert to object
var ajax_url = '<?php echo Horde::getServiceLink('ajax', 'shout') ?>';
var curmenu = null;
var menuInfo = $H();
var menuActions = $H(<?php echo Horde_Serialize::serialize(Shout::getMenuActions(), Horde_Serialize::JSON, Horde_Nls::getCharset()); ?>);
var destinations = $H(<?php echo Horde_Serialize::serialize($destinations, Horde_Serialize::JSON, Horde_Nls::getCharset()); ?>);
var soundfiles = $H(<?php echo Horde_Serialize::serialize($soundfiles, Horde_Serialize::JSON, Horde_Nls::getCharset()); ?>);

function empty(p)
{
    while ((e = $(p).childNodes[0]) != null) {
        $(p).removeChild(e);
    }
}

function editAction(digit)
{
    empty('editAction');

    if ($('selectAction').firstChild == null) {
        $('digitGraphic').src = '<?php echo Horde_Themes::img(null, array('notheme' => true, 'nohorde' => true)); ?>/digit-'+digit+'.png';

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
        select.setAttribute('onChange', 'editAction('+digit+')');
        var option = document.createElement('option');
        option.value = '';
        var text = document.createTextNode('<?php echo _("-- Select Action --"); ?>');
        option.appendChild(text);
        select.appendChild(option);
        menuActions.each(function (item) {
            option = document.createElement('option');
            option.value = item.key;
            if (menuInfo.get(curmenu).actions[digit] != null) {
                if (item.key == menuInfo.get('actions')[digit].action) {
                    option.selected = "true";
                }
            }
            var text = document.createTextNode(item.value.description);
            option.appendChild(text);
            select.appendChild(option);
        });
        var option = document.createElement('option');
        option.value = 'none';
        var text = document.createTextNode('<?php echo _("No action"); ?>');
        option.appendChild(text);
        select.appendChild(option);
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
        var action = $('editActionForm').down('select').getValue();
        var div = document.createElement('div');
        switch(action) {
        case 'jump':
            var span = document.createElement('span');
            var text = document.createTextNode('<?php echo _("Menu:"); ?>');
            span.appendChild(text);
            div.appendChild(span);

            var select = document.createElement('select');
            select.name = 'menuName';
            menuInfo.each(function (item) {
                var option = document.createElement('option');
                option.value = item.key;
                var text = document.createTextNode(item.value.name);
                option.appendChild(text);
                select.appendChild(option);
            })
            div.appendChild(select);
            break;
        case 'ringexten':
        case 'leave_message':
            var span = document.createElement('span');
            var text;
            if (action == 'ringexten') {
                text = document.createTextNode('<?php echo _("Extension:"); ?>');
            } else {
                text = document.createTextNode('<?php echo _("Mailbox:"); ?>');
            }
            span.appendChild(text);
            div.appendChild(span);

            var select = document.createElement('select');
            select.name = 'exten';
            destinations.each(function (item) {
                var option = document.createElement('option');
                option.value = item.key;
                var text = document.createTextNode(item.value.name);
                option.appendChild(text);
                select.appendChild(option);
            })
            div.appendChild(select);
            break;
        case 'conference':
            alert(action);
            break;
        case 'dial':
            alert(action);
            break;
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
}

function saveAction(digit)
{
    var params = $('editActionForm').serialize(true);
    params.menu = menuInfo.get('meta').name;
    params.action = $('editActionForm').getElements().first().getValue();
    new Ajax.Request(ajax_url + 'saveAction',
    {
        method: 'post',
        parameters: params,
        onSuccess: function(r) {
            empty('selectAction');
            empty('editAction');
            $('editActionOverlay').hide();
            new Ajax.Request(ajax_url + 'getMenuInfo',
            {
                method: 'post',
                parameters: $H({
                    'menu': curmenu
                }),
                onSuccess: function(r) {
                    menuInfo = $H(r.responseJSON.response);
                    refreshMenu();
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

function saveSoundFile(event)
{
    Event.stop(event);
    var form = event.target;
    Element.extend(form);
    var file = form.getElements().first().getValue();
    // TODO: Allow editing other menu details
    // TODO: Add spinner
    new Ajax.Request(ajax_url + 'saveMenuInfo',
    {
        method: 'post',
        parameters: $H({
            'file': file
        }),
        onSuccess: function(r) {
            refreshMenu();
            // TODO: Stop spinner
        }
    });
}

function playSoundFile()
{
    alert("Playing soundfile");
}

function refreshMenu()
{
    curmenu = $('menu.select').value;
    if (!curmenu || !menuInfo.get(curmenu)) {
        // TODO Show an error of some kind
        return false;
    }

    var text = document.createTextNode(menuInfo.get(curmenu).description);
    empty('menu.description');
    $('menu.description').appendChild(text);

    var text = document.createTextNode(menuInfo.get(curmenu).soundfile);
    empty('menu.soundfile');
    $('menu.soundfile').appendChild(text);

    // Fill in the actions
    $H(menuInfo.get('actions')).each(function (pair) {
        var text;
        var digit = pair.key;
        var action = pair.value.action;
        var button = $('digit_'+digit);
        empty(button);
        var p = document.createElement('p');
        p.className = 'buttonActionLabel';
        var span = document.createElement('span');
        span.className='digitLabel';
        if (digit == 'star') {
            text = document.createTextNode('*');
        } else if (digit == 'octothorpe') {
            text = document.createTextNode('#');
        } else {
            text = document.createTextNode(digit);
        }
        span.appendChild(text);
        p.appendChild(span);
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
                text = document.createTextNode(roomno);
            } else {
                text = document.createTextNode('');
            }
            break;
        case 'dial':
            if (pair.value.args.length > 1) {
                text = document.createTextNode(pair.value.args.length + ' <?php echo _("numbers"); ?>');
            } else {
                text = document.createTextNode(pair.value.args.first().number);
            }
            break;
        default:
            text = document.createTextNode('');
            break;
        }

        p.appendChild(text);
        button.appendChild(p);
    });

    var select = $('editMenu').down('select');
    empty(select);
    soundfiles.each(function (item) {
        var option = document.createElement('option');
        option.value = item.key;
        var text = document.createTextNode(item.value.name);
        option.appendChild(text);
        select.appendChild(option);
    })
}

function editMenu()
{
    $('editMenu').show();
}

$('editActionOverlay').hide();
$('editMenu').hide();
new Ajax.Request(ajax_url + 'getMenus',
{
    method: 'get',
    onSuccess: function(r) {
        menuInfo = $H(r.responseJSON.response);
        menuInfo.each(function (item) {
            var option = document.createElement('option');
            option.value = item.key;
            var text = document.createTextNode(item.value.name);
            option.appendChild(text);
            $('menu.select').appendChild(option);
        })
        refreshMenu();
        Event.observe($('editMenu'), 'submit', function(event) {saveSoundFile(event);});
    }
});
// -->
</script>
