<table width="95%" border="0" cellpadding="0" cellspacing="0" class="header">
  <tr valign="bottom">
    <td width="61"><div align="center"><font size="1">
      &nbsp;
    </td>
    <td width="148"><div align="center"></div></td>
    <td width="332">&nbsp;</td>
    <td width="202"><font size="3">Context: <?php echo $context; ?></font></td>
  </tr>
</table>
<table class="item">
    <tbody>
        <?php
        $line = 0;
        $dialplan = $shout->getDialplan($context);
        if (isset($dialplan['extensions']) &&
            (count($dialplan['extensions']) > 0)) {
            foreach ($dialplan['extensions'] as $extension => $priorities) {
                foreach ($priorities as $priority => $application) {
                    $rowcolor = $line % 2;
                    $line++;
        ?>
        <tr style="item<?php echo $rowcolor; ?>">
            <td><?php echo $extension; ?></td>
            <td><?php echo $priority;
                      ?></td>
            <td>
                <?php echo $application; ?>
            </td>
        </tr>
        <?php
                }
            }
        }
        ?>
    </tbody>
</table>