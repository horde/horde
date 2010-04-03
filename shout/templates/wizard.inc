<div id="wizard">
<div class="step">
    <h3 onclick="showstep('welcome')">Welcome</h3>
    <div id="welcome">
        <p>Here you will configure your new PBX.  Follow the simple instructions below, yada, yada.
        <p><span class="button" onclick="showstep('step1')">Continue to Step 1</span></p>
    </div>
</div>

<div class="step">
    <h3 onclick="showstep('step1')">Step 1: Create your company greeting</h3>
    <div id="step1">
        <ol>
            <li>Call your new number: <span class="attention"><?php echo $number; ?></span></li>
            <li>When you hear the default greeting, press the star key, "<span class="attention">*</span>".</li>
            <li>When prompted, enter your admin PIN: <span class="attention"><?php echo $curaccount['adminpin']; ?></span>.</li>
            <li>Press <span class="attention">1</span> to manage recordings.</li>
            <li>Enter the recording ID for your new menu: <span class="attention"><?php echo $recording['id']; ?></span><br>
            Follow the prompts to create and save your new recording.</li>
        </ol>
        <p>When finished, hang up.</p>
        <p><span class="button" onclick="showstep('step2')">Continue to Step 2</span></p>
    </div>
</div>

<div class="step">
    <h3 onclick="showstep('step2')">Step 2: Create your first extension</h3>
    <div id="step2">
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
</div>

<div class="step">
<h3 onclick="showstep('finished')">Finished!</h3>
    <div id="finished">
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