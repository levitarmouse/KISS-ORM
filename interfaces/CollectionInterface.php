<?php
/**
 * Colection interface
 *
 * PHP version 5
 *
 * @category  FrontEnd
 * @package   ORM
 * @created   May 23, 2014
 * @author    Gabriel Prieto <gabriel.pietro@levitarmouse.com>
 * @copyright 2014 Levitarmouse
 * @license   levitarmouse http://www.levitarmouse.com
 * @link      http://www.levitarmouse.com
 *
 */

namespace levitarmouse\kiss_orm\interfaces;

use levitarmouse\kiss_orm\dto\GetByFilterDTO;
use levitarmouse\kiss_orm\dto\LimitDTO;
use levitarmouse\kiss_orm\dto\OrderByDTO;
/**
 * Interface CollectionInterface
 *
 * description
 *
 * PHP version 5
 *
 * @category  FrontEnd
 * @package   ORM
 * @created   May 23, 2014
 * @author    Gabriel Prieto <gabriel.pietro@levitarmouse.com>
 * @copyright 2014 Levitarmouse
 * @license   levitarmouse http://www.levitarmouse.com
 * @link      http://www.levitarmouse.com
 *
 */
interface CollectionInterface
{
    //public function getAll(\levitarmouse\kiss_orm\dto\GetAllDTO $dto);
    public function getAll();

    public function getByFilter(GetByFilterDTO $filterDto, OrderByDTO $orderDto = null, LimitDTO $limitDto = null);

//    public function getByExample(GetByExampleDTO $exampleDTO);

//    public function getNext();
}
