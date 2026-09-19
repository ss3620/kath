<?php

/**
 * Box packing (3D bin packing, knapsack problem).
 *
 * @author Samin Yaser
 */
declare (strict_types=1);
namespace WTRS_Vendor\SaminYaser\BoxPackerLite;

use RuntimeException;
/**
 * Exception used when an item cannot be packed into any box.
 */
class NoBoxesAvailableException extends RuntimeException
{
    public Item $item;
    public function __construct(string $message, Item $item)
    {
        $this->item = $item;
        parent::__construct($message);
    }
    public function getItem(): Item
    {
        return $this->item;
    }
}
