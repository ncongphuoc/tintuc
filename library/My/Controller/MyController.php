<?php

namespace My\Controller;

use Zend\Mvc\MvcEvent,
    Zend\Mvc\Controller\AbstractActionController,
    My\General;

class MyController extends AbstractActionController {
    /* @var $groupService \My\Models\Group */
    /* @var $serviceUser \My\Models\User */
    /* @var $serviceTemplate \My\Models\Template */

    protected $defaultJS = '';
    protected $externalJS = '';
    protected $defaultCSS = '';
    protected $externalCSS = '';
    protected $serverUrl;
    protected $authservice;
    private $resource;
    private $renderer;

    public function onDispatch(MvcEvent $e) {
        if (php_sapi_name() != 'cli') {
            $this->serverUrl = $this->request->getUri()->getScheme() . '://' . $this->request->getUri()->getHost();
            $this->params = array_merge($this->params()->fromRoute(),$this->params()->fromQuery());
            $this->params['module'] = strtolower($this->params['module']);
            $this->params['controller'] = strtolower($this->params['__CONTROLLER__']);
            $this->params['action'] = strtolower($this->params['action']);
            $this->resource = $this->params['module'] . ':' . $this->params['controller'] . ':' . $this->params['action'];
            $this->renderer = $this->serviceLocator->get('Zend\View\Renderer\PhpRenderer');
            $auth = $this->authenticate($this->params);

            if ($this->params['module'] === 'backend' && !$auth) {
                if (!$this->permission($this->params)) {
                    if ($this->request->isXmlHttpRequest()) {
                        die('Permission Denied!!!');
                    }
                    $this->layout('backend/error/accessDeny');
                    return false;
                }
            }

            $instanceStaticManager = new \My\StaticManager\StaticManager($this->resource, $this->serviceLocator);
            $instanceStaticManager
                    ->setJS(array('defaultJS' => $this->defaultJS))
                    ->setJS(array('externalJS' => $this->externalJS))
                    ->setCSS(array('defaultCSS' => $this->defaultCSS))
                    ->setCSS(array('externalCSS' => $this->externalCSS))
                    ->render(2.1);
            $this->setMeta($this->params);
        }
        return parent::onDispatch($e);
    }

    private function setMeta($arrData) {
        $this->renderer->headMeta()->setCharset('UTF-8');
        $this->renderer->headMeta()->appendName('viewport', 'width=device-width, initial-scale=1.0');
        switch ($this->resource) {
            case 'frontend:index:index':
                $this->renderer->headTitle(\My\General::SITE_DOMAIN . ' - ' .General::SITE_SLOGAN);
                $this->renderer->headMeta()->appendName('keywords', General::KEYWORD_DEFAULT);
                $this->renderer->headMeta()->appendName('description', General::DESCRIPTION_DEFAULT);
                $this->renderer->headMeta()->appendName('dc.description', html_entity_decode(General::SITE_SLOGAN) . General::TITLE_META);
                $this->renderer->headMeta()->appendName('dc.subject', html_entity_decode(General::SITE_SLOGAN) . General::TITLE_META);
                $this->renderer->headMeta()->appendName('social', General::SITE_SOCIAL);
                $this->renderer->headMeta()->setProperty('og:url', $this->url()->fromRoute('home'));
                $this->renderer->headMeta()->setProperty('og:title', html_entity_decode(\My\General::SITE_DOMAIN . ' -'.General::SITE_SLOGAN));
                $this->renderer->headMeta()->setProperty('og:description', html_entity_decode(\My\General::SITE_DOMAIN . ' - '.General::SITE_SLOGAN));
                $this->renderer->headMeta()->setProperty('og:image', General::SITE_IMAGES_DEFAULT);
                $this->renderer->headMeta()->setProperty('og:image:type', 'image/png');
                $this->renderer->headMeta()->setProperty('og:image:width', '621');
                $this->renderer->headMeta()->setProperty('og:image:height', '132');
                $this->renderer->headMeta()->setProperty('og:type', 'website');

//                $this->renderer->headMeta()->setProperty('fb:pages', '272925143041233');

                $this->renderer->headMeta()->setProperty('itemprop:name', General::SITE_DOMAIN);
                $this->renderer->headMeta()->setProperty('itemprop:description', html_entity_decode(General::SITE_SLOGAN) . General::TITLE_META);
                $this->renderer->headMeta()->setProperty('itemprop:image', General::SITE_IMAGES_DEFAULT);

                $this->renderer->headMeta()->setProperty('twitter:card', 'summary');
                $this->renderer->headMeta()->setProperty('twitter:site', General::SITE_AUTH);
                $this->renderer->headMeta()->setProperty('twitter:title', General::SITE_AUTH);
                $this->renderer->headMeta()->setProperty('twitter:description', html_entity_decode(General::SITE_SLOGAN) . General::TITLE_META);
                $this->renderer->headMeta()->setProperty('twitter:creator', General::SITE_AUTH);
                $this->renderer->headMeta()->setProperty('twitter:image:src', General::SITE_IMAGES_DEFAULT);
                break;
            default:
                break;
        }
        if ($arrData['module'] === 'backend') {
            $this->renderer->headTitle('Administrator - '.General::SITE_AUTH);
        }
    }

