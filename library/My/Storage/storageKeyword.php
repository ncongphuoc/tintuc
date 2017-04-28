<?php

namespace My\Storage;

use Zend\Db\TableGateway\AbstractTableGateway,
    Zend\Db\Sql\Sql,
    Zend\Db\Adapter\Adapter,
    Zend\Db\Sql\Where,
    Zend\Db\Sql\Select,
    My\Validator\Validate,
    My\General;

class storageKeyword extends AbstractTableGateway {

    protected $table = 'tbl_keywords';

    public function __construct(Adapter $adapter) {
        $adapter->getDriver()->getConnection()->connect();
        $this->adapter = $adapter;
    }

    public function __destruct() {
        $this->adapter->getDriver()->getConnection()->disconnect();
    }

    public function add($p_arrParams) {
        try {
            if (!is_array($p_arrParams) || empty($p_arrParams)) {
                return false;
            }

            $p_arrParams = array_merge(array(
                'is_crawler' => 0,
                'key_level' => 1,
                'key_weight' => 1,
                'content_id' => 1,
                'content_crawler' => 1,
                'key_status' => 1,
            ), $p_arrParams);

            $adapter = $this->adapter;
            $sql = new Sql($adapter);
            $insert = $sql->insert($this->table)->values($p_arrParams);
            $query = $sql->getSqlStringForSqlObject($insert);
            $adapter->createStatement($query)->execute();
            $keyword_id = $adapter->getDriver()->getLastGeneratedValue();
            //
            if ($keyword_id) {
                return $keyword_id;
            }
            //
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

    public function edit($p_arrParams, $id) {
        try {
            if (!is_array($p_arrParams) || empty($p_arrParams) || empty($id)) {
                return false;
            }
            $result = $this->update($p_arrParams, 'key_id=' . $id);
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

    public function getListLimit($arrCondition, $intPage, $intLimit, $strOrder = 'key_id ASC',$arrFields = '*') {
        try {
            $strWhere = $this->_buildWhere($arrCondition);
            $adapter = $this->adapter;
            $sql = new Sql($adapter);
            $query = 'select ' . $arrFields
                . ' from ' . $this->table
                . ' where 1=1 ' . $strWhere
                . ' order by ' . $strOrder
                . ' limit ' . $intLimit
                . ' offset ' . ($intLimit * ($intPage - 1));
            return $adapter->query($query, $adapter::QUERY_MODE_EXECUTE)->toArray();

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

    public function getDetail($arrCondition = array()) {
        try {
            $strWhere = $this->_buildWhere($arrCondition);
            $adapter = $this->adapter;
            $sql = new Sql($adapter);
            $select = $sql->Select($this->table)
                ->where('1=1' . $strWhere);
            //
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


    public function getListLimitJob($arrCondition, $intPage, $intLimit, $strOrder = '', $arrFields = '*') {
        try {
            $strWhere = $this->_buildWhere($arrCondition);
            $adapter = $this->adapter;
            $sql = new Sql($adapter);
            $query = 'select ' . $arrFields
                . ' from ' . $this->table
                . ' where 1=1 ' . $strWhere
                . ' limit ' . $intLimit
                . ' offset ' . ($intLimit * ($intPage - 1));

            if(!empty($strOrder)) {
                $query .= ' order by ' . $strOrder;
            }
            
            return $adapter->query($query, $adapter::QUERY_MODE_EXECUTE)->toArray();

        } catch (\Exception $exc) {
			echo "<pre>";
            print_r($query);
            echo "</pre>";
            die;
            $actor = array(
                "Class" => __CLASS__,
                "Function" => __FUNCTION__,
                "Message" => $exc->getMessage()
            );
            return General::writeLog(General::FILE_ERROR_SQL, $actor);
        }
    }


    private function _buildWhere($arrCondition) {
        $strWhere = '';

        if(isset($arrCondition['key_status'])) {
            $strWhere .= ' AND key_status = ' . $arrCondition['key_status'];
        }

        if(isset($arrCondition['key_id'])) {
            $strWhere .= ' AND key_id = ' . $arrCondition['key_id'];
        }

        if(isset($arrCondition['is_crawler'])) {
            $strWhere .= ' AND is_crawler = ' . $arrCondition['is_crawler'];
        }

        if(isset($arrCondition['key_slug'])) {
            $strWhere .= ' AND key_slug = "' . $arrCondition['key_slug'] . '"';
        }

        if(isset($arrCondition['in_key_id'])) {
            $strWhere .= ' AND key_id IN (' . $arrCondition['in_key_id'] . ')';
        }

        if(isset($arrCondition['content_id'])) {
            $strWhere .= ' AND content_id ="' . $arrCondition['content_id'] . '"';
        }

        if(isset($arrCondition['content_crawler'])) {
            $strWhere .= ' AND content_crawler ="' . $arrCondition['content_crawler'] . '"';
        }

        if(isset($arrCondition['not_content_crawler'])) {
            $strWhere .= ' AND content_crawler <> "' . $arrCondition['not_content_crawler'] . '"';
        }

        if (!empty($arrCondition['fulltext_key_name'])) {
            $strWhere .= ' AND MATCH (key_name) AGAINST ("' . preg_replace('/[^A-Za-z0-9\-]/', '', $arrCondition['fulltext_key_name']) . '")';
        }

        return $strWhere;
    }

}
