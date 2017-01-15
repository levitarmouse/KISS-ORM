<?php
/**
 * GetByIdDTO class
 *
 * PHP version 5
 *
 * @package   ORM
 * @author    Gabriel Prieto <gab307@gmail.com>
 * @copyright 2012 LM
 * @link      LM
 */

namespace levitarmouse\kiss_orm\dto;

/**
 * GetByIdDTO class
 *
 * @package   ORM
 * @author    Gabriel Prieto <gab307@gmail.com>
 * @copyright 2012 LM
 * @link      LM
 */
class GetByIdDTO
{
    public $id;

    public function __construct($id)
    {
        $this->id      = $id;
    }
}