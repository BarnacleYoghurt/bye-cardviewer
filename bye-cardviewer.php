<?php
/*
Plugin Name: BYE Card Viewer
*/

include_once ("classes/DBException.php");
include_once ("classes/Admin.php");
include_once ("classes/Blocks.php");
include_once ("classes/Database.php");
include_once ("classes/ApiController.php");
use bye_plugin\Admin;
use bye_plugin\ApiController;
use bye_plugin\Blocks;
use bye_plugin\Database;

$database = new Database();
$blocks = new Blocks($database);
$admin = new Admin($database);
$apiController = new ApiController($database);

register_activation_hook( __FILE__, array($database, 'setup_tables') );
add_action('block_categories_all', array($blocks, 'register_categories'));
add_action('init', array($blocks, 'register_blocks'));
add_action('wp_enqueue_scripts', array($blocks, 'enqueue_cardlink_events'));
add_action('admin_menu', array($admin, 'setup_menu'));
add_action('rest_api_init', array($apiController, 'register_routes'));

add_shortcode('cardOfTheDay', array($blocks,'shortcode_cotd'));
add_shortcode('cardlink', array($blocks,'shortcode_cardlink'));