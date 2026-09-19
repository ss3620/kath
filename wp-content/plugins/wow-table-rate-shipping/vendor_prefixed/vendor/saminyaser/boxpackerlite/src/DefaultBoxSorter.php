<?php

/**
 * Box packing (3D bin packing, knapsack problem).
 *
 * @author Samin Yaser
 */
declare (strict_types=1);
namespace WTRS_Vendor\SaminYaser\BoxPackerLite;

class DefaultBoxSorter implements BoxSorter
{
    public function compare(Box $boxA, Box $boxB): int
    {
        $boxAVolume = $boxA->getInnerWidth() * $boxA->getInnerLength() * $boxA->getInnerDepth();
        $boxBVolume = $boxB->getInnerWidth() * $boxB->getInnerLength() * $boxB->getInnerDepth();
        $volumeDecider = $boxAVolume <=> $boxBVolume;
        // try smallest box first
        if ($volumeDecider !== 0) {
            return $volumeDecider;
        }
        $emptyWeightDecider = $boxA->getEmptyWeight() <=> $boxB->getEmptyWeight();
        // with smallest empty weight
        if ($emptyWeightDecider !== 0) {
            return $emptyWeightDecider;
        }
        // maximum weight capacity as fallback decider
        return $boxA->getMaxWeight() - $boxA->getEmptyWeight() <=> $boxB->getMaxWeight() - $boxB->getEmptyWeight();
    }
}
