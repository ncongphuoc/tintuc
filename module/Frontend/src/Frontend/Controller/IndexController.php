<?php

namespace Frontend\Controller;

use My\Controller\MyController,
    My\General;

class IndexController extends MyController
{
    /* @var $serviceCategory \My\Models\Category */
    /* @var $serviceProduct \My\Models\Product */

    public function __construct()
    {
    }

    public function indexAction()
    {
        $params = $this->params()->fromRoute();
        $serviceContent = $this->serviceLocator->get('My\Models\Content');
        $page = 1;
        $limit = 5;

        $arr_category_info = unserialize(ARR_CATEGORY_INFO);
        $tree_category = unserialize(ARR_TREE_CATEGORY);

        $list_cate_id = array(
            General::CATEGORY_CONG_NGHE,
            General::CATEGORY_CUOC_SONG,
            General::CATEGORY_KHOA_HOC
        );


        $arrFields = 'cont_id, cont_title, cont_slug, cate_id, cont_main_image, created_date, cont_description';
        $arr_category = array();
        $arr_content_cate = array();
        foreach ($list_cate_id as $category) {
            $arrCondition = array(
                'cont_status' => 1,
                'in_cate_id' => implode(',',$tree_category[$category])
            );
            $arr_content_new = $serviceContent->getListHomePage($arrCondition, $page, $limit, 'cont_id DESC', $arrFields);
            $arr_content_cate[$category] = $arr_content_new;
        }
        
        $helper_title = $this->serviceLocator->get('viewhelpermanager')->get('MyHeadTitle');
        $helper_title->setTitle(General::SITE_AUTH);

        $this->renderer = $this->serviceLocator->get('Zend\View\Renderer\PhpRenderer');
        $this->renderer->headMeta()->appendName('robots', 'index');
        
        return [
            'param' => $params,
            'arrCategory' => $arr_category_info,
            'arrContent' => $arr_content_cate
        ];
    }

}
