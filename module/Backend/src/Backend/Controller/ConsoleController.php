<?php

namespace Backend\Controller;

use My\General,
    My\Controller\MyController,
    Zend\Dom\Query,
    Sunra\PhpSimple\HtmlDomParser;

class ConsoleController extends MyController
{
    const IMAGE_DEFAULT = STATIC_URL . '/f/v1/images/no-image-available.jpg';
    const DIV_ADS = '<div id="adsarticletop" class="adbox">SCRIPT ADS</div>';

    public function __construct()
    {
//        if (PHP_SAPI !== 'cli') {
//            die('Only use this controller from command line!');
//        }
        ini_set('default_socket_timeout', -1);
        ini_set('max_execution_time', -1);
        ini_set('mysql.connect_timeout', -1);
        ini_set('memory_limit', -1);
        ini_set('output_buffering', 0);
        ini_set('zlib.output_compression', 0);
        ini_set('implicit_flush', 1);
    }

    private function flush()
    {
        ob_end_flush();
        ob_flush();
        flush();
    }

    public function migrateAction()
    {
        $params = $this->request->getParams();
        $intIsCreateIndex = (int)$params['createindex'];

        if (empty($params['type'])) {
            return General::getColoredString("Unknown type \n", 'light_cyan', 'red');
        }

        switch ($params['type']) {
            case 'logs':
                $this->__migrateLogs($intIsCreateIndex);
                break;

            case 'content':
                $this->__migrateContent($intIsCreateIndex);
                break;

            case 'category' :
                $this->__migrateCategory($intIsCreateIndex);
                break;
            case 'keyword' :
                $this->__migrateKeyword($intIsCreateIndex);
                break;
        }
        echo General::getColoredString("Index ES sucess", 'light_cyan', 'yellow');
        return true;
    }

    public function __migrateCategory($intIsCreateIndex)
    {
        $service = $this->serviceLocator->get('My\Models\Category');
        $intLimit = 1000;
        $instanceSearch = new \My\Search\Category();
//        $instanceSearch->createIndex();
//        die();
        for ($intPage = 1; $intPage < 10000; $intPage++) {
            $arrList = $service->getListLimit([], $intPage, $intLimit, 'cate_id ASC');
            if (empty($arrList)) {
                break;
            }

            if ($intPage == 1) {
                if ($intIsCreateIndex) {
                    $instanceSearch->createIndex();
                } else {
                    $result = $instanceSearch->removeAllDoc();
                    if (empty($result)) {
                        $this->flush();
                        return General::getColoredString("Cannot delete old search index \n", 'light_cyan', 'red');
                    }
                }
            }
            $arrDocument = [];
            foreach ($arrList as $arr) {
                $id = (int)$arr['cate_id'];

                $arrDocument[] = new \Elastica\Document($id, $arr);
                echo General::getColoredString("Created new document with id = " . $id . " Successfully", 'cyan');

                $this->flush();
            }

            unset($arrList); //release memory
            echo General::getColoredString("Migrating " . count($arrDocument) . " documents, please wait...", 'yellow');
            $this->flush();

            $instanceSearch->add($arrDocument);
            echo General::getColoredString("Migrated " . count($arrDocument) . " documents successfully", 'blue', 'cyan');

            unset($arrDocument);
            $this->flush();
        }

        die('done');
    }

    public function __migrateContent($intIsCreateIndex)
    {
        $serviceContent = $this->serviceLocator->get('My\Models\Content');
        $intLimit = 200;
        $instanceSearchContent = new \My\Search\Content();
//        $instanceSearchContent->createIndex();
//        die();

        if ($intIsCreateIndex) {
            $instanceSearchContent->createIndex();
        } else {
            $result = $instanceSearchContent->removeAllDoc();
            if (empty($result)) {
                $this->flush();
                return General::getColoredString("Cannot delete old search index \n", 'light_cyan', 'red');
            }
        }

        for ($intPage = 1; $intPage < 10000; $intPage++) {
            $arrContentList = $serviceContent->getListLimit([], $intPage, $intLimit, 'cont_id ASC');
            if (empty($arrContentList)) {
                break;
            }
            $arrDocument = [];
            foreach ($arrContentList as $arrContent) {
                $id = (int)$arrContent['cont_id'];

                $arrDocument[] = new \Elastica\Document($id, $arrContent);
                echo General::getColoredString("Created new document with cont_id = " . $id . " Successfully", 'cyan');

                $this->flush();
            }

            unset($arrContentList); //release memory
            echo General::getColoredString("Migrating " . count($arrDocument) . " documents, please wait...", 'yellow');
            $this->flush();

            $instanceSearchContent->add($arrDocument);
            echo General::getColoredString("Migrated " . count($arrDocument) . " documents successfully", 'blue', 'cyan');

            unset($arrDocument);
            $this->flush();
        }

        die('done');
    }

