<?php

/**
 * Backend modules
 */
$GLOBALS['BE_MOD']['devtools']['cleaner'] = [
    'tables' => ['tl_cleaner'],
    'icon'   => 'system/modules/cleaner/assets/img/icon.png'
];

/**
 * Crons
 */
$GLOBALS['TL_CRON']['minutely']['runMinutelyCleaner'] = ['HeimrichHannot\Cleaner\Cleaner', 'runMinutely'];
$GLOBALS['TL_CRON']['hourly']['runHourlyCleaner']     = ['HeimrichHannot\Cleaner\Cleaner', 'runHourly'];
$GLOBALS['TL_CRON']['daily']['runDailyCleaner']       = ['HeimrichHannot\Cleaner\Cleaner', 'runDaily'];
$GLOBALS['TL_CRON']['weekly']['runWeeklyCleaner']     = ['HeimrichHannot\Cleaner\Cleaner', 'runWeekly'];

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_cleaner'] = 'HeimrichHannot\Cleaner\CleanerModel';