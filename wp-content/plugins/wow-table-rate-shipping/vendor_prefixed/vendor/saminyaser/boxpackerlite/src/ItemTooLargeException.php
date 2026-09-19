<?php

/**
 * Box packing (3D bin packing, knapsack problem).
 *
 * @author Samin Yaser
 */
declare (strict_types=1);
namespace WTRS_Vendor\SaminYaser\BoxPackerLite;

/**
 * Exception used when an item is too large to pack into any box.
 *
 * @deprecated now unused, just catch NoBoxesAvailableException
 */
class ItemTooLargeException extends NoBoxesAvailableException
{
}
