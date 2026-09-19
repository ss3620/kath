<?php

/**
 * Box packing (3D bin packing, knapsack problem).
 *
 * @author Samin Yaser
 */
declare (strict_types=1);
namespace WTRS_Vendor\SaminYaser\BoxPackerLite;

class DefaultPackedBoxSorter implements PackedBoxSorter
{
    public function compare(PackedBox $boxA, PackedBox $boxB): int
    {
        $choice = $boxB->getItems()->count() <=> $boxA->getItems()->count();
        if ($choice === 0) {
            $choice = $boxB->getVolumeUtilisation() <=> $boxA->getVolumeUtilisation();
        }
        if ($choice === 0) {
            $choice = $boxB->getUsedVolume() <=> $boxA->getUsedVolume();
        }
        return $choice;
    }
}