    public function __migrateKeyword($intIsCreateIndex)
    {

        $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');
        $intLimit = 2000;
        $instanceSearchKeyword = new \My\Search\Keyword();

        if ($intIsCreateIndex) {
            $instanceSearchKeyword->createIndex();
        } else {
            $result = $instanceSearchKeyword->removeAllDoc();
            if (empty($result)) {
                $this->flush();
                return General::getColoredString("Cannot delete old search index \n", 'light_cyan', 'red');
            }
        }

        for ($intPage = 1; $intPage < 10000; $intPage++) {
            $arrList = $serviceKeyword->getListLimit([], $intPage, $intLimit, 'key_id ASC');

            if (empty($arrList)) {
                break;
            }

            $arrDocument = [];
            foreach ($arrList as $arr) {
                $id = (int)$arr['key_id'];
                $arrDocument[] = new \Elastica\Document($id, $arr);
                echo General::getColoredString("Created new document with cont_id = " . $id . " Successfully", 'cyan');

                $this->flush();
            }

            unset($arrList); //release memory
            echo General::getColoredString("Migrating " . count($arrDocument) . " documents, please wait...", 'yellow');
            $this->flush();

            $instanceSearchKeyword->add($arrDocument);
            echo General::getColoredString("Migrated " . count($arrDocument) . " documents successfully", 'blue', 'cyan');

            unset($arrDocument);
            $this->flush();
        }
        die('done');
    }

    public function crawlerKeywordAction()
    {
        $this->getKeyword();
        return;
    }

    public function getKeyword()
    {
        $match = [
            '', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'x', 'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'
        ];
        $instanceSearchKeyWord = new \My\Search\Keyword();
        $arr_keyword = current($instanceSearchKeyWord->getListLimit(['is_crawler' => 0], 1, 1, ['key_weight' => ['order' => 'desc'], 'key_id' => ['order' => 'asc']]));

        unset($instanceSearchKeyWord);
        if (empty($arr_keyword)) {
            return;
        }

        $keyword = $arr_keyword['key_name'];
        $count = str_word_count($keyword);
        if ($count > 6) {
            $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');
            $int_result = $serviceKeyword->edit(array('is_crawler' => 1, 'key_weight' => 1), $arr_keyword['key_id']);
            unset($serviceKeyword);

            if ($int_result) {
                echo \My\General::getColoredString("Crawler success keyword_id = {$arr_keyword['key_id']}", 'green');
            }
            $this->getKeyword();

        }

        foreach ($match as $key => $value) {
            if ($key == 0) {
                $key_match = $keyword . $value;
                $url = 'http://www.google.com/complete/search?output=search&client=chrome&q=' . rawurlencode($key_match) . '&hl=vi&gl=vn';
                $return = General::crawler($url);
                //
                $list_keyword = json_decode($return)[1];
//print_r($list_keyword);die;
                $this->add_keyword($list_keyword, $arr_keyword);
                continue;
            } else {
                for ($i = 0; $i < 2; $i++) {
                    if ($i == 0) {
                        $key_match = $keyword . ' ' . $value;
                    } else {
                        $key_match = $value . ' ' . $keyword;
                    }
                    $url = 'http://www.google.com/complete/search?output=search&client=chrome&q=' . rawurlencode($key_match) . '&hl=vi&gl=vn';
                    $return = General::crawler($url);
                    $this->add_keyword(json_decode($return)[1], $arr_keyword);
                    continue;
                }
            }
            sleep(3);
        };

        $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');
        $int_result = $serviceKeyword->edit(array('is_crawler' => 1, 'key_weight' => 1), $arr_keyword['key_id']);
        unset($serviceKeyword);

        if ($int_result) {
            echo \My\General::getColoredString("Crawler success keyword_id = {$arr_keyword['key_id']}", 'green');
        }

        sleep(3);
        $this->getKeyword();
    }

    public function add_keyword($arr_key, $keyword_detail = null)
    {
        if (empty($arr_key)) {
            return false;
        }

        $arr_block_string = General::blockString();

        $instanceSearchKeyWord = new \My\Search\Keyword();
        $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');
        foreach ($arr_key as $key_word) {

            $word_slug = trim(General::getSlug($key_word));
            $is_exsit = $instanceSearchKeyWord->getDetail(['key_slug' => $word_slug]);
//print_r($is_exsit);die;
            if ($is_exsit) {
                echo \My\General::getColoredString("Exsit keyword: " . $word_slug, 'red');
                continue;
            }
            $block = false;
            foreach ($arr_block_string as $string) {
                if (strpos($key_word, $string) !== false) {
                    $block = true;
                }
            }

            if ($block) {
                continue;
            }

            $arr_data = [
                'key_name' => $key_word,
                'key_slug' => $word_slug,
            ];

            $int_result = $serviceKeyword->add($arr_data);
            if ($int_result) {
                echo \My\General::getColoredString("Insert success 1 row with id = {$int_result}", 'green');
            }
            $this->flush();
        }
        unset($instanceSearchKeyWord);
        return true;
    }

