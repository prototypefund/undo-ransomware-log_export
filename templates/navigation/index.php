<ul>
	<li class="active">
        <a href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('log_export.page.index', [])); ?>">
            <img alt="" src="<?php print_unescaped(\OC::$server->getURLGenerator()->imagePath('core', 'actions/history.svg')); ?>">
            <span>Sync</span>
        </a>
    </li>
    <li>
        <a href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('log_export.scan.download', [])); ?>">
            <img alt="" src="<?php print_unescaped(\OC::$server->getURLGenerator()->imagePath('core', 'actions/download.svg')); ?>">
            <span>Download file activity</span>
        </a>
    </li>
	<li>
        <a href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('log_export.page.download', [])); ?>">
            <img alt="" src="<?php print_unescaped(\OC::$server->getURLGenerator()->imagePath('core', 'actions/download.svg')); ?>">
            <span>Download sync history</span>
        </a>
    </li>
</ul>
