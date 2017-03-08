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
        try {
            $params = $this->params()->fromRoute();
            //
            $serviceContent = $this->serviceLocator->get('My\Models\Content');
            $serviceCategory = $this->serviceLocator->get('My\Models\Category');
            $instanceSearchContent = new \My\Search\Content();
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
            
//            $arrContent = $instanceSearchContent->getDetail($arrConditionContent);
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
            $serviceContent->edit($p_arrParams, $cont_id);

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

            //content same category
//            $arrContentCateList = $instanceSearchContent->getListLimit(
//                ['cate_id' => $arrContent['cate_id'], 'not_cont_status' => -1, 'less_cont_id' => $arrContent['cont_id']],
//                1,
//                6,
//                ['cont_id' => ['order' => 'desc']],
//                $arrFields
//            );

            $arrFields = 'cont_id, cont_title, cont_slug, cate_id, cont_resize_image, created_date, cont_description';
            $arrContentCateList = $serviceContent->getListLimit(
                ['cate_id' => $arrContent['cate_id'], 'not_cont_status' => -1, 'not_cont_id' => $arrContent['cont_id']],
                1,
                6,
                'cont_id DESC',
                $arrFields
            );
//            //content like title
            $arrFields = array('cont_id', 'cont_title', 'cont_slug', 'cate_id','cont_resize_image','created_date','cont_description');
            $arrContentLikeList = $instanceSearchContent->getListLimit(
                ['cont_status' => 1, 'full_text_title' => $arrContent['cont_title'], 'not_cont_id' => $arrContent['cont_id']],
                1,
                6,
                ['_score' => ['order' => 'desc']],
                $arrFields
            );
//
            //lấy 10 keyword 
            $instanceSearchKeyword = new \My\Search\Keyword();
            $arrKeywordList = $instanceSearchKeyword->getListLimit(['full_text_keyname' => $arrContent['cont_title'],'not_cate_id'=>-2], 1, 10, ['_score' => ['order' => 'desc']]);

            unset($serviceContent);
            unset($instanceSearchContent);
            unset($instanceSearchKeyword);
            unset($arrConditionContent);

            return array(
                'params' => $params,
                'arrContent' => $arrContent,
                'arrCategoryDetail' => $categoryDetail,
                'arrContentLikeList' => $arrContentLikeList,
                'arrContentCateList' => $arrContentCateList,
//                'arrContentNews' => $arrContentNews,
                'arrKeywordList' => $arrKeywordList,
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
