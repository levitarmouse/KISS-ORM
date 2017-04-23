<?php

namespace levitarmouse\kiss_orm\dto;

final class CollectionDTO {
    public $type;
    public $size;

    protected $index;
    
    use levitarmouse\core\LmIterator;

    public function __construct() {
        $this->size = 0;
        $this->type = '';
        $this->aCollection = array();
        $this->index = 0;
    }
}
