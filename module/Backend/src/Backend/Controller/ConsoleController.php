<?php

namespace Backend\Controller;

use My\General,
    My\Controller\MyController,
    Zend\Dom\Query,
    Sunra\PhpSimple\HtmlDomParser;

class ConsoleController extends MyController
{
    const IMAGE_DEFAULT = STATIC_URL . '/f/v1/images/no-image-available.jpg';
    const DIV_ADS = '<div id="adsarticletop" class="adbox">
<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
		<!-- tuoitre.mobi -->
		<ins class="adsbygoogle"
			 style="display:inline-block;"
			 data-ad-client="ca-pub-9166980393030854"
			 data-ad-slot="5007199245"></ins>
		<script>
		(adsbygoogle = window.adsbygoogle || []).push({});
		</script>
		</div>';

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
        $params = $this->request->getParams();
        $PID = $params['pid'];
        if (!empty($PID)) {
            shell_exec('kill -9 ' . $PID);
        }

        $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');
        //
        $match = [
            '', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'x', 'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'
        ];

        $arr_keyword = current($serviceKeyword->getListLimit(['is_crawler' => 0], 1, 1, 'key_id ASC, key_weight DESC'));

        if (empty($arr_keyword)) {
            return;
        }

        $keyword = $arr_keyword['key_name'];
        $count = str_word_count($keyword);
        if ($count > 6) {
            $int_result = $serviceKeyword->edit(array('is_crawler' => 1, 'key_weight' => 1), $arr_keyword['key_id']);
            unset($serviceKeyword);

            if ($int_result) {
                echo \My\General::getColoredString("Crawler success keyword_id = {$arr_keyword['key_id']}", 'green');
            }

            $this->flush();
            unset($arr_keyword);
            exec("ps -ef | grep -v grep | grep crawlerkeyword | awk '{ print $2 }'", $PID);
            return shell_exec('php ' . PUBLIC_PATH . '/index.php crawlerkeyword --pid=' . current($PID));

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
                    $this->add_keyword(json_decode($return)[1]);
                    continue;
                }
            }
            sleep(3);
        };

        $int_result = $serviceKeyword->edit(array('is_crawler' => 1, 'key_weight' => 1), $arr_keyword['key_id']);
        unset($serviceKeyword);

        if ($int_result) {
            echo \My\General::getColoredString("Crawler success keyword_id = {$arr_keyword['key_id']}", 'green');
        }

        sleep(3);

        $this->flush();
        unset($arr_keyword);
        exec("ps -ef | grep -v grep | grep crawlerkeyword | awk '{ print $2 }'", $PID);
        return shell_exec('php ' . PUBLIC_PATH . '/index.php crawlerkeyword --pid=' . current($PID));
    }

    public function getKeyword()
    {
        $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');
        //
        $match = [
            '', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'x', 'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'
        ];

        $arr_keyword = current($serviceKeyword->getListLimit(['is_crawler' => 0], 1, 1, 'key_id ASC, key_weight DESC'));

        if (empty($arr_keyword)) {
            return;
        }

        $keyword = $arr_keyword['key_name'];
        $count = str_word_count($keyword);
        if ($count > 6) {
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
                    $this->add_keyword(json_decode($return)[1]);
                    continue;
                }
            }
            sleep(3);
        };

        $int_result = $serviceKeyword->edit(array('is_crawler' => 1, 'key_weight' => 1), $arr_keyword['key_id']);
        unset($serviceKeyword);

        if ($int_result) {
            echo \My\General::getColoredString("Crawler success keyword_id = {$arr_keyword['key_id']}", 'green');
        }

        sleep(3);
        $this->getKeyword();
    }

    public function add_keyword($arr_key)
    {
        if (empty($arr_key)) {
            return false;
        }

        $arr_block_string = General::blockString();

        $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');
        foreach ($arr_key as $key_word) {

            $word_slug = trim(General::getSlug($key_word));
            $is_exsit = $serviceKeyword->getDetail(['key_slug' => $word_slug]);

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
        $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');
        $intLimit = 4000;
        for ($intPage = 1; $intPage < 10000; $intPage++) {
            $file = PUBLIC_PATH . '/xml/keyword-' . $intPage . '.xml';
            $arrKeyList = $serviceKeyword->getListLimit(['not_content_crawler' => 1], $intPage, $intLimit, 'key_id ASC');

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
       $PID = isset($params['pid']) ? $params['pid'] : 123;
       if (!empty($PID)) {
           shell_exec('kill -9 ' . $PID);
       }
	   
        $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');
        //
        $limit = 100;
        $arr_keyword = $serviceKeyword->getListLimit(['content_crawler' => 1], 1, $limit, 'key_id ASC');

        if (empty($arr_keyword)) {
            return;
        }
        //
        foreach ($arr_keyword as $keyword) {
            //$url = 'http://coccoc.com/composer?q=' . rawurlencode($keyword['key_name']) . '&p=0&reqid=UqRAi2nK&_=1480603345568';

            $url = 'https://www.google.com.vn/search?sclient=psy-ab&biw=1366&bih=212&espv=2&q=' . rawurlencode($keyword['key_name']) . '&oq=' . rawurlencode($keyword['key_name']);
// print_r($keyword);die;
            $content = General::crawler($url);

//            $dom = HtmlDomParser::str_get_html($content);
//            $dom_a = $dom->find('div._NId div.g div.rc h3.r a');
//            $dom_description = $dom->find('div._NId div.g div.rc div.s span.st');

            $dom = new Query($content);
            $dom_a = $dom->execute('div.g div.rc h3.r a');
            $dom_description = $dom->execute('div.g div.rc div.s span.st');

            $arr_link = array();
            foreach ($dom_a as $a) {
                $arr_link[] = array(
                    'link' => $a->getAttribute('href'),
                    'title' => $a->textContent
                );
            }

            $arr_description = array();
            foreach ($dom_description as $desc) {
                $arr_description[] = $desc->textContent;
            }

            $arr_content_crawler = array();
            foreach ($arr_link as $key => $item) {
                $arr_item = array(
                    'href' => $item['link'],
                    'title' => $item['title'],
                    'description' => isset($arr_description[$key]) ? $arr_description[$key] : ''
                );

                $arr_content_crawler[] = $arr_item;
            }

            $arr_update = array(
                'content_crawler' => json_encode($arr_content_crawler),
                'content_id' => $this->searchFullText('content', $keyword['key_name'], 15)
            );
            $serviceKeyword->edit($arr_update, $keyword['key_id']);
            sleep(rand(6, 10));
        }
        //
		
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
				print_r('php ' . PUBLIC_PATH . '/index.php getcontent --pid=' . current($current_PID));die;
                    shell_exec('php ' . PUBLIC_PATH . '/index.php getcontent --pid=1');
                    break;
            }
        }

        return true;
    }

    public function crawlerContentAction()
    {
        $arr_url = array(
            2 => 'ung-dung',
            3 => 'he-thong',
            4 => array('ios', 'android'),
            5 => 'phan-cung',
            //
            7 => 'bi-an-chuyen-la',
            8 => 'suc-khoe',
            9 => 'kham-pha-thien-nhien',
            14 => 'kham-pha-khoa-hoc',
            //
            11 => array('ki-nang', 'am-thuc'),
            12 => 'lam-dep',
            13 => array('meo-vat', 'chon-qua-tang', 'giang-sinh-noel', 'tet')
        );
        //
        foreach ($arr_url as $cate => $url) {
            if (is_array($url)) {
                foreach ($url as $link) {
                    $this->__quantrimang($link, $cate);
                }
            } else {
                $this->__quantrimang($url, $cate);
            }
            sleep(5);
        }

        return true;

    }

    public function __quantrimang($tail_url, $cate)
    {
        $serviceContent = $this->serviceLocator->get('My\Models\Content');
        $upload_dir = General::mkdirUpload();

        for ($page = 1; $page < 2; $page++) {
            $url_default = 'https://quantrimang.com/';
            $url_crawler = $url_default . $tail_url;
            //
            $url = $url_crawler . '?p=' . $page;
            $content = General::crawler($url);
            $dom = HtmlDomParser::str_get_html($content);


            $dom_link_content = $dom->find('div.listview ul li.listitem a.title');
            $dom_link_image = $dom->find('div.listview ul li.listitem a.thumb img');
            $dom_description = $dom->find('div.listview ul li.listitem div.desc');

            if (empty($dom_link_content) || empty($dom_link_image) || empty($dom_description)) {
                break;
            }
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
                    $arr_data['cont_keyword'] = 1;

                    //insert Data
                    $id = $serviceContent->add($arr_data);

                    if ($id) {
                        echo \My\General::getColoredString("Crawler success 1 post id = {$id} \n", 'green');
                    } else {
                        echo \My\General::getColoredString("Can not insert content db", 'red');
                    }
                }
                //
                sleep(3);
            }
        }
        return true;
    }

    public function searchFullText($object, $str_search, $intLimit = 15)
    {
        $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');
        $serviceContent = $this->serviceLocator->get('My\Models\Content');
        //
        $intPage = 1;
        switch ($object) {
            case 'keyword':
                $arr_condition = array(
                    'fulltext_key_name' => $str_search
                );
                $arr_keyword = $serviceKeyword->getListLimitJob($arr_condition, $intPage, $intLimit, '', 'key_id');
                //
                if (empty($arr_keyword)) {
                    $total = $serviceKeyword->getTotal();
                    $arr_id = array();
                    for ($i = 1; $i <= 15; $i++) {
                        $arr_id[] = rand(1, $total);
                    }
                    $arr_keyword = $serviceKeyword->getListLimit(['in_key_id' => implode(',', $arr_id)], 1, $intLimit, 'key_id ASC', 'key_id');
                }

                $str_id = '';
                if (!empty($arr_keyword)) {
                    foreach ($arr_keyword as $keyword) {
                        $str_id .= $keyword['key_id'] . ',';
                    }
                }
                $result = rtrim($str_id, ',');
                break;
            case 'content':
                $arr_condition = array(
                    'fulltext_cont_title' => $str_search
                );
                $arr_content = $serviceContent->getListLimitJob($arr_condition, $intPage, $intLimit, '', 'cont_id');

                //
                if (empty($arr_content)) {
                    $total = $serviceContent->getTotal();
                    $arr_id = array();
                    for ($i = 1; $i <= 15; $i++) {
                        $arr_id[] = rand(1, $total);
                    }
                    $arr_content = $serviceContent->getListLimit(['in_cont_id' => implode(',', $arr_id)], 1, $intLimit, 'cont_id ASC', 'cont_id');
                }
                $str_id = '';

                if (!empty($arr_content)) {
                    foreach ($arr_content as $content) {
                        $str_id .= $content['cont_id'] . ',';
                    }
                }

                $result = rtrim($str_id, ',');
                break;
        }

        return $result;
    }

    function getKeywordContentAction()
    {
        $intLimit = 20;
        $intPage = 1;
        for ($i = 1; $i <= 10000; $i++) {
            $serviceContent = $this->serviceLocator->get('My\Models\Content');

            $arr_content = $serviceContent->getListLimit(array('cont_keyword' => '1'), $intPage, $intLimit, 'cont_id ASC', 'cont_id, cont_title');

            if (empty($arr_content)) {
                break;
            }
            //
            foreach ($arr_content as $content) {
                //
                $list_keyword = $this->searchFullText('keyword', $content['cont_title'], 15);
                //edit content
                $serviceContent->edit(array('cont_keyword' => $list_keyword), $content['cont_id']);

                sleep(1);
            }
        }
        print_r("done");
    }

    function getContentKeywordAction()
    {
        $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');

        $intLimit = 20;

        for ($i = 1; $i < 1000; $i++) {
            $arr_keyword = $serviceKeyword->getListLimit(array('content_id' => 1), 1, $intLimit, 'key_id ASC');

            if (empty($arr_keyword)) {
                break;
            }
            //
            foreach ($arr_keyword as $keyword) {

                $list_content = $this->searchFullText('content', $keyword['key_name'], 15);
                //edit content
                $serviceKeyword->edit(array('content_id' => $list_content), $keyword['key_id']);
                sleep(0.5);
            }
        }
        print_r("done");
    }

    public function initKeywordAction()
    {
        $string = 'Instagram,video,lưu,livestream,tính năng,tương tác,thú vị,biến mất,quá trình,người dùng,bản cập nhật,mới nhất,khả năng,comment,like,người xem,thiết bị,tính năng,facebook,tự động,ứng dụng,di động,live video,mặc định,vĩnh viễn,biến mất,cải thiện,quản lý,lựa chọn,phiên bản,Android,IOS,cập nhật,google assistant,tính năng,trợ lý ảo,điện thoại,tiện ích,giọng nói,ứng dụng,cuộc hẹn,remind,khẩu lệnh,quen thuôc,thực hiện,nút Home,đơn giản,gợi ý,ngón tay,cài đặt,chat,kết nối,trò chuyện,Siri,Apple,dữ liệu di động,địa điểm,du lịch,thao tác,hẹn giờ,chơi nhạc,bản đồ,màn hình,liên quan,công cụ,tự động,tìm kiếm,kết quả,đặt lịch,đặt chỗ,phím,danh bạ,biệt danh,nhân vật,yêu thích,xác nhận,,biểu tượng,người yêu,phát ra,âm thanh,khó chịu,yên lặng,góc phải,Bluetooth,ra lệnh,chế độ,máy bay,danh sách,mua hàng,chơi,hài hước,trích dẫn,mới nhất,Android SDK,hệ điều hành,mobile,thế giới,công nghệ,điên đảo,cơ hội,tiếp cận,may mắn,cung cấp,phiên bản,mô phỏng,chạy thử,,lưu,kích hoạt,tập tin,tệp nén,giải nén,thư mục,hướng dẫn,gỡ bỏ,truy cập,trang web,chính thức,trợ giúp,terminal,thông tin,chạy lệnh,đường dẫn,giải thích,phần mềm,smartphone,WiFi,hữu ích,tốc độ,thanh trạng thái,thời gian thực,mất mã,Passcode,đăng nhập,tài khoản,khôi phục,mật khẩu,tính năng,định vị,Click,mở khóa,kích hoạt,mã số,ra mắt,sự kiện,samsung,tổng hợp,kỹ thuật,thế hệ,tổng thể,màu sắc,trang tin,phục vụ,nhu cầu,bộ nhớ,nghiên cứu,toàn cầu,giá dự kiến,bảo hành,chính thức,chương trình,đặt hàng,mâu thuẫn,camera,lấy nét,cải tiến,điều kiện ánh sáng,camera kép,điểm yếu,bộ cảm biến,định dạng,chống nước,ứng dụng VR,quét vân tay,giắc cắm,bộ xử lý,pin,hiệu năng,tốc độ,hiệu suất,thị trường,hãng điện thoại,khe cắm,dung lượng pin,sạc pin,không dây,doanh thu,tăng trưởng,đồ gia dụng,nhà thông minh,phân tích,ngoại vi,bàn phím,máy quét,nhận dạng,đóng băng,nhiệt độ cao,biến hình,hình dáng,hiện tượng,giả tưởng,phù thủy,nhà khoa học,chất liệu,điều nghịch lý,phủ nano,bộ phim,giáng sinh,tết,gia đình,bạn gái,hóa trang,đạo diễn,năng lượng tái tạo,tiêu thụ,năng lượng,điện năng,kỷ lục,ghế nhựa,áp lực khí,không gian,chân không,áp lực,ảnh hưởng,độ bền,nhà sưu tầm,sở hữu,giá trị,bảo vật,rượu vang,đắt nhất thế giới,bán đấu giá,từ thiện,quảng cáo,súng ngắn,tổng thống,tiền cổ,xe hơi,điện ảnh,chạm khắc,xe mô tô,đồng hồ,kim cương,khôn ngoan,âm nhạc,nhạc cụ,cải thiện,chỉ số IQ,thân hình,vòng eo,giáo dục,sữa mẹ,trẻ em,trí nhớ,thuận tay trái,phản biện,sáng tạo,chiều cao,hài hước,tò mò,tư duy,nổi loạn,trí tuệ,dậy sớm,sản sinh,pha pitstop,thợ cơ khí,năng lượng gió,trang trại,tuabin gió,người giàu,hoàng đế,khối tài sản,tỉ phú,đạo Hồi,học giả,nghệ sĩ,đồ lưu niệm,lạm phát,phân hủy,trái đất,quỹ đạo,hành tinh,nhà khoa học,Basilica Therma,khách du lịch,pho tượng,La Mã,bức tượng,khai quật,tham quan,điện giật,chết người,trăng tròn,siêu trăng,ngôi sao,vũ trụ,bầu trời,trọng lực,cặp đôi,gái xinh,tâm lý con gái,ngoại hình,tích cách,tâm lý gia đình,quan hệ,độc thân,bệnh tim mạch,yêu nhầm người,tình yêu,dạy trẻ,đồ chơi Lego,tài năng,kỳ thi,kỹ năng,cuộc sống hiện nay,khắc nghiệt,tự tin,trò chuyện,làm sạch đồ,mẹo vặt,bí kíp,thủ thuật,phương pháp,căn hộ,không gian nhỏ,sắp xếp đồ,không gian làm việc,tiết kiệm';
        $arr_keyword = explode(',', $string);

        for ($i = 1; $i <= 3; $i++) {
            shuffle($arr_keyword);
        }
        $this->add_keyword($arr_keyword);
        //
        die("done");
    }

    function testAction()
    {
        $this->getContentKeyword();
        die("done");
    }
}
