<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 12.11.17
 * Time: 15:43
 */

namespace PhotoDatabase;

use RecursiveIteratorIterator;
use Traversable;


class PhotoDbIterator extends RecursiveIteratorIterator
{
public function __construct(Traversable $iterator, int $mode = self::LEAVES_ONLY, int $flags = 0, )
{
    parent::__construct($iterator, $mode, $flags);
}
}