<?php

namespace levitarmouse\kiss_orm\dto;

final class CollectionDTO {
    public $type;
    public $size;
    public $aCollection;

    protected $index;
//    protected $collectionSize;

    public function __construct() {
        $this->size = 0;
        $this->type = '';
        $this->aCollection = array();
        $this->index = 0;
    }

    public function getNext()
    {
        if ($this->size > 0) {

            if ($this->index < $this->size) {

                    $index = $this->index;
                    $this->index ++;

                $return = $this->aCollection[$index];

                return $return;
            }
        } else {
//            return $this;
        }
        return null;
    }

    public function getCollection()
    {
        return $this->aCollection;
    }

    public function getCollectionSize()
    {
        return count($this->aCollection);
    }

}