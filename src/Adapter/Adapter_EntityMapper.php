<?php

/**
 * Class Adapter_EntityMapper
 *
 * @since 1.9.1.0
 */

// @codingStandardsIgnoreStart
class Adapter_EntityMapper {

    // @codingStandardsIgnoreEnd

    /**
     * Load ObjectModel
     *
     * @param int    $id
     * @param int    $idLang
     * @param object $entity
     * @param mixed  $entityDefs
     * @param int    $idCompany
     * @param bool   $shouldCacheObjects
     *
     * @throws PhenyxShopDatabaseException
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function load($id, $idLang, $entity, $entityDefs, $shouldCacheObjects, $dbUser= _DB_USER_, $dbPasswd = _DB_PASSWD_, $dbName = _DB_NAME_, $dServer = _DB_SERVER_, $dbPrefix = _DB_PREFIX_) {

        // Load object from database if object id is present
        $cacheId = 'objectmodel_' . $entityDefs['classname'] . '_' . (int) $id . '_' . (int) $idLang;

        if (!$shouldCacheObjects || !CacheApi::isStored($cacheId)) {
            $sql = new DbQuery($dbPrefix);
            $sql->from($entityDefs['table'], 'a');
            $sql->where('a.`' . bqSQL($entityDefs['primary']) . '` = ' . (int) $id);

            // Get lang informations

            if ($idLang && isset($entityDefs['multilang']) && $entityDefs['multilang']) {
                $sql->leftJoin($entityDefs['table'] . '_lang', 'b', 'a.`' . bqSQL($entityDefs['primary']) . '` = b.`' . bqSQL($entityDefs['primary']) . '` AND b.`id_lang` = ' . (int) $idLang);

            }
            
            if (isset($entityDefs['have_meta']) && $entityDefs['have_meta']) {
                $sql->leftJoin($entityDefs['table'] . '_meta', 'c', 'a.`' . bqSQL($entityDefs['primary']) . '` = c.`' . bqSQL($entityDefs['primary']). '`');

            }
            
            if ($objectData = Db::getCrmInstance($dbUser, $dbPasswd, $dbName, $dServer)->getRow($sql)) {

                if (!$idLang && isset($entityDefs['multilang']) && $entityDefs['multilang']) {
                    $sql = (new DbQuery($dbPrefix))
                        ->select('*')
                        ->from($entityDefs['table'] . '_lang')
                        ->where('`' . $entityDefs['primary'] . '` = ' . (int) $id);

                    if ($objectDatasLang = Db::getCrmInstance($dbUser, $dbPasswd, $dbName, $dServer)->executeS($sql)) {

                        foreach ($objectDatasLang as $row) {

                            foreach ($row as $key => $value) {

                                if ($key !== $entityDefs['primary']
                                    && $key !== 'id_lang'
                                    && property_exists($entity, $key)) {

                                    if (!isset($objectData[$key]) || !is_array($objectData[$key])) {
                                        $objectData[$key] = [];
                                    }

                                    $objectData[$key][$row['id_lang']] = $value;
                                }

                            }

                        }

                    }

                }
                
                if (isset($entityDefs['have_meta']) && $entityDefs['have_meta']) {
                    $sql = (new DbQuery($dbPrefix))
                        ->select('*')
                        ->from($entityDefs['table'] . '_meta')
                        ->where('`' . $entityDefs['primary'] . '` = ' . (int) $id);
                    if ($objectDatasMeta = Db::getCrmInstance($dbUser, $dbPasswd, $dbName, $dServer)->executeS($sql)) {

                        foreach ($objectDatasMeta as $row) {

                            foreach ($row as $key => $value) {

                                if ($key !== $entityDefs['primary']
                                    && property_exists($entity, $key)) {

                                    if (!isset($objectData[$key]) || !is_array($objectData[$key])) {
                                        $objectData[$key] = [];
                                    }

                                    $objectData[$key] = $value;
                                }

                            }

                        }

                    }

                }

                $entity->id = (int) $id;

                foreach ($objectData as $key => $value) {

                    if (property_exists($entity, $key)) {
                        $entity->{$key}
                        = $value;
                    } else {
                        unset($objectData[$key]);
                    }

                }

                if ($shouldCacheObjects) {
                    CacheApi::store($cacheId, $objectData);
                }

            }

        } else {
            $objectData = CacheApi::retrieve($cacheId);

            if ($objectData) {
                $entity->id = (int) $id;

                foreach ($objectData as $key => $value) {
                    $entity->{$key}
                    = $value;
                }

            }

        }

    }

}
