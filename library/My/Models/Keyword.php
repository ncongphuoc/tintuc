<?php

namespace My\Models;

class Keyword extends ModelAbstract {

    private function getParentTable() {
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        return new \My\Storage\storageKeyword($dbAdapter);
    }

    public function __construct() {
    }

    public function add($p_arrParams) {
        $intResult = $this->getParentTable()->add($p_arrParams);
        return $intResult;
    }

    public function getListLimit($arrCondition = [], $intPage = 1, $intLimit = 15, $strOrder = 'key_id ASC',$arrFields = '*') {
        $arrResult = $this->getParentTable()->getListLimit($arrCondition, $intPage, $intLimit, $strOrder, $arrFields);
        return $arrResult;
    }

    public function getDetail($arrCondition) {
        $arrResult = $this->getParentTable()->getDetail($arrCondition);
        return $arrResult;
    }

    public function getTotal($arrCondition) {
        return $this->getParentTable()->getTotal($arrCondition);
    }
    
    public function edit($p_arrParams, $intKeywordID) {
        $intResult = $this->getParentTable()->edit($p_arrParams, $intKeywordID);
        return $intResult;
    }

}
