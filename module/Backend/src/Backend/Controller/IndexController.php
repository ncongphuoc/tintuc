<?php

namespace Backend\Controller;

use My\Controller\MyController;
use Sunra\PhpSimple\HtmlDomParser,
    Zend\View\Model\ViewModel,
    My\General;

class IndexController extends MyController
{

    public function __construct()
    {

    }

    public function indexAction()
    {
        return;
        $instanceSearchCategory = new \My\Search\Category();
        $arr_category = $instanceSearchCategory->getList(['cate_status' => 1], [], ['cate_sort' => ['order' => 'asc'], 'cate_id' => ['order' => 'asc']]);
        $instanceSearchContent = new \My\Search\Content();
        foreach ($arr_category as $category) {
            if (empty($category['cate_crawler_url'])) {
                continue;
            }
            for ($i = 290; $i >= 1; $i--) {
                $source_url = $category['cate_crawler_url'] . '?p=' . $i;
                $page_cate_content = General::crawler($source_url);
                $page_cate_dom = HtmlDomParser::str_get_html($page_cate_content);
                try{
                    $item_content_in_cate = $page_cate_dom->find('.listitem');
                }catch (\Exception $exc){
                    continue;
                }
                if(empty($item_content_in_cate)){
                    continue;
                }

                foreach ($item_content_in_cate as $item_content){
                    $arr_data_content = [];
                    $item_content_dom = HtmlDomParser::str_get_html($item_content->outertext);
                    $item_content_source = 'http://khoahoc.tv'.$item_content_dom->find('a',0)->href;
                    $item_content_title = trim($item_content_dom->find('.title',0)->plaintext);
                    $arr_data_content['cont_title'] = html_entity_decode($item_content_title);
                    $arr_data_content['cont_slug'] = General::getSlug(html_entity_decode($item_content_title));

                    $item_content_description = html_entity_decode(trim($item_content_dom->find('.desc',0)->plaintext));
                    $img_avatar_url = $item_content_dom->find('img',0)->src;
                    $arr_detail = $instanceSearchContent->getDetail(['cont_slug' => $arr_data_content['cont_slug'], 'not_cont_status' => -1]);

                    if(!empty($arr_detail)){
                        continue;
                    }

                    //lấy hình đại diện
                    if($img_avatar_url == 'http://img.khoahoc.tv/photos/image/blank.png'){
                        $arr_data_content['cont_main_image'] = STATIC_URL.'/f/v1/img/black.png';
                    }else{
                        $extension = end(explode('.', end(explode('/',$img_avatar_url))));
                        $name = $arr_data_content['cont_slug'].'.'. $extension;
                        file_put_contents(STATIC_PATH . '/uploads/content/' . $name, General::crawler($img_avatar_url));
                        $arr_data_content['cont_main_image'] = STATIC_URL . '/uploads/content/' . $name;
                    }

                    //crawler nội dung bài đọc
                    $content_detail_page_dom = HtmlDomParser::str_get_html(General::crawler($item_content_source));
                    foreach ($content_detail_page_dom->find('script') as $item){
                        $item->outertext='';
                    }
                    foreach ($content_detail_page_dom->find('.adbox') as $item){
                        $item->outertext='';
                    }
                    $content_detail_html = $content_detail_page_dom->find('.content-detail',0);
                    $content_detail_outertext = $content_detail_page_dom->find('.content-detail',0)->outertext;
                    $img_all = $content_detail_html->find("img");

                    //lấy hình ảnh trong bài
                    if (count($img_all) > 0) {
                        foreach ($img_all as $key => $im) {
                            $extension = end(explode('.', end(explode('/', $im->src))));
                            $name = $arr_data_content['cont_slug'] . '-' . ($key + 1) . '.' . $extension;
                            file_put_contents(STATIC_PATH . '/uploads/content/' . $name, General::crawler($im->src));
                            $content_detail_outertext = str_replace($im->src, STATIC_URL . '/uploads/content/' . $name, $content_detail_outertext);
                        }
                    }

                    $content_detail_outertext = trim(strip_tags($content_detail_outertext, '<a><div><img><b><p><br><span><br /><strong><h2><h1><h3><h4><table><td><tr><th><tbody>'));
                    $arr_data_content['cont_detail'] = html_entity_decode($content_detail_outertext);
                    $arr_data_content['created_date'] = time();
                    $arr_data_content['user_created'] = 1;
                    $arr_data_content['cate_id'] = $category['cate_id'];
                    $arr_data_content['cont_description'] = $item_content_description;
                    $arr_data_content['cont_status'] = 1;
                    $arr_data_content['cont_views'] = rand(1,rand(100,1000));
                    $arr_data_content['method'] = 'crawler';
                    $arr_data_content['from_source'] = $item_content_source;
                    $arr_data_content['meta_keyword'] = str_replace(' ', ',', $arr_data_content['cont_title']);
                    $arr_data_content['updated_date'] = time();
                    unset($content_detail_outertext);
                    unset($img_all);
                    unset($img_avatar_url);
                    unset($content_detail_html);
                    unset($content_detail_page_dom);
                    unset($item_content_dom);

                    $serviceContent = $this->serviceLocator->get('My\Models\Content');
                    $id = $serviceContent->add($arr_data_content);

                    if ($id) {
                        echo \My\General::getColoredString("Crawler success 1 post id = {$id} \n", 'green');
                    } else {
                        echo \My\General::getColoredString("Can not insert content db", 'red');
                    }

                    unset($serviceContent);
                    unset($arr_data_content);
                    $this->flush();
                    continue;
                }
            }
        }
        return;

        $arr_key_start = [
            'tin tuc', 'tin tuc quoc te', 'tin tuc trong nuoc', 'the thao', 'tin the thao', 'cup c1', 'ngoai hang anh', 'laliga', 'champion league',
            'giai vo dich quoc gia', 'vleague', 'tennis', 'quan vot', 'xe cong thuc 1', 'dua ngua', 'tin tuc thoi su', 'tin hot', 'europa league', 'world cup',
            'ronaldo', 'messi', 'robben', 'rooney', 'nguyen cong phuong', 'nguoi dep', 'gioi tre', 'guong mat tre', 'thoi trang', 'lam dep', 'mac dep', 'trang diem',
            'ca si', 'showbiz', 'manchester united', 'liverpool', 'real marrid', 'barcelona', 'sexy', 'khoe hang', 'son tung mtp', 'hot girl', 'giai tri', 'sao viet', 'sao chau a', 'sao hollyword',
            'truyen cuoi', 'anh hai huoc', 'truyen tieu lam', 'tin nong', 'tin giat gan', 'hai huoc', 'cong nghe', 'do choi cong nghe',
            'dien thoai', 'smart phone', 'dien thoai thong minh', 'internet', 'kham pha cong nghe', 'cong nghe so', 'thu thuat',
            'thoi trang sao', 'goc hai huoc', 'cong dong mang', 'su kien'
        ];

        $serviceKeyword = $this->serviceLocator->get('My\Models\Keyword');

        foreach ($arr_key_start as $key_word) {
            $arr_data = [
                'key_name' => $key_word,
                'key_slug' => General::getSlug($key_word)
            ];
            $id = $serviceKeyword->add($arr_data);
        }
        die('done');

        $arr = [
            ' a', ' b', ' c', ' d', ' e'
        ];
        $keyword = 'bien tan';

        foreach ($arr as $key => $v) {
            $keytemp = $keyword . $v;
            $url = 'http://www.google.com/complete/search?output=search&client=chrome&q=' . rawurlencode($keytemp) . '&hl=vi&gl=vn';
            $content = General::crawler($url);
            echo '<pre>';
            print_r(json_decode($content));
            echo '</pre>';
//            die();
        }
        die();

        //http://www.google.com/complete/search?output=search&client=chrome&q=etec&hl=vi&gl=vn
        $url = 'http://www.google.com/complete/search?output=search&client=chrome&q=' . rawurlencode($key) . '&hl=vi&gl=vn';
//        echo '<pre>';
//        print_r($url);
//        echo '</pre>';
//        die();

        $content = General::crawler($url);

        echo '<pre>';
        print_r(json_decode($content));
        echo '</pre>';
        die();
        foreach ($arr as $v) {

        }
        echo '<pre>';
        print_r(json_decode('["etec a",["etec associates","etec arabia","etec at","etec adalah","etec albert einstein","etec auckland","etec antibiotics","etec americana","etec artes","stec and ehec","etec araraquara","etec aristóteles ferreira","etec agency","etec alberto santos dumont","etec aruja","etec australia","etec atibaia","etec araçatuba","etech antivirus","etec avare"],["","","","","","","","","","","","","","","","","","","",""],[],{"google:clientdata":{"bpc":false,"tlw":false},"google:suggestrelevance":[601,600,567,566,565,564,563,562,561,560,559,558,557,556,555,554,553,552,551,550],"google:suggesttype":["QUERY","QUERY","QUERY","QUERY","QUERY","QUERY","QUERY","QUERY","QUERY","QUERY","QUERY","QUERY","QUERY","QUERY","QUERY","QUERY","QUERY","QUERY","QUERY","QUERY"],"google:verbatimrelevance":851}]'));
        echo '</pre>';
        die();
        return;
        include PUBLIC_PATH . '/simple_html_dom.php';
//        http://2sao.vn/p0c1005/hoi-dap/trang-50.vnn
        $arr_cate = [
            24 => 'http://2sao.vn/p0c1005/hoi-dap/trang-'
        ];

        foreach ($arr_cate as $cate_id => $strURL) {
            echo \My\General::getColoredString("Start crawler url {$strURL} \n", 'red');

            for ($i = 100; $i >= 1; $i--) {
                $sourceURL = $strURL . $i . '.vnn';

                echo \My\General::getColoredString("Start crawler url {$sourceURL} \n", 'green');

                $content = General::crawler($sourceURL);
                $dom = str_get_html($content);

                $results = $dom->find('.span85 .nav1 li.lilist .divnav2 a');

                if (count($results) <= 0) {
                    continue;
                }
//                ksort($results);

                foreach ($results as $item) {
                    if (strpos($item->href, 'clip')) {
                        echo \My\General::getColoredString("Khong lay clip url {$item->href} \n", 'red');
                        continue;
                    }
                    $arr_data = [];
                    $arr_data['cont_title'] = trim($item->plaintext);
                    $arr_data['cont_slug'] = General::getSlug($arr_data['cont_title']);

                    //find in db with
                    $instanceSearchContent = new \My\Search\Content();
                    $arr_content_detail = $instanceSearchContent->getDetail(['cont_slug' => $arr_data['cont_slug'], 'not_cont_status' => -1, 'cate_id' => $cate_id]);

                    if ($arr_content_detail) {
                        echo \My\General::getColoredString("Continue with exits title", 'red');
                        continue;
                    }

                    $content = General::crawler('http://2sao.vn' . $item->href);

                    if ($content == false) {
                        continue;
                    }

                    $html = str_get_html($content);

                    $arr_data['cont_desciption'] = trim($html->find('.fixfont', 0)->plaintext);

                    $cont_detail = $html->find('.2saodetial', 0)->outertext;
                    $img = $html->find(".2saodetial img");

                    if (count($img) > 0) {
                        $arr_data['cont_image'] = [];
                        foreach ($img as $key => $im) {
                            $extension = end(explode('.', end(explode('/', $im->src))));
                            $name = $arr_data['cont_slug'] . '-' . ($key + 1) . '.' . $extension;
                            file_put_contents(STATIC_PATH . '/uploads/content/' . $name, General::crawler($im->src));
                            $cont_detail = str_replace($im->src, STATIC_URL . '/uploads/content/' . $name, $cont_detail);
                            if ($key == 0) {
                                $arr_data['cont_main_image'] = STATIC_URL . '/uploads/content/' . $name;
                                $images = General::resizeImages('content', STATIC_PATH . '/uploads/content/' . $name, $name);
                                if ($images != false) {
                                    $arr_data['cont_image'] = json_encode($images);
                                }
                            }
                        }
                    }

                    $cont_detail = trim(strip_tags($cont_detail, '<img><b><p><br><span><br /><strong><table><td><tr><th><tbody>'));
                    $cont_detail = str_replace('class="Normal"', 'class="content"', $cont_detail);
                    $arr_data['cont_detail'] = $cont_detail;

                    unset($cont_detail);
                    unset($content);
                    unset($html);
                    unset($img);

                    $arr_data['cont_detail_text'] = trim(strip_tags($arr_data['cont_detail']));
                    $arr_data['created_date'] = time();
                    $arr_data['updated_date'] = time();
                    $arr_data['cate_id'] = $cate_id;
                    $arr_data['method'] = 'crawler';
                    $arr_data['from_source'] = '2sao.vn';
                    $arr_data['cont_views'] = 0;
                    $arr_data['meta_keyword'] = str_replace(' ', ',', $arr_data['cont_title']);
                    $arr_data['cont_status'] = 1;

                    $serviceContent = $this->serviceLocator->get('My\Models\Content');
                    $id = $serviceContent->add($arr_data);

                    if ($id) {
                        echo \My\General::getColoredString("Crawler success 1 post from 2sao.vn id = {$id} \n", 'green');
                    } else {
                        echo \My\General::getColoredString("Can not insert content db", 'red');
                    }
                    unset($serviceContent);
                    unset($arr_data);

                    $this->flush();
                }
            }
            echo \My\General::getColoredString("Crawler 2SAO.VN success {$strURL} \n", 'green');
        }
        echo \My\General::getColoredString("Crawler success 2SAO.VN", 'green');
        return true;
    }

