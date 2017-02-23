<?php

namespace HeimrichHannot\Backend\Cleaner;

use HeimrichHannot\Haste\Dca\General;

class Cleaner extends \Controller
{
    public static function getFieldsAsOptions(\DataContainer $objDc)
    {
        if (!$objDc->activeRecord->dataContainer)
        {
            return [];
        }

        return General::getFields($objDc->activeRecord->dataContainer, false);
    }
}