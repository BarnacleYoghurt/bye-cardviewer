<?php

namespace bye_plugin;

use \WP_REST_Controller;
use \WP_REST_Server;
use \WP_REST_Response;

class ApiController extends WP_REST_Controller
{
    private Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    function register_routes()
    {
        $version = '1';
        $namespace = 'bye/v' . $version;
        register_rest_route($namespace, '/expansions', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_expansions'),
            'permission_callback' => '__return_true',
            'args' => array(),
        ));
    }

    function get_expansions($data)
    {
        return new WP_REST_Response($this->database->all_expansions(), 200);
    }
}