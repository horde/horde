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