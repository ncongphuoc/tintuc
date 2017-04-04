<?php

namespace Frontend\Controller;

use My\Controller\MyController,
    My\General;

class SearchController extends MyController
{

    public function __construct()
    {
//        $this->externalJS = [
//            STATIC_URL . '/f/v1/js/my/??search.js'
//        ];
    }

    public function indexAction()
    {
        try {
            $params = array_merge($this->params()->fromRoute(), $this->params()->fromQuery());

            if (empty($params['keyword'])) {
                return $this->redirect()->toRoute('404');
            }

            $key_name = General::clean($params['keyword']);

            $intPage = (int)$params['page'] > 0 ? (int)$params['page'] : 1;
            $intLimit = 10;

            $arr_condition_content = array(
                'cont_status' => 1,
                'full_text_title' => $key_name
            );

            $arrFields = array('cont_id', 'cont_title', 'cont_slug', 'cate_id','cont_main_image','created_date');
            $instanceSearchContent = new \My\Search\Content();
            $arrContentList = $instanceSearchContent->getListLimit($arr_condition_content, $intPage, $intLimit, ['_score' => ['order' => 'desc']], $arrFields);

            //phân trang
            $intTotal = $instanceSearchContent->getTotal($arr_condition_content);
            $helper = $this->serviceLocator->get('viewhelpermanager')->get('Paging');
            $paging = $helper($params['module'], $params['__CONTROLLER__'], $params['action'], $intTotal, $intPage, $intLimit, 'search', $params);

            $helper_title = $this->serviceLocator->get('viewhelpermanager')->get('MyHeadTitle');
            $helper_title()->setTitle($params['keyword'] . General::TITLE_META);

            //get keyword
            $listContent = array();
            $instanceSearchKeyword = new \My\Search\Keyword();
            foreach ($arrContentList as $content) {
                $listContent[$content['cont_id']] = $content;
                $arrCondition = array(
                    'full_text_keyname' => $content['cont_title'],
                    'in_cate_id' => array($content['cate_id'], -1)
                );
                $arrKeywordList = $instanceSearchKeyword->getListLimit($arrCondition, 1, 5, ['_score' => ['order' => 'desc']]);
                $listContent[$content['cont_id']]['list_keyword'] = $arrKeywordList;
            }

            $this->renderer = $this->serviceLocator->get('Zend\View\Renderer\PhpRenderer');
            $this->renderer->headTitle($params['keyword'] . General::TITLE_META);
            $this->renderer->headMeta()->appendName('robots', 'index');
            $this->renderer->headMeta()->appendName('keywords', General::KEYWORD_DEFAULT . ', ' . $params['keyword']);
            $this->renderer->headMeta()->appendName('description', $params['keyword']);
            $this->renderer->headMeta()->setProperty('og:url', BASE_URL . $this->url()->fromRoute('search', ['keyword' => $params['keyword'], 'page' => $intPage]));
            $this->renderer->headMeta()->setProperty('og:title', $params['keyword']);
            $this->renderer->headMeta()->setProperty('og:description', $params['keyword']);

            $this->renderer->headLink(array('rel' => 'amphtml', 'href' => BASE_URL . $this->url()->fromRoute('search', ['keyword' => $params['keyword']])));
            $this->renderer->headLink(array('rel' => 'canonical', 'href' => BASE_URL . $this->url()->fromRoute('search', ['keyword' => $params['keyword']])));

            //get 50 keyword gần giống nhất
//            $instanceSearchKeyword = new \My\Search\Keyword();
//            $arrKeywordList = $instanceSearchKeyword->getListLimit(['full_text_keyname' => $key_name], 1, $intLimit, ['_score' => ['order' => 'desc']]);

            return [
                'paging' => $paging,
                'params' => $params,
                'arrContentList' => $listContent,
                'arrKeywordList' => $arrKeywordList,
                'intTotal' => $intTotal
            ];
        } catch (\Exception $exc) {
            echo '<pre>';
            print_r([
                'code' => $exc->getCode(),
                'messages' => $exc->getMessage()
            ]);
            echo '</pre>';
            die();
        }
    }

