<table width="95%" border="0" cellpadding="0" cellspacing="0" class="item">
  <tr valign="bottom" class="header">
   <td colspan="2"><font size="3">Context: <?php echo $context; ?></font></td>
  </tr>
  <tr class="body"><td colspan="2">&nbsp;</td></tr>
  <tr class="body"><td colspan="2" align="center">
  <table width="95%" border="0" cellpadding="0" cellspacing="0" class="item">
  <?php
    $line = 0;
    $dialplan = $shout->getDialplan($context);
    if (isset($dialplan['extensions']) &&
        (count($dialplan['extensions']) > 0)) {
        foreach ($dialplan['extensions'] as $extension => $priorities) {
            unset($extname);
            # Cast as a string to avoid misinterpreted digits
            switch ((string)$extension) {
                case "h":
                    $extname = "Hangup";
                    break;
                case "i":
                    $extname = "Invalid";
                    break;
                case "t":
                    $extname = "Timeout";
                    break;
                case "s":
                    $extname = "Start";
                    break;
                case "o":
                    $extname = "Operator";
                    break;
                default:
                    break;
            }
            ?>
            <tr class="header">
              <th colspan="2" class="header">
                <?php
                if (isset($extname)) {
                    echo $extname;
                } else {
                    echo "Extension $extension";
                }
                ?>
              </th>
            </tr>
            <tr class="smallheader">
              <th align="left">Priority</th>
              <th align="left">Application</th>
            </tr>
            <?php
            foreach ($priorities as $priority => $application) {
                $rowcolor = $line % 2;
                $line++;
                ?>
                <tr class="item<?php echo $rowcolor; ?>">
                    <td align="center"><?php echo $priority; ?></td>
                    <td align="left"><?php echo $application; ?></td>
                </tr>
                <?php
            }
            ?>
            <tr class="body"><td colspan="2">&nbsp;</td></tr>
            <?php
        }
    }
  ?>
  </table>
  </td></tr>
</>