    public function sitemapAction()
    {
        $this->sitemapOther();
        $this->siteMapCategory();
        //$this->siteMapContent();
        $this->siteMapSearch();

        $xml = '<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>';
        $xml = new \SimpleXMLElement($xml);

        $all_file = scandir(PUBLIC_PATH . '/xml/');
        sort($all_file, SORT_NATURAL | SORT_FLAG_CASE);
//        sort($all_file);
        foreach ($all_file as $file_name) {
            if (strpos($file_name, 'xml') !== false) {
                $sitemap = $xml->addChild('sitemap', '');
                $sitemap->addChild('loc', BASE_URL . '/xml/' . $file_name);
                //$sitemap->addChild('lastmod', date('c', time()));
            }
        }

        $result = file_put_contents(PUBLIC_PATH . '/xml/hellonews.xml', $xml->asXML());
        if ($result) {
            echo General::getColoredString("Create sitemap.xml completed!", 'blue', 'cyan');
            $this->flush();
        }
        echo General::getColoredString("DONE!", 'blue', 'cyan');
        return true;
    }

    public function siteMapCategory()
    {
        $doc = '<?xml version="1.0" encoding="UTF-8"?>';
        $doc .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $doc .= '</urlset>';
        $xml = new \SimpleXMLElement($doc);
        $this->flush();
        $instanceSearchCategory = new \My\Search\Category();
        $arrCategoryList = $instanceSearchCategory->getList(['cate_status' => 1], [], ['cate_sort' => ['order' => 'asc'], 'cate_id' => ['order' => 'asc']]);

        $arrCategoryParentList = [];
        $arrCategoryByParent = [];
        if (!empty($arrCategoryList)) {
            foreach ($arrCategoryList as $arrCategory) {
                if ($arrCategory['parent_id'] == 0) {
                    $arrCategoryParentList[$arrCategory['cate_id']] = $arrCategory;
                } else {
                    $arrCategoryByParent[$arrCategory['parent_id']][] = $arrCategory;
                }
            }
        }

        ksort($arrCategoryByParent);

        foreach ($arrCategoryParentList as $value) {
            $strCategoryURL = BASE_URL . '/danh-muc/' . $value['cate_slug'] . '-' . $value['cate_id'] . '.html';
            $url = $xml->addChild('url');
            $url->addChild('loc', $strCategoryURL);
//            $url->addChild('lastmod', date('c', time()));
            $url->addChild('changefreq', 'daily');
//            $url->addChild('priority', 0.9);

            if (!empty($value['cate_img_url'])) {
                $image = $url->addChild('image:image', null, 'http://www.google.com/schemas/sitemap-image/1.1');
                $image->addChild('image:loc', STATIC_URL . $value['cate_img_url'], 'http://www.google.com/schemas/sitemap-image/1.1');
                $image->addChild('image:caption', $value['cate_name'] . General::TITLE_META, 'http://www.google.com/schemas/sitemap-image/1.1');
            }
        }
        foreach ($arrCategoryByParent as $key => $arr) {
            foreach ($arr as $value) {
                $strCategoryURL = BASE_URL . '/danh-muc/' . $value['cate_slug'] . '-' . $value['cate_id'] . '.html';
                $url = $xml->addChild('url');
                $url->addChild('loc', $strCategoryURL);
//                $url->addChild('lastmod', date('c', time()));
                $url->addChild('changefreq', 'daily');
//                $url->addChild('priority', 0.9);
                if (!empty($value['cate_img_url'])) {
                    $image = $url->addChild('image:image', null, 'http://www.google.com/schemas/sitemap-image/1.1');
                    $image->addChild('image:loc', STATIC_URL . $value['cate_img_url'], 'http://www.google.com/schemas/sitemap-image/1.1');
                    $image->addChild('image:caption', $value['cate_name'] . General::TITLE_META, 'http://www.google.com/schemas/sitemap-image/1.1');
                }
            }
        }

        unlink(PUBLIC_PATH . '/xml/category.xml');
        $result = file_put_contents(PUBLIC_PATH . '/xml/category.xml', $xml->asXML());
        if ($result) {
            echo General::getColoredString("Sitemap category done", 'blue', 'cyan');
            $this->flush();
        }

        return true;
    }

