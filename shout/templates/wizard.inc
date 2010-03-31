<div id="step1" class="step">
    <h2>Step 1: Create your company greeting.</h2>
    <ol>
        <li>1: Call your new Verendus number: 404-475-4840</li>
        <li>2: When you hear the default greeting, press the star key, "<span class="attention">*</span>".</li>
        <li>3: When prompted, enter your admin PIN: <span class="attention"><?php echo $curaccount['adminpin']; ?></span>.</li>
        <li>4: Press <span class="attention">1</span> to manage recordings.</li>
        <li>5: Enter the recording ID for your new menu: <span class="attention"><?php echo $recording['id']; ?></span><br>
        Follow the prompts to create and save your new recording.</li>
    </ol>
    When finished, hang up.
    <input type="cancel" onclick="shownext()" value="Continue to Step 2">
</div>

<div id="step2" class="step">
    Here we create an extension.
</div>