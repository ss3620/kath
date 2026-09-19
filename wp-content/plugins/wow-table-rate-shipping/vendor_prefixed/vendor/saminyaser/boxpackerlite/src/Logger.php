<?php

/**
 * Box packing (3D bin packing, knapsack problem).
 *
 * @author Samin Yaser
 */
declare (strict_types=1);
namespace WTRS_Vendor\SaminYaser\BoxPackerLite;

/**
 * Lightweight logger for WordPress environments.
 */
class Logger
{
    public function log(string $message, array $context = []): void
    {
        if (!$this->isWordPress()) {
            return;
        }
        if ($context) {
            $message .= ' ' . print_r($context, \true);
        }
        error_log(print_r($message, \true));
    }
    public function info(string $message, array $context = []): void
    {
        $this->log($message, $context);
    }
    public function debug(string $message, array $context = []): void
    {
        $this->log($message, $context);
    }
    private function isWordPress(): bool
    {
        return defined('ABSPATH') || function_exists('WTRS_Vendor\add_action');
    }
}
