<table id="menuInfo" class="striped">
</table>

<div id="digitpad">
  <div id="digitAction">
      <div onClick="saveAction('x');">SAVE</div>
  </div>
  <div class="digit" onClick="editAction('1');"><span class="digitLabel">1</span></div>
  <div class="digit" onClick="editAction('2');"><span class="digitLabel">2</span></div>
  <div class="digit" onClick="editAction('3');"><span class="digitLabel">3</span></div>
  <br style="clear:both;">
  <div class="digit" onClick="editAction('4');"><span class="digitLabel">4</span></div>
  <div class="digit" onClick="editAction('5');"><span class="digitLabel">5</span></div>
  <div class="digit" onClick="editAction('6');"><span class="digitLabel">6</span></div>
  <br style="clear:both;">
  <div class="digit" onClick="editAction('7');"><span class="digitLabel">7</span></div>
  <div class="digit" onClick="editAction('8');"><span class="digitLabel">8</span></div>
  <div class="digit" onClick="editAction('9');"><span class="digitLabel">9</span></div>
  <br style="clear:both;">
  <div class="digit" onClick="editAction('*');"><span class="digitLabel">*</span></div>
  <div class="digit" onClick="editAction('0');"><span class="digitLabel">0</span></div>
  <div class="digit" onClick="editAction('#');"><span class="digitLabel">#</span></div>
</div>

<script type="text/javascript">
<!--
var ajax_url = '<?php echo Horde::getServiceLink('ajax', 'shout') ?>';
var menu = '<?php echo $menu['name']; ?>';
var menuInfo = $H();

function editAction(digit)
{
    $('digitAction').show();
}

function saveAction(digit)
{
    $('digitAction').hide();
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
    while ((e = $('menuInfo').childNodes[0]) != null) {
        $('menuInfo').removeChild(e);
    }

    var meta = menuInfo.get('meta');

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
    img.src = '<?php echo $registry->getImageDir('shout') . '/edit.png'; ?>';
    img.alt = '<?php echo _("Change Soundfile"); ?>';
    img.setAttribute('onclick', 'changeSoundfile()');
    col.appendChild(img);
    img = document.createElement('img');
    img.src = '<?php echo $registry->getImageDir('shout') . '/recordings.png'; ?>';
    img.alt = '<?php echo _("Play Soundfile"); ?>';
    img.setAttribute('onclick', 'playSoundfile()');
    img.setAttribute("style", img.getAttribute("style") + "; float:right; ");
    col.appendChild(img);
    row.appendChild(col);
    $('menuInfo').appendChild(row);

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

$('digitAction').hide();
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