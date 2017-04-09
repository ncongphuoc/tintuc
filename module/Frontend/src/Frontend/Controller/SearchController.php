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
        $params = array_merge($this->params()->fromRoute(), $this->params()->fromQuery());

        if (empty($params['word'])) {
            return $this->redirect()->toRoute('404');
        }

        $key_name = General::clean($params['word']);

        $intPage = (int)$params['page'] > 0 ? (int)$params['page'] : 1;
        $intLimit = 10;

        $arr_condition_content = array(
            'cont_status' => 1,
            'fulltext_cont_title' => $key_name
        );

        $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');
        $serviceContent = $this->serviceLocator->get('My\Models\Content');

        $arrFields = 'cont_id, cont_title, cont_slug, cate_id, cont_main_image, cont_keyword, cont_description';
        $arrResult = $serviceContent->getListLimit($arr_condition_content, $intPage, $intLimit, 'cont_id ASC', $arrFields);

        $arrContentList = array();
        foreach ($arrResult as $content){
            $arrContentList[$content['cont_id']] = $content;
        }

        //get keyword
        $listContent = array();
        foreach ($arrContentList as $content) {
            $listContent[$content['cont_id']] = $content;

            $arrKeywordList = array();
            if($content['cont_keyword'] != '1') {
                $arrCondition = array(
                    'in_key_id' => $content['cont_keyword']
                );
                $arrKeywordList = $serviceKeyword->getListLimit($arrCondition, 1, 5, 'key_id ASC', 'key_id, key_name, key_slug');
            }
            $listContent[$content['cont_id']]['list_keyword'] = $arrKeywordList;
        }

        $listContent = array_values($listContent);

        //phân trang
        $intTotal = $serviceContent->getTotal($arr_condition_content);


        $helper = $this->serviceLocator->get('viewhelpermanager')->get('Paging');
        $paging = $helper($params['module'], $params['__CONTROLLER__'], $params['action'], $intTotal, $intPage, $intLimit, 'search', $params);

        $helper_title = $this->serviceLocator->get('viewhelpermanager')->get('MyHeadTitle');
        $helper_title->setTitle(html_entity_decode($params['word']) . General::TITLE_META);

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

        return array(
            'paging' => $paging,
            'params' => $params,
            'word' => $key_name,
            'arrContentList' => $listContent,
            'intTotal' => $intTotal
        );
    }

    public function keywordAction()
    {
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
        foreach ($arrResult as $content){
            $arrContentList[$content['cont_id']] = $content;
        }

        //get keyword
        $listContent = array();
        foreach ($arrContentList as $content) {
            $listContent[$content['cont_id']] = $content;

            $arrKeywordList = array();
            if($content['cont_keyword'] != '1') {
                $arrCondition = array(
                    'in_key_id' => $content['cont_keyword']
                );
                $arrKeywordList = $serviceKeyword->getListLimit($arrCondition, 1, 5, 'key_id ASC', 'key_id, key_name, key_slug');
            }
            $listContent[$content['cont_id']]['list_keyword'] = $arrKeywordList;
        }

        $listContent = array_values($listContent);

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
    }

    public function listKeywordAction()
    {
        $params = array_merge($this->params()->fromRoute(), $this->params()->fromQuery());
        $intPage = is_numeric($params['page']) ? $params['page'] : 1;
        $intLimit = 100;

        $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');

        $arrKeywordList = $serviceKeyword->getListLimit(array(), $intPage, $intLimit, 'key_id ASC', 'key_id, key_name, key_slug');
        $intTotal = $serviceKeyword->getTotal(array());

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
    }

}
