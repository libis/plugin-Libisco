<?php
/**
 * User: NaeemM
 */

/**
 * LibcoMappingRecord model.
 *
 * @package Libisco
 */

class LibcoMappingRecord extends Omeka_Record_AbstractRecord{

    protected $db;

    public function __construct()
    {
        $this->db = get_db();
    }

    /**
     * Add a mapping between an omeka record and corresponding record in datastore
     * @param $recordId
     * @param $recordType
     * @param $recordKey
     */
    public function addMapping($recordId, $recordType, $recordKey){
        $record = $this->getMappingByRecordId($recordId);
        if(empty($record))
            $mapping = $this->db->insert("LibcoMappings", array('record_id' => $recordId, 'record_type' => $recordType, 'datastore_key' => $recordKey));
        else{
            $this->updateMapping($record[0]->record_id, $recordKey);
        }
    }

    /**
     * @param $recordId
     * @return array|null
     */
    public function getMappingByRecordId($recordId){
        $mappingTable = $this->db->getTable('LibcoMapping');
        $record = $mappingTable->findBy(array('record_id' => $recordId));
        return $record;
    }

    /**
     * @param $recordId
     * @param $recordKey
     */
    public function updateMapping($recordId, $recordKey){
        $sql = "UPDATE ". $this->db->Libco_Mapping. " SET datastore_key='".$recordKey."' WHERE record_id=".$recordId;
        $this->db->query($sql);
    }

    /**
     * @param $recordId
     */
    public function deleteMapping($recordId){
        $sql = "DELETE from ". $this->db->Libco_Mapping. " WHERE record_id=".$recordId;
        $this->db->query($sql);

    }
}