    public function keywordAction()
    {
        try {
            $params = array_merge($this->params()->fromRoute(), $this->params()->fromQuery());
            $key_id = (int)$params['keyId'];
            $key_slug = $params['keySlug'];

            $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');
            $serviceContent = $this->serviceLocator->get('My\Models\Content');

            if (empty($key_id)) {
                return $this->redirect()->toRoute('404', array());
            }
            //
            $arrKeyDetail = $serviceKeyword->getDetail(['key_id' => $key_id]);

            if (empty($arrKeyDetail)) {
                return $this->redirect()->toRoute('404', array());
            }

            $arrFields = 'cont_id, cont_title, cont_slug, cate_id, cont_main_image, cont_keyword, cont_description';
            $arr_condition_content = array(
                'cont_status' => 1,
                'in_cont_id' => $arrKeyDetail['content_id']
            );
            $arrResult = $serviceContent->getList($arr_condition_content, 'cont_id ASC', $arrFields);

            $arrContentList = array();
            $arr_temp = array();
            foreach ($arrResult as $content){
                $arrContentList[$content['cont_id']] = $content;
            }

            //get keyword
            $listContent = array();
            foreach ($arrContentList as $content) {
                $listContent[$content['cont_id']] = $content;
                $arrCondition = array(
                    'in_key_id' => $content['cont_keyword']
                );
                $arrKeywordList = $serviceKeyword->getListLimit($arrCondition, 1, 5);
                $listContent[$content['cont_id']]['list_keyword'] = $arrKeywordList;
            }

            $helper_title = $this->serviceLocator->get('viewhelpermanager')->get('MyHeadTitle');
            $helper_title->setTitle(html_entity_decode($arrKeyDetail['key_name']) . General::TITLE_META);

            $this->renderer = $this->serviceLocator->get('Zend\View\Renderer\PhpRenderer');
            $this->renderer->headMeta()->appendName('robots', 'index');
            $this->renderer->headMeta()->appendName('dc.description', html_entity_decode($arrKeyDetail['key_name']) . General::TITLE_META);
            $this->renderer->headMeta()->appendName('dc.subject', html_entity_decode($arrKeyDetail['key_name']) . General::TITLE_META);
            $this->renderer->headTitle(html_entity_decode($arrKeyDetail['key_name']) . General::TITLE_META);
            $this->renderer->headMeta()->appendName('keywords', html_entity_decode($arrKeyDetail['key_name']));
            $this->renderer->headMeta()->appendName('description', html_entity_decode('Danh sách bài viết trong từ khoá : ' . $arrKeyDetail['key_name'] . General::TITLE_META));
            $this->renderer->headMeta()->appendName('social', null);
            $this->renderer->headMeta()->setProperty('og:url', $this->url()->fromRoute('keyword', array('keySlug' => $arrKeyDetail['key_slug'], 'keyId' => $arrKeyDetail['key_id'], 'page' => $intPage)));
            $this->renderer->headMeta()->setProperty('og:title', html_entity_decode('Danh sách bài viết trong từ khoá : ' . $arrKeyDetail['key_name'] . General::TITLE_META));
            $this->renderer->headMeta()->setProperty('og:description', html_entity_decode('Danh sách bài viết trong từ khoá : ' . $arrKeyDetail['key_name'] . General::TITLE_META));
            $this->renderer->headLink(array('rel' => 'amphtml', 'href' => BASE_URL . $this->url()->fromRoute('keyword', array('keySlug' => $arrKeyDetail['key_slug'], 'keyId' => $arrKeyDetail['key_id'], 'page' => $intPage))));
            $this->renderer->headLink(array('rel' => 'canonical', 'href' => BASE_URL . $this->url()->fromRoute('keyword', array('keySlug' => $arrKeyDetail['key_slug'], 'keyId' => $arrKeyDetail['key_id'], 'page' => $intPage))));

            return array(
                'params' => $params,
                'intPage' => $intPage,
                'arrContentList' => $listContent,
                'arrKeyDetail' => $arrKeyDetail
            );
        } catch (\Exception $exc) {
            echo '<pre>';
            print_r([
                'code' => $exc->getCode(),
                'messages' => $exc->getMessage()
            ]);
            echo '</pre>';
            die();
        }
    }

    public function listKeywordAction()
    {
        try {
            $params = array_merge($this->params()->fromRoute(), $this->params()->fromQuery());
            $intPage = is_numeric($params['page']) ? $params['page'] : 1;
            $intLimit = 100;

            $instanceSearch = new \My\Search\Keyword();

            $arrCondition = array(
                'word_id_less' => round((time() - 1465036100) / 4)
            );
            $arrKeywordList = $instanceSearch->getListLimit($arrCondition, $intPage, $intLimit, ['key_id' => ['order' => 'desc']]);
            $intTotal = $instanceSearch->getTotal($arrCondition);
            $helper = $this->serviceLocator->get('viewhelpermanager')->get('Paging');
            $paging = $helper($params['module'], $params['__CONTROLLER__'], $params['action'], $intTotal, $intPage, $intLimit, 'list-keyword', $params);

            $this->renderer = $this->serviceLocator->get('Zend\View\Renderer\PhpRenderer');
            $this->renderer->headMeta()->appendName('dc.description', html_entity_decode('Danh sách từ khoá trang ' . $intPage) . General::TITLE_META);
            $this->renderer->headMeta()->appendName('dc.subject', html_entity_decode('Danh sách từ khoá trang ' . $intPage) . General::TITLE_META);
            $this->renderer->headTitle('Từ khoá - ' . html_entity_decode('Danh sách từ khoá trang ' . $intPage) . General::TITLE_META);
            $this->renderer->headMeta()->appendName('keywords', html_entity_decode('Danh sách từ khoá trang ' . $intPage));
            $this->renderer->headMeta()->appendName('description', html_entity_decode('Danh sách từ khoá trang ' . $intPage . General::TITLE_META));
            $this->renderer->headMeta()->appendName('social', null);
            $this->renderer->headMeta()->setProperty('og:url', $this->url()->fromRoute('list-keyword', array('page' => $intPage)));
            $this->renderer->headMeta()->setProperty('og:title', html_entity_decode('Danh sách từ khoá trang ' . $intPage . General::TITLE_META));
            $this->renderer->headMeta()->setProperty('og:description', html_entity_decode('Danh sách từ khoá trang ' . $intPage . General::TITLE_META));

            return array(
                'params' => $params,
                'arrKeywordList' => $arrKeywordList,
                'paging' => $paging,
                'intPage' => $intPage,
                'intLimit' => $intLimit,
                'intTotal' => $intTotal,
                'title' => 'Keyword'
            );
        } catch (\Exception $exc) {
            echo '<pre>';
            print_r([
                'code' => $exc->getCode(),
                'messages' => $exc->getMessage()
            ]);
            echo '</pre>';
            die();
        }
    }

}
