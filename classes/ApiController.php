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
        register_rest_route($namespace, '/cards(?:/(?P<expansion_code>[a-zA-Z0-9]+))?', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_cards'),
            'permission_callback' => '__return_true',
            'args' => array(
                'max_version' => array('default' => '99.99.99'),
                'lang' => array('default' => 'en')
            ),
        ));
    }

    function get_expansions($data)
    {
        return new WP_REST_Response($this->database->all_expansions(), 200);
    }

    function get_cards($data){
        $expansion_code = $data['expansion_code'];
        $max_version = $data['max_version'];
        $lang = $data['lang'];
        if (isset($expansion_code) && strlen($expansion_code) > 0) {
            return new WP_REST_Response($this->database->all_cards_in_expansion($expansion_code,$max_version,$lang), 200);
        } else {
            return new WP_REST_Response($this->database->all_cards($max_version,$lang), 200);
        }
    }
}