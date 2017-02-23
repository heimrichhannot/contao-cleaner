<?php

namespace HeimrichHannot\Cleaner;

use HeimrichHannot\Haste\Util\Files;

class Cleaner extends \Controller
{
    const TYPE_ENTITY = 'entity';
    const TYPE_FILE   = 'file';

    const TYPES = [
        self::TYPE_ENTITY,
        self::TYPE_FILE
    ];

    const FILEDIR_RETRIEVAL_MODE_ENTITY_FIELDS = 'entityFields';
    const FILEDIR_RETRIEVAL_MODE_DIRECTORY = 'directory';

    const FILEDIR_RETRIEVAL_MODES = [
        self::FILEDIR_RETRIEVAL_MODE_ENTITY_FIELDS,
        self::FILEDIR_RETRIEVAL_MODE_DIRECTORY
    ];

    public static function runMinutely()
    {
        static::run('minutely');
    }

    public static function runHourly()
    {
        static::run('hourly');
    }

    public static function runWeekly()
    {
        static::run('weekly');
    }

    public static function runDaily()
    {
        static::run('daily');
    }

    public static function run($strPeriod)
    {
        $arrOrder   = deserialize(\Config::get('cleanerOrder'), true);
        $arrOptions = [];

        if (count($arrOrder) > 0)
        {
            $arrOptions = [
                'order' => 'FIELD(id,' . implode(',', $arrOrder) . ')'
            ];
        }

        if (($objCleaners = CleanerModel::findBy(['published=?', 'period=?'], [true, $strPeriod], $arrOptions)) !== null)
        {
            while ($objCleaners->next())
            {
                switch ($objCleaners->type)
                {
                    case static::TYPE_ENTITY:
                        if (!$objCleaners->whereCondition)
                        {
                            continue 2;
                        }

                        $strQuery = "DELETE FROM $objCleaners->dataContainer WHERE ($objCleaners->whereCondition)";

                        if ($objCleaners->addMaxAge)
                        {
                            $strQuery .= static::getMaxAgeCondition($objCleaners);
                        }

                        \Database::getInstance()->execute(html_entity_decode($strQuery));

                        break;
                    case static::TYPE_FILE:
                        switch ($objCleaners->fileDirRetrievalMode)
                        {
                            case static::FILEDIR_RETRIEVAL_MODE_DIRECTORY:
                                $strPath = Files::getPathFromUuid($objCleaners->directory);

                                $objFolder = new \Folder($strPath);

                                $objFolder->purge();

                                if ($objCleaners->addGitKeepAfterClean)
                                {
                                    touch(TL_ROOT . '/' . $strPath . '/.gitkeep');
                                }

                                break;
                            case static::FILEDIR_RETRIEVAL_MODE_ENTITY_FIELDS:
                                if (!$objCleaners->whereCondition)
                                {
                                    continue 2;
                                }

                                $arrFields = deserialize($objCleaners->entityFields, true);

                                if (empty($arrFields))
                                {
                                    continue 2;
                                }

                                $strQuery = "SELECT * FROM $objCleaners->dataContainer WHERE ($objCleaners->whereCondition)";

                                if ($objCleaners->addMaxAge)
                                {
                                    $strQuery .= static::getMaxAgeCondition($objCleaners);
                                }

                                $objInstances = \Database::getInstance()->execute(html_entity_decode($strQuery));

                                if ($objInstances->numRows > 0)
                                {
                                    while ($objInstances->next())
                                    {
                                        foreach ($arrFields as $strField)
                                        {
                                            if (!$objInstances->{$strField})
                                            {
                                                continue;
                                            }

                                            // deserialize if necessary
                                            $varValue = deserialize($objInstances->{$strField});

                                            if (!is_array($varValue))
                                            {
                                                $varValue = [$varValue];
                                            }

                                            foreach ($varValue as $strFile)
                                            {
                                                if (($objFile = Files::getFileFromUuid($strFile, true)) === null)
                                                {
                                                    continue;
                                                }

                                                $objFile->delete();
                                            }
                                        }
                                    }
                                }
                                break;
                        }

                        break;
                }
            }
        }
    }

    public static function getMaxAgeCondition($objCleaner)
    {
        $arrMaxAge = deserialize($objCleaner->maxAge, true);

        $intFactor = 1;
        switch ($arrMaxAge['unit'])
        {
            case 'm':
                $intFactor = 60;
                break;
            case 'h':
                $intFactor = 60 * 60;
                break;
            case 'd':
                $intFactor = 24 * 60 * 60;
                break;
        }

        $intMaxInterval = $arrMaxAge['value'] * $intFactor;

        return " AND (UNIX_TIMESTAMP() > $objCleaner->dataContainer.$objCleaner->maxAgeField + $intMaxInterval)";
    }
}