<?php

/**
 * Box packing (3D bin packing, knapsack problem).
 *
 * @author Samin Yaser
 */
declare (strict_types=1);
namespace WTRS_Vendor\SaminYaser\BoxPackerLite;

/**
 * A callback to be used with usort(), implementing logic to determine which Item is a higher priority for packing.
 */
interface ItemSorter
{
    /**
     * Return -1 if $itemA is preferred, 1 if $itemB is preferred or 0 if neither is preferred.
     */
    public function compare(Item $itemA, Item $itemB): int;
}
