<?php

namespace Frontend\Controller;

use My\Controller\MyController,
    My\General;

class CategoryController extends MyController {
    /* @var $serviceCategory \My\Models\Category */
    /* @var $serviceProduct \My\Models\Product */
    /* @var $serviceProperties \My\Models\Properties */

    public function __construct() {
//        $this->externalJS = [
//            STATIC_URL . '/f/v1/js/library/??jquery.swipemenu.init.js'
//        ];
    }

    public function indexAction() {
        $params = $this->params()->fromRoute();

        if (empty($params['cateId'])) {
            return $this->redirect()->toRoute('404', array());
        }
        $instanceSearchCategory = new \My\Search\Category();
        $categoryDetail = $instanceSearchCategory->getDetail(array('cate_id'=>$params['cateId']));

        if (empty($categoryDetail)) {
            return $this->redirect()->toRoute('404', array());
        }

        $intPage = (int) $params['page'] > 0 ? (int) $params['page'] : 1;
        $intLimit = 10;

        $arrCondition = array(
            'cont_status' => 1,
            'cate_id' => $categoryDetail['cate_id']
        );

        $arrFields = array('cont_id', 'cont_title', 'cont_slug', 'cate_id','cont_resize_image','created_date','cont_description');
        $instanceSearchContent = new \My\Search\Content();
        $arrContentList = $instanceSearchContent->getListLimit($arrCondition, $intPage, $intLimit, ['created_date' => ['order' => 'desc']],$arrFields);

        $intTotal = $instanceSearchContent->getTotal($arrCondition);
        $helper = $this->serviceLocator->get('viewhelpermanager')->get('Paging');
        $paging = $helper($params['module'], $params['__CONTROLLER__'], $params['action'], $intTotal, $intPage, $intLimit, 'category', $params);
        //50 KEYWORD :)
        $instanceSearchKeyword = new \My\Search\Keyword();
        $arrKeywordList = $instanceSearchKeyword->getListLimit(['full_text_keyname' => 'trẻ em'], 1, 50, ['_score' => ['order' => 'desc']]);

        $metaTitle = $categoryDetail['cate_meta_title'] ? $categoryDetail['cate_meta_title'] : $categoryDetail['cate_name'];
        $metaKeyword = $categoryDetail['cate_meta_keyword'] ? $categoryDetail['cate_meta_keyword'] : NULL;
        $metaDescription = $categoryDetail['cate_meta_description'] ? $categoryDetail['cate_meta_description'] : NULL;
        $metaSocial = $categoryDetail['cate_meta_social'] ? $categoryDetail['cate_meta_social'] : NULL;

        $helper_title = $this->serviceLocator->get('viewhelpermanager')->get('MyHeadTitle');
        $helper_title->setTitle(html_entity_decode($metaTitle) . General::TITLE_META);

        $this->renderer = $this->serviceLocator->get('Zend\View\Renderer\PhpRenderer');

        $this->renderer->headMeta()->appendName('dc.description', html_entity_decode($metaDescription) . General::TITLE_META);
        $this->renderer->headMeta()->appendName('dc.subject', html_entity_decode($categoryDetail['cate_name']) . General::TITLE_META);
        $this->renderer->headTitle(html_entity_decode($metaTitle) . General::TITLE_META);
        $this->renderer->headMeta()->appendName('keywords', html_entity_decode($metaKeyword));
        $this->renderer->headMeta()->appendName('description', html_entity_decode('Danh sách bài viết trong danh mục : ' . $categoryDetail['cate_name'] . General::TITLE_META));
        $this->renderer->headMeta()->appendName('social', $metaSocial);
        $this->renderer->headMeta()->setProperty('og:url', $this->url()->fromRoute('category', array('cateSlug' => $params['cateSlug'], 'cateId' => $params['cateId'], 'page' => $intPage)));
        $this->renderer->headMeta()->setProperty('og:title', html_entity_decode('Danh sách bài viết trong danh mục : ' . $categoryDetail['cate_name'] . General::TITLE_META));
        $this->renderer->headMeta()->setProperty('og:description', html_entity_decode('Danh sách bài viết trong danh mục : ' . $categoryDetail['cate_name'] . General::TITLE_META));

        return array(
            'params' => $params,
            'paging' => $paging,
            'arrCategoryDetail' => $categoryDetail,
            'arrContentList' => $arrContentList,
            'intTotal' => $intTotal,
            'arrKeywordList'=>$arrKeywordList
        );
    }

}
