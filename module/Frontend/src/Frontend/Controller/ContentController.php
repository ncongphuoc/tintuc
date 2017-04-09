<?php

namespace Frontend\Controller;

use My\Controller\MyController,
    My\General,
    My\Validator\Validate,
    Zend\Validator\File\Size,
    Zend\View\Model\ViewModel,
    Zend\Session\Container;

class ContentController extends MyController
{
    /* @var $serviceCategory \My\Models\Category */
    /* @var $serviceProduct \My\Models\Product */
    /* @var $serviceProperties \My\Models\Properties */
    /* @var $serviceDistrict \My\Models\District */
    /* @var $serviceComment \My\Models\Comment */

    public function __construct()
    {
        $this->externalJS = [
        ];
        $this->externalCSS = [
        ];
    }

    public function detailAction()
    {
        $params = $this->params()->fromRoute();
        //
        $serviceContent = $this->serviceLocator->get('My\Models\Content');
        $serviceCategory = $this->serviceLocator->get('My\Models\Category');
        $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');
        //
        $cont_id = (int)$params['contentId'];
        $cont_slug = $params['contentSlug'];

        if (empty($cont_id) || empty($cont_slug)) {
            return $this->redirect()->toRoute('404', []);
        }
        $arrConditionContent = [
            'cont_id' => $cont_id,
            'not_cont_status' => -1
        ];
        $arrContent = $serviceContent->getDetail($arrConditionContent);

        if (empty($arrContent)) {
            return $this->redirect()->toRoute('404');
        }

        if ($cont_slug != $arrContent['cont_slug']) {
            return $this->redirect()->toRoute('view-content', ['contentSlug' => $arrContent['cont_slug'], 'contentId' => $cont_id]);
        }

        //update số lần view
        $p_arrParams = array(
            'cont_views' => $arrContent['cont_views'] + 1,
            'cont_id' => $cont_id
        );
        $serviceContent->editView($p_arrParams, $cont_id);

        /*
         render meta
        */
        $metaTitle = $arrContent['meta_title'] ? $arrContent['meta_title'] : $arrContent['cont_title'];
        $metaKeyword = $arrContent['meta_keyword'] ? $arrContent['meta_keyword'] : $arrContent['cont_title'];
        $metaDescription = $arrContent['cont_description'] ? $arrContent['cont_description'] : $arrContent['cont_title'];
        $arrContent['meta_social'] ? $metaSocial = $arrContent['meta_social'] : NULL;

        /*
         * Category
         */
//            $instanceSearchCategory = new \My\Search\Category();
//            $categoryDetail = $instanceSearchCategory->getDetail(array('cate_id'=>$arrContent['cate_id']));

        $categoryDetail = $serviceCategory->getDetail(array('cate_id'=>$arrContent['cate_id']));
        $categoryParent = array();
        if($categoryDetail['parent_id']) {
            $categoryParent = $serviceCategory->getDetail(array('cate_id'=>$categoryDetail['parent_id']));
        }

        //content same cate
        $arrFields = 'cont_id, cont_title, cont_slug, cate_id, cont_main_image, created_date, cont_description';
        $arrContentCate = $serviceContent->getListLimitContent(
            ['cate_id' => $arrContent['cate_id'], 'not_cont_status' => -1],
            1,
            6,
            'cont_id DESC',
            $arrFields
        );

//      //content new
        $arrFields = 'cont_id, cont_title, cont_slug, cate_id, cont_main_image, created_date, cont_description';
        $arrContentNew = $serviceContent->getListLimitContent(
            ['not_cont_status' => -1],
            1,
            6,
            'cont_id DESC',
            $arrFields
        );

        $arrKeywordList = array();
        if($arrContent['cont_keyword'] != '1') {
            $arrKeywordList = $serviceKeyword->getListLimit(array('in_key_id' => $arrContent['cont_keyword']), 1, 10);
        }
        
        $helper_title = $this->serviceLocator->get('viewhelpermanager')->get('MyHeadTitle');
        $helper_title->setTitle(html_entity_decode($metaTitle) . General::TITLE_META);

        $this->renderer = $this->serviceLocator->get('Zend\View\Renderer\PhpRenderer');
//            <meta name="robots" content="INDEX, FOLLOW"/>
        //$this->renderer->headMeta()->appendName('robots','noindex');
        $this->renderer->headMeta()->appendName('dc.description', html_entity_decode($categoryDetail['cate_meta_description']) . General::TITLE_META);
        $this->renderer->headMeta()->appendName('dc.subject', html_entity_decode($categoryDetail['cate_name']) . General::TITLE_META);
        $this->renderer->headTitle(html_entity_decode($metaTitle) . General::TITLE_META);
        $this->renderer->headMeta()->appendName('keywords', html_entity_decode($metaKeyword));
        $this->renderer->headMeta()->appendName('description', html_entity_decode($metaDescription));
        $this->renderer->headMeta()->appendName('robots', 'noindex');
        $this->renderer->headMeta()->appendName('social', $metaSocial);
        $this->renderer->headMeta()->setProperty('og:url', $this->url()->fromRoute('view-content', ['contentSlug' => $arrContent['cont_slug'], 'contentId' => $cont_id]));
        $this->renderer->headMeta()->setProperty('og:title', html_entity_decode($arrContent['cont_title']));
        $this->renderer->headMeta()->setProperty('og:description', html_entity_decode($arrContent['cont_title']));

        $this->renderer->headMeta()->setProperty('og:image', $arrContent['cont_main_image']);

        $this->renderer->headMeta()->setProperty('itemprop:datePublished', date('Y-m-d H:i', $arrContent['created_date']) . ' + 07:00');
        $this->renderer->headMeta()->setProperty('itemprop:dateModified', date('Y-m-d H:i', $arrContent['updated_date']) . ' + 07:00');
        $this->renderer->headMeta()->setProperty('itemprop:dateCreated', date('Y-m-d H:i', $arrContent['created_date']) . ' + 07:00');

        $this->renderer->headMeta()->setProperty('og:type', 'article');
        $this->renderer->headMeta()->setProperty('article:section', $categoryDetail['cate_name']);
        $this->renderer->headMeta()->setProperty('article:published_time', date('Y-m-d H:i', $arrContent['created_date']) . ' + 07:00');
        $this->renderer->headMeta()->setProperty('article:modified_time', date('Y-m-d H:i', $arrContent['updated_date']) . ' + 07:00');

        $this->renderer->headMeta()->setProperty('itemprop:name', html_entity_decode($arrContent['cont_title']));
        $this->renderer->headMeta()->setProperty('itemprop:description', html_entity_decode($metaDescription));
        $this->renderer->headMeta()->setProperty('itemprop:image', $arrContent['cont_main_image']);

        $this->renderer->headMeta()->setProperty('twitter:card', 'summary');
        $this->renderer->headMeta()->setProperty('twitter:site', General::SITE_AUTH);
        $this->renderer->headMeta()->setProperty('twitter:title', html_entity_decode($arrContent['cont_title']));
        $this->renderer->headMeta()->setProperty('twitter:description', html_entity_decode($metaDescription));
        $this->renderer->headMeta()->setProperty('twitter:creator', General::SITE_AUTH);
        $this->renderer->headMeta()->setProperty('twitter:image:src', $arrContent['cont_main_image']);

        $this->renderer->headLink(array('rel' => 'amphtml', 'href' => BASE_URL . $this->url()->fromRoute('view-content', ['contentSlug' => $arrContent['cont_slug'], 'contentId' => $cont_id])));
        $this->renderer->headLink(array('rel' => 'canonical', 'href' => BASE_URL . $this->url()->fromRoute('view-content', ['contentSlug' => $arrContent['cont_slug'], 'contentId' => $cont_id])));


        return array(
            'params' => $params,
            'arrContent' => $arrContent,
            'arrCategoryDetail' => $categoryDetail,
            'arrCategoryParent' => $categoryParent,
            'arrContentNew' => $arrContentNew,
            'arrContentCate' => $arrContentCate,
            'arrKeywordList' => $arrKeywordList,
        );
    }

}
