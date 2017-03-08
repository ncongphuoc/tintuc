<?php
namespace My\View\Helper;

use Zend\View\Helper\AbstractHelper;

class MyHeadTitle extends AbstractHelper {

    static $title = 'Hellonews.info';

    public function __construct() {
        
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function __invoke($string) {
        return $this->myHeadTitle($string);
    }
    
    public function myHeadTitle() {
        $title = '<title>' . $this->title . '</title>';
        return $title;
    }
    
}