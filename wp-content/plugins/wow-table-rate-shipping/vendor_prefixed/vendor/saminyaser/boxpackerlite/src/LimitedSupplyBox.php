<?php

/**
 * Box packing (3D bin packing, knapsack problem).
 *
 * @author Samin Yaser
 */
declare (strict_types=1);
namespace WTRS_Vendor\SaminYaser\BoxPackerLite;

/**
 * A "box" (or envelope?) to pack items into with limited supply.
 */
interface LimitedSupplyBox extends Box
{
    /**
     * Quantity of boxes available.
     */
    public function getQuantityAvailable(): int;
}
