<?php

namespace Backend\Controller;

use My\General,
    My\Controller\MyController,
    Zend\Dom\Query,
    Sunra\PhpSimple\HtmlDomParser;

class ConsoleController extends MyController
{

    protected static $_arr_worker = [
        'content', 'keyword'
    ];

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

    public function indexAction()
    {
        die();
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

    public function workerAction()
    {
        $params = $this->request->getParams();

        //stop all job
        if ($params['stop'] === 'all') {
            if ($params['type'] || $params['background']) {
                return General::getColoredString("Invalid params \n", 'light_cyan', 'red');
            }
            exec("ps -ef | grep -v grep | grep 'type=" . WORKER_PREFIX . "-*' | awk '{ print $2 }'", $PID);

            if (empty($PID)) {
                return General::getColoredString("Cannot found PID \n", 'light_cyan', 'red');
            }

            foreach ($PID as $worker) {
                shell_exec("kill " . $worker);
                echo General::getColoredString("Kill worker with PID = {$worker} stopped running in background \n", 'green');
            }

            return true;
        }

        $arr_worker = self::$_arr_worker;
        if (in_array(trim($params['stop']), $arr_worker)) {
            if ($params['type'] || $params['background']) {
                return General::getColoredString("Invalid params \n", 'light_cyan', 'red');
            }
            $stopWorkerName = WORKER_PREFIX . '-' . trim($params['stop']);
            exec("ps -ef | grep -v grep | grep 'type={$stopWorkerName}' | awk '{ print $2 }'", $PID);
            $PID = current($PID);
            if ($PID) {
                shell_exec("kill " . $PID);
                return General::getColoredString("Job {$stopWorkerName} is stopped running in background \n", 'green');
            } else {
                return General::getColoredString("Cannot found PID \n", 'light_cyan', 'red');
            }
        }

        $worker = General::getWorkerConfig();
        switch ($params['type']) {
            case WORKER_PREFIX . '-logs':
                //start job in background
                if ($params['background'] === 'true') {
                    $PID = shell_exec("nohup php " . PUBLIC_PATH . "/index.php worker --type=" . WORKER_PREFIX . "-logs >/dev/null & echo 2>&1 & echo $!");
                    if (empty($PID)) {
                        echo General::getColoredString("Cannot deamon PHP process to run job " . WORKER_PREFIX . "-logs in background. \n", 'light_cyan', 'red');
                        return;
                    } else {
                        echo General::getColoredString("Job " . WORKER_PREFIX . "-logs is running in background ... \n", 'green');
                    }
                }

                $funcName1 = SEARCH_PREFIX . 'writeLog';
                $methodHandler1 = '\My\Job\JobLog::writeLog';
                $worker->addFunction($funcName1, $methodHandler1, $this->serviceLocator);

                break;

            case WORKER_PREFIX . '-content':
                //start job in background
                if ($params['background'] === 'true') {
                    $PID = shell_exec("nohup php " . PUBLIC_PATH . "/index.php worker --type=" . WORKER_PREFIX . "-content >/dev/null & echo 2>&1 & echo $!");
                    if (empty($PID)) {
                        echo General::getColoredString("Cannot deamon PHP process to run job " . WORKER_PREFIX . "-content in background. \n", 'light_cyan', 'red');
                        return;
                    }
                    echo General::getColoredString("Job " . WORKER_PREFIX . "-content is running in background ... \n", 'green');
                }

                $funcName1 = SEARCH_PREFIX . 'writeContent';
                $methodHandler1 = '\My\Job\JobContent::writeContent';
                $worker->addFunction($funcName1, $methodHandler1, $this->serviceLocator);

                $funcName2 = SEARCH_PREFIX . 'editContent';
                $methodHandler2 = '\My\Job\JobContent::editContent';
                $worker->addFunction($funcName2, $methodHandler2, $this->serviceLocator);

                $funcName3 = SEARCH_PREFIX . 'multiEditContent';
                $methodHandler3 = '\My\Job\JobContent::multiEditContent';
                $worker->addFunction($funcName3, $methodHandler3, $this->serviceLocator);

                break;

            case WORKER_PREFIX . '-mail':
                //start job in background
                if ($params['background'] === 'true') {
                    $PID = shell_exec("nohup php " . PUBLIC_PATH . "/index.php worker --type=" . WORKER_PREFIX . "-mail >/dev/null & echo 2>&1 & echo $!");
                    if (empty($PID)) {
                        echo General::getColoredString("Cannot deamon PHP process to run job " . WORKER_PREFIX . "-mail in background. \n", 'light_cyan', 'red');
                        return;
                    }
                    echo General::getColoredString("Job " . WORKER_PREFIX . "-mail is running in background ... \n", 'green');
                }

                $funcName1 = SEARCH_PREFIX . 'sendMail';
                $methodHandler1 = '\My\Job\JobMail::sendMail';
                $worker->addFunction($funcName1, $methodHandler1, $this->serviceLocator);

                break;

            case WORKER_PREFIX . '-category':
                //start job in background
                if ($params['background'] === 'true') {
                    $PID = shell_exec("nohup php " . PUBLIC_PATH . "/index.php worker --type=" . WORKER_PREFIX . "-category >/dev/null & echo 2>&1 & echo $!");
                    if (empty($PID)) {
                        echo General::getColoredString("Cannot deamon PHP process to run job " . WORKER_PREFIX . "-category in background. \n", 'light_cyan', 'red');
                        return;
                    }
                    echo General::getColoredString("Job " . WORKER_PREFIX . "-category is running in background ... \n", 'green');
                }

                $funcName1 = SEARCH_PREFIX . 'writeCategory';
                $methodHandler1 = '\My\Job\JobCategory::writeCategory';
                $worker->addFunction($funcName1, $methodHandler1, $this->serviceLocator);

                $funcName2 = SEARCH_PREFIX . 'editCategory';
                $methodHandler2 = '\My\Job\JobCategory::editCategory';
                $worker->addFunction($funcName2, $methodHandler2, $this->serviceLocator);

                $funcName3 = SEARCH_PREFIX . 'multiEditCategory';
                $methodHandler3 = '\My\Job\JobCategory::multiEditCategory';
                $worker->addFunction($funcName3, $methodHandler3, $this->serviceLocator);

                break;

            case WORKER_PREFIX . '-user':
                //start job in background
                if ($params['background'] === 'true') {
                    $PID = shell_exec("nohup php " . PUBLIC_PATH . "/index.php worker --type=" . WORKER_PREFIX . "-user >/dev/null & echo 2>&1 & echo $!");
                    if (empty($PID)) {
                        echo General::getColoredString("Cannot deamon PHP process to run job " . WORKER_PREFIX . "-user in background. \n", 'light_cyan', 'red');
                        return;
                    }
                    echo General::getColoredString("Job " . WORKER_PREFIX . "-user is running in background ... \n", 'green');
                }

                $funcName1 = SEARCH_PREFIX . 'writeUser';
                $methodHandler1 = '\My\Job\JobUser::writeUser';
                $worker->addFunction($funcName1, $methodHandler1, $this->serviceLocator);

                $funcName2 = SEARCH_PREFIX . 'editUser';
                $methodHandler2 = '\My\Job\JobUser::editUser';
                $worker->addFunction($funcName2, $methodHandler2, $this->serviceLocator);

                $funcName3 = SEARCH_PREFIX . 'multiEditUser';
                $methodHandler3 = '\My\Job\JobUser::multiEditUser';
                $worker->addFunction($funcName3, $methodHandler3, $this->serviceLocator);

                break;

            case WORKER_PREFIX . '-general':
                //start job in background
                if ($params['background'] === 'true') {
                    $PID = shell_exec("nohup php " . PUBLIC_PATH . "/index.php worker --type=" . WORKER_PREFIX . "-general >/dev/null & echo 2>&1 & echo $!");
                    if (empty($PID)) {
                        echo General::getColoredString("Cannot deamon PHP process to run job " . WORKER_PREFIX . "-general in background. \n", 'light_cyan', 'red');
                        return;
                    }
                    echo General::getColoredString("Job " . WORKER_PREFIX . "-general is running in background ... \n", 'green');
                }

                $funcName1 = SEARCH_PREFIX . 'writeGeneral';
                $methodHandler1 = '\My\Job\JobGeneral::writeGeneral';
                $worker->addFunction($funcName1, $methodHandler1, $this->serviceLocator);

                $funcName2 = SEARCH_PREFIX . 'editGeneral';
                $methodHandler2 = '\My\Job\JobGeneral::editGeneral';
                $worker->addFunction($funcName2, $methodHandler2, $this->serviceLocator);

                break;

            case WORKER_PREFIX . '-keyword':
                //start job in background
                if ($params['background'] === 'true') {
                    $PID = shell_exec("nohup php " . PUBLIC_PATH . "/index.php worker --type=" . WORKER_PREFIX . "-keyword >/dev/null & echo 2>&1 & echo $!");
                    if (empty($PID)) {
                        echo General::getColoredString("Cannot deamon PHP process to run job " . WORKER_PREFIX . "-keyword in background. \n", 'light_cyan', 'red');
                        return;
                    }
                    echo General::getColoredString("Job " . WORKER_PREFIX . "-keyword is running in background ... \n", 'green');
                }

                $funcName1 = SEARCH_PREFIX . 'writeKeyword';
                $methodHandler1 = '\My\Job\JobKeyword::writeKeyword';
                $worker->addFunction($funcName1, $methodHandler1, $this->serviceLocator);

                $funcName2 = SEARCH_PREFIX . 'editKeyword';
                $methodHandler2 = '\My\Job\JobKeyword::editKeyword';
                $worker->addFunction($funcName2, $methodHandler2, $this->serviceLocator);

                break;

            case WORKER_PREFIX . '-group':
                //start job group in background
                if ($params['background'] === 'true') {
                    $PID = shell_exec("nohup php " . PUBLIC_PATH . "/index.php worker --type=" . WORKER_PREFIX . "-group >/dev/null & echo 2>&1 & echo $!");
                    if (empty($PID)) {
                        echo General::getColoredString("Cannot deamon PHP process to run job " . WORKER_PREFIX . "-group in background. \n", 'light_cyan', 'red');
                        return;
                    }
                    echo General::getColoredString("Job " . WORKER_PREFIX . "-group is running in background ... \n", 'green');
                }

                $funcName1 = SEARCH_PREFIX . 'writeGroup';
                $methodHandler1 = '\My\Job\JobGroup::writeGroup';
                $worker->addFunction($funcName1, $methodHandler1, $this->serviceLocator);

                $funcName2 = SEARCH_PREFIX . 'editGroup';
                $methodHandler2 = '\My\Job\JobGroup::editGroup';
                $worker->addFunction($funcName2, $methodHandler2, $this->serviceLocator);

                break;

            case WORKER_PREFIX . '-permission':
                //start job group in background
                if ($params['background'] === 'true') {
                    $PID = shell_exec("nohup php " . PUBLIC_PATH . "/index.php worker --type=" . WORKER_PREFIX . "-permission >/dev/null & echo 2>&1 & echo $!");
                    if (empty($PID)) {
                        echo General::getColoredString("Cannot deamon PHP process to run job " . WORKER_PREFIX . "-permission in background. \n", 'light_cyan', 'red');
                        return;
                    }
                    echo General::getColoredString("Job " . WORKER_PREFIX . "-permission is running in background ... \n", 'green');
                }

                $funcName1 = SEARCH_PREFIX . 'writePermission';
                $methodHandler1 = '\My\Job\JobPermission::writePermission';
                $worker->addFunction($funcName1, $methodHandler1, $this->serviceLocator);

                $funcName2 = SEARCH_PREFIX . 'editPermission';
                $methodHandler2 = '\My\Job\JobPermission::editPermission';
                $worker->addFunction($funcName2, $methodHandler2, $this->serviceLocator);

                break;

            default:
                return General::getColoredString("Invalid or not found function \n", 'light_cyan', 'red');
        }

        if (empty($params['background'])) {
            echo General::getColoredString("Waiting for job...\n", 'green');
        } else {
            return;
        }
        $this->flush();
        while (@$worker->work() || ($worker->returnCode() == GEARMAN_IO_WAIT) || ($worker->returnCode() == GEARMAN_NO_JOBS)) {
            if ($worker->returnCode() != GEARMAN_SUCCESS) {
                echo "return_code: " . $worker->returnCode() . "\n";
                break;
            }
        }
    }

    public function checkWorkerRunningAction()
    {
        $arr_worker = self::$_arr_worker;
        foreach ($arr_worker as $worker) {
            $worker_name = WORKER_PREFIX . '-' . $worker;
            exec("ps -ef | grep -v grep | grep 'type={$worker_name}' | awk '{ print $2 }'", $PID);
            $PID = current($PID);

            if (empty($PID)) {
                $command = 'nohup php ' . PUBLIC_PATH . '/index.php worker --type=' . $worker_name . ' >/dev/null & echo 2>&1 & echo $!';
                $PID = shell_exec($command);
                if (empty($PID)) {
                    echo General::getColoredString("Cannot deamon PHP process to run job {$worker_name} in background. \n", 'light_cyan', 'red');
                } else {
                    echo General::getColoredString("PHP process run job {$worker_name} in background with PID : {$PID}. \n", 'green');
                }
            }
        }
    }

    public function crontabAction()
    {
        $params = $this->request->getParams();

        if (empty($params['type'])) {
            return General::getColoredString("Unknown type or id \n", 'light_cyan', 'red');
        }

        switch ($params['type']) {

            case 'update-vip-content':
                $this->_jobUpdateVipContent();
                break;

            default:
                echo General::getColoredString("Unknown type or id \n", 'light_cyan', 'red');

                break;
        }

        return true;
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
                'created_date' => time(),
                'cate_id' => (!empty($keyword_detail) && $keyword_detail['cate_id'] == -2) ? -1 : $keyword_detail['cate_id'],
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

    public function crawlerAction()
    {
        $params = $this->request->getParams();
        $type = $params['type'];
        if (empty($type)) {
            $this->__khoahocTV();
        }

        if ($type == 'khoahocTV') {
            $this->__khoahocTV();
//            shell_exec("nohup php " . PUBLIC_PATH . "/index.php sitemap >/dev/null & echo 2>&1 & echo $!");
            return true;
        }

        //crawler xong thì tạo sitemap
//        shell_exec("nohup php " . PUBLIC_PATH . "/index.php sitemap >/dev/null & echo 2>&1 & echo $!");
        return true;
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

    public function __foxnewsTechCrawler()
    {
        $url = 'http://www.foxnews.com/tech.html';
        $cate_id = General::CATEGORY_TECH;

        $instanceSearchContent = new \My\Search\Content();
        $upload_dir = General::mkdirUpload();

        $url_page = $url;
        $content = General::crawler($url_page);
        $dom = HtmlDomParser::str_get_html($content);
        $results = $dom->find('section.top-story div.m a, 
                                div.row-2 section.news-feed li.article-ct h2 a');

        if (count($results) <= 0) {
            return;
        }

        foreach ($results as $key => $item) {
            $content = General::crawler('http://www.foxnews.com/' . $item->href);

            if ($content == false) {
                continue;
            }
            $html = HtmlDomParser::str_get_html($content);

            $arr_data = array();
            if ($html->find('div.article-text', 0)) {

                $cont_title = html_entity_decode($html->find("div.main h1", 0)->plaintext);
                $arr_data['cont_title'] = $cont_title;
                $arr_data['cont_slug'] = General::getSlug($cont_title);

                //check post exist
                $arr_detail = $instanceSearchContent->getDetail(
                    array(
                        'cont_slug' => $arr_data['cont_slug'],
                        'not_cont_status' => -1
                    )
                );
                if (!empty($arr_detail)) {
                    echo \My\General::getColoredString("Exist this content:" . $arr_data['cont_slug'], 'red');
                    continue;
                }

                $cont_description = $html->find('div.article-text p', 0)->plaintext;

                $html->find('section.sponsor-partner', 0)->outertext = '';

                $cont_detail = $html->find('div.article-text', 0)->outertext;

                $link_content = $html->find("div.article-text a");
                if (count($link_content) > 0) {
                    foreach ($link_content as $key => $link) {
                        $href = $link->href;
                        $cont_detail = str_replace($href, BASE_URL, $cont_detail);
                    }
                }

                //get image
                $arr_data['cont_main_image'] = STATIC_URL . '/f/v1/images/no-image-available.jpg';
                $arr_data['cont_resize_image'] = STATIC_URL . '/f/v1/images/no-image-available.jpg';

                $arr_image = $html->find("div.main div.m img, div.article-text img");
                if (count($arr_image) > 0) {
                    foreach ($arr_image as $key => $img) {
                        $src = $img->src;
                        $extension = end(explode('.', end(explode('/', $src))));
                        $extension = current(explode('?', $extension));
                        $name_img = $arr_data['cont_slug'] . '_' . ($key + 1) . '.' . $extension;
                        $image_content = General::crawler($src);
                        if($image_content) {
                            file_put_contents($upload_dir['path'] . '/' . $name_img, $image_content);
                        } else {
                            $image_content = General::crawler(STATIC_URL . '/f/v1/images/no-image-available.jpg');
                            file_put_contents($upload_dir['path'] . '/' . $name_img, $image_content);
                        }
                        $cont_detail = str_replace($src, $upload_dir['url'] . '/' . $name_img, $cont_detail);
                        if ($key == 0) {
                            $arr_data['cont_main_image'] = $upload_dir['url'] . '/' . $name_img;
                            $arr_data['cont_resize_image'] = $upload_dir['url'] . '/' . $name_img;
                            $results = $this->resizeImage($upload_dir,$arr_data['cont_slug'], $extension, $cate_id);
                            if($results) {
                                $arr_data['cont_resize_image'] = $results;
                            }
                        }

                    }
                }

                $arr_data['cont_detail'] = html_entity_decode($cont_detail);
                $arr_data['cont_description'] = $cont_description;
                $arr_data['created_date'] = time();
                $arr_data['cate_id'] = $cate_id;
                $arr_data['cont_views'] = 0;
                $arr_data['cont_status'] = 1;
                $arr_data['from_source'] = 'foxnews';
                //insert Data
                $serviceContent = $this->serviceLocator->get('My\Models\Content');
                $id = $serviceContent->add($arr_data);

                if ($id) {
                    echo \My\General::getColoredString("Crawler success 1 post id = {$id} \n", 'green');
                } else {
                    echo \My\General::getColoredString("Can not insert content db", 'red');
                }
            }
            sleep(5);
        }
        return true;
    }

    public function __foxnewsCrawler()
    {
        $url_page = 'http://www.foxnews.com/entertainment.html';
        $cate_id = General::CATEGORY_SHOWBIZ;
        //
        $instanceSearchContent = new \My\Search\Content();
        $upload_dir = General::mkdirUpload();

        $content = General::crawler($url_page);
        $dom = HtmlDomParser::str_get_html($content);
        $results = $dom->find('div.big-top div.info h2 a, 
                                div.big-top-cont div.content li.article-ct div.info h2 a,
                                div.latest div.content li.article-ct div.info h2 a,');

        if (count($results) <= 0) {
            return;
        }

        foreach ($results as $key => $item) {
            $content = General::crawler('http://www.foxnews.com/' . $item->href);

            if ($content == false) {
                continue;
            }
            $html = HtmlDomParser::str_get_html($content);

            $arr_data = array();
            if ($html->find('div.article-text', 0)) {

                $cont_title = html_entity_decode($html->find("div.main h1", 0)->plaintext);
                $arr_data['cont_title'] = $cont_title;
                $arr_data['cont_slug'] = General::getSlug($cont_title);

                //check post exist
                $arr_detail = $instanceSearchContent->getDetail(
                    array(
                        'cont_slug' => $arr_data['cont_slug'],
                        'not_cont_status' => -1
                    )
                );
                if (!empty($arr_detail)) {
                    echo \My\General::getColoredString("Exist this content:" . $arr_data['cont_slug'], 'red');
                    continue;
                }

                $cont_description = $html->find('div.article-text p', 0)->plaintext;

                $html->find('section.sponsor-partner', 0)->outertext = '';

                $cont_detail = $html->find('div.article-text', 0)->outertext;

                $link_content = $html->find("div.article-text a");
                if (count($link_content) > 0) {
                    foreach ($link_content as $key => $link) {
                        $href = $link->href;
                        $cont_detail = str_replace($href, BASE_URL, $cont_detail);
                    }
                }

                //get image
                $arr_data['cont_main_image'] = STATIC_URL . '/f/v1/images/no-image-available.jpg';
                $arr_data['cont_resize_image'] = STATIC_URL . '/f/v1/images/no-image-available.jpg';

                $arr_image = $html->find("div.main div.m img, div.article-text img");
                if (count($arr_image) > 0) {
                    foreach ($arr_image as $key => $img) {
                        $src = $img->src;
                        $extension = end(explode('.', end(explode('/', $src))));
                        $extension = current(explode('?', $extension));
                        $name_img = $arr_data['cont_slug'] . '_' . ($key + 1) . '.' . $extension;
                        $image_content = General::crawler($src);
                        if($image_content) {
                            file_put_contents($upload_dir['path'] . '/' . $name_img, $image_content);
                        } else {
                            $image_content = General::crawler(STATIC_URL . '/f/v1/images/no-image-available.jpg');
                            file_put_contents($upload_dir['path'] . '/' . $name_img, $image_content);
                        }
                        $cont_detail = str_replace($src, $upload_dir['url'] . '/' . $name_img, $cont_detail);
                        if ($key == 0) {
                            $arr_data['cont_main_image'] = $upload_dir['url'] . '/' . $name_img;
                            $arr_data['cont_resize_image'] = $upload_dir['url'] . '/' . $name_img;
                            $results = $this->resizeImage($upload_dir,$arr_data['cont_slug'], $extension, $cate_id);
                            if($results) {
                                $arr_data['cont_resize_image'] = $results;
                            }
                        }

                    }
                }

                $arr_data['cont_detail'] = html_entity_decode($cont_detail);
                $arr_data['cont_description'] = $cont_description;
                $arr_data['created_date'] = time();
                $arr_data['cate_id'] = $cate_id;
                $arr_data['cont_views'] = 0;
                $arr_data['cont_status'] = 1;
                $arr_data['from_source'] = 'foxnews';
                //insert Data
                $serviceContent = $this->serviceLocator->get('My\Models\Content');
                $id = $serviceContent->add($arr_data);

                if ($id) {
                    echo \My\General::getColoredString("Crawler success 1 post id = {$id} \n", 'green');
                } else {
                    echo \My\General::getColoredString("Can not insert content db", 'red');
                }
            }
            sleep(5);
        }
        return true;
    }

    public function __skynewsCrawler()
    {
        $cate_id = General::CATEGORY_WORLD;
        $instanceSearchContent = new \My\Search\Content();
        $upload_dir = General::mkdirUpload();

        $url = 'http://news.sky.com';
        $content = General::crawler($url);
        $dom = HtmlDomParser::str_get_html($content);

        $results = $dom->find('div.site-main div.sky-component-story-grid .sky-component-story-grid__card h3.sky-component-story-grid__headline a');

        if (count($results) <= 0) {
            return 0;
        }
        foreach ($results as $key => $item) {
            $content = General::crawler('http://news.sky.com/' . $item->href);
            //$content = General::crawler('http://news.sky.com/story/european-parliament-demands-brexit-talks-role-as-it-picks-president-10732038');

            if ($content == false) {
                continue;
            }
            $html = HtmlDomParser::str_get_html($content);

            $arr_data = array();
            if ($html->find('div.sky-component-story-article__body', 0)) {

                $cont_title = html_entity_decode($html->find("h1.sky-component-story-article-header__headline ", 0)->plaintext);
                $arr_data['cont_title'] = $cont_title;
                $arr_data['cont_slug'] = General::getSlug($cont_title);

                //check post exist
                $arr_detail = $instanceSearchContent->getDetail(
                    array(
                        'cont_slug' => $arr_data['cont_slug'],
                        'not_cont_status' => -1
                    )
                );
                if (!empty($arr_detail)) {
                    echo \My\General::getColoredString("Exist this content:" . $arr_data['cont_slug'], 'red');
                    continue;
                }

                //get content detail
                $cont_description = $html->find('p.sky-component-story-article-header__standfirst', 0)->plaintext;

                $html->find('div.sky-component-story-article__byline', 0)->outertext = '';
                $cont_detail = $html->find('.sky-component-story-article', 0)->outertext;

                $link_content = $html->find("div.sky-component-story-article a");
                if (count($link_content) > 0) {
                    foreach ($link_content as $key => $link) {
                        $href = $link->href;
                        $cont_detail = str_replace($href, BASE_URL, $cont_detail);
                    }
                }

                //get image
                $arr_data['cont_main_image'] = STATIC_URL . '/f/v1/images/no-image-available.jpg';
                $arr_data['cont_resize_image'] = STATIC_URL . '/f/v1/images/no-image-available.jpg';
                //
                $arr_image = $html->find("div.sdc-image img, sky-component-story-article img");
                if (count($arr_image) > 0) {
                    foreach ($arr_image as $key => $img) {
                        $src = $img->src;
                        if(!empty($img->srcset)) {
                            $arr_src = explode(',', $img->srcset);
                            $src = current(explode(' ', trim($arr_src[2])));
                        }
                        $extension = end(explode('.', end(explode('/', $src))));
                        $extension = current(explode('?', $extension));
                        $name_img = $arr_data['cont_slug'] . '_' . ($key + 1) . '.' . $extension;

                        $expl_src = explode('?', $src);
                        if(!empty($expl_src)) {
                            $image_content = General::crawler(current($expl_src));
                        } else {
                            $image_content = General::crawler($src);
                        }
                        //
                        if($image_content) {
                            file_put_contents($upload_dir['path'] . '/' . $name_img, $image_content);
                        } else {
                            $image_content = General::crawler(STATIC_URL . '/f/v1/images/no-image-available.jpg');
                            file_put_contents($upload_dir['path'] . '/' . $name_img, $image_content);
                        }
                        $cont_detail = str_replace($src, $upload_dir['url'] . '/' . $name_img, $cont_detail);
                        if ($key == 0) {
                            $arr_data['cont_main_image'] = $upload_dir['url'] . '/' . $name_img;
                            $arr_data['cont_resize_image'] = $upload_dir['url'] . '/' . $name_img;
                            $results = $this->resizeImage($upload_dir,$arr_data['cont_slug'], $extension, $cate_id);
                            if($results) {
                                $arr_data['cont_resize_image'] = $results;
                            }
                        }

                    }
                }

                $arr_data['cont_detail'] = html_entity_decode($cont_detail);
                $arr_data['cont_description'] = $cont_description;
                $arr_data['created_date'] = time();
                $arr_data['cate_id'] = 1;
                $arr_data['cont_views'] = 0;
                $arr_data['cont_status'] = 1;
                $arr_data['from_source'] = 'news.sky';

                //insert Data
                $serviceContent = $this->serviceLocator->get('My\Models\Content');
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

    public function __naturallyCrawler($page, $url)
    {
        $cate_id = General::CATEGORY_NATURALLY_SAVVY;
        $instanceSearchContent = new \My\Search\Content();
        $upload_dir = General::mkdirUpload();

        $url_page = $url . '?page=' . $page;
        $content = General::crawler($url_page);
        $dom = HtmlDomParser::str_get_html($content);
        $results = $dom->find('div.ArticleListing ul li.Article h2 a');

        if (count($results) <= 0) {
            return;
        }

        foreach ($results as $key => $item) {

            $content = General::crawler('http://naturallysavvy.com' . $item->href);
            //$content = General::crawler('http://naturallysavvy.com/eat/healthy-super-bowl-snacks');

            if ($content == false) {
                continue;
            }
            $html = HtmlDomParser::str_get_html($content);

            $arr_data = array();
            if ($html->find('.ArticleDetails .Media', 0)) {

                $cont_title = html_entity_decode($html->find(".ArticleDetails h1", 0)->plaintext);
                $arr_data['cont_title'] = $cont_title;
                $arr_data['cont_slug'] = General::getSlug($cont_title);

                //check post exist
                $arr_detail = $instanceSearchContent->getDetail(
                    array(
                        'cont_slug' => $arr_data['cont_slug'],
                        'not_cont_status' => -1
                    )
                );
                if (!empty($arr_detail)) {
                    echo \My\General::getColoredString("Exist this content:" . $arr_data['cont_slug'], 'red');
                    continue;
                }

                //get content detail
                $cont_description = $html->find('.ArticleDetails .Media p', 1)->plaintext;
                $cont_description = strip_tags(mb_substr($cont_description, 0, 300, 'UTF-8'));

                $html->find('.ArticleDetails .Media .adsbygoogle', 0)->outertext = '';
                $html->find('.ArticleDetails .Media script', 0)->outertext = '';
                $html->find('.ArticleDetails .Media p a img', 0)->outertext = '';

                $cont_detail = $html->find('.ArticleDetails .Media', 0)->outertext;

                $link_content = $html->find(".ArticleDetails .Media a");
                if (count($link_content) > 0) {
                    foreach ($link_content as $key => $link) {
                        $href = $link->href;
                        $cont_detail = str_replace($href, BASE_URL . '/category/naturally-savvy-5.html', $cont_detail);
                    }
                }

                //get image
                $arr_data['cont_main_image'] = STATIC_URL . '/f/v1/images/no-image-available.jpg';
                $arr_data['cont_resize_image'] = STATIC_URL . '/f/v1/images/no-image-available.jpg';
                $arr_image = $html->find(".ArticleDetails .Media img");
                if (count($arr_image) > 0) {

                    $url_image_ads = 'http://cdn.agilitycms.com/naturally-savvy/Images/Articles/SpecialButtons';
                    foreach ($arr_image as $key => $img) {
                        $src = $img->src;
                        if (strpos($src, $url_image_ads) !== false) {
                            continue;
                        }

                        $extension = end(explode('.', end(explode('/', $src))));
                        $extension = current(explode('?', $extension));
                        $name_img = $arr_data['cont_slug'] . '_' . ($key + 1) . '.' . $extension;
                        $image_content = General::crawler($src);
                        if($image_content) {
                            file_put_contents($upload_dir['path'] . '/' . $name_img, $image_content);
                        } else {
                            $image_content = General::crawler(STATIC_URL . '/f/v1/images/no-image-available.jpg');
                            file_put_contents($upload_dir['path'] . '/' . $name_img, $image_content);
                        }
                        $cont_detail = str_replace($src, $upload_dir['url'] . '/' . $name_img, $cont_detail);
                        if ($key == 0) {
                            $arr_data['cont_main_image'] = $upload_dir['url'] . '/' . $name_img;
                            $arr_data['cont_resize_image'] = $upload_dir['url'] . '/' . $name_img;
                            $results = $this->resizeImage($upload_dir,$arr_data['cont_slug'], $extension, $cate_id);
                            if($results) {
                                $arr_data['cont_resize_image'] = $results;
                            }
                        }
                        sleep(1);
                    }
                }

                $arr_data['cont_detail'] = html_entity_decode($cont_detail);
                $arr_data['cont_description'] = $cont_description;
                $arr_data['created_date'] = time();
                $arr_data['cate_id'] = $cate_id;
                $arr_data['cont_views'] = 0;
                $arr_data['cont_status'] = 1;
                $arr_data['from_source'] = 'http://naturallysavvy.com/';

                //insert Data
                $serviceContent = $this->serviceLocator->get('My\Models\Content');
                $id = $serviceContent->add($arr_data);

                if ($id) {
                    echo \My\General::getColoredString("Crawler success 1 post id = {$id} \n", 'green');
                } else {
                    echo \My\General::getColoredString("Can not insert content db", 'red');
                }
            }
            sleep(2);
        }
        return true;
    }

    public function __newscientistCrawler()
    {
        $cate_id = General::CATEGORY_HEALTH;
        $url_page = 'https://www.newscientist.com/subject/health/';
        $instanceSearchContent = new \My\Search\Content();
        $upload_dir = General::mkdirUpload();

        $content = General::crawler($url_page);
        $dom = HtmlDomParser::str_get_html($content);
        $results = $dom->find('div.article-content div.article-index-row .index-entry h2 a');

        if (count($results) <= 0) {
            return;
        }

        foreach ($results as $key => $item) {
            $content = General::crawler($item->href);
            //$content = General::crawler('https://www.newscientist.com/article/2120747-women-with-a-thicker-brain-cortex-are-more-likely-to-have-autism/');

            if ($content == false) {
                continue;
            }
            $html = HtmlDomParser::str_get_html($content);

            $arr_data = array();
            if ($html->find('.article-content', 0)) {

                $cont_title = html_entity_decode($html->find("h1.article-title", 0)->plaintext);
                $arr_data['cont_title'] = $cont_title;
                $arr_data['cont_slug'] = General::getSlug($cont_title);

                //check post exist
                $arr_detail = $instanceSearchContent->getDetail(
                    array(
                        'cont_slug' => $arr_data['cont_slug'],
                        'not_cont_status' => -1
                    )
                );

                if (!empty($arr_detail)) {
                    echo \My\General::getColoredString("Exist this content:" . $arr_data['cont_slug'], 'red');
                    continue;
                }

                $cont_description = $html->find('.article-content p', 2)->plaintext;
                $cont_description = strip_tags(mb_substr($cont_description, 0, 300, 'UTF-8'));

                $html->find('.article-content section.article-topics', 0)->outertext = '';

                $cont_detail = $html->find('.article-content', 0)->outertext;

                $link_content = $html->find("div.article-content a");
                if (count($link_content) > 0) {
                    foreach ($link_content as $key => $link) {
                        $href = $link->href;
                        $cont_detail = str_replace($href, BASE_URL . '/category/health-4.html', $cont_detail);
                    }
                }

                //get image
                $arr_data['cont_main_image'] = STATIC_URL . '/f/v1/images/no-image-available.jpg';
                $arr_data['cont_resize_image'] = STATIC_URL . '/f/v1/images/no-image-available.jpg';

                $arr_image = $html->find("div.article-content img");
                if (count($arr_image) > 0) {
                    foreach ($arr_image as $key => $img) {
                        $src = $img->src;
                        $extension = end(explode('.', end(explode('/', $src))));
                        $name_img = $arr_data['cont_slug'] . '_' . ($key + 1) . '.' . $extension;
                        $image_content = General::crawler($src);
                        if($image_content) {
                            file_put_contents($upload_dir['path'] . '/' . $name_img, $image_content);
                        } else {
                            $image_content = General::crawler(STATIC_URL . '/f/v1/images/no-image-available.jpg');
                            file_put_contents($upload_dir['path'] . '/' . $name_img, $image_content);
                        }
                        $cont_detail = str_replace($src, $upload_dir['url'] . '/' . $name_img, $cont_detail);
                        if ($key == 0) {
                            $arr_data['cont_main_image'] = $upload_dir['url'] . '/' . $name_img;
                            $arr_data['cont_resize_image'] = $upload_dir['url'] . '/' . $name_img;
                            $results = $this->resizeImage($upload_dir,$arr_data['cont_slug'], $extension, $cate_id);
                            if($results) {
                                $arr_data['cont_resize_image'] = $results;
                            }
                        }
                        sleep(0.5);
                    }
                }

                $arr_data['cont_detail'] = html_entity_decode($cont_detail);
                $arr_data['cont_description'] = $cont_description;
                $arr_data['created_date'] = time();
                $arr_data['cate_id'] = $cate_id;
                $arr_data['cont_views'] = 0;
                $arr_data['cont_status'] = 1;
                $arr_data['from_source'] = 'newscientist';

                //insert Data
                $serviceContent = $this->serviceLocator->get('My\Models\Content');
                $id = $serviceContent->add($arr_data);

                if ($id) {
                    echo \My\General::getColoredString("Crawler success 1 post id = {$id} \n", 'green');
                } else {
                    echo \My\General::getColoredString("Can not insert content db", 'red');
                }
            }
            sleep(2);
        }
        return true;
    }

    public function urlKidspot($type, $page) {
        $cate_id = 6;

//        $baby = array(
//            'http://www.kidspot.com.au/baby/newborn/newborn-development',
//            'http://www.kidspot.com.au/baby/newborn/newborn-sleep',
//            'http://www.kidspot.com.au/baby/newborn/new-parents',
//            'http://www.kidspot.com.au/baby/newborn/newborn-care',
//            'http://www.kidspot.com.au/baby/baby-care/bathing-and-body-care',
//            'http://www.kidspot.com.au/baby/baby-care/ask-the-child-health-nurse',
//            'http://www.kidspot.com.au/baby/baby-development/routines'
//        );

        //10
        if($page < 10 && $type == 'baby_page') {
            $baby_page = array(
                'http://www.kidspot.com.au/baby/baby-care/crying-and-colic',
                'http://www.kidspot.com.au/baby/baby-care/nappies-and-bottom-care',
                'http://www.kidspot.com.au/baby/baby-care/baby-sleep-and-settling',
                'http://www.kidspot.com.au/baby/baby-development/baby-behaviour',
                'http://www.kidspot.com.au/baby/baby-development/milestones',
                'http://www.kidspot.com.au/baby/baby-development/social-and-emotional'
            );

            foreach ($baby_page as $url) {
                $this->__kidspotCrawler($page, $url, $cate_id);
            }
        }

        //5
        if($page < 5 && $type == 'parent') {
            $parent = array(
                'http://www.kidspot.com.au/parenting/parenthood/dads',
                'http://www.kidspot.com.au/parenting/parenthood/divorce-and-separation',
                'http://www.kidspot.com.au/parenting/parenthood/siblings',
                'http://www.kidspot.com.au/parenting/parenthood/discipline',
            );
            foreach ($parent as $url) {
                $this->__kidspotCrawler($page, $url, $cate_id);
            }
        }

        //6
        if($page < 5 && $type == 'pregnancy') {
            $pregnancy = array(
                'http://www.kidspot.com.au/birth/pregnancy/signs-and-symptoms',
                'http://www.kidspot.com.au/birth/pregnancy/pregnancy-testing',
                'http://www.kidspot.com.au/birth/pregnancy/miscarriage',
                'http://www.kidspot.com.au/birth/pregnancy/foetal-health'
            );
            foreach ($pregnancy as $url) {
                $this->__kidspotCrawler($page, $url, $cate_id);
            }
        }

        //22
        if($page < 22 && $type == 'something') {
            $something = array(
                'http://www.kidspot.com.au/birth/pregnancy/pregnancy-health',
                'http://www.kidspot.com.au/parenting/parenthood/mums',
            );
            foreach ($something as $url) {
                $this->__kidspotCrawler($page, $url, $cate_id);
            }
        }
        return;
    }

    public function __kidspotCrawler($url, $cate_id)
    {

        $instanceSearchContent = new \My\Search\Content();
        $upload_dir = General::mkdirUpload();

        $url_page = $url;
        if($page =! 0) {
            $url_page = $url . '?page=' . $page;
        }

        $content = General::crawler($url_page);
        $dom = HtmlDomParser::str_get_html($content);
        $results = $dom->find('div.main-content .articles-list .articles-list-image a');

        if (count($results) <= 0) {
            return;
        }
        $results = array_reverse($results);
        foreach ($results as $key => $item) {
            $content = General::crawler('http://www.kidspot.com.au' . $item->href);
            //$content = General::crawler('http://www.kidspot.com.au/baby/baby-care/bathing-and-body-care/the-homemade-breast-milk-lotion-every-new-mum-needs-to-make');

            if ($content == false) {
                continue;
            }
            $html = HtmlDomParser::str_get_html($content);

            $arr_data = array();
            if ($html->find('.article-body', 0)) {

                $cont_title = html_entity_decode($html->find("h1.content-title", 0)->plaintext);
                $arr_data['cont_title'] = $cont_title;
                $arr_data['cont_slug'] = General::getSlug($cont_title);

                //check post exist
                $arr_detail = $instanceSearchContent->getDetail(
                    array(
                        'cont_slug' => $arr_data['cont_slug'],
                        'not_cont_status' => -1
                    )
                );
                if (!empty($arr_detail)) {
                    echo \My\General::getColoredString("Exist this content:" . $arr_data['cont_slug'], 'red');
                    continue;
                }

                $cont_description = $html->find('p.article-summary', 0)->plaintext;


                $html->find('.article-body . ad-block', 0)->outertext = '';
                $cont_detail = $html->find('.article-body', 0)->outertext;

                //get image
                $arr_data['cont_main_image'] = STATIC_URL . '/f/v1/images/no-image-available.jpg';
                $arr_data['cont_resize_image'] = STATIC_URL . '/f/v1/images/no-image-available.jpg';

                $arr_image = $html->find(".main-content img.show");

                if (count($arr_image) > 0) {
                    foreach ($arr_image as $key => $img) {
                        $src = $img->src;

                        $extension = end(explode('.', end(explode('/', $src))));
                        $name_img = $arr_data['cont_slug'] . '_' . ($key + 1) . '.' . $extension;
                        $image_content = General::crawler('http:' . $src);

                        if($image_content) {
                            file_put_contents($upload_dir['path'] . '/' . $name_img, $image_content);
                        } else {
                            $image_content = General::crawler(STATIC_URL . '/f/v1/images/no-image-available.jpg');
                            file_put_contents($upload_dir['path'] . '/' . $name_img, $image_content);
                        }
                        $cont_detail = str_replace($src, $upload_dir['url'] . '/' . $name_img, $cont_detail);

                        if ($key == 0) {
                            $arr_data['cont_main_image'] = $upload_dir['url'] . '/' . $name_img;
                            $arr_data['cont_resize_image'] = $upload_dir['url'] . '/' . $name_img;
                            $results = $this->resizeImage($upload_dir,$arr_data['cont_slug'], $extension, $cate_id);
                            if($results) {
                                $arr_data['cont_resize_image'] = $results;
                            }
                        }
                        sleep(1);
                    }
                }

                $arr_data['cont_detail'] = html_entity_decode($cont_detail);
                $arr_data['cont_description'] = $cont_description;
                $arr_data['created_date'] = time();
                $arr_data['cate_id'] = $cate_id;
                $arr_data['cont_views'] = 0;
                $arr_data['cont_status'] = 1;
                $arr_data['from_source'] = 'kidspot';

                //insert Data
                $serviceContent = $this->serviceLocator->get('My\Models\Content');
                $id = $serviceContent->add($arr_data);

                if ($id) {
                    echo \My\General::getColoredString("Crawler success 1 post id = {$id} \n", 'green');
                } else {
                    echo \My\General::getColoredString("Can not insert content db", 'red');
                }
            }
            sleep(2);
        }

        return true;
    }

    public function __kidspotMultiCrawler($url)
    {
        $cate_id = General::CATEGORY_MON_AND_BABY;
        $instanceSearchContent = new \My\Search\Content();
        $upload_dir = General::mkdirUpload();

        $url_page = $url;

        $content = General::crawler($url_page);
        $dom = HtmlDomParser::str_get_html($content);
        $results = $dom->find('div.homepage-container div#container-start-1 p.homepage-grey a');

        if (count($results) <= 0) {
            return;
        }
        $results = array_reverse($results);
        foreach ($results as $key => $item) {
            $content = General::crawler('http://www.kidspot.com.au' . $item->href);
            //$content = General::crawler('http://www.kidspot.com.au/baby/baby-care/bathing-and-body-care/the-homemade-breast-milk-lotion-every-new-mum-needs-to-make');

            if ($content == false) {
                continue;
            }
            $html = HtmlDomParser::str_get_html($content);

            $arr_data = array();
            if ($html->find('.article-body', 0)) {

                $cont_title = html_entity_decode($html->find("h1.content-title", 0)->plaintext);
                $arr_data['cont_title'] = $cont_title;
                $arr_data['cont_slug'] = General::getSlug($cont_title);

                //check post exist
                $arr_detail = $instanceSearchContent->getDetail(
                    array(
                        'cont_slug' => $arr_data['cont_slug'],
                        'not_cont_status' => -1
                    )
                );
                if (!empty($arr_detail)) {
                    echo \My\General::getColoredString("Exist this content:" . $arr_data['cont_slug'], 'red');
                    continue;
                }

                $cont_description = $html->find('p.article-summary', 0)->plaintext;


                $html->find('.article-body . ad-block', 0)->outertext = '';
                $cont_detail = $html->find('.article-body', 0)->outertext;

                //get image
                $arr_data['cont_main_image'] = STATIC_URL . '/f/v1/images/no-image-available.jpg';
                $arr_data['cont_resize_image'] = STATIC_URL . '/f/v1/images/no-image-available.jpg';

                $arr_image = $html->find(".main-content img.show");

                if (count($arr_image) > 0) {
                    foreach ($arr_image as $key => $img) {
                        $src = $img->src;

                        $extension = end(explode('.', end(explode('/', $src))));
                        $name_img = $arr_data['cont_slug'] . '_' . ($key + 1) . '.' . $extension;
                        $image_content = General::crawler('http:' . $src);

                        if($image_content) {
                            file_put_contents($upload_dir['path'] . '/' . $name_img, $image_content);
                        } else {
                            $image_content = General::crawler(STATIC_URL . '/f/v1/images/no-image-available.jpg');
                            file_put_contents($upload_dir['path'] . '/' . $name_img, $image_content);
                        }
                        $cont_detail = str_replace($src, $upload_dir['url'] . '/' . $name_img, $cont_detail);

                        if ($key == 0) {
                            $arr_data['cont_main_image'] = $upload_dir['url'] . '/' . $name_img;
                            $arr_data['cont_resize_image'] = $upload_dir['url'] . '/' . $name_img;
                            $results = $this->resizeImage($upload_dir,$arr_data['cont_slug'], $extension, $cate_id);
                            if($results) {
                                $arr_data['cont_resize_image'] = $results;
                            }
                        }
                        sleep(1);
                    }
                }

                $arr_data['cont_detail'] = html_entity_decode($cont_detail);
                $arr_data['cont_description'] = $cont_description;
                $arr_data['created_date'] = time();
                $arr_data['cate_id'] = $cate_id;
                $arr_data['cont_views'] = 0;
                $arr_data['cont_status'] = 1;
                $arr_data['from_source'] = 'kidspot';

                //insert Data
                $serviceContent = $this->serviceLocator->get('My\Models\Content');
                $id = $serviceContent->add($arr_data);

                if ($id) {
                    echo \My\General::getColoredString("Crawler success 1 post id = {$id} \n", 'green');
                } else {
                    echo \My\General::getColoredString("Can not insert content db", 'red');
                }
            }
            sleep(2);
        }

        return true;
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

    public function addKeywordDemo($textContent)
    {

        $arr_stop_word = ["bị", "bởi", "cả", "các", "cái", "cần", "càng", "chỉ", "chiếc", "cho", "chứ", "chưa", "có", "thể", "cứ", "của", "cùng", "cũng", "đã", "đang", "đây", "để", "nỗi", "đều", "điều", "do", "đó", "được", "dưới", "gì", "khi", "không", "là", "lại", "lên", "lúc", "mà", "mỗi", "một", "này", "nên", "nếu", "ngay", "nhiều", "như", "nhưng", "những", "nơi", "nữa", "phải", "qua", "ra", "rằng", "rằng", "rất", "rất", "rồi", "sau", "sẽ", "so", "sự", "tại", "theo", "thì", "trên", "trước", "từ", "từng", "và", "vẫn", "vào", "vậy", "vì", "việc", "với", "vừa", "2014", "2015", "2016"];

        $arr_word_content = explode(" ", $textContent);
        $arr_word_content = array_filter($arr_word_content);
        $arr_word_content = array_diff($arr_word_content, $arr_stop_word);

        $instanceSearchKeyWord = new \My\Search\Keyword();
        foreach ($arr_word_content as $word) {

            if (preg_match('/[\'^£$%&*().:"}{@#~?><>,|=_+¬-]/', $word)) {
                continue;
            }
            if(strlen($word) > 7){
                continue;
            }

            $word_slug = trim(General::getSlug($word));
            $is_exsit = $instanceSearchKeyWord->getDetail(['key_slug' => $word_slug]);

            if ($is_exsit) {
                echo \My\General::getColoredString("Exsit keyword: " . $word_slug, 'red');
                continue;
            }

            $arr_data = [
                'key_name' => $word,
                'key_slug' => $word_slug,
                'created_date' => time(),
                'is_crawler' => 0,
                'cate_id' => -2,
                'key_weight' => 2,
            ];

            $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');
            $int_result = $serviceKeyword->add($arr_data);
            unset($serviceKeyword);
            if ($int_result) {
                echo \My\General::getColoredString("Insert success 1 row with id = {$int_result}", 'green');
            }
            $this->flush();
        }
        unset($instanceSearchKeyWord);
        return true;
    }

    public function resizeImage($upload_dir, $cont_slug, $extension, $cate_id){

        $path_old = $upload_dir['path'] . '/' . $cont_slug . '_1.' . $extension;
        if (!file_exists($path_old)) {
            $path_old = STATIC_PATH . '/f/v1/images/no-image-available.jpg';
        }
        $name_main_image = $cont_slug . '_main.' . $extension;
        $result = General::resizeImages($cate_id, $path_old, $name_main_image, $upload_dir['path']);
        if($result) {
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

    public function shareFacebookAction() {
        $instanceSearchContent = new \My\Search\Content();
        $params = $this->request->getParams();

        $cate_id = $params['cateId'];


        $arrContentList = $instanceSearchContent->getList(['not_cont_status' => -1,'cate_id' => $cate_id], ['cont_id' => ['order' => 'asc']], array('cont_id'));
        if (empty($arrContentList)) {
            return false;
        }
        $total = count($arrContentList);
        $index = rand(1,$total);

        $cont_id = $arrContentList[$index]['cont_id'];
        $contentDetail = $instanceSearchContent->getDetail(['cont_id' => $cont_id], array('cont_id','cate_id','cont_main_image','cont_slug','cont_description'));

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

    public function getContentAction(){

        $params = $this->request->getParams();
        $PID = $params['pid'];
        if (!empty($PID)) {
            shell_exec('kill -9 ' . $PID);
        }

        $instanceSearchKeyword = new \My\Search\Keyword();
        $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');
        //
        $limit = 100;
        $arr_keyword = $instanceSearchKeyword->getListLimit(['content_crawler' => 1,'key_id_greater' => 333377], 1, $limit, ['key_id' => ['order' => 'asc']]);

        foreach($arr_keyword as $keyword) {
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

    public function setContentAction(){

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

            if(empty($arrKeyList)) {
                return;
            }
            foreach ($arrKeyList as $keyword){
                $arr_condition_content = array(
                    'cont_status' => 1,
                    'full_text_title' => $keyword['key_name']
                );
                if ($keyword['cate_id'] != -1 && $keyword['cate_id'] != -2) {
                    $arr_condition_content['in_cate_id'] = array($keyword['cate_id']);
                }

                $arrContentList = $instanceSearchContent->getListLimit($arr_condition_content, 1, 15, ['_score' => ['order' => 'desc']],array('cont_id'));

                $text_cont_id = '';
                if(!empty($arrContentList)){
                    $arr_cont_id = array();
                    foreach ($arrContentList as $content){
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

    public function initKeywordAction() {

        $list_keyword = array(
            1 => ["day care","local day care","child development","day care centers","day care center","kindercare","early learning","child day care","child care day care","home daycare","infant care","day care job","day care costs","infant day care","kids day care","day care careers","day care nj","day care jobs","day care licensing","family day care","day care prices","day care center in my area","day care in md","daycares in my area","tutor time","children day care","licensed day care","day care business","day care providers","day care provider","montessori preschool","florida day care","preschool program","nanny services","day care school","day care rates","day care assistance","day care preschool","in home child care","baby day care","day care service","montessori education","seattle day care","day care seattle","day care in seattle","day care regulations","toddler day care","san diego day care","day care listing","find day care","babysitter jobs","babysitting services","day care las vegas","dc day care","day care help","child care seattle","day care programs","day care centers in nj","day care facilities","nanny seattle","day care management","day care information","day care certification","day care houston tx","affordable day care","child day care centers","day care center jobs","dog day care seattle","day care centre","infant day care costs","doggie day care seattle","child time day care","day care policies","kindercare tuition rates","day care centers in nyc","day care centres","child day care cost","day care centers for sale","day care resources","day care seattle wa","day care redmond","infant toddler day care","child day care provider","day care qualifications","child day care business","corporate day care","child day care certification","day care bothell","day care renton","day care redmond wa","family day care providers","new york city day care centers","day care center software","child day care software","child day care training","day care bellevue wa","child day care san diego","west seattle child care","day care centers seattle","seattle day care centers"],
            2 => ["stock market","stock quotes","bakugan","diverticulitis","arthritis","skin cancer","hair extensions","colonoscopy","colon cleanse","master cleanse","laser hair removal","pedicure","wii in stock","rubbermaid","epilepsy","dansko","penny stocks","intervention","stock prices","hair loss","teeth whitening","hospice","corbis","stock market game","diverticulosis","baby shower cakes","bathtubs","diaper cakes","air conditioner","pepper spray","acne treatment","celiac","essential oils","mineral makeup","portable air conditioner","aromatherapy","wii fit in stock","hair removal","make a wish foundation","chi flat iron","stock ticker","rogue status","dale jr","day trading","lace front wigs","tanning beds","medela","lumineers","hobbytron","colon cancer symptoms","incontinence","bridal shower","green mountain coffee","podiatrist","online trading","veneers","fragrance","golden west college","dandruff","nail art","dual action cleanse","dow jones today","stock charts","norelco","hair dye","bunion","opi nail polish","stock market quotes","blackheads","tanning lotion","hair growth","orthotics","stock trading","chi hair straightener","gum disease","differin","colon cleanser","halitosis","bad breath","hair straighteners","abreva","cosmetic dentistry","lace wigs","chi straightener","face painting","hair products","periodontal disease","eyelash extensions","scalp med","dentures","provillus","aspen dental","shower bench","garnier fructis","stock images","johnlscott","dog toys","acrylic nails","meaningful beauty","breast lift"],
            3 => ["anthrax diseases","anthrax infections","bacteria","bacteria diseases","bacteria infections","bacterial","bacterial disease","bacterial diseases","bacterial infection","bacterial infections","bacterium","bacterium diseases","bacterium infections","blood diseases","blood infections","bloodstream","cause diseases","caused","caused by","causes","causes infections","causing","causing diseases","cdc infections","chicken pox diseases","chicken pox infections","cholera","cholera diseases","cholera epidemic","cholera infections","common","common disease","common diseases","common diseases africa","common human diseases","common infections","communicable disease","communicable diseases","contagious","contagious diseases","contagious infections","deadly","deadly diseases","deadly epidemic","deadly infections","deseases","diagnosis diseases","diarrhea diseases","diarrhea epidemic","dieases","diesease","disease","diseases","diseases antibiotics","diseases caused","diseases caused by","diseases caused by bacteria","diseases causes","diseases illness","diseases illnesses","diseases infections","diseases infectious","diseases outbreaks","diseases transmitted","diseases treatment","epidemic","epidemic causes","epidemic desease","epidemic deseases","epidemic disease","epidemic diseases","epidemic illness","epidemic viruses","epidemics","epidemics diseases","fatal","fatal disease list","fatal diseases","fatal infections","fever diseases","fever infections","fungal","fungal diseases","fungal infections","fungi","fungi diseases","fungi infections","fungus","germs","germs diseases","human diseases","human epidemic","human infections","humans","humans diseases","illness","illnesses","immune system","immune system infections","infect","infected diseases","infection","infection diseases","infections","infections bacterial","infections cause","infections caused","infections deseases","infections diease","infections disease","infections diseases","infections spread","infections transmitted","infections viral","infectious","infectious disease","infectious diseases","influenza","influenza diseases","influenza infections","leprosy","leprosy diseases","lyme disease","malaria diseases","malaria infections","measles","measles diseases","medicine","meningitis diseases","meningitis infections","microbes","microbes diseases","microbiology diseases","most common","most common disease","most common diseases","most common infections","mouth","mouth diseases","mouth infections","mumps","mumps diseases","organism","organisms","outbreak","outbreaks","parasites","parasites diseases","parasites infections","pathogen","pathogen diseases","pathogen infections","pathogenic","pathogenic diseases","pathogenic infections","pathogens","pathogens diseases","pathology diseases","pneumonia","pneumonia diseases","pneumonia infections","polio diseases","polio infections","rare diseases","respiratory diseases","respiratory infections","salmonella diseases","salmonella epidemic","scarlet fever","severe diseases","sickness","sickness diseases","skin diseases","skin infections","smallpox diseases","smallpox infections","spread","spread diseases","symptoms diseases","symptoms infections","symptoms of smallpox","throat diseases","throat infections","transmitted","treatment infections","tuberculosis diseases","tuberculosis infections","viral","viral disease","viral diseases","viral epidemic","viral infection","viral infections","virus diseases","virus infections","viruses","viruses diseases","viruses infections","why antibiotics don t work on viruses","yellow fever"],
            4 => ["acne treatment","blackheads","differin","acne scars","tazorac","scar removal","back acne","best acne treatment","clear skin","acne medication","home remedies for acne","acne products","finacea","acne cure","acne scar removal","acne medicine","acne scar treatment","acne solutions","acne rosacea","constipation remedies","scar treatment","body acne","acne jeans","get rid of acne","acne remedies","natural acne treatment","acne care","rash on face","face cream","acne pills","home remedies for constipation","derma","face wash","clean and clear blackhead eraser","isolaz","get rid of blackheads","laser acne treatment","acne cream","best acne products","dermanew","scar cream","keloid scar","neutrogena acne","scalp acne","roaccutane","adult acne treatment","dermarest","facial cleanser","best facial moisturizer","acne tips","acne help","sulfur soap","blackhead eraser","laser scar removal","exposed acne treatment","cystic acne treatment","get rid of acne scars","vitamins for acne","back acne treatment","acne laser","keloid removal","best acne medication","natural remedies for acne","acne removal","pimple treatment","get rid of pimples","zyporex","keloid treatment","best acne medicine","herpes cures","best face wash","best face moisturizer","clear acne","rosacea skin care","clearasil ultra","teenage acne","acne wash","homemade acne treatments","acne pictures","photorejuvenation","oily face","acne problems","photo rejuvenation","constipation cures","benzyl peroxide","face moisturizer","acne cleanser","best facial cleanser","face wrinkles","brown spots on face","body acne treatment","acne soap","home remedies for gout","acne treatment products","acne body wash","rosacea symptoms","new acne treatment","bumps on face","best face cream","rosacea cure"],
            5 => ["ache","aches","achey","aching","achy","acl","acne","acupuncture","advil","aleve","allergic","allergies","allergy","ankle","antibiotics","anxiety","anxious","appetite","appointment","appt","arthritis","aspirin","asthma","backache","battling","bedtime","benadryl","bladder","blisters","body","breathing","bronchitis","bruised","burning","bypass","caffeine","cancer","chemo","chest","chronic","clinic","clogged","codeine","cold","colds","coma","congested","congestion","contagious","cough","coughed","coughing","coughs","cramps","cravings","crutches","cure","cured","dealing","dehydrated","dehydration","dental","dentist","depression","diabetes","diagnosed","diarrhea","dieting","dizziness","dizzy","doctor","doctors","dose","drained","drowsy","drugged","ear","earache","eaten","elbow","emergency","excedrin","excruciating","exercise","exhausted","exhaustion","faint","fatigue","feelin","fever","feverish","fevers","flu","fluids","forehead","freezing","gastric","germs","glands","groggy","h1n1","hacking","hayfever","headache","headaches","heal","healed","heartburn","hiccups","hives","hospital","hungover","hurtin","hurting","hurts","ibuprofen","ick","icky","ill","illness","infected","infection","infections","inhaler","insomnia","insurance","intense","irritated","itch","itching","itchy","jaw","kidney","killing","knee","lasik","limping","lump","lung","lungs","massage","medication","medicine","meds","migraine","migraines","migrane","mild","miserable","morphine","motrin","mri","muscles","nasal","nausea","nauseous","neck","needles","nose","numb","nurse","nyquil","ouch","pain","painkillers","pains","panadol","paracetamol","physical","physically","pill","pills","pimples","pneumonia","poisoning","pollen","pounding","pounds","prescription","puffy","puke","puking","rash","recover","recovered","recovering","recovery","rehab","relieve","remedies","remedy","respiratory","resting","ribs","runny","scratchy","seasonal","severe","shivering","sick","sicker","sickness","sinus","sinuses","skull","sneeze","sneezed","sneezing","sniffles","sniffling","snot","sore","spasms","spine","splitting","sprain","steroids","stiff","stomach","stomachache","stomache","strep","stroke","stuffy","sunburn","sunscreen","surgeon","surgery","swelling","swollen","symptoms","tension","thirsty","throat","throats","throbbing","thyroid","tiredness","tissues","tonsillitis","tonsils","tooth","toothache","torn","treatment","tumor","tylenol","ugh","ulcer","ulcers","unbearable","uncomfortable","unwell","vaccine","veins","vertigo","vicodin","viral","vision","vitamins","vomit","vomiting","watering","watery","wheezing","withdrawal","woken","wrist","yucky"],
            6 => ["newborn baby care","care of newborn baby","newborn care","care of newborn","care for newborn baby","taking care of newborn","taking care of a newborn","how to care newborn baby","caring for a newborn","care of the newborn","newborn baby care products","newborn babies care","care newborn baby","caring newborn baby","newborn child care","newborn baby care at home","care of newborn baby and mother","care for newborn","caring for newborn baby","newborn infant care","baby care newborn","baby care for newborn","care of a newborn","newborn baby care guide","caring of newborn baby","how care newborn baby","care of newborn babies","care for newborn babies","care of the newborn baby","caring for newborn babies","newborn baby care in hindi","child care for newborns","newborns care","baby care products for newborn","caring for your newborn","caring for newborns","caring for a newborn baby","how can i care my newborn baby","care for a newborn","caring for newborn","caring for your newborn baby","taking care of newborns","newborn baby care in winter","care for the newborn","care for newborns","care of newborns","care of newborn baby in hindi","nursing care of newborn","taking care of your newborn","care and treatment of the newborn baby","take care of newborn","day care for newborn","newborn care now","newborn baby day care","newborn baby care checklist","care newborn","newborn care 101","nursing care of the newborn","newborn baby care 1st month","newborn baby care 101","all about newborn baby care","newborn care checklist","baby care tips","baby health tips","baby tips","baby health care tips","tips for baby care","tips for new born baby","baby skin care tips","healthy baby tips","baby care tips health","babies care tips","baby sleeping tips","baby caring tips","health tips for babies","babies health tips","new baby care tips","baby feeding tips","tips on baby care","new baby tips","one month baby care tips","baby care tips for first time parents","parenting tips for new born babies","tips for new born baby and mother","tips for healthy baby","tips of baby care","baby tips for new parents","1 month baby care tips","baby feeding tips advice","parenting tips for babies","baby care tips for new moms","tips for new born babies","tips for new baby","safety tips in taking care of babies","health tips for baby","baby parenting tips","tips to have a healthy baby","baby health","baby health care","baby s health","baby health questions","health care for babies","baby health problems","baby health food","babies health","health baby","health care for baby","babies health care","baby health line","baby health issues","baby health care products","baby health products","baby health center","health care baby","baby health monitor","health of baby","baby care health","baby health helpline","babies health questions","baby health book","baby health clinic","baby health checks","babies health problems","health babies","infant care","infant baby care","caring for infants","care of infant","infants care","caring for an infant","taking care of infants","infant health care","taking care of an infant","infant skin care","health care for infants","taking care of infant","care of an infant","care of infants","basic infant care","infant development care","care for infants","care for infant","caring for infant","infant care and feeding","care needs for infants","infant care information","newborn baby care tips","newborn care tips","newborn baby tips","parenting tips for newborns","tips to take care of newborn baby","newborn child care tips","newborn baby health tips","newborn baby health care tips","newborn parenting tips","newborn babies care tips","tips for taking care of a newborn","newborn tips","newborn baby take care tips","tips for caring for a newborn","parenting tips for newborn","tips for newborn care","newborn care tips new moms","newborn baby tips care","newborn baby caring tips","tips for newborn baby","tips on taking care of a newborn","tips for newborns","newborn baby skin care tips","newborn tips for new parents","tips on caring for a newborn","tips for newborn babies","newborn tips for first time parents","newborn baby feeding tips","tips for new moms with newborn","newborn baby care tips in tamil","babycare","babycare center","babycare centre","babycare advice","babycare baby","babycare shop","children s health","children health","health tips for children","health care for children","health and safety for children","children health care","children s health care","health for children","health children","health care coverage for children","children health tips","children health problems","health of children","children health issues","children and health","health care children","health care plans for children","health plans for children","health in children","children health center","health programs for children","children health plus","health and children","health and safety topics for children","children health websites","health information for children","children health clinic","children with health problems","health websites for children","health care of children","health advice for children","health topics for children","good health tips for children","pregnancy advice","pregnancy","pregnancy care tips","pregnancy essentials","first time pregnancy advice","pregnancy advice line","more about pregnancy","mother pregnancy","baby and pregnancy","pregnancy pregnancy","advice pregnancy","pregnancy baby care","website for pregnancy","child health","child health care","paediatrics and child health","child health nursing","child health plan","child s health","child health problems","child health clinic","child health and safety","child health issues","what is child health","health child","child health clinics","child health center","health care for child","child health organizations","child health care plans","child health safety","paediatrics & child health","child health and development","child care health","pediatrics and child health","national child health programme","child health programme","child health promotion","child health development","child health plus plans","child health websites","child health information","international child health","paediatric and child health","health of child","child health information systems","child health nutrition","parenting and child health","apply for child health plus","health for child","paediatric child health","apply for child health plus online","kid health","health for kids","kids health","health care for kids","health kids","health topics for kids","kids health tips","health questions for kids","kids health care","health information for kids","kids health problems","kids health topics","health sites for kids","kids health for kids","kids for health","health kid","health care kids","health ins for kids","kids health sites","kids and health","health info for kids","kids health site","health resources for kids","health related topics for kids","health advice for kids","kids health kids","kids health info","how to take care of newborn baby","taking care of newborn baby","how to take care of baby","take care of newborn baby","baby take care","taking care of babies","taking care of a baby","how to take care of a newborn baby","take care of babies","take care of a baby","taking care of baby","take care of the baby","taking care of newborn babies","how take care of baby","how take care of newborn baby","take care newborn baby","how can i take care of my newborn baby","take care baby","how take care of a baby","how to take care a baby","how to take care a newborn baby","take care of baby","taking care of the baby","how to take care baby","how do you take care of a baby","taking care of baby play","take care of your baby","taking care of your baby","take care of my baby","take care of a baby online","taking care newborn baby","baby information","baby care information","new baby information","baby health information","baby information sites","information about new born baby","babies information","information on babies","parenting advice","parent advice","parenting website","parenting advice websites","parenting site","parenting infants","parenting tips for new parents","new parenting tips","parenting babies","parent and baby","child care tips","child health care tips","child health tips","tips for child care","health tips for child","baby feeding","feeding baby","feeding a baby","baby feeding problems","feeding the baby","feeding baby to sleep","feeding of baby","how to feeding baby","feeding to baby","new baby feeding","how to feed the baby","woman feeding baby","feeding for baby","just born baby care","newly born baby care","born baby care","born baby care tips","tips for new born baby care","new baby born care","newly born baby care tips","just born baby care tips","care of newly born baby","care for newly born baby","care new born baby","about new born baby care","newborn baby products","baby newborn","newborn baby","newborn babies","a newborn baby","how to handle newborn baby","for newborn baby","babies newborn","how to handle a newborn baby","newborn baby requirements","newborn baby help","how to breastfeed newborn baby","newborn baby nurse","newborn baby delivery","newborn baby with mother","newborn baby all in ones","newborn baby sites","newborn baby and mother","what to get for a newborn baby","new baby care","new baby essentials","new babies","the new baby","a new baby","baby care for new parents","for new baby","caring for new baby","infant care tips","parenting tips for infants","health tips for infants","infant baby care tips","tips for infant care","infant tips for parents","baby needs","new baby needs","baby care needs","needs for baby","what i need for new born baby","needs of a baby","needs for a baby","healthy baby","healthy baby products","healthy baby care","healthy baby healthy child","baby be healthy","healthy pregnancy healthy baby","for a healthy baby","for healthy baby","healthy baby program","new born baby","newly born baby","for new born baby","newly born babies","about new born baby","a new born baby","born new baby","new born baby birth","requirements for new born baby","just born for baby","new born baby born","the new born baby","how to get a new born baby to sleep","feeding time for new born baby","sleeping time of new born baby","baby care advice","baby advice","advice for baby","newborn baby advice","new baby advice","baby health advice","advice for new baby","baby advice line","baby advice books","advice on babies","new born child","healthy child","child websites","child advice websites","feeding child","how to feed a child","newly born child","child advice line","your baby and child","mother milk","mother milk baby","mother feeding milk","mother breast milk","mother baby milk","mother milk feeding","baby feeding mother milk","baby mother milk","mother milk for baby","mother milk child","milk feeding mother","mother feeding milk to father","mother feeding breast milk","mother milk feeding to child","baby feed mother milk","www mother milk baby","mother milk to child","mother milk to baby","mother breast milk baby","baby and mother milk","mother feeding baby milk","breast milk mother","healthcare for children","healthcare for kids","healthcare for babies","children healthcare","child healthcare plus","kids healthcare","healthcare for child","baby sleep","newborn baby sleep","newborn baby sleeping","baby sleeping time","how to sleep baby","newborn baby sleeping time","sleep for baby","baby and sleep","making baby sleep","baby sleeps","sleeping newborn baby","baby care websites","baby care website","baby health websites","baby information websites","newborn baby website","baby bathing","bathing baby","bathing a baby","bath a baby","bathing the baby","baby bath milk","maternal and child health","maternal and child health care","maternal child health","maternal & child health","maternity and child health","child and maternal health","maternal child health center","maternal and child health organizations","healthy kids","healthy eating tips for kids","kids healthy","healthy kid","healthy information for kids","tips for healthy kids","breastfeeding advice","child breastfeeding","breastfeeding milk","mothers breastfeeding","advice on breastfeeding","breastfeeding children","breastfeeding child","mother breastfeeding child","problems in breastfeeding","breastfeeding breast","pregnant care breastfeeding","breastfeeding for newborn","how to feed a newborn baby","newborn baby feeding","how to feed newborn baby","feeding newborn baby","feeding a newborn baby","feeding of newborn baby","newborn baby feeding problems","feeding for newborn","newborn baby needs","what a newborn baby needs","baby needs newborn","what i need for newborn baby","needs for newborn baby","what newborn babies need","needs of newborn baby","what i need for a newborn baby","basic baby needs for newborn babies","health websites for kids","kids health websites","healthy websites for kids","health website for kids","healthy kids website","newborn baby boy care","care for newborn baby boy","taking care of newborn baby boy","newborn boy care","caring for newborn baby boy","baby boy care","baby boy care tips","caring for a newborn boy","newborn baby health","newborn baby health problems","newborn baby health care","newborn health","newborn health care","health care for newborns","baby care after birth","new baby birth","after birth baby care","baby care from birth","newborn baby birth","birth of new baby","newborn","newborn advice","newborn parenting","newborn child","newborn basics","newborn infants","newborn children","newborn help","breastfeed newborn","feeding breast","breast with milk","mother breast","how to do breast feeding","breast feedings","young breast feeding","breast of milk","baby breast","the breast milk baby","breast baby","breast feeding to baby","baby breast milk","baby feeding breast","breast milk for baby","baby milk breast","a girl breast feeding a baby","breast milk feeding baby","breast baby feeding","baby milk feeding","feeding breast milk","milk feeding breast","baby feeding milk","milk feeding to baby","milk breast feeding","feeding milk to baby","how to feed milk to baby","feeding milk to child","milk feeding baby","newborn baby information","newborn information","newborn information new parents","information on newborn babies","newborn babies information","mother feeding","mother feeding baby","mother feeding child","feeding mother","mother feed baby","mother feeding a baby","mothers feeding","mother baby feeding","newborn baby care after birth","newborn care after birth","newborn care after delivery","looking after newborn babies","looking after newborn baby","care of newborn baby after delivery","mother and child health","mother and child health care","mother child health","mother child health care","mother health care","baby care for girls","newborn baby girl care","care for newborn baby girl","baby girl care","care of girl child","caring for newborn baby girl","newborn skin care","newborn baby skin care","newborn baby skin care products","newborn baby skin","skin care for newborn babies","skin care of newborn baby","advantages of breastfeeding for mother and baby","mother breastfeeding baby","breastfeeding to baby","girl breastfeeding baby","breastfeeding baby to sleep","breastfeeding for baby","baby child care","child care advice","pregnancy and child care","taking care of your child","taking care of a child","newborn bathing","bathing a newborn baby","bath newborn","bath newborn baby","bathing of newborn baby","chip health program","child health program","maternal health program","chips health program","maternal health programs","baby care","baby care products","baby skin care","baby care center","care baby","care of baby","babies care","baby care books","how to care baby","baby caring","baby s care","baby care centre","care for baby","premature baby care","baby care sites","care for babies","caring for babies","caring baby","how care baby","best baby care","natural baby care","how to care a baby","caring for baby","baby care site","basic baby care","mother and baby care","how to baby care","caring for a baby","caring of baby","small baby care","baby care 2","about baby care","baby cares","play baby care","baby care guide","care for a baby","best baby care books","care of the baby","care of babies","the baby care","baby care home","baby care baby","provide care for babies","one year baby care","care of a baby","baby care checklist","baby care at home","care.com baby","caring of babies","how to care of baby","neonatal baby care","care babies","best care for baby","baby care centres","top baby care","care for the baby","home care for babies","care of baby after delivery","baby baby care","caring babies","baby skin care home remedies","baby in care","10 days baby care","baby care things","my baby care","mother baby care","baby i care","baby care 3","caring a baby"],
            7 => ["fashion","women fashion","vintage fashion","retro fashion","vintage retro fashion","shoes fashion","size fashion","fashion design","men fashion","girls fashion","women fashion shoes","fashion games","fashion show","fashion accessories","fashion dresses","fashion week","jobs fashion","fashion clothing","handbags fashion","fashion boys","jewelry fashion","new fashion","women fashion dresses","fashion 2008","wholesale fashion","fashion bags","tops fashion","shirts fashion","up fashion","online fashion","old fashion","ladies fashion","jobs fashion london","jobs fashion design","jewellery fashion","high fashion","graphic fashion","graphic design fashion","fashion design jobs london","fashion bug","fashion 2007","fashion 10","jeans fashion","fashion tv","fashion style","fashion mall","fashion intimates","fashion graphic design jobs","fashion bratz","models fashion"],
            8 => ["acne beauty","acne facial","acne facials","aesthetic facial","anti aging facial","beauty","beauty equipment","beauty esthetician","beauty face","beauty face lift","beauty machine","beauty machines","beauty massage","beauty massager","beauty massagers","beauty product","beauty rejuvenation","beauty review","beauty reviews","beauty skincare","beauty steamer","beauty stimulator","beauty therapy","beauty tone","beauty treatment","belavi face lift","caci face lift","caci facial","cream beauty","crystal clear facial","derma facial","desincrustation","diamond microdermabrasion","diamond peel facial","esthetic facial","european facial","exfoliation facial","extractions facial","face cream","face lift","face lift equipment","face lift machine","face lift machines","face lift massage","face lift non surgical","face lift product","face lift products","face lift treatment","face lift treatments","face lifting","face lifts","face massager","face peel","facelift facial","facial","facial beauty","facial cellulite","facial cleanser","facial contraindications","facial cream","facial dermabrasion","facial electrolysis","facial electrotherapy","facial equipment","facial esthetician","facial esthetics","facial face","facial face lift","facial firming","facial high frequency","facial machine","facial machines","facial massage","facial massager","facial massagers","facial muscle toning","facial muscles","facial product","facial products","facial rejuvenation","facial rejuvenator","facial review","facial salons","facial scrub","facial skin","facial skincare","facial slimming","facial steamer","facial steamers","facial stimulator","facial tone","facial toning","facial treatment","facial treatments","facial waxing","facial wrinkles","faradic","galvanic beauty","galvanic current facial","galvanic facial","guinot facial","instant face lift","ipl facial","lower face lift","lymphatic drainage facial","lymphatic facial","micro current facial","microcurrent","microcurrent beauty","microcurrent face lift","microcurrent facial","microcurrent facial machine","microcurrent facial rejuvenation","microcurrent facial toning","microderm","microderm facial","microdermabrasion","microdermabrasion facial","microdermabrasion facials","microdermabrasion machine","mini face lift","natural face lift","natural skincare","non surgical face lift","non surgical face lifts","non surgical facial","nonsurgical face lift","nonsurgical facelift","oxygen facial","oxygen facials","oxyjet facial","peel beauty","peel facial","perfector face lift","perfector facial","photo facial","professional facial steamer","quick face lift","rejuvenation facial","skin beauty","skin care","skin galvanic","skin massager","skin peel","skin product","skin rejuvenation","skin steamer","skin ultrasonic","skin ultrasound","skincare products","slimming beauty","toning facial","ultrasonic beauty","ultrasonic facial","ultrasonic facial massager","ultrasonic massage","ultrasound beauty","ultrasound facial","ultratone facial"],
        );

        foreach ($list_keyword as $index => $keyword) {
            switch ($index) {
                case 1:
                case 6:
                    $cate_id = 6;
                    break;
                case 2:
                case 4:
                case 8:
                    $cate_id = 5;
                    break;
                case 3:
                case 5:
                    $cate_id = 4;
                    break;
                case 7:
                    $cate_id = 3;
                    break;

            }
            //
            $cate['cate_id'] = $cate_id;
            $this->add_keyword($keyword, $cate);
        }

        die("done");
    }


    public function testAction() {
        $path_file_upload = fopen(PUBLIC_PATH . "/keyword.txt", "r");

        while (!feof($path_file_upload)) {
            $line_data = explode(". ", fgets($path_file_upload));
            $arr_key[] = $line_data[1];
        }

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

            $arr_data = [
                'key_name' => $key_word,
                'key_slug' => $word_slug,
                'created_date' => time(),
                'cate_id' => 3,
                'key_weight' => 2
            ];

            $int_result = $serviceKeyword->add($arr_data);
            if ($int_result) {
                echo \My\General::getColoredString("Insert success 1 row with id = {$int_result}", 'green');
            }
            $this->flush();
        }
        unset($instanceSearchKeyWord);
        die();
    }
}
