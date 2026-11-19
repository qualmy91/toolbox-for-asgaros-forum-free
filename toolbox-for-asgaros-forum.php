<?php

/*
Plugin Name: Toolbox for Asgaros Forum
Description: Toolbox to adjust your Asgaros Forum in a simple way.
Version: 1.2.5
Requires at least: 4.9
Requires PHP: 5.5
Author: Dominik Rauch
Author URI: https://www.dominikrauch.de/en
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: toolbox-for-asgaros-forum
*/

use Tfaf\Inc\Base\Activate;
use Tfaf\Inc\Base\Deactivate;

defined('ABSPATH') or die('Hey, that\s a human free area!!!');

define('TFAF_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('TFAF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TFAF_PLUGIN', plugin_basename(__FILE__));
define('TFAF_VERSION', '1.2.5');


require_once TFAF_PLUGIN_PATH . 'Inc/Init.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Base/Activate.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Base/Deactivate.php';

register_activation_hook(__FILE__, 'tfaf_activate_plugin');
register_deactivation_hook(__FILE__, 'tfaf_deactivate_plugin');

/**
 * Activate the Plugin
 */
function tfaf_activate_plugin()
{
    Activate::activate();
}

/**
 * Deactivate the Plugin
 */
function tfaf_deactivate_plugin()
{
    Deactivate::deactivate();
}

// Initialize all the services of the Plugin
if (class_exists('Tfaf\Inc\\Init')) {
    Tfaf\Inc\Init::register_services();
}