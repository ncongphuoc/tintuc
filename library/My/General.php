<?php

namespace My;

class General {

    const SITE_DOMAIN_FULL = 'http://hellonews.info';
    const TITLE_META = " - Hellonews.info";
    const SITE_AUTH = "Hellonews.info";
    const SITE_DOMAIN = 'Hellonews.info';
    const SITE_SLOGAN = 'Tin tức tổng hợp';
    const SITE_HOTLINE = '';
    const KEYWORD_DEFAULT = 'teen viet nam, giới trẻ, xu hướng, âm nhạc, xem phim, đời sống, hàng hiệu, chuyện lạ, thời trang trẻ, mới lớn, đang yêu, sành điệu, chuyện cười, khéo tay, các sao';
    const DESCRIPTION_DEFAULT = 'Tintuc360.me - Trang tin tức giải trí - xã hội Việt Nam - Quốc Tế. Đưa tin nhanh nhất : thời trang, video ngôi sao, phim ảnh, tình yêu, học đường, các chuyển động xã hội.';
    const SITE_IMAGES_DEFAULT = 'https://static.tintuc360.me/f/v1/images/logo-01.png';
    const SITE_SOCIAL = 'https://www.facebook.com/tintuc360.me/';
    const SITE_LOCATION = "";
    const MEMBER = 5;
    const ADMINISTRATOR = 2;
    const MODERATOR = 3;
    const SEO = 4;
    const SUPPORTER = 5;
    const EDITOR = 6;
    const MS = 0;
    const MR = 1;
    const MRS = 2;
    //define email admin 
    const EMAIL_SUPPORT = 'support@tin247.mobi';
    const EMAIL_BCC = 'no-reply@tin247.mobi';
    const EMAIL_SYS = 'no-reply@tin247.mobi';
    const EMAIL_SYS_PASSWORD = 'quynhon@best247';
    //define host mail
    const HOST_MAIL = 'smtp.gmail.com';

    const SEND_MAIL_MESSAGES = 1;
    //define social url
    const SOCIAL_FACEBOOK_URL = 'https://www.facebook.com/tintuc360.me';

    //category
    const CATEGORY_WORLD = 1;
    const CATEGORY_SHOWBIZ = 2;
    const CATEGORY_TECH = 3;
    const CATEGORY_HEALTH = 4;
    const CATEGORY_NATURALLY_SAVVY= 5;
    const CATEGORY_MON_AND_BABY = 6;

    static $configFB = array(
        'access_token' => 'EAARQrqSKvfcBAF8FNlecuAu73Hsdl96XNCZCz5mW7HZCf14WbuXuc76kJxxfrZBBA0H9Rh2ZAaZBdZCnXT8ZC81ufko0WfDq3yBIrNBt0RP3ya9xfDKB262YQosZByMDjZANrGcv1vdMTv9NYtLHNZBTLDcrX7n2fZBYQ6ToWyeevbqTZAEsUuAyxHX1',
        'fb_id' => '404120303310522',
        'app_secret' => '6966f59963e7fc50bfed91a4b5895a32',
        'app_id' => '1214610921930231'
    );

    static $acc_share_teen = array(
        1 => 'EAARQrqSKvfcBAK8tL8UpApzW7iMq1iWCeA9JAdcLMZA1ow6ZCyYvOzkgs1H0gy8C0zLR6kIyikVqctnMLNEn4ABaZAQoQniH4HUwHOKZBfS2u4t7LJ61xj7jlj5z4Ho26YDh1ceginVn3nU1MrOXqaCDkD7ZA7CwZD',
        2 => 'EAARQrqSKvfcBAJlY8zArxSYyOZB8tS8OaFy3LqEDyhO7WA1uJFaP9f327JrKSPIb4YFRCxmwnguuZB0lhetN67grNPkR1gORmccSC0kCdD9ZBXQ3yYsAsgALRoi8mZCEipD7H2LKE89sOfoPNEboxdu2PvHyJGIZD',
        3 => 'EAARQrqSKvfcBAGxZBdZAG2GFfWkL2ZAawqEqEgsZBYx7EGA3rtkLbmVcM2TnnV4ZCik3ZCw3zj7qQu3UUkgpHZBZCkYCwboRGam1ZA9LhtkRX1tPnf2tksqZBYZCKwFDb6y9bwdN9BevZCkZB4dXcvsSgWBZC5vXWyZBJZBhZAuAZD',
    );