    private function permission($params) {

        //check can access CPanel
        if (IS_ACP != 1) {
            return false;
        }

        //check use in fullaccess role
        if (FULL_ACCESS) {
            return true;
        }

        $ser = $this->serviceLocator;
        $serviceACL = $this->serviceLocator->get('ACL');

        $strActionName = $params['action'];

        if (strpos($params['action'], '-')) {
            $strActionName = '';
            $arrActionName = explode('-', $params['action']);
            foreach ($arrActionName as $k => $str) {
                if ($k > 0) {
                    $strActionName .= ucfirst($str);
                }
            }
            $strActionName = $arrActionName[0] . $strActionName;
        }

        $strControllerName = $params['controller'];
        if (strpos($params['controller'], '-')) {
            $strControllerName = '';
            $arrControllerName = explode('-', $params['controller']);
            foreach ($arrControllerName as $k => $str) {
                if ($k > 0) {
                    $strControllerName .= ucfirst($str);
                }
            }
            $strControllerName = $arrControllerName[0] . $strControllerName;
        }

        $strActionName = str_replace('_', '', $strActionName);
        $strControllerName = str_replace('_', '', $strControllerName);

        return $serviceACL->checkPermission($params['module'], $strControllerName, $strActionName);
    }

    protected function getAuthService() {
        if (!$this->authservice) {
            $this->authservice = $this->getServiceLocator()->get('AuthService');
        }
        return $this->authservice;
    }

    private function authenticate($arrData) {
        $arrUserData = $this->getAuthService()->getIdentity();
        if ($arrData['module'] === 'backend') {

            if (empty($arrUserData)) {
                return $this->redirect()->toRoute('backend', array('controller' => 'auth', 'action' => 'login'));
            }

            define('UID', (int) $arrUserData['user_id']);
            define('MODULE', $arrData['module']);
            define('CONTROLLER', $arrData['controller']);
            define('FULLNAME', $arrUserData['user_fullname']);
            define('USERNAME', $arrUserData['user_name']);
            define('EMAIL', $arrUserData['user_email']);
            define('GROU_ID', $arrUserData['group_id'] ? (int) $arrUserData['group_id'] : 0);
            define('IS_ACP', (empty($arrUserData['group_id']) ? 0 : $arrUserData['is_acp']));
            define('PERMISSION', json_encode($arrUserData['permission']));
            define('FULL_ACCESS', empty($arrUserData['is_full_access']) ? 0 : 1);
        }

        if ($arrData['module'] === 'frontend') {
            $serviceCategory = $this->serviceLocator->get('My\Models\Category');
            $arrCategoryList = $serviceCategory->getListCategory(['cate_status' => 1]);

            $arr_category = array();
            foreach ($arrCategoryList as $category) {
                $arr_category[$category['cate_id']] = $category;
            }
            define('ARR_CATEGORY', serialize($arr_category));

            //get list content hot
            $serviceContent = $this->serviceLocator->get('My\Models\Content');
            $arrFields = 'cont_id, cont_title, cont_slug, cate_id, cont_resize_image, created_date, cont_description';
            $arr_content_hot = $serviceContent->getListHostContent(['cont_status' => 1], 1, 5, 'cont_views DESC', $arrFields);

            define('ARR_CONTENT_HOT_LIST', serialize($arr_content_hot));

            unset($arrKeywordList);
            unset($arr_content_hot);
            unset($instanceSearchCategory);
            unset($arrCategory);
        }
    }

}
