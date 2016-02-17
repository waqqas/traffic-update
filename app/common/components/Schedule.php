<?php

namespace common\components;

class Schedule extends \omnilight\scheduling\Schedule{

    public function setEvents($events){
        $this->_events = $events;
    }
}