    static $acc_share_old = array(
        4 => 'EAARQrqSKvfcBABe2xCv93ZA7JZBjvvuGjLOzv1ZBlwMkU6sJtcmepu2iCBLeYSsy82pclKHJVHWtqY0Jcjqqm8T3htmPp5gZBbMJz1bjaAVj2C5DzcVRT8Nk6ut8t0SnxPNgQ3ZATigLRrxCFihkzCQBUYZCYDQ9OfQkKWhNdOvgZDZD',
    );


    static $foreground_colors = array(
        'black' => '0;30', 'dark_gray' => '1;30',
        'blue' => '0;34', 'light_blue' => '1;34',
        'green' => '0;32', 'light_green' => '1;32',
        'cyan' => '0;36', 'light_cyan' => '1;36',
        'red' => '0;31', 'light_red' => '1;31',
        'purple' => '0;35', 'light_purple' => '1;35',
        'brown' => '0;33', 'yellow' => '1;33',
        'light_gray' => '0;37', 'white' => '1;37',
    );
    static $background_colors = array(
        'black' => '40', 'red' => '41',
        'green' => '42', 'yellow' => '43',
        'blue' => '44', 'magenta' => '45',
        'cyan' => '46', 'light_gray' => '47',
    );

    public static function getSlug($string, $maxLength = 255, $separator = '-') {
        $arrCharFrom = array("ạ", "á", "à", "ả", "ã", "Ạ", "Á", "À", "Ả", "Ã", "â", "ậ", "ấ", "ầ", "ẩ", "ẫ", "Â", "Ậ", "Ấ", "Ầ", "Ẩ", "Ẫ", "ă", "ặ", "ắ", "ằ", "ẳ", "ẵ", "ẫ", "Ă", "Ắ", "Ằ", "Ẳ", "Ẵ", "Ặ", "Ẵ", "ê", "ẹ", "é", "è", "ẻ", "ẽ", "Ê", "Ẹ", "É", "È", "Ẻ", "Ẽ", "ế", "ề", "ể", "ễ", "ệ", "Ế", "Ề", "Ể", "Ễ", "Ệ", "ọ", "ộ", "ổ", "ỗ", "ố", "ồ", "Ọ", "Ộ", "Ổ", "Ỗ", "Ố", "Ồ", "Ô", "ô", "ó", "ò", "ỏ", "õ", "Ó", "Ò", "Ỏ", "Õ", "ơ", "ợ", "ớ", "ờ", "ở", "ỡ", "Ơ", "Ợ", "Ớ", "Ờ", "Ở", "Ỡ", "ụ", "ư", "ứ", "ừ", "ử", "ữ", "ự", "Ụ", "Ư", "Ứ", "Ừ", "Ử", "Ữ", "Ự", "ú", "ù", "ủ", "ũ", "Ú", "Ù", "Ủ", "Ũ", "ị", "í", "ì", "ỉ", "ĩ", "Ị", "Í", "Ì", "Ỉ", "Ĩ", "ỵ", "ý", "ỳ", "ỷ", "ỹ", "Ỵ", "Ý", "Ỳ", "Ỷ", "Ỹ", "đ", "Đ");
        $arrCharEnd = array("a", "a", "a", "a", "a", "A", "A", "A", "A", "A", "a", "a", "a", "a", "a", "a", "A", "A", "A", "A", "A", "A", "a", "a", "a", "a", "a", "a", "a", "A", "A", "A", "A", "A", "A", "A", "e", "e", "e", "e", "e", "e", "E", "E", "E", "E", "E", "E", "e", "e", "e", "e", "e", "E", "E", "E", "E", "E", "o", "o", "o", "o", "o", "o", "O", "O", "O", "O", "O", "O", "O", "o", "o", "o", "o", "o", "O", "O", "O", "O", "o", "o", "o", "o", "o", "o", "O", "O", "O'", "O", "O", "O", "u", "u", "u", "u", "u", "u", "u", "U", "U", "U", "U", "U", "U", "U", "u", "u", "u", "u", "U", "U", "U", "U", "i", "i", "i", "i", "i", "I", "I", "I", "I", "I", "y", "y", "y", "y", "y", "Y", "Y", "Y", "Y", "Y", "d", "D");
        $arrCharnonAllowed = array("©", "®", "Æ", "Ç", "Å", "Ç", "๏", "๏̯͡๏", "Î", "Ø", "Û", "Þ", "ß", "å", "", "¼", "æ", "ð", "ñ", "ø", "û", "!", "ñ", "[", "\"", "$", "%", "'", "(", ")", "♥", "(", "+", "*", "/", "\\", ",", ":", "¯", "", "+", ";", "<", ">", "=", "?", "@", "`", "¶", "[", "]", "^", "~", "`", "|", "", "_", "?", "*", "{", "}", "€", "�", "ƒ", "„", "", "…", "‚", "†", "‡", "ˆ", "‰", "ø", "´", "Š", "‹", "Œ", "�", "Ž", "�", "ॐ", "۩", "�", "‘", "’", "“", "”", "•", "۞", "๏", "—", "˜", "™", "š", "›", "œ", "�", "ž", "Ÿ", "¡", "¢", "£", "¤", "¥", "¦", "§", "¨", "«", "¬", "¯", "°", "±", "²", "³", "´", "µ", "¶", "¸", "¹", "º", "»", "¼", "¾", "¿", "Å", "Æ", "Ç", "?", "×", "Ø", "Û", "Þ", "ß", "å", "æ", "ç", "ï", "ð", "ñ", "÷", "ø", "ÿ", "þ", "û", "½", "☺", "☻", "♥", "♦", "♣", "♠", "•", "░", "◘", "○", "◙", "♂", "♀", "♪", "♫", "☼", "►", "◄", "↕", "‼", "¶", "§", "▬", "↨", "↑", "↓", "←", "←", "↔", "▲", "▼", "⌂", "¢", "→", "¥", "ƒ", "ª", "º", "▒", "▓", "│", "┤", "╡", "╢", "╖", "╕", "╣", "║", "╗", "╝", "╜", "╛", "┐", "└", "┴", "┬", "├", "─", "┼", "╞", "╟", "╚", "╔", "╩", "╦", "╠", "═", "╬", "╧", "╨", "╤", "╥", "╙", "╘", "╒", "╓", "╫", "╪", "┘", "┌", "█", "▄", "▌", "▐", "▀", "α", "Γ", "π", "Σ", "σ", "µ", "τ", "Φ", "Θ", "Ω", "δ", "∞", "φ", "ε", "∩", "≡", "±", "≥", "≤", "⌠", "⌡", "≈", "°", "∙", "·", "√", "ⁿ", "²", "■", "¾", "×", "Ø", "Þ", "ð", "ღ", "ஐ", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "•", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "•", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "Ƹ", 'Ӝ', 'Ʒ', "★", "●", "♡", "ஜ", "ܨ");
        $string = str_replace($arrCharFrom, $arrCharEnd, $string);
        $finalString = str_replace($arrCharnonAllowed, '', $string);
        $url = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $finalString);
        $url = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $url);
        $url = trim(substr(strtolower($url), 0, $maxLength));
        $url = preg_replace("/[\/_|+ -]+/", $separator, $url);
        return $url;
    }

    //permission
    public static function getRole($roleID) {
        $arrRole = self::listRole();
        if (isset($arrRole[$roleID])) {
            return $arrRole[$roleID];
        }

        return false;
    }

    public static function getGender() {
        return array(
            self::MR => 'Nam',
            self::MS => 'Nữ',
            self::MRS => 'Không xác định',
        );
    }

    public static function listRole() {
        return array(
            self::ADMINISTRATOR => 'Administrator',
            self::MODERATOR => 'Moderator',
            self::EDITOR => 'Editor',
            self::SEO => 'SEO',
            self::SUPPORTER => 'Supporter',
            self::MEMBER => 'Người dùng thường',
        );
    }

    // Returns colored string
    public static function getColoredString($string, $foreground_color = null, $background_color = null) {
        $colored_string = "";

        // Check if given foreground color found
        if (isset(self::$foreground_colors[$foreground_color])) {
            $colored_string .= "\033[" . self::$foreground_colors[$foreground_color] . "m";
        }
        // Check if given background color found
        if (isset(self::$background_colors[$background_color])) {
            $colored_string .= "\033[" . self::$background_colors[$background_color] . "m";
        }

        // Add string and end coloring
        $colored_string .= trim($string) . "\033[0m\n\n";

        return $colored_string;
    }

    // Returns all foreground color names
    public static function getForegroundColors() {
        return array_keys(self::$foreground_colors);
    }

    // Returns all background color names
    public static function getBackgroundColors() {
        return array_keys(self::$background_colors);
    }

    public static function randomDigits($length = 6) {
        $characters = '0123456789';
        $string = '';
        $strLeng = strlen($characters) - 1;
        for ($p = 0; $p < $length - 1; $p ++) {
            $string .= $characters [mt_rand(0, $strLeng)];
        }
        return rand(1, 9) . $string;
    }

    /**
     * Upload images
     *
     * @param array $arrFile array images to upload
     * @param string $strControllerName controller name
     * @return array
     */
    public static function ImageUpload($arrFile = array(), $strControllerName = '') {
        $arrResult = array();
        if (empty($arrFile)) {
            return $arrResult;
        }
        $tmp = 1;
        $arrResult = array();
        $strTime = date('Y') . '/' . date('m') . '/' . date('d') . '/';
        $strFolderType = UPLOAD_PATH . $strControllerName;
        if (!is_dir($strFolderType)) {
            mkdir($strFolderType, 0777, true);
        }

        if (!is_writable($strFolderType) || !is_readable($strFolderType)) {
            chmod($strFolderType, 0777);
        }
        $strFolderByDate = $strFolderType . '/' . $strTime;
        $strFolderThumb = $strFolderByDate . 'thumbs/';
        if (!is_dir($strFolderByDate)) {
            mkdir($strFolderByDate, 0777, true);
            chmod($strFolderByDate, 0777);
            mkdir($strFolderThumb, 0777, true);
            chmod($strFolderThumb, 0777);
        }

        $arrFile = $arrFile[0] ? $arrFile : array($arrFile);
        $adapter = new \Zend\File\Transfer\Adapter\Http();
        $is_image = new \Zend\Validator\File\IsImage();
        $size = new \Zend\Validator\File\Size(array('max' => 2097152)); //2MB
        $total = new \Zend\Validator\File\Count(array('min' => 0, 'max' => 6));
        foreach ($arrFile as $k => $file) {
            $adapter->setValidators(array($size, $is_image, $total), $file['name']);
            $strExtension = pathinfo($strFolderByDate . $file['name'], PATHINFO_EXTENSION);
            if ($adapter->isValid($file['name'])) {
                $adapter->setDestination($strFolderByDate);
                $newFileName = microtime(true) . '.' . $strExtension;
                $adapter->addFilter('File\Rename', array(
                    'target' => $strFolderByDate . $newFileName,
                    'overwrite' => true,
                ));
                if ($adapter->receive($file['name'])) {
                    $arrSourceImage = UPLOAD_URL . $strControllerName . '/' . $strTime . $newFileName;
                }

                $arrThumb = self::getThumbSize($strControllerName);
                $serviceImage = new \Intervention\Image\ImageManager();

                foreach ($arrThumb as $thumbSize) {
                    list($width, $height) = explode('x', $thumbSize);
                    $thumbFileDir = $strFolderThumb . $thumbSize . '/';

                    if (!is_dir($thumbFileDir)) {
                        mkdir($thumbFileDir, 0777, true);
                        chmod($thumbFileDir, 0777);
                    }
                    $image = $serviceImage->make($strFolderByDate . $newFileName)->fit($width, $height);
                    $resultThumb = $image->save($thumbFileDir . $newFileName);

                    if ($resultThumb) {
                        $arrThumbImage[$thumbSize] = UPLOAD_URL . $strControllerName . '/' . $strTime . 'thumbs/' . $thumbSize . '/' . $newFileName;
                    }
                }
                array_push($arrResult, array('sourceImage' => $arrSourceImage, 'thumbImage' => $arrThumbImage));
            }
        }
        return $arrResult;
    }

    /**
     * Send Email to users
     * @param string|array $email email list
     * @param String $strTitle email title
     * @param String $strMessage email message
     * @return Boolean
     */
    public static function sendMail($email, $strTitle, $strMessage) {

        try {
            if (empty($email) || empty($strTitle) || empty($strMessage)) {
                $filename = WEB_ROOT . '/data/json_log.txt';
                $strError = " Ko co email, hoac ko co title, hoac ko co body \n";
                file_put_contents($filename, $strError, FILE_APPEND);
                return false;
            }

            //parse to array
            $arrEmail = (array) $email;

            $html = new \Zend\Mime\Part($strMessage);
            $html->type = \Zend\Mime\Mime::TYPE_HTML;
            $html->charset = 'utf-8';

            $body = new \Zend\Mime\Message();
            $body->setParts(array($html));

            $mail = new \Zend\Mail\Message();
            $mail->setSubject($strTitle)
                    ->addFrom(self::EMAIL_SYS, self::SITE_AUTH)
                    ->addReplyTo(self::EMAIL_SYS)
                    ->setBody($body);

            $mail->addTo($arrEmail);

            if ($mail->isValid()) {

                $smtpOptions = new \Zend\Mail\Transport\SmtpOptions();
                $smtpOptions->setHost(self::HOST_MAIL)
                        ->setPort(465)
                        ->setConnectionClass('login')
                        ->setConnectionConfig(
                                array(
                                    'username' => self::EMAIL_SYS,
                                    'password' => self::EMAIL_SYS_PASSWORD,
                                    'ssl' => 'ssl'
                                )
                );
                $transport = new \Zend\Mail\Transport\Smtp($smtpOptions);
                $transport->send($mail);
                return true;
            }
        } catch (\Exception $exc) {
            echo '<pre>';
            print_r($exc->getMessage());
            echo '</pre>';
            die();
        }
    }

    /**
     * Get thumb image size for user, topic, category, banner controller
     *
     * @param String $strControllerName
     * @return Array
     * @throws \Exception
     */
    public static function getThumbSize($strControllerName = '') {
        if ($strControllerName) {
            switch ($strControllerName) {
                case 6:
                case 5:
                    return array('356x220');
                case 7:
                    return array('265x198');
                case 2:
                    return array('324x160');
                case 3:
                    return array('324x235');
                case 4:
                    return array('218x150');
                default:
                    return array();
            }
        } else {
            throw new \Zend\Http\Exception('Controller name cannot be empty');
        }
    }

    public static function resizeImages($strCategory, $pathFile, $fileName, $thumbFileDir) {

        //$arrThumb = self::getThumbSize($strCategory);
        $arrThumb = array('265x198');
        $serviceImage = new \Intervention\Image\ImageManager();

        foreach ($arrThumb as $thumbSize) {
            list($width, $height) = explode('x', $thumbSize);

            try {
                $image = $serviceImage->make($pathFile)->resize($width, $height);
                $resultThumb = $image->save($thumbFileDir . '/' . $fileName);
            } catch (\Exception $exc) {
                echo $exc->getMessage();
                continue;
            }
        }
        if($resultThumb){
            return 1;
        } else {
            return 0;
        }
    }


    /**
     * Get Elasticsearch Config
     * @return \Elastica\Client
     */
    public static function getSearchConfig() {
        $port = 9200;
        $client = new \Elastica\Client(
                array('servers' => array(
                array('host' => 'localhost', 'port' => $port),
            )
        ));
        return $client;
    }

    /**
     * Get Client config
     * @return \GearmanClient
     */
    public static function getClientConfig() {
        $client = new \GearmanClient();
        $client->addServer('127.0.0.1', 4730);
        return $client;
    }

    /**
     * Get worker config
     * @return \GearmanWorker
     */
    public static function getWorkerConfig() {
        $worker = new \GearmanWorker();
        $worker->addServer('127.0.0.1', 4730);
        return $worker;
    }

    /**
     * Get Redis config for pageview, comment, notification, banned user ... etc
     * @param String $strType
     */
    public static function getRedisConfig($strType) {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 15);

        switch ($strType) {
            case 'cart':
                $redis->setOption(\Redis::OPT_PREFIX, 'Amazon247:cart:');
                $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
                $redis->select(6);
                break;
            default:
                break;
        }
        return $redis;
    }

    /**
     * Split long String
     *
     * @param string $strText
     * @param int $totalSplit
     * @return array
     */
    public static function splitWords($strText, $totalSplit = 2) {
        $arrWords = explode(' ', $strText);
        $result = array();
        $icnt = count($arrWords) - ($totalSplit - 1);
        for ($i = 0; $i < $icnt; $i++) {
            $str = '';
            for ($o = 0; $o < $totalSplit; $o++) {
                $str .= $arrWords[$i + $o] . ' ';
            }
            array_push($result, trim($str));
        }
        return $result;
    }

    public static function toPrettyTime($seconds) {
        $day = 24 * 60 * 60;
        $hour = 60 * 60;
        $minute = 60;

        $d = floor($seconds / $day);
        $h = floor(($seconds % $day) / $hour);
        $m = floor(($seconds % ($day * $hour) ) / $minute);

        if ($d > 0) {
            return $d . ' ngày';
        } elseif ($h > 0) {
            return $h . ' giờ';
        } else {
            return $m . ' phút';
        }
    }

    public static function formatPrice($price) {
        list($priceEven, $priceOdd) = explode('.', $price);
        if (empty($priceOdd)) {
            return number_format($price);
        }
        return number_format($priceEven) . '.' . $priceOdd;
    }

    public static function dateToTimestamp($day, $month, $year, $hour = 0, $minute = 0, $second = 0) {
        return mktime($hour, $minute, $second, $month, $day, $year);
    }

    public static function dateRange($first, $last, $step = '+1 day', $format = 'Y/m/d') {
        $dates = array();
        $current = strtotime($first);
        $last = strtotime($last);

        while ($current <= $last) {
            $dates[] = date($format, $current);
            $current = strtotime($step, $current);
        }
        return $dates;
    }

    public static function generateCaptcha($maxWordLength = 4, $width = 100, $height = 70) {
        if (!is_writable(CAPTCHA_PATH) || !is_dir(CAPTCHA_PATH)) {
            throw new \Exception(CAPTCHA_PATH . ' does not exist or cannot writable');
        }
        $captcha = new \Zend\Captcha\Image();
        $captcha->setWordlen($maxWordLength);
        $captcha->setWidth($width)->setHeight($height);
        $captcha->setFont(FRONTEND_FONT_PATH . 'arial.ttf');
        $captcha->setImgDir(CAPTCHA_PATH)->setImgUrl(CAPTCHA_URL);
        $captcha->setDotNoiseLevel(0)->setLineNoiseLevel(0);
        $captcha->setFontSize(15);
        $captcha->setExpiration(60 * 15); //session captcha expire in 15ms

        $captcha->generate();
        $id = $captcha->getId();

        $ses = new \Zend\Session\Container('Zend_Form_Captcha_' . $id);
        $captchaIterator = $ses->getIterator();
        return array(
            'id' => $id,
            'word' => $captchaIterator['word'],
            'url' => $captcha->getImgUrl() . $id . '.png'
        );
    }

    public static function escapeJsonString($value) { # list from www.json.org: (\b backspace, \f formfeed)
        $escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c", "'");
        $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b", "\\'");
        $result = str_replace($escapers, $replacements, $value);
        return $result;
    }

    public static function getRealIpAddr() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {  //check ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //to check ip is pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public static function crawler($strURL = '', $strCookiePath = '', $arrHeader = array(), $arrData = array()) {
        try {
            if (empty($strURL)) {
                return false;
                throw new \Zend\Http\Exception('URL cannot be empty');
            }
            $crawler = curl_init($strURL);

            if ($arrHeader) {
                curl_setopt($crawler, CURLOPT_HTTPHEADER, $arrHeader);
            }

            if ($strCookiePath) {
                curl_setopt($crawler, CURLOPT_COOKIEJAR, $strCookiePath);
                curl_setopt($crawler, CURLOPT_COOKIEFILE, $strCookiePath);
                curl_setopt($crawler, CURLOPT_COOKIE, session_name() . '=' . session_id());
            }
            curl_setopt($crawler, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($crawler, CURLOPT_COOKIESESSION, TRUE);
            curl_setopt($crawler, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($crawler, CURLOPT_DNS_CACHE_TIMEOUT, 0);
            curl_setopt($crawler, CURLOPT_MAXREDIRS, 5);
            curl_setopt($crawler, CURLOPT_FRESH_CONNECT, TRUE);
            curl_setopt($crawler, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:32.0) Gecko/20100101 Firefox/32.0');
            curl_setopt($crawler, CURLOPT_RETURNTRANSFER, TRUE);
            $arrData ? curl_setopt($crawler, CURLOPT_POSTFIELDS, $arrData) : '';
            $data = curl_exec($crawler);
            curl_close($crawler);
            return $data;
        } catch (\Zend\Http\Exception $exc) {
            die('<b>' . $exc->getMessage() . '</b>' . '<p></p>' . $exc->getTraceAsString());
        }
    }

    public static function sortArray($records, $field, $reverse = false) {
        $hash = array();

        foreach ($records as $record) {
            $hash[$record[$field]] = $record;
        }

        ($reverse) ? krsort($hash) : ksort($hash);

        $records = array();

        foreach ($hash as $record) {
            $records[] = $record;
        }

        return $records;
    }

    /**
     * Detect time of the day
     * @return string
     */
    public static function detectTimeOfDay() {
        $time = date("H");
        if ($time < "12") {
            echo "buổi sáng vui vẻ và hạnh phúc.";
        } else
        if ($time >= "12" && $time < "18") {
            echo "buổi chiều vui vẻ và hạnh phúc.";
        } else
        if ($time >= "18" && $time < "22") {
            echo "buổi tối vui vẻ và hạnh phúc.";
        } else
        if ($time >= "22") {
            echo "buổi tối ngon giấc.";
        }
    }

    /**
     * Set FlashMessenger
     * @param string $namespace
     * @param string $message
     */
    public static function setFlashMessenger($namespace = '', $message = '') {
        $messenger = new \Zend\Mvc\Controller\Plugin\FlashMessenger();
        $messenger->setNamespace($namespace)->addMessage($message);
    }

    public static function formatDateString($date) {
        $current = time() - $date;

        if ($current < 60) {
            return $current . " giây trước";
        } else {
            $minute = round($current / 60);
            if ($minute < 60) {
                return $minute . " phút trước";
            } else {
                $house = round($minute / 60);
                if ($house < 24) {
                    return $house . " giờ trước";
                } else {
                    $day = round($house / 24);
                    if ($day < 8) {
                        return $day . " Ngày trước";
                    } else {
                        return date("d/m/Y", $date);
                    }
                }
            }
        }
    }

    public static function clean($str = "") {
        $vowels = array('/', '\\', ':', ';', '!', '#', '$', '%', '^', '*', '(', ')', '=', '{', '}', '[', ']', '"', "'", '<', '>', '?', '~', '`', '&');
        return str_replace($vowels, "", $str);
    }

    public static function cleanArray($array = "") {
        $arrReturn = [];
        foreach ($array as $key => $value) {
            $arrReturn[$key] = self::clean($value);
        }
        return $arrReturn;
    }

    public static function createLogs($arrParamsRoute, $arrParams, $intId) {
        $arrData = array(
            'user_id' => UID,
            'module' => $arrParamsRoute['module'],
            'controller' => $arrParamsRoute['__CONTROLLER__'],
            'action' => $arrParamsRoute['action'],
            'created_date' => time(),
            'log_content' => json_encode($arrParams),
            'table_id' => $intId
        );

        return $arrData;
    }

    public static function formatDateTime($time) {
        $temp = date('d/m/Y/H/i/s', $time);
        list($day, $month, $year, $hour, $min, $second) = explode('/', $temp);
        $strReturn = 'Ngày ' . $day . ' tháng ' . $month . ' năm ' . $year . ' - ' . $hour . ' giờ ' . $min . ' phút ' . $second . ' giây';
        return $strReturn;
    }
    
    public static function mkdirUpload(){
        $today = date('Y') . '/' . date('m') . '/' . date('d');
        $upload_dir['path'] = STATIC_PATH . '/uploads/content/'. $today;
        if (!file_exists($upload_dir['path'])) {
            mkdir($upload_dir['path'], 0777, true);
        }

        $upload_dir['url'] = STATIC_URL . '/uploads/content/'. $today;
        return $upload_dir;
    }

    public static function blockString(){
        return array(
            'http','2012','2013','2014','2015','2016','webtretho','excel','phương trình','c++', 'java', 'sql', 'giải tích', 'mp3',
            'ebook','download','youtube','zing','tướng tuần','pdf','lyric','tiếng anh','tiếng nhật','tiếng trung','karaoke',
            '.com','beat','vietsub','tap', 'toán', 'hóa', 'lý'
        );
    }

    public static function writeLog($fileName = '', $arrParam = array())
    {
        date_default_timezone_set('Asia/Saigon');

        $path = LOG_FOLDER . '/CP7_' . $fileName;

        // set newline character to "\r\n" if script is used on Windows
        //if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $nl = "\r\n";
        //}

        // define the current date (it will be appended to the log file name)
        $today = date('Y-m-d');
        //
        
        // open log file for writing only; place the file pointer at the end of the file
        // if the file does not exist, attempt to create it
        $fp = fopen($path . '_' . $today, 'a') or exit("Can't open $path!");

        $arrParam['Time'] = date('H:i:s');

        // define script name
        $script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
        // define current time
        $time = date('H:i:s');
        // write current time, script name and message to the log file
        $message = json_encode($arrParam);
        fwrite($fp, "$message" . $nl);

        fclose($fp);

    }

}
