<?php

namespace My\Storage;

use My\General;
use Zend\Db\TableGateway\AbstractTableGateway,
    Zend\Db\Sql\Sql,
    Zend\Db\Adapter\Adapter,
    My\Validator\Validate,
    Zend\Db\TableGateway\TableGateway;

class storageContent extends AbstractTableGateway {

    protected $table = 'tbl_contents';

    public function __construct(Adapter $adapter) {
        $adapter->getDriver()->getConnection()->connect();
        $this->adapter = $adapter;
    }

    public function __destruct() {
        $this->adapter->getDriver()->getConnection()->disconnect();
    }

    public function getList($arrCondition = array(), $strOrder = 'cont_id DESC', $arrFields = '*') {
        try {
            $strWhere = $this->_buildWhere($arrCondition);
            $adapter = $this->adapter;
            $query = 'select ' . $arrFields
                . ' from ' . $this->table
                . ' where 1=1 ' . $strWhere
                . ' order by ' . $strOrder;
            return $adapter->query($query, $adapter::QUERY_MODE_EXECUTE)->toArray();
        } catch (\Zend\Http\Exception $exc) {
            $actor = array(
                "Class" => __CLASS__,
                "Function" => __FUNCTION__,
                "Message" => $exc->getMessage()
            );
            if (APPLICATION_ENV !== 'production') {
                echo "<pre>";
                print_r($actor);
                echo "</pre>";
                die;
            } else {
                return General::writeLog(General::FILE_ERROR_SQL, $actor);
            }
        }
    }

    public function getListLimit($arrCondition = [], $intPage = 1, $intLimit = 15, $strOrder = 'cont_id DESC', $arrFields = '*') {
        try {
            $strWhere = $this->_buildWhere($arrCondition);
            $adapter = $this->adapter;
            
            $query = 'select ' . $arrFields
                . ' from ' . $this->table 
                . ' where 1=1 ' . $strWhere
                . ' order by ' . $strOrder
                . ' limit ' . $intLimit
                . ' offset ' . ($intLimit * ($intPage - 1));
            return $adapter->query($query, $adapter::QUERY_MODE_EXECUTE)->toArray();
        } catch (\Zend\Http\Exception $exc) {
            $actor = array(
                "Class" => __CLASS__,
                "Function" => __FUNCTION__,
                "Message" => $exc->getMessage()
            );
            if (APPLICATION_ENV !== 'production') {
                echo "<pre>";
                print_r($actor);
                echo "</pre>";
                die;
            } else {
                return General::writeLog(General::FILE_ERROR_SQL, $actor);
            }
        }
    }

    public function getDetail($arrCondition = array()) {
        try {
            $strWhere = $this->_buildWhere($arrCondition);
            $adapter = $this->adapter;
            $sql = new Sql($adapter);
            $select = $sql->Select($this->table)
                    ->where('1=1' . $strWhere);
            $query = $sql->getSqlStringForSqlObject($select);

            return current($adapter->query($query, $adapter::QUERY_MODE_EXECUTE)->toArray());
        } catch (\Zend\Http\Exception $exc) {
            $actor = array(
                "Class" => __CLASS__,
                "Function" => __FUNCTION__,
                "Message" => $exc->getMessage()
            );
            if (APPLICATION_ENV !== 'production') {
                echo "<pre>";
                print_r($actor);
                echo "</pre>";
                die;
            } else {
                return General::writeLog(General::FILE_ERROR_SQL, $actor);
            }
        }
    }

    public function getTotal($arrCondition = []) {
        try {
            $strWhere = $this->_buildWhere($arrCondition);
            $adapter = $this->adapter;
            $sql = new Sql($adapter);
            $select = $sql->Select($this->table)
                    ->columns(array('total' => new \Zend\Db\Sql\Expression('COUNT(*)')))
                    ->where('1=1' . $strWhere);
            $query = $sql->getSqlStringForSqlObject($select);
            return (int) current($adapter->query($query, $adapter::QUERY_MODE_EXECUTE)->toArray())['total'];
        } catch (\Zend\Http\Exception $exc) {
            $actor = array(
                "Class" => __CLASS__,
                "Function" => __FUNCTION__,
                "Message" => $exc->getMessage()
            );
            if (APPLICATION_ENV !== 'production') {
                echo "<pre>";
                print_r($actor);
                echo "</pre>";
                die;
            } else {
                return General::writeLog(General::FILE_ERROR_SQL, $actor);
            }
        }
    }

