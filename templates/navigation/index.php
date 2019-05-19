<ul>
	<li class="active">
        <a href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('log_export.page.index', [])); ?>">
            <img alt="" src="<?php print_unescaped(\OC::$server->getURLGenerator()->imagePath('core', 'actions/history.svg')); ?>">
            <span>Sync history</span>
        </a>
    </li>
	<li>
        <a href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('log_export.page.download', [])); ?>">
            <img alt="" src="<?php print_unescaped(\OC::$server->getURLGenerator()->imagePath('core', 'actions/download.svg')); ?>">
            <span>Download</span>
        </a>
    </li>
</ul>
