<?php

namespace console\components\sms;

class Response extends \yii\console\Response
{
    const CONTENT_BEGINNING = 0;
    const CONTENT_END = 1;

    public $content = [];


    public function addContent($content, $position = self::CONTENT_END)
    {
        // convert content to array
//        if (!is_array($this->content)) {
//            if (!empty($this->content)) {
//                $this->content = [$this->content];
//            } else {
//                $this->content = [];
//            }
//        }

        if ($position == self::CONTENT_END) {
            $position = count($this->content);
        }


        array_splice($this->content, $position, 0, $content);

        \Yii::info("content = ". print_r($this->content, true));
    }

    public function getContent()
    {
        if (is_array($this->content)) {
            return implode('', $this->content);
        }
        return $this->content;
    }
}