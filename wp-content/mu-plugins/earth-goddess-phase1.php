<?php
/**
 * Plugin Name: Earth Goddess AU – Phase 1 Backend
 * Description: User roles, VIP/Affiliate/Ambassador seeding helpers, wholesale pricing, application forms, and admin SOP for Phase 1.
 * Version: 1.0.0
 * Author: Earth Goddess AU
 */

defined( 'ABSPATH' ) || exit;

define( 'EG_PHASE1_VERSION', '1.0.1' );
define( 'EG_PHASE1_PATH', __DIR__ . '/earth-goddess-phase1' );
define( 'EG_PHASE1_OPTION_SEEDED', 'eg_phase1_seeded_v1' );

require_once EG_PHASE1_PATH . '/class-roles.php';
require_once EG_PHASE1_PATH . '/class-wholesale-pricing.php';
require_once EG_PHASE1_PATH . '/class-vip-discounts.php';
require_once EG_PHASE1_PATH . '/class-applications.php';
require_once EG_PHASE1_PATH . '/class-setup-seeder.php';
require_once EG_PHASE1_PATH . '/class-admin-sop.php';
require_once EG_PHASE1_PATH . '/class-live-pathway-tester.php';

EG_Roles::init();
EG_Wholesale_Pricing::init();
EG_VIP_Discounts::init();
EG_Applications::init();
EG_Setup_Seeder::init();
EG_Admin_SOP::init();
EG_Live_Pathway_Tester::init();