    public function siteMapContent()
    {
        $instanceSearchContent = new \My\Search\Content();
        $intLimit = 2000;
        for ($intPage = 1; $intPage < 100; $intPage++) {

            $file = PUBLIC_PATH . '/xml/content-' . $intPage . '.xml';
            $arrContentList = $instanceSearchContent->getListLimit(['not_cont_status' => -1], $intPage, $intLimit, ['cont_id' => ['order' => 'desc']]);

            if (empty($arrContentList)) {
                break;
            }

            $doc = '<?xml version="1.0" encoding="UTF-8"?>';
            $doc .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            $doc .= '</urlset>';
            $xml = new \SimpleXMLElement($doc);
            $this->flush();

            foreach ($arrContentList as $arr) {
                $href = BASE_URL . '/bai-viet/' . $arr['cont_slug'] . '-' . $arr['cont_id'] . '.html';
                $url = $xml->addChild('url');
                $url->addChild('loc', $href);
//                $url->addChild('title', $arr['cont_title']);
//                $url->addChild('lastmod', date('c', time()));
                $url->addChild('changefreq', 'daily');
//                $url->addChild('priority', 0.7);

                if (!empty($arr['cont_main_image'])) {
                    $image = $url->addChild('image:image', null, 'http://www.google.com/schemas/sitemap-image/1.1');
                    $image->addChild('image:loc', $arr['cont_main_image'], 'http://www.google.com/schemas/sitemap-image/1.1');
                    $image->addChild('image:caption', $arr['cont_title'], 'http://www.google.com/schemas/sitemap-image/1.1');
                }
            }

            unlink($file);
            $result = file_put_contents($file, $xml->asXML());

            if ($result) {
                echo General::getColoredString("Site map complete content page {$intPage}", 'yellow', 'cyan');
                $this->flush();
            }

        }

        return true;
    }

    public function siteMapSearch()
    {
        $instanceSearchKeyword = new \My\Search\Keyword();
        $intLimit = 4000;
        for ($intPage = 1; $intPage < 10000; $intPage++) {
            $file = PUBLIC_PATH . '/xml/keyword-' . $intPage . '.xml';
            $arrKeyList = $instanceSearchKeyword->getListLimit(['not_content_crawler' => 1], $intPage, $intLimit, ['key_id' => ['order' => 'asc']]);

            if (empty($arrKeyList)) {
                break;
            }

            $doc = '<?xml version="1.0" encoding="UTF-8"?>';
            $doc .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            $doc .= '</urlset>';
            $xml = new \SimpleXMLElement($doc);
            $this->flush();

            foreach ($arrKeyList as $arr) {
                $href = BASE_URL . '/tu-khoa/' . $arr['key_slug'] . '-' . $arr['key_id'] . '.html';
                $url = $xml->addChild('url');
                $url->addChild('loc', $href);
//                $url->addChild('lastmod', date('c', time()));
                $url->addChild('changefreq', 'daily');
//                $url->addChild('priority', 0.7);
            }

            unlink($file);
            $result = file_put_contents($file, $xml->asXML());

            if ($result) {
                echo General::getColoredString("Site map complete keyword page {$intPage}", 'yellow', 'cyan');
                $this->flush();
            }
        }
        return true;
    }

    private function sitemapOther()
    {
        $doc = '<?xml version="1.0" encoding="UTF-8"?>';
        $doc .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $doc .= '</urlset>';
        $xml = new \SimpleXMLElement($doc);
        $this->flush();
        $arrData = ['https://tintuc360.me/'];
        foreach ($arrData as $value) {
            $href = $value;
            $url = $xml->addChild('url');
            $url->addChild('loc', $href);
            $url->addChild('lastmod', date('c', time()));
            $url->addChild('changefreq', 'daily');
            $url->addChild('priority', 1);
        }

        unlink(PUBLIC_PATH . '/xml/other.xml');
        $result = file_put_contents(PUBLIC_PATH . '/xml/other.xml', $xml->asXML());
        if ($result) {
            echo General::getColoredString("Sitemap orther done", 'blue', 'cyan');
            $this->flush();
        }
    }


    public function crawlerContentAction()
    {
        $this->__skynewsCrawler();

        $this->__naturallyCrawler(1, 'http://naturallysavvy.com/care');

        $this->__kidspotMultiCrawler('http://www.kidspot.com.au/birth/');

        $this->__foxnewsCrawler();

        $this->__naturallyCrawler(1, 'http://naturallysavvy.com/eat');

        $this->__kidspotMultiCrawler('http://www.kidspot.com.au/baby/');

        $this->__foxnewsTechCrawler();

        $this->__newscientistCrawler();

        //$this->__kidspotMultiCrawler('http://www.kidspot.com.au/parenting/');

        $this->__naturallyCrawler(1, 'http://naturallysavvy.com/nest');

    }

