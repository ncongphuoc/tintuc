<?php

namespace Frontend\Controller;

use My\Controller\MyController,
    My\General;

class CategoryController extends MyController {
    /* @var $serviceCategory \My\Models\Category */
    /* @var $serviceProduct \My\Models\Product */
    /* @var $serviceProperties \My\Models\Properties */

    public function __construct() {

    }

    public function indexAction() {
        $params = $this->params()->fromRoute();
        $cate_id = $params['cateId'];


        if (empty($params['cateId'])) {
            return $this->redirect()->toRoute('404', array());
        }
        $serviceCategory = $this->serviceLocator->get('My\Models\Category');
        $serviceContent = $this->serviceLocator->get('My\Models\Content');
        //
        $categoryDetail = $serviceCategory->getDetail(
            array(
                'cate_status' => 1,
                'cate_id' => $cate_id
            )
        );
        if (empty($categoryDetail)) {
            return $this->redirect()->toRoute('404', array());
        }

        $intPage = (int) $params['page'] > 0 ? (int) $params['page'] : 1;
        $intLimit = 10;

        //get child cate
        $tree_category = unserialize(ARR_TREE_CATEGORY);

        $arrCondition = array(
            'cont_status' => 1,
            'in_cate_id' => (isset($tree_category[$cate_id])) ? implode(',',$tree_category[$cate_id]) : $cate_id
        );

        $arrFields = 'cont_id, cont_title, cont_slug, cate_id, cont_main_image, created_date, cont_description';
        $arrContentList = $serviceContent->getListLimit($arrCondition, $intPage, $intLimit, 'cont_id DESC', $arrFields);
        $intTotal = $serviceContent->getTotal($arrCondition);

        $helper = $this->serviceLocator->get('viewhelpermanager')->get('Paging');
        $paging = $helper($params['module'], $params['__CONTROLLER__'], $params['action'], $intTotal, $intPage, $intLimit, 'category', $params);
        //
        $metaTitle = $categoryDetail['cate_meta_title'] ? $categoryDetail['cate_meta_title'] : $categoryDetail['cate_name'];
        $metaKeyword = $categoryDetail['cate_meta_keyword'] ? $categoryDetail['cate_meta_keyword'] : NULL;
        $metaDescription = $categoryDetail['cate_meta_description'] ? $categoryDetail['cate_meta_description'] : NULL;
        $metaSocial = $categoryDetail['cate_meta_social'] ? $categoryDetail['cate_meta_social'] : NULL;

        //
        $helper_title = $this->serviceLocator->get('viewhelpermanager')->get('MyHeadTitle');
        $helper_title->setTitle(html_entity_decode($metaTitle) . General::TITLE_META);

        //
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
        );
    }

}
