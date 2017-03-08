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
        $page = 1;
        $limit = 6;

        $listCategory = unserialize(ARR_CATEGORY);

        $instanceSearchContent = new \My\Search\Content();
        $instanceTotalContent = new \My\Search\Content();

        $arrFields = array('cont_id', 'cont_title', 'cont_slug', 'cate_id','cont_main_image','created_date','cont_description','cont_resize_image');
        $arr_category = array();
        $arr_content_cate = array();
        foreach ($listCategory as $category) {

            $arr_category[$category['cate_id']] = $category;
            $totalContent = $instanceTotalContent->getTotal(array('cont_status' => 1, 'cate_id' => $category['cate_id']));
            $arr_category[$category['cate_id']]['total_content'] = $totalContent;

            $arrCondition = array(
                'cont_status' => 1,
                'cate_id' => $category['cate_id']
            );
            $arr_content_new = $instanceSearchContent->getListLimit(
                $arrCondition,
                $page,
                $limit,
                ['cont_id' => ['order' => 'desc']],
                $arrFields
            );

            $arr_content_cate[$category['cate_id']] = $arr_content_new;
        }

        $helper_title = $this->serviceLocator->get('viewhelpermanager')->get('MyHeadTitle');
        $helper_title->setTitle(General::SITE_AUTH);

        $this->renderer = $this->serviceLocator->get('Zend\View\Renderer\PhpRenderer');
        $this->renderer->headMeta()->appendName('robots', 'index');
        
        return [
            'param' => $params,
            'arrCategory' => $arr_category,
            'arrContent' => $arr_content_cate
        ];
    }

}