    public function add($p_arrParams) {
        try {
            if (!is_array($p_arrParams) || empty($p_arrParams)) {
                return false;
            }
            $adapter = $this->adapter;
            $sql = new Sql($adapter);
            $insert = $sql->insert($this->table)->values($p_arrParams);
            $query = $sql->getSqlStringForSqlObject($insert);
            $adapter->createStatement($query)->execute();
            $cont_id = $adapter->getDriver()->getLastGeneratedValue();
            //
            return $cont_id;
        } catch (\Exception $exc) {
            $actor = array(
                "Class" => __CLASS__,
                "Function" => __FUNCTION__,
                "Message" => $exc->getMessage()
            );
            if (APPLICATION_ENV !== 'production') {
                echo "<pre>";
                print_r($actor);
                echo "</pre>";
                die;
            } else {
                return General::writeLog(General::FILE_ERROR_SQL, $actor);
            }
        }
    }

    public function edit($p_arrParams, $intContentID) {
        try {
            if (!is_array($p_arrParams) || empty($p_arrParams) || empty($intContentID)) {
                return false;
            }
            $result = $this->update($p_arrParams, 'cont_id=' . $intContentID);
            return $result;
        } catch (\Exception $exc) {
            $actor = array(
                "Class" => __CLASS__,
                "Function" => __FUNCTION__,
                "Message" => $exc->getMessage()
            );
            if (APPLICATION_ENV !== 'production') {
                echo "<pre>";
                print_r($actor);
                echo "</pre>";
                die;
            } else {
                return General::writeLog(General::FILE_ERROR_SQL, $actor);
            }
        }
    }

    public function editBackground($p_arrParams, $intProductID) {
        try {
            if (!is_array($p_arrParams) || empty($p_arrParams) || empty($intProductID)) {
                return false;
            }
            $result = $this->update($p_arrParams, 'cont_id=' . $intProductID);
            return $result;
        } catch (\Exception $exc) {
            $actor = array(
                "Class" => __CLASS__,
                "Function" => __FUNCTION__,
                "Message" => $exc->getMessage()
            );
            if (APPLICATION_ENV !== 'production') {
                echo "<pre>";
                print_r($actor);
                echo "</pre>";
                die;
            } else {
                return General::writeLog(General::FILE_ERROR_SQL, $actor);
            }
        }
    }

    public function multiEdit($p_arrParams, $arrCondition) {
        try {
            if (!is_array($p_arrParams) || empty($p_arrParams) || empty($arrCondition) || !is_array($arrCondition)) {
                return false;
            }
            $strWhere = $this->_buildWhere($arrCondition);
            $result = $this->update($p_arrParams, '1=1 ' . $strWhere);
            return $result;
        } catch (\Exception $exc) {
            $actor = array(
                "Class" => __CLASS__,
                "Function" => __FUNCTION__,
                "Message" => $exc->getMessage()
            );
            if (APPLICATION_ENV !== 'production') {
                echo "<pre>";
                print_r($actor);
                echo "</pre>";
                die;
            } else {
                return General::writeLog(General::FILE_ERROR_SQL, $actor);
            }
        }
    }

    private function _buildWhere($arrCondition) {
        $strWhere = '';

        if (!empty($arrCondition['cont_slug'])) {
            $strWhere .= " AND cont_slug= '" . $arrCondition['cont_slug'] . "'";
        }

        if (!empty($arrCondition['cont_id'])) {
            $strWhere .= " AND cont_id= " . $arrCondition['cont_id'];
        }

        if (!empty($arrCondition['cont_title'])) {
            $strWhere .= " AND cont_title= " . $arrCondition['cont_title'];
        }

        if (isset($arrCondition['cont_status'])) {
            $strWhere .= " AND cont_status = " . $arrCondition['cont_status'];
        }

        if (isset($arrCondition['not_cont_status'])) {
            $strWhere .= " AND cont_status != " . $arrCondition['not_cont_status'];
        }

        if (!empty($arrCondition['cate_id'])) {
            $strWhere .= " AND cate_id = " . $arrCondition['cate_id'];
        }

        if (!empty($arrCondition['cont_keyword'])) {
            $strWhere .= " AND cont_keyword = " . $arrCondition['cont_keyword'];
        }

        if (!empty($arrCondition['in_cate_id'])) {
            $strWhere .= " AND cate_id IN (" . $arrCondition['in_cate_id'] . ")";
        }

        if (!empty($arrCondition['not_cont_id'])) {
            $strWhere .= " AND cont_id != " . $arrCondition['not_cont_id'];
        }

        if (!empty($arrCondition['in_cont_id'])) {
            $strWhere .= " AND cont_id IN (" . $arrCondition['in_cont_id'] . ")";
        }

        if (!empty($arrCondition['fulltext_cont_title'])) {
            $strWhere .= " AND MATCH (cont_title) AGAINST (" . $arrCondition['fulltext_cont_title'] . ")";
        }

        return $strWhere;
    }

}
