<?php

namespace Frontend\Controller;

use My\Controller\MyController,
    My\General;

class GeneralController extends MyController {

    public function __construct() {
        $this->externalJS = [
            //STATIC_URL . '/f/v1/js/my/??general.js'
        ];
        $this->externalCSS = [
            //STATIC_URL . '/b/css/??bootstrap-wysihtml5.css'
        ];
    }

    public function indexAction() {
        $params = $this->params()->fromRoute();

        if (empty($params['geneId']) || empty($params['geneSlug'])) {
            return $this->redirect()->toRoute('404');
        }

        $instanceSeachGeneral = new \My\Search\General();

        $arr_general = $instanceSeachGeneral->getDetail(['gene_id' => $params['geneId'], 'gene_status' => 1]);

        if (empty($arr_general)) {
            return $this->redirect()->toRoute('404');
        }

        if ($arr_general['gene_slug'] != $params['geneSlug']) {
            return $this->redirect()->toRoute('general', array('geneSlug' => $arr_general['gene_slug'], 'geneId' => $params['geneId']));
        }

        $this->renderer = $this->serviceLocator->get('Zend\View\Renderer\PhpRenderer');

        $this->renderer->headTitle(html_entity_decode($arr_general['gene_title']) . General::TITLE_META);
        $this->renderer->headMeta()->appendName('keywords', html_entity_decode($arr_general['gene_title']));
        $this->renderer->headMeta()->appendName('description', html_entity_decode($arr_general['gene_title']));
//        $this->renderer->headMeta()->appendName('social', $metaSocial);
        $this->renderer->headMeta()->setProperty('og:url', $this->url()->fromRoute('general', array('geneSlug' => $arr_general['gene_slug'], 'geneId' => $arr_general['gene_id'])));
        $this->renderer->headMeta()->setProperty('og:title', html_entity_decode($arr_general['gene_title']));
        $this->renderer->headMeta()->setProperty('og:description', html_entity_decode($arr_general['gene_title']));
        $metaImage = STATIC_URL . '/f/v1/images/logoct.png';
        $this->renderer->headMeta()->setProperty('og:image', $metaImage);

        $this->renderer->headMeta()->setProperty('itemprop:datePublished', date('Y-m-d H:i', $arr_general['created_date']) . ' + 07:00');
        $this->renderer->headMeta()->setProperty('itemprop:dateModified', date('Y-m-d H:i', $arr_general['updated_date']) . ' + 07:00');
        $this->renderer->headMeta()->setProperty('itemprop:dateCreated', date('Y-m-d H:i', $arr_general['created_date']) . ' + 07:00');

        $this->renderer->headMeta()->setProperty('og:type', 'article');
        $this->renderer->headMeta()->setProperty('article:published_time', date('Y-m-d H:i', $arr_general['created_date']) . ' + 07:00');
        $this->renderer->headMeta()->setProperty('article:modified_time', date('Y-m-d H:i', $arr_general['updated_date']) . ' + 07:00');

//        $this->renderer->headMeta()->setProperty('fb:pages', '272925143041233');

        $this->renderer->headMeta()->setProperty('itemprop:name', html_entity_decode($arr_general['gene_title']));
        $this->renderer->headMeta()->setProperty('itemprop:description', html_entity_decode($arrContent['gene_title']));
        $this->renderer->headMeta()->setProperty('itemprop:image', $metaImage);

        $this->renderer->headMeta()->setProperty('twitter:card', 'summary');
        $this->renderer->headMeta()->setProperty('twitter:site', General::SITE_AUTH);
        $this->renderer->headMeta()->setProperty('twitter:title', html_entity_decode($arr_general['gene_title']));
        $this->renderer->headMeta()->setProperty('twitter:description', html_entity_decode($arr_general['gene_title']));
        $this->renderer->headMeta()->setProperty('twitter:creator', General::SITE_AUTH);
        $this->renderer->headMeta()->setProperty('twitter:image:src', $metaImage);

        return [
            'arr_general' => $arr_general
        ];
    }

    public function contactAction() {

        $this->renderer = $this->serviceLocator->get('Zend\View\Renderer\PhpRenderer');
        $this->renderer->headMeta()->appendName('dc.description', html_entity_decode('Contact - Tintuc360.me') . General::TITLE_META);
        $this->renderer->headTitle(html_entity_decode('Contact - Tintuc360.me') . General::TITLE_META);
        $this->renderer->headMeta()->appendName('keywords', html_entity_decode('lien he, lien he tintuc360.me, lien he voi tintuc360,') . General::SITE_DOMAIN);
        $this->renderer->headMeta()->appendName('description', html_entity_decode('Contact - Tintuc360.me') . General::TITLE_META);
        //$this->renderer->headMeta()->setProperty('og:url', $this->url()->fromRoute('add-contact'));
        $this->renderer->headMeta()->setProperty('og:title', html_entity_decode('Contact - Tintuc360.me') . General::TITLE_META);
        $this->renderer->headMeta()->setProperty('og:description', html_entity_decode('Contact - Tintuc360.me') . General::TITLE_META);


    }

}
