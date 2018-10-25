<?php

namespace HeimrichHannot\Cleaner;

use Contao\Database;
use HeimrichHannot\Haste\Dca\General;
use HeimrichHannot\Haste\Util\Files;

class Cleaner extends \Controller
{
    const TYPE_ENTITY           = 'entity';
    const TYPE_DEPENDENT_ENTITY = 'dependent_entity';
    const TYPE_FILE             = 'file';
    
    const TYPES = [
        self::TYPE_ENTITY,
        self::TYPE_DEPENDENT_ENTITY,
        self::TYPE_FILE
    ];
    
    const FILEDIR_RETRIEVAL_MODE_ENTITY_FIELDS = 'entityFields';
    const FILEDIR_RETRIEVAL_MODE_DIRECTORY     = 'directory';
    
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
        $db         = Database::getInstance();
        
        if (count($arrOrder) > 0) {
            $arrOptions = [
                'order' => 'FIELD(id,' . implode(',', $arrOrder) . ')'
            ];
        }
        
        
        if (($objCleaners = CleanerModel::findBy(['published=?', 'period=?'], [true, $strPeriod], $arrOptions)) !== null) {
            foreach ($objCleaners as $cleaner) {
                switch ($cleaner->type) {
                    case static::TYPE_ENTITY:
                        if (!$cleaner->whereCondition) {
                            continue 2;
                        }
                        
                        $strQuery = "SELECT * FROM $cleaner->dataContainer WHERE ($cleaner->whereCondition)";
                        
                        if ($cleaner->addMaxAge) {
                            $strQuery .= static::getMaxAgeCondition($cleaner->dataContainer, $cleaner->maxAgeField, $cleaner->maxAge);
                        }
                        
                        $result = $db->execute(html_entity_decode($strQuery));
                        
                        if (0 == $result->numRows) {
                            continue;
                        }
                        
                        while($result->next())
                        {
                            static::cleanEntity($result, $cleaner);
                        }
                        
                        break;
                    case static::TYPE_DEPENDENT_ENTITY:
                        if (!$cleaner->whereCondition) {
                            continue 2;
                        }
                        
                        $query = "SELECT * FROM $cleaner->dependentTable WHERE $cleaner->whereCondition";
                        
                        if ($cleaner->addMaxAge) {
                            $query .= static::getMaxAgeCondition($cleaner->dependentTable, $cleaner->maxAgeField, $cleaner->maxAge);
                        }
                        
                        $dependenceEntities = $db->execute(html_entity_decode($query));
                        
                        if (0 == $dependenceEntities->numRows) {
                            continue;
                        }
                        
                        $dependenceEntities = $dependenceEntities->fetchEach('id');
                        $query = "SELECT * FROM $cleaner->dataContainer WHERE $cleaner->dataContainer.$cleaner->dependentField IN (".implode(",", $dependenceEntities).")";
                        
                        $cleanEntities = $db->execute(html_entity_decode($query));
                        
                        if (0 == $cleanEntities->numRows) {
                            continue;
                        }
                        
                        while($cleanEntities->next())
                        {
                            static::cleanEntity($cleanEntities, $cleaner);
                        }
                        
                        break;
                    case static::TYPE_FILE:
                        switch ($cleaner->fileDirRetrievalMode) {
                            case static::FILEDIR_RETRIEVAL_MODE_DIRECTORY:
                                $strPath = Files::getPathFromUuid($cleaner->directory);
                                
                                $objFolder = new \Folder($strPath);
                                
                                $objFolder->purge();
                                
                                if ($cleaner->addGitKeepAfterClean) {
                                    touch(TL_ROOT . '/' . $strPath . '/.gitkeep');
                                }
                                
                                break;
                            case static::FILEDIR_RETRIEVAL_MODE_ENTITY_FIELDS:
                                if (!$cleaner->whereCondition) {
                                    continue 2;
                                }
                                
                                $arrFields = deserialize($cleaner->entityFields, true);
                                
                                if (empty($arrFields)) {
                                    continue 2;
                                }
                                
                                $strQuery = "SELECT * FROM $cleaner->dataContainer WHERE ($cleaner->whereCondition)";
                                
                                if ($cleaner->addMaxAge) {
                                    $strQuery .= static::getMaxAgeCondition($cleaner->dataContainer, $cleaner->maxAgeField, $cleaner->maxAge);
                                }
                                
                                $objInstances = \Database::getInstance()->execute(html_entity_decode($strQuery));
                                
                                if ($objInstances->numRows > 0) {
                                    while ($objInstances->next()) {
                                        foreach ($arrFields as $strField) {
                                            if (!$objInstances->{$strField}) {
                                                continue;
                                            }
                                            
                                            // deserialize if necessary
                                            $varValue = deserialize($objInstances->{$strField});
                                            
                                            if (!is_array($varValue)) {
                                                $varValue = [$varValue];
                                            }
                                            
                                            foreach ($varValue as $strFile) {
                                                if (($objFile = Files::getFileFromUuid($strFile, true)) === null) {
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
    
    public static function getMaxAgeCondition($table, $mageAgeField, $maxAge)
    {
        $arrMaxAge = deserialize($maxAge, true);
        
        $intFactor = 1;
        switch ($arrMaxAge['unit']) {
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
        
        return " AND (UNIX_TIMESTAMP() > $table.$mageAgeField + $intMaxInterval)";
    }
    
    
    protected static function cleanEntity($entity, $cleaner)
    {
        $data          = $entity->row();
        $data['table'] = $cleaner->dataContainer;
        
        $deleteResult =
            Database::getInstance()->prepare("DELETE FROM $cleaner->dataContainer WHERE $cleaner->dataContainer.id=?")->execute($entity->id);
        
        if ($deleteResult->affectedRows > 0 && $cleaner->addPrivacyProtocolEntry) {
            $protocolManager = new \HeimrichHannot\Privacy\Manager\ProtocolManager();
            
            if ($cleaner->privacyProtocolEntryDescription) {
                $data['description'] = $cleaner->privacyProtocolEntryDescription;
            }
            
            $protocolManager->addEntry(
                $cleaner->privacyProtocolEntryType,
                $cleaner->privacyProtocolEntryArchive,
                $data,
                'heimrichhannot/contao-cleaner'
            );
        }
    }
}