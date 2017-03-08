<?php

namespace My\Models;

class User extends ModelAbstract {

    private function getParentTable() {
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        return new \My\Storage\storageUser($dbAdapter);
    }

    public function __construct() {
    }

    public function getList($arrCondition = array()) {
        return $this->getParentTable()->getList($arrCondition);
    }

    public function getListLimit($arrCondition = array(), $intPage = 1, $intLimit = 15, $strOrder = 'user_id DESC') {
        $arrResult = $this->getParentTable()->getListLimit($arrCondition, $intPage, $intLimit, $strOrder);
        return $arrResult;
    }

    public function getTotal($arrCondition = array()) {
        return $this->getParentTable()->getTotal($arrCondition);
    }

    /**
     * Get user detail by user_id or by email address
     * @param array $arrCondition
     * @param string $options
     * @return array user detail
     */
    public function getDetail($arrCondition = array()) {
        $arrResult = $this->getParentTable()->getDetail($arrCondition);
        return $arrResult;
    }

    public function add($p_arrParams) {
        $intResult = $this->getParentTable()->add($p_arrParams);
        return $intResult;
    }

    public function edit($p_arrParams, $intUserID) {
        $intResult = $this->getParentTable()->edit($p_arrParams, $intUserID);
        return $intResult;
    }

    public function statisticOrder($arrCondition, $intPage, $intLimit, $strOrder = null) {
        $arrResult = $this->getParentTable()->statisticOrder($arrCondition, $intPage, $intLimit, $strOrder);
        return $arrResult;
    }

    public function statisticUserRegistered($strBenginDate, $strEndDate) {
        return $this->getParentTable()->statisticUserRegistered($strBenginDate, $strEndDate);
    }

    public function getTotalStatisticOrder($arrCondition = array()) {
        return $this->getParentTable()->getTotalStatisticOrder($arrCondition);
    }

    public function getUserBought($arrCondition = array(), $intPage = 1, $intLimit = 15, $strOrder = 'user_id DESC') {
        $arrResult = $this->getParentTable()->getUserBought($arrCondition, $intPage, $intLimit, $strOrder);
        return $arrResult;
    }    
    public function getTotalUserBought($arrCondition = array()) {
        return $this->getParentTable()->getTotalUserBought($arrCondition);
    }
    
}