    public function coverStr($str)
    {
        $arrPatent = [
            'mọi người',
            'tận nhà',
            'mobi',
            'vina',
            'https://web.facebook.com/',
            'https://facebook.com/',
            'Đ/C',
            'A.',
            'bạn',
            'lh',
            'v/c',
            'Tôi',
            'tôi',
            'nhà mới',
            'dt',
            'thue',
            'nha nguyen can',
            'QL1A',
            'Binh Dinh',
            'DT',
            'Tiện',
            'tiện',
            'ai cần',
            'LH',
            'Tuyển nhân viên',
        ];
        $arrReplace = [
            'tất cả mọi người',
            'tận nơi',
            'mobiphone',
            'vinaphone',
            'http://fb.com/',
            'http://fb.com/',
            'địa chỉ',
            'anh',
            'anh chị',
            'liên hệ',
            'vợ/chồng',
            'Mình',
            'mình',
            'nhà mới xây',
            'diện tích',
            'thuê',
            'nhà nguyên căn',
            'Quốc lộ 1A',
            'Bình Định',
            'diện tích',
            'Thuận tiện',
            'thuận tiện',
            'ai có nhu cầu',
            'liên hệ',
            'Cần tuyển nhân viên',
        ];

        $strRt = str_replace($arrPatent, $arrReplace, $str);
        return $strRt;
    }

    private function flush()
    {
        ob_end_flush();
        ob_flush();
        flush();
    }

}
