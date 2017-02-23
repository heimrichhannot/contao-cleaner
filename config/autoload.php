<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'HeimrichHannot',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Classes
	'HeimrichHannot\Cleaner\Cleaner'         => 'system/modules/cleaner/classes/Cleaner.php',
	'HeimrichHannot\Backend\Cleaner\Cleaner' => 'system/modules/cleaner/classes/backend/Cleaner.php',

	// Models
	'HeimrichHannot\Cleaner\CleanerModel'    => 'system/modules/cleaner/models/CleanerModel.php',
));