    public function keywordContentAction()
    {
        $arr_category = [6, 1, 2, 3, 5, 7, 4];
        for ($i = 1; $i < 5; $i++) {
            foreach ($arr_category as $cate_id) {
                switch ($cate_id) {
                    case 1:
                        if ($i > 0 && $i < 2) {
                            $this->kenh14CrawlerKeyword($i, $cate_id);
                        }
                        break;
                    case 2:
                        $this->emdepCrawlerKeyword($i, 'http://emdep.vn/thoi-trang', $cate_id);
                        break;
                    case 3:
                        $this->afamilyCrawlerKeyword($i, 'http://afamily.vn/suc-khoe', $cate_id);
                        $this->emdepCrawlerKeyword($i, 'http://emdep.vn/song-khoe', $cate_id);
                        break;
                    case 4:
                        $this->afamilyCrawlerKeyword($i, 'http://afamily.vn/dep', $cate_id);
                        $this->emdepCrawlerKeyword($i, 'http://emdep.vn/lam-dep', $cate_id);
                        break;
                    case 5:
                        if ($i > 0 && $i < 11) {
                            $this->_24hCrawlerKeyword($i, $cate_id);
                        }
                        $this->emdepCrawlerKeyword($i, 'http://emdep.vn/mon-ngon', $cate_id);
                        break;
                    case 6:
                        $this->emdepCrawlerKeyword($i, 'http://emdep.vn/lam-me', $cate_id);
                        break;
                    case 7:
                        $this->ivivuCrawlerKeyword($i, $cate_id);
                        break;
                }
            }
            echo \My\General::getColoredString("Finish page " . $i, 'white');
            sleep(2);
        }

        echo \My\General::getColoredString("DONE time: " . date('H:i:s'), 'light_cyan');
    }

    public function resizeImage($upload_dir, $cont_slug, $extension, $cate_id)
    {

        $path_old = $upload_dir['path'] . '/' . $cont_slug . '_1.' . $extension;
        if (!file_exists($path_old)) {
            $path_old = STATIC_PATH . '/f/v1/images/no-image-available.jpg';
        }
        $name_main_image = $cont_slug . '_main.' . $extension;
        $result = General::resizeImages($cate_id, $path_old, $name_main_image, $upload_dir['path']);
        if ($result) {
            return $upload_dir['url'] . '/' . $cont_slug . '_main.' . $extension;
        } else {
            return false;
        }
    }

