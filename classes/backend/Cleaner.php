<?php

namespace HeimrichHannot\Cleaner\Backend;

use Contao\DataContainer;
use HeimrichHannot\FormHybrid\Backend\Module;
use HeimrichHannot\Haste\Dca\General;

class Cleaner extends \Controller
{
    public static function getFieldsAsOptions(DataContainer $objDc)
    {
        if (!$objDc->activeRecord->dataContainer)
        {
            return [];
        }

        return General::getFields($objDc->activeRecord->dataContainer, false);
    }
    
    /**
     * get tables
     *
     * @param DataContainer $dc
     *
     * @return array
     */
    public static function getTables(DataContainer $dc)
    {
        return Module::getDataContainers($dc);
    }
    
    /**
     * get fields from dependent table
     *
     * @param DataContainer $dc
     *
     * @return array
     */
    public static function getDependentFields(DataContainer $dc)
    {
        if(!$dc->activeRecord->dependentTable)
        {
            return [];
        }
        
        return General::getFields($dc->activeRecord->dependentTable);
    }
}