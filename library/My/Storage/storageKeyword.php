<?php

namespace My\Storage;

use Zend\Db\TableGateway\AbstractTableGateway,
    Zend\Db\Sql\Sql,
    Zend\Db\Adapter\Adapter,
    Zend\Db\Sql\Where,
    Zend\Db\Sql\Select,
    My\Validator\Validate;

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
            ), $p_arrParams);

            $adapter = $this->adapter;
            $sql = new Sql($adapter);
            $insert = $sql->insert($this->table)->values($p_arrParams);
            $query = $sql->getSqlStringForSqlObject($insert);
            $adapter->createStatement($query)->execute();
            $keyword_id = $adapter->getDriver()->getLastGeneratedValue();
            //
            if ($keyword_id) {
                $instanceSearch = new \My\Search\Keyword();

                $p_arrParams['key_id'] = $keyword_id;
                $arrDocument = new \Elastica\Document($keyword_id, $p_arrParams);
                $intResult = $instanceSearch->add($arrDocument);
            }
            //
            return $keyword_id;
        } catch (\Exception $exc) {
            echo '<pre>';
            print_r($exc->getMessage());
            echo '</pre>';
            die();
        }
    }

    public function edit($p_arrParams, $id) {
        try {
            if (!is_array($p_arrParams) || empty($p_arrParams) || empty($id)) {
                return false;
            }
            $result = $this->update($p_arrParams, 'key_id=' . $id);
            if ($result) {
                $updateData = new \Elastica\Document();
                $updateData->setData($p_arrParams);
                $document = new \Elastica\Document($id, $p_arrParams);
                $document->setUpsert($updateData);

                $instanceSearch = new \My\Search\Keyword();
                $result = $instanceSearch->edit($document);
            }
            return $result;
        } catch (\Exception $exc) {
            echo '<pre>';
            print_r($exc->getMessage());
            echo '</pre>';
            die();
            if (APPLICATION_ENV !== 'production') {
                die($exc->getMessage());
            }
            return false;
        }
    }

    public function getListLimit($arrCondition, $intPage, $intLimit, $strOrder) {
        try {
            $strWhere = $this->_buildWhere($arrCondition);
            $adapter = $this->adapter;
            $sql = new Sql($adapter);
            $select = $sql->Select($this->table)
                    ->where('1=1' . $strWhere)
                    ->order($strOrder)
                    ->limit($intLimit)
                    ->offset($intLimit * ($intPage - 1));
            $query = $sql->getSqlStringForSqlObject($select);
            return $adapter->query($query, $adapter::QUERY_MODE_EXECUTE)->toArray();
        } catch (\Exception $exc) {
            echo '<pre>';
            print_r($exc->getMessage());
            echo '</pre>';
            die();
        }
    }

    private function _buildWhere($arrCondition) {
        $strWhere = '';
        return $strWhere;
    }

}
