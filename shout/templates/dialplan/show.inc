<script type="text/javascript">
<!--
function editAction(digit)
{
    $('digitAction').show();
}

function saveAction(digit)
{
    $('digitAction').hide();
}
// -->
</script>

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
$('digitAction').hide();
// -->
</script>