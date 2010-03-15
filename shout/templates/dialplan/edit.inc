<div id="dialplan">
    <table id="menuInfo" class="striped">
    </table>

    <div id="digitpad">
      <div id="editActionOverlay">
          <img id="digitGraphic" alt="Current Digit">
          <div id="selectAction"></div>
          <div id="editAction"></div>
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
      <br style="clear:both;">
      <div id="defaultAction" onclick="editAction('defaultAction');"><span class="digitLabel">Default Action</span></div>
    </div>
</div>

<script type="text/javascript">
<!--
var ajax_url = '<?php echo Horde::getServiceLink('ajax', 'shout') ?>';
var menu = '<?php echo $menu['name']; ?>';
var menuInfo = $H();
var menuActions = $H(<?php echo Horde_Serialize::serialize(Shout::getMenuActions(), Horde_Serialize::JSON, Horde_Nls::getCharset()); ?>);
var destinations = $H(<?php echo Horde_Serialize::serialize($destinations, Horde_Serialize::JSON, Horde_Nls::getCharset()); ?>);

function editAction(digit)
{
    if ($('selectActionForm') == null) {
        $('digitGraphic').src = '<?php echo Horde_Themes::img(null, array('notheme' => true, 'nohorde' => true)); ?>/digit-'+digit+'.png';

        // Draw the selectActionForm
        $('editActionOverlay').show();
        empty('selectAction');
        var p = document.createElement('p');
        p.id = 'actionPrompt';
        var text = document.createTextNode('<?php echo _("Action:"); ?>');
        p.appendChild(text);
        $('selectAction').appendChild(p);
        var form = document.createElement('form');
        form.id = 'selectActionForm';
        form.setAttribute('onChange', 'editAction('+digit+')');
        var select = document.createElement('select');
        select.name = 'action';
        var option = document.createElement('option');
        option.value = '';
        var text = document.createTextNode('<?php echo _("-- Select Action --"); ?>');
        option.appendChild(text);
        select.appendChild(option);
        menuActions.each(function (item) {
            option = document.createElement('option');
            option.value = item.key;
            if (menuInfo.get('actions')[digit] != null) {
                if (item.key == menuInfo.get('actions')[digit].action) {
                    option.selected = "true";
                }
            }
            var text = document.createTextNode(item.value.description);
            option.appendChild(text);
            select.appendChild(option);
        });
        form.appendChild(select);
        $('selectAction').appendChild(form);

        // Cancel button
        var span = document.createElement('span');
        span.id = 'cancelAction';
        span.setAttribute('onclick', 'cancelEdit()');
        var text = document.createTextNode('<?php echo _("Cancel"); ?>');
        span.appendChild(text);
        $('selectAction').appendChild(span);
    }

    if ($('selectActionForm').getElements().first().getValue() != '') {
        var action = null;
        // Draw the options for this action
        $('selectActionForm').getElements().each(function (e){
            if (e.name == 'action') {
                action = e.value;
            }
        });
        var params = $H({'action': action});
        new Ajax.Updater('editAction', ajax_url + 'getActionForm',
        {
            method: 'post',
            parameters: params,
            onComplete: function() {
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'digit';
                hidden.value = digit;
                $('editActionForm').appendChild(hidden);
            }
        });
    }
}

function saveAction(digit)
{
    var params = $('editActionForm').serialize(true);
    params.menu = menuInfo.get('meta').name;
    new Ajax.Request(ajax_url + 'saveAction',
    {
        method: 'post',
        parameters: params,
        onSuccess: function(r) {
            alert('FIXME');
            empty('selectAction');
            empty('editAction');
            $('editActionOverlay').hide();
            new Ajax.Request(ajax_url + 'getMenuInfo',
            {
                method: 'post',
                parameters: $H({
                    'menu': menu
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

function changeSoundfile()
{
    alert("Changing soundfile");
}

function playSoundfile()
{
    alert("Playing soundfile");
}

function refreshMenu()
{
    var row;
    var col;
    var img;
    var text;
    empty('menuInfo');

    var meta = menuInfo.get('meta');

    // Draw the menu metainfo box
    row = getMetaRow('<?php echo _("Menu Name"); ?>', meta.name);
    $('menuInfo').appendChild(row);
    row = getMetaRow('<?php echo _("Description"); ?>', meta.description);
    $('menuInfo').appendChild(row);

    // Handle the soundfile row specially
    row = document.createElement('tr');
    col = document.createElement('td');
    col.className = 'menuStatName';
    text = document.createTextNode('<?php echo _("Sound file"); ?>');
    col.appendChild(text);
    row.appendChild(col);
    col = document.createElement('td');
    col.className='menuStatValue';
    text = document.createTextNode(meta.soundfile);
    col.appendChild(text);
    img = document.createElement('img');
    img.src = '<?php echo Horde_Themes::img('edit.png') ?>';
    img.alt = '<?php echo _("Change Soundfile"); ?>';
    img.setAttribute('onclick', 'changeSoundfile()');
    col.appendChild(img);
    img = document.createElement('img');
    img.src = '<?php echo Horde_Themes::img('recordings.png') ?>';
    img.alt = '<?php echo _("Play Soundfile"); ?>';
    img.setAttribute('onclick', 'playSoundfile()');
    img.setAttribute("style", img.getAttribute("style") + "; float:right; ");
    col.appendChild(img);
    row.appendChild(col);
    $('menuInfo').appendChild(row);

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
            text = document.createTextNode(destinations.get(exten).name);
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
}

function getMetaRow(name, value)
{
    var row;
    var col;
    var text;
    row = document.createElement('tr');
    col = document.createElement('td');
    col.className = 'menuStatName';
    text = document.createTextNode(name);
    col.appendChild(text);
    row.appendChild(col);

    col = document.createElement('td');
    col.className='menuStatValue';
    text = document.createTextNode(value);
    col.appendChild(text);
    row.appendChild(col);

    return row;
}

function empty(p)
{
    while ((e = $(p).childNodes[0]) != null) {
        $(p).removeChild(e);
    }
}

$('editActionOverlay').hide();
new Ajax.Request(ajax_url + 'getMenuInfo',
{
    method: 'post',
    parameters: $H({
        'menu': menu
    }),
    onSuccess: function(r) {
        menuInfo = $H(r.responseJSON.response);
        refreshMenu();
    }
});
// -->
</script>
