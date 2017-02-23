<?php

$arrDca = &$GLOBALS['TL_DCA']['tl_settings'];

/**
 * Palettes
 */
$arrDca['palettes']['default'] .= '{cleaner_legend},cleanerOrder;';

/**
 * Fields
 */
$arrDca['fields']['cleanerOrder'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_settings']['cleanerOrder'],
    'exclude'   => true,
    'inputType' => 'checkboxWizard',
    'foreignKey' => 'tl_cleaner.title',
    'eval'      => ['tl_class' => 'w50 clr', 'multiple' => true]
];