    public function postToFanpage($arrParams, $acc_share)
    {
        $config_fb = General::$configFB;
        $url_content = 'https://tintuc360.me/bai-viet/' . $arrParams['cont_slug'] . '-' . $arrParams['cont_id'] . '.html';
        $data = array(
            "access_token" => $config_fb['access_token'],
            "message" => $arrParams['cont_description'],
            "link" => $url_content,
            "picture" => $arrParams['cont_main_image'],
            "name" => $arrParams['cont_title'],
            "caption" => "tintuc360.me",
            "description" => $arrParams['cont_description']
        );
        $post_url = 'https://graph.facebook.com/' . $config_fb['fb_id'] . '/feed';

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $post_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $return = curl_exec($ch);
            curl_close($ch);
            echo \My\General::getColoredString($return, 'green');
            unset($ch);

            if (!empty($return)) {
                $post_id = explode('_', json_decode($return, true)['id'])[1];
                foreach ($acc_share as $key => $value) {
                    $this->shareToWall([
                        'post_id' => $post_id,
                        'access_token' => $value,
                        'name' => $key
                    ]);
                }
            }

            echo \My\General::getColoredString("Post 1 content to facebook success cont_id = {$arrParams['cont_id']}", 'green');
            unset($ch, $return, $post_id, $data, $post_url, $url_content, $config_fb, $arrParams);
            $this->flush();
            return true;
        } catch (Exception $e) {
            echo \My\General::getColoredString($e->getMessage(), 'red');
            return true;
        }
    }

    public function shareToWall($arrParams)
    {
        $config_fb = General::$configFB;
        try {
            $fb = new \Facebook\Facebook([
                'app_id' => $config_fb['app_id'],
                'app_secret' => $config_fb['app_secret']
            ]);
            $fb->setDefaultAccessToken($arrParams['access_token']);
            $rp = $fb->post('/me/feed', ['link' => 'https://web.facebook.com/tintuc360.me/posts/' . $arrParams['post_id']]);
            echo \My\General::getColoredString(json_decode($rp->getBody(), true), 'green');
            echo \My\General::getColoredString('Share post id ' . $arrParams['post_id'] . ' to facebook ' . $arrParams['name'] . ' SUCCESS', 'green');
            unset($data, $return, $arrParams, $rp, $config_fb);
            return true;
        } catch (\Exception $exc) {
            echo \My\General::getColoredString($exc->getMessage(), 'red');
            echo \My\General::getColoredString('Share post id ' . $arrParams['post_id'] . ' to facebook ' . $arrParams['name'] . ' ERROR', 'red');
            return true;
        }
    }

    public function shareFacebookAction()
    {
        $instanceSearchContent = new \My\Search\Content();
        $params = $this->request->getParams();

        $cate_id = $params['cateId'];


        $arrContentList = $instanceSearchContent->getList(['not_cont_status' => -1, 'cate_id' => $cate_id], ['cont_id' => ['order' => 'asc']], array('cont_id'));
        if (empty($arrContentList)) {
            return false;
        }
        $total = count($arrContentList);
        $index = rand(1, $total);

        $cont_id = $arrContentList[$index]['cont_id'];
        $contentDetail = $instanceSearchContent->getDetail(['cont_id' => $cont_id], array('cont_id', 'cate_id', 'cont_main_image', 'cont_slug', 'cont_description'));

        switch ($cate_id) {
            case General::CATEGORY_THOI_TRANG:
            case General::CATEGORY_LAM_DEP:
            case General::CATEGORY_DU_LICH:
                $acc_share = General::$acc_share_teen;
                break;
            case General::CATEGORY_SUC_KHOE:
            case General::CATEGORY_ME_VA_BE:
                $acc_share = General::$acc_share_old;
                break;
            default:
                $acc_share = General::$acc_share_teen;
                break;
        }
        $this->postToFanpage($contentDetail, $acc_share);

        return true;
    }

    public function getContentAction()
    {

        $params = $this->request->getParams();
        $PID = $params['pid'];
        if (!empty($PID)) {
            shell_exec('kill -9 ' . $PID);
        }

        $instanceSearchKeyword = new \My\Search\Keyword();
        $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');
        //
        $limit = 100;
        $arr_keyword = $instanceSearchKeyword->getListLimit(['content_crawler' => 1, 'key_id_greater' => 333377], 1, $limit, ['key_id' => ['order' => 'asc']]);

        foreach ($arr_keyword as $keyword) {
            //$url = 'http://coccoc.com/composer?q=' . rawurlencode($keyword['key_name']) . '&p=0&reqid=UqRAi2nK&_=1480603345568';

            $url = 'https://www.google.com/search?sclient=psy-ab&biw=1366&bih=212&espv=2&q=' . rawurlencode($keyword['key_name']) . '&oq=' . rawurlencode($keyword['key_name']);

            $content = General::crawler($url);
            $dom = new Query($content);
            $results = $dom->execute('span.st');

            $arr_content_crawler = array();
            foreach ($results as $item) {
                $arr_item = array(
                    'description' => $item->textContent
                );

                $arr_content_crawler[] = $arr_item;
            }

            $arr_update = array(
                'content_crawler' => json_encode($arr_content_crawler)
            );
            $serviceKeyword->edit($arr_update, $keyword['key_id']);
            sleep(rand(6, 10));
        }
        $this->flush();
        unset($arr_keyword);
        exec("ps -ef | grep -v grep | grep getcontent | awk '{ print $2 }'", $PID);
        return shell_exec('php ' . PUBLIC_PATH . '/index.php getcontent --pid=' . current($PID));
    }

    public function setContentAction()
    {

        try {
            $filename = "Set_Content";
            $arrData = array();
            $params = $this->request->getParams();
            //
            $instanceSearchKeyword = new \My\Search\Keyword();
            $instanceSearchContent = new \My\Search\Content();
            $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');

            // $arrKeyList = $instanceSearchKeyword->getListLimit(['key_content' => 0,'not_cate_id' => -2,'key_id_greater' => 922000], 1, 1, ['key_id' => ['order' => 'asc']]);
            // print_r($arrKeyList);die;
            $intLimit = 1000;
            $intPage = $params['page'];

            //for($intPage = 1001; $intPage < 10000;$intPage ++){
            $arrKeyList = $instanceSearchKeyword->getLimit([], $intLimit, ['key_id' => ['order' => 'asc']]);

            if (empty($arrKeyList)) {
                return;
            }
            foreach ($arrKeyList as $keyword) {
                $arr_condition_content = array(
                    'cont_status' => 1,
                    'full_text_title' => $keyword['key_name']
                );
                if ($keyword['cate_id'] != -1 && $keyword['cate_id'] != -2) {
                    $arr_condition_content['in_cate_id'] = array($keyword['cate_id']);
                }

                $arrContentList = $instanceSearchContent->getListLimit($arr_condition_content, 1, 15, ['_score' => ['order' => 'desc']], array('cont_id'));

                $text_cont_id = '';
                if (!empty($arrContentList)) {
                    $arr_cont_id = array();
                    foreach ($arrContentList as $content) {
                        $arr_cont_id[] = $content['cont_id'];
                    }
                    $text_cont_id = implode(',', $arr_cont_id);
                }

                $arr_update = array(
                    'key_content' => $text_cont_id,
                    'content_crawler' => '1'
                );
                $serviceKeyword->edit($arr_update, $keyword['key_id']);

                $arrData['Data']['Keyword'][] = $keyword['key_id'];
                $arrData['Params']['Page'] = $intPage;

                General::writeLog($filename, $arrData);

                $this->flush();
            }
            //}
            $next_page = $intPage + 1;
            return shell_exec("php /var/www/tintuc360/html/public/index.php setcontent --page=" . $next_page);
        } catch (\Exception $exc) {
            echo $exc->getMessage();
            die;
        }
    }

    public function checkProcessAction()
    {
        $params = $this->request->getParams();
        $process_name = $params['name'];
        if (empty($process_name)) {
            return true;
        }

        exec("ps -ef | grep -v grep | grep '.$process_name.' | awk '{ print $2 }'", $PID);
        exec("ps -ef | grep -v grep | grep getcontent | awk '{ print $2 }'", $current_PID);

        if (empty($PID)) {
            switch ($process_name) {
                case 'getcontent':
                    shell_exec('php ' . PUBLIC_PATH . '/index.php getcontent --pid=' . current($current_PID));
                    break;
                case 'crawlerkeyword':
                    shell_exec('php ' . PUBLIC_PATH . '/index.php crawlerkeyword --pid=' . current($current_PID));
                    break;
            }
        }

        return true;
    }

    public function crawlerAction()
    {
        $arr_url = array(
            2 => 'ung-dung',
            3 => 'he-thong',
            4 => 'ios', 'android',
            5 => 'phan-cung',
            //
            7 => 'bi-an-chuyen-la',
            8 => 'suc-khoe',
            9 => 'kham-pha-thien-nhien',
            14 => 'kham-pha-khoa-hoc',
            //
            11 => 'ki-nang',
            12 => 'lam-dep',
            13 => 'meo-vat'
        );
        foreach ($arr_url as $cate => $url) {
            $this->__quantrimang($url, $cate);
            sleep(5);
        }

        return true;
    }

    public function __quantrimang($tail_url, $cate)
    {
        $serviceContent = $this->serviceLocator->get('My\Models\Content');
        $upload_dir = General::mkdirUpload();

        $page = 1;
        $url_default = 'https://quantrimang.com/';
        $url_crawler = $url_default . $tail_url;
        //
        $url = $url_crawler . '?p=' . $page;
        $content = General::crawler($url);
        $dom = HtmlDomParser::str_get_html($content);


        $dom_link_content = $dom->find('div.listview ul li.listitem a.title');
        $dom_link_image = $dom->find('div.listview ul li.listitem a.thumb img');
        $dom_description = $dom->find('div.listview ul li.listitem div.desc');

        $arr_link_content = array();
        $arr_link_image = array();
        $arr_description = array();
        // arr content
        foreach ($dom_link_content as $conent) {
            $link_content = '';
            if ($conent->href) {
                $link_content = $conent->href;
            }
            $arr_link_content[] = $link_content;
        }

        // arr image
        foreach ($dom_link_image as $img) {
            $link_img = '';
            if ($img->src) {
                $link_img = $img->src;
            }
            $arr_link_image[] = $link_img;
        }

        // arr description
        foreach ($dom_description as $desc) {
            $description = '';
            if ($desc->plaintext) {
                $description = $desc->plaintext;
            }
            $arr_description[] = $description;
        }

        if (empty($arr_link_content)) {
            return false;
        }

        //
        foreach ($arr_link_content as $index => $item) {
            if ($index == 2) {
                break;
            }
            $content = General::crawler(General::SITE_CRAWLER . $item);
            //$content = General::crawler('http://news.sky.com/story/european-parliament-demands-brexit-talks-role-as-it-picks-president-10732038');

            if ($content == false) {
                continue;
            }

            $html = HtmlDomParser::str_get_html($content);

            $arr_data = array();
            if ($html->find('div.content-detail', 0)) {

                $cont_title = trim(html_entity_decode($html->find("div.post-detail h1", 0)->plaintext));
                $arr_data['cont_title'] = $cont_title;
                $arr_data['cont_slug'] = General::getSlug($cont_title);

                //check post exist
                $arrConditionContent = [
                    'cont_slug' => $arr_data['cont_slug'],
                    'not_cont_status' => -1
                ];

                $arrContent = $serviceContent->getDetail($arrConditionContent);
                if (!empty($arrContent)) {
                    echo \My\General::getColoredString("Exist this content:" . $arr_data['cont_slug'], 'red');
                    continue;
                }

                //get content detail
                $cont_description = $arr_description[$index];
                $cont_description = str_replace(General::NAME_CRAWLER, General::SITE_NAME, $cont_description);


                $html->find('div.content-detail div#adsarticletop', 0)->innertext = '';
                $cont_detail = $html->find('div.content-detail', 0)->outertext;
                $cont_detail = str_replace(General::NAME_CRAWLER, General::SITE_NAME, $cont_detail);
                $cont_detail = str_replace('<div id="adsarticletop" class="adbox"></div>', self::DIV_ADS, $cont_detail);
                //
                $link_content = $html->find("div.content-detail a");

                if (count($link_content) > 0) {
                    foreach ($link_content as $link) {
                        $href = $link->href;
                        $cont_detail = str_replace($href, BASE_URL, $cont_detail);
                    }
                }


                //get image
                $arr_image = $html->find("div.content-detail img");
                if (count($arr_image) > 0) {
                    foreach ($arr_image as $key => $img) {
                        $src = $img->src;
                        $extension = end(explode('.', end(explode('/', $src))));
                        $name_img = $arr_data['cont_slug'] . '_' . ($key + 1) . '.' . $extension;
                        $image_content = General::crawler($src);
                        //
                        if ($image_content) {
                            file_put_contents($upload_dir['path'] . '/' . $name_img, $image_content);
                            $cont_detail = str_replace($src, $upload_dir['url'] . '/' . $name_img, $cont_detail);
                        } else {
                            $cont_detail = str_replace($src, self::IMAGE_DEFAULT, $cont_detail);
                        }
                    }
                }
                // MAIN IMAGE
                if ($arr_link_image[$index]) {
                    $src = $arr_link_image[$index];
                    $extension = end(explode('.', end(explode('/', $src))));
                    $name_img = $arr_data['cont_slug'] . '.' . $extension;
                    $image_content = General::crawler($src);
                    if ($image_content) {
                        file_put_contents($upload_dir['path'] . '/' . $name_img, $image_content);
                        $arr_data['cont_main_image'] = $upload_dir['url'] . '/' . $name_img;
                    } else {
                        $arr_data['cont_main_image'] = self::IMAGE_DEFAULT;
                    }
                } else {
                    $arr_data['cont_main_image'] = self::IMAGE_DEFAULT;
                }

                //

                $arr_data['cont_detail'] = html_entity_decode($cont_detail);
                $arr_data['cont_description'] = $cont_description;
                $arr_data['created_date'] = time();
                $arr_data['cate_id'] = $cate;
                $arr_data['cont_views'] = 0;
                $arr_data['cont_status'] = 1;

                //insert Data
                $id = $serviceContent->add($arr_data);

                if ($id) {
                    echo \My\General::getColoredString("Crawler success 1 post id = {$id} \n", 'green');
                } else {
                    echo \My\General::getColoredString("Can not insert content db", 'red');
                }
            }
            sleep(3);
        }
        return true;
    }

    public function searchFullText($object, $str_search)
    {
        $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');
        $serviceContent = $this->serviceLocator->get('My\Models\Content');
        //
        $intPage = 1;
        $intLimit = 10;
        switch ($object) {
            case 'keyword':
                $arr_condition = array(
                    'fulltext_key_name' => $str_search
                );
                $arr_keyword = $serviceKeyword->getListLimit($arr_condition, $intPage, $intLimit);
                $str_id = '';
                foreach ($arr_keyword as $keyword) {
                    $str_id .=  $keyword . ',';
                }
                $result = rtrim($str_id, ',');
                break;
            case 'content':
                $arr_condition = array(
                    'fulltext_cont_title' => $str_search
                );
                $arr_content = $serviceContent->getListLimit($arr_condition, $intPage, $intLimit,'cont_id DESC', 'cont_id');
                $str_id = '';
                foreach ($arr_content as $content) {
                    $str_id .=  $content . ',';
                }
                $result = rtrim($str_id, ',');
                break;
        }

        return $result;
    }

    function testAction()
    {
        $intPage = 1;
        $intLimit = 20;
        $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');
        $arr_keyword = $serviceKeyword->getListLimit(array('key_status' => 1), $intPage, $intLimit);

        foreach ($arr_keyword as $keyword) {
            $key_slug = General::getSlug($keyword['key_name']);
            $serviceKeyword->edit(array('key_slug' => $key_slug), $keyword['key_id']);
        }
        die("done");
    }
}
