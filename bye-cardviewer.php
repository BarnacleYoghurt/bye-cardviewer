<?php
/*
Plugin Name: BYE Card Viewer
*/

include_once ("classes/DBException.php");
include_once ("classes/Admin.php");
include_once ("classes/Blocks.php");
include_once ("classes/Database.php");
use bye_plugin\Admin;
use bye_plugin\Blocks;
use bye_plugin\Database;

$database = new Database();
$blocks = new Blocks($database);
$admin = new Admin($database);

register_activation_hook( __FILE__, array($database, 'setup_tables') );
add_action('block_categories_all', array($blocks, 'register_categories'));
add_action('init', array($blocks, 'register_blocks'));
add_action('admin_menu', array($admin, 'setup_menu'));