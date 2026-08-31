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
        register_rest_route($namespace, '/cards-ddm(?:/(?P<expansion_code>[a-zA-Z0-9]+))?', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_cards_ddm'),
            'permission_callback' => '__return_true',
            'args' => array(
                'max_version' => array('default' => '99.99.99'),
                'lang' => array('default' => 'en')
            ),
        ));
        register_rest_route($namespace, '/cardoftheday', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_cardoftheday'),
            'permission_callback' => '__return_true',
            'args' => array(),
        ));
        register_rest_route($namespace, '/cardblock-renderer', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_cardblockRenderer'),
            'permission_callback' => '__return_true',
        ));
    }

    function get_expansions($data)
    {
        return new WP_REST_Response($this->database->all_expansions(), 200);
    }

    function get_cards($data)
    {
        $expansion_code = $data['expansion_code'];
        $max_version = $data['max_version'];
        $lang = $data['lang'];
        if (isset($expansion_code) && strlen($expansion_code) > 0) {
            return new WP_REST_Response($this->database->all_cards_in_expansion($expansion_code, $max_version, $lang), 200);
        } else {
            return new WP_REST_Response($this->database->all_cards($max_version, $lang), 200);
        }
    }

    function get_cards_ddm($data)
    {
        $expansion_code = $data['expansion_code'];
        $max_version = $data['max_version'];
        $lang = $data['lang'];
        if (isset($expansion_code) && strlen($expansion_code) > 0) {
            $raw = $this->database->all_cards_in_expansion($expansion_code, $max_version, $lang);
        } else {
            $raw = $this->database->all_cards($max_version, $lang);
        }

        $cooked = [];
        foreach ($raw as $rc) {
            $cc = new CardInfo(
                -1, //not relevant here anyway
                $rc->code,
                '0.0.0', //not relevant here anyway
                $rc->expansion_id,
                $rc->type,
                $rc->attribute,
                $rc->race,
                $rc->level,
                $rc->atk,
                $rc->def,
                $rc->lang,
                $rc->name,
                $rc->description
            );
            $type = $cc->getTypeName();
            $race = $cc->getRaceName();
            if (str_ends_with($type, ' Spell')) {
                $race = str_replace(' Spell', '', $type);
                $type = 'Spell';
            } else {
                if (str_ends_with($type, ' Trap')) {
                    $race = str_replace(' Trap', '', $type);
                    $type = 'Trap';
                }
            }
            if ($type === 'Spell' || $type === 'Trap') {
                $cooked[] = array_merge(array(
                    'name' => $cc->getName(),
                    'type' => $type,
                    'desc' => $cc->getDescription(),
                    'credits' => 'The LEGO Group'
                ), $race !== '?' ? array('race' => $race) : array());
            } else {
                $cooked[] = array(
                    'name' => $cc->getName(),
                    'type' => $type,
                    'attribute' => $cc->getAttributeName(),
                    'race' => $race,
                    'level' => $cc->getLevel(),
                    'atk' => $cc->getAtk(),
                    'def' => $cc->getDef(),
                    'desc' => $cc->getDescription(),
                    'credits' => 'The LEGO Group'
                );
            }
        }

        return new WP_REST_Response($cooked, 200);
    }

    function get_cardoftheday($data)
    {
        try {
            return new WP_REST_Response($this->database->find_card_ofTheDay(), 200);
        } catch (DBException $e) {
            return new WP_REST_Response($e,404);
        }
    }

    function get_cardblockRenderer($data) {
        $out = array(
            'rendered' => render_block(array(
                    'blockName'     => 'bye-cardviewer/card',
                    'attrs'         => $data->get_query_params(),
                    'innerHTML'     => '',
                    'innerContent'  => array()
                ))
        );
        return new WP_REST_Response($out, 200);
    }
}