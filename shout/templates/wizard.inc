<div id="wizard">
<form id="wizardForm" name="wizard" action="#">
<h3 onclick="showstep('welcome')">Welcome</h3>
<div id="welcome" class="step">
    <p>Here you will configure your new PBX.  Follow the simple instructions below, yada, yada.
    <p><input type="submit" class="button" name="step1" onclick="showstep('step1')" value="Continue to Step 1">
</div>

<h3 onclick="showstep('step1')">Step 1: Create your company greeting</h3>
<div id="step1" class="step">
    <ol>
        <li>1: Call your new number: FIXME</li>
        <li>2: When you hear the default greeting, press the star key, "<span class="attention">*</span>".</li>
        <li>3: When prompted, enter your admin PIN: <span class="attention"><?php echo $curaccount['adminpin']; ?></span>.</li>
        <li>4: Press <span class="attention">1</span> to manage recordings.</li>
        <li>5: Enter the recording ID for your new menu: <span class="attention"><?php echo $recording['id']; ?></span><br>
        Follow the prompts to create and save your new recording.</li>
    </ol>
    <p>When finished, hang up.</p>
    <input type="submit" class="button" name="step2" value="Continue to Step 2" />
</div>

<h3 onclick="showstep('step2')">Step 2: Create your first extension</h3>
<div id="step2" class="step">
    <?php require SHOUT_TEMPLATES . '/extensions/list.inc'; ?>
    <input type="submit" class="button" name="finished" value="Finished" />
</div>

<h3 onclick="showstep('finished')">Finished!</h3>
<div id="finished" class="step">
    <p>Congratulations! Your new Verendus phone system is now configured and ready to use.</p>
    <p>For additional information on using these tools, we have the following resources available:</p>
    <ul>
        <li><a>Printable documentation</a></li>
        <li><a>Getting Started video</a></li>
    </ul>
    <br />
    <input type="submit" class="button" name="continue" value="Continue to the control panel" />
</div>
</form>
</div>
<script type="text/javascript">
<!--
$('step1').hide();
$('step2').hide();
$('finished').hide();
var curblock = 'welcome';
function showstep(e)
{
    if (e == curblock) {
        return true;
    }

    if (curblock != null) {
        Effect.BlindUp(curblock)
    }
    
    curblock = e;
    Effect.BlindDown(curblock);
}

function handleClick(event)
{
    event.stop();
    var clicked = event.explicitOriginalTarget.name;

    switch(clicked) {
    case 'addExtension':
        alert('Adding an extension');
        break;

    case 'welcome':
    case 'step1':
    case 'step2':
    case 'finished':
        showstep(clicked);
        break;

    default:
        alert("Unhandled button: " + clicked);
    }
}

Event.observe($('wizard').down('form'), 'submit', function(event) {handleClick(event);})
// -->
</script>