<?php
/**
 * Plugin Name:       RawConscious MS Graph
 * Plugin URI:        https://wp.rawconscious.com/docs/plugins/rc-msgraph
 * Description:       Middleware for Microsoft Graph API
 * Version:           1.0.0
 * Author:            John Smith
 * Author URI:        https://author.example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */ //phpcs:ignore.

define( 'RC_MSGRAPH_VERSION', '1.0.0' );
define( 'RC_MSGRAPH_PREFIX', 'rc_msgraph' );
define( 'RC_MSGRAPH_PATH', plugin_dir_path( __FILE__ ) );
define( 'RC_MSGRAPH_URI', plugin_dir_url( __FILE__ ) );

require_once RC_MSGRAPH_PATH . '/vendor/autoload.php';

$root_path = ABSPATH;

$dotenv = Dotenv\Dotenv::createImmutable( $root_path );
$dotenv->load();
$dotenv->required( array( 'CLIENT_ID', 'CLIENT_SECRET', 'TENANT_ID' ) );

require_once RC_MSGRAPH_PATH . '/includes/class-rc-msgraph.php';
require_once RC_MSGRAPH_PATH . '/templates/email.php';
