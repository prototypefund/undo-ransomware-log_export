<table>
    <thead>
        <tr>
            <th>Path</th>
            <th>Original Name</th>
            <th>New Name</th>
            <th>Type</th>
            <th>Mime Type</th>
            <th>Size</th>
            <th>Command</th>
            <th>Entropy</th>
            <th>Standard Deviation</th>
            <th>Timestamp</th>
        </tr>
    </thead>
    <tbody>
        <?php
            foreach($_['fileOperations'] as $key => $fileOperation) {
        ?>
        <tr>
            <td>
                <?php echo $fileOperation->getPath(); ?>
            </td>
            <td>
                <?php echo $fileOperation->getOriginalName(); ?>
            </td>
            <td>
                <?php echo $fileOperation->getNewName(); ?>
            </td>
            <td>
                <?php echo $fileOperation->getType(); ?>
            </td>
            <td>
                <?php echo $fileOperation->getMimeType(); ?>
            </td>
            <td>
                <?php echo $fileOperation->getSize(); ?>
            </td>
            <td>
                <?php echo $fileOperation->getCommand(); ?>
            </td>
            <td>
                <?php echo $fileOperation->getEntropy(); ?>
            </td>
            <td>
                <?php echo $fileOperation->getStandardDeviation(); ?>
            </td>
            <td>
                <?php echo $fileOperation->getTimestamp(); ?>
            </td>
        </tr>
        <?php
            }
        ?>
    </tbody>
</table>