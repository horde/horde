<div id="wizard">
<h3 onclick="showstep('welcome')">Welcome</h3>
<div id="welcome" class="step">
    <p>Here you will configure your new PBX.  Follow the simple instructions below, yada, yada.
    <p><span class="button" onclick="showstep('step1')">Continue to Step 1</span></p>
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
    <p><span class="button" onclick="showstep('step2')">Continue to Step 2</span></p>
</div>

<h3 onclick="showstep('step2')">Step 2: Create your first extension</h3>
<div id="step2" class="step">
    <?php require SHOUT_TEMPLATES . '/extensions.inc.php'; ?>
    <br />
    <br />
    <div>
        <span class="button" onclick="showstep('finished')">
            <?php echo Horde::img('save.png'); ?>
            Finished
        </span>
    </div><br/>
</div>

<h3 onclick="showstep('finished')" class="step">Finished!</h3>
<div id="finished" class="step">
    <p>Congratulations! Your new phone system is now configured and ready to use.</p>
    <p>For additional information on using these tools, we have the following resources available:</p>
    <ul>
        <li><a>Printable documentation</a></li>
        <li><a>Getting Started video</a></li>
    </ul>
    <br />
    <p><a class="button" href="dialplan.php">Continue to the control panel</a></p>
</div>
</div>
<script type="text/javascript">
<!--
// Show the extension add form by default
$('addExtension').show();
$('extensionsControls').hide();

// Hide everything but the instructions
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

// -->
</script>