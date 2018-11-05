<?php
class ApiException{
    private $text;
    private $code;

    public function __construct($text, $code){
        $this->text = $text;
        $this->code = $code;
    }

    public function getException(){
        if($this->code !== NULL) {
            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
            { header($protocol . ' ' . $this->code . ' ' . $this->text); exit;}
        }
    }
}