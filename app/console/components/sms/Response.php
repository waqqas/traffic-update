<?php

namespace console\components\sms;

class Response extends \yii\console\Response
{
    const CONTENT_BEGINNING = 0;
    const CONTENT_END = -1;

    public $content = [];

    public $session = [];

    public function addContent($content, $position = self::CONTENT_END)
    {
        // convert content to array
        if (!is_array($this->content)) {
            if (!empty($this->content)) {
                $this->content = [$this->content];
            } else {
                $this->content = [];
            }
        }

        if ($position == self::CONTENT_END) {
            $position = count($this->content);
        }


        array_splice($this->content, $position, 0, $content);
    }

    public function getContent()
    {
        if (is_array($this->content)) {
            return implode('\n\n', $this->content);
        }
        return $this->content;
    }

    public function addSession($key, $value){
        $this->session[$key] = $value;
    }
}