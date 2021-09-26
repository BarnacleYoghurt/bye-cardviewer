<?php

namespace bye_plugin;

class Blocks
{
    private Database $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    function register_categories( $block_categories ) {
        return array_merge(
            $block_categories,
            [
                [
                    'slug'  => 'bye-blocks',
                    'title' => esc_html__( 'BYE', 'text-domain' )
                ],
            ]
        );
    }

    function register_blocks() {
        register_block_type(__DIR__ . '/../block-meta/bye-cardviewer-card', array('render_callback' => array($this,'bye_cardviewer_card_render')));
        //register_block_type(__DIR__ . '/../block-meta/bye-cardviewer-helloworld', array());

        wp_add_inline_script( //still doesn't work for some reason
            'bye-cardviewer-card',
            'var _siteUrl = ' . get_site_url() . ';',
            'before'
        );
    }

    function bye_cardviewer_card_render($block_attributes, $content) {
        $image_url = get_site_url() . '/wp-content/uploads/cards/' . $block_attributes['expansion'] . '/' . $block_attributes['version'] . '/' . $block_attributes['cardId'] . '.png';
        $carddata = $this->database->find_card($block_attributes['cardId'],$block_attributes['version']);

        $el_img = sprintf('<a class="bye-card-image" href="%s"><img src="%s"/></a>',$image_url,$image_url);
        $el_cardname = sprintf('<h3 class="bye-card-cardname">%s</h3>',$carddata->name);
        $el_cardtype = sprintf('<span class="bye-card-cardtype">%s</span>',$carddata->type);
        if (($carddata->type & 0x1) == 0x1) {
            $el_cardstats = sprintf('<span class="bye-card-cardstats">%s %s %s %s</span>',
                $this->format_level($carddata->level, $carddata->type),
                $this->format_attribute($carddata->attribute, $carddata->type),
                $this->format_race($carddata->race, $carddata->type),
                $this->format_atkdef($carddata->atk,$carddata->def, $carddata->type));
        }
        else {
            $el_cardstats = '';
        }
        $el_cardtext = sprintf('<p class="bye-card-cardtext">%s</p>',$carddata->description);

        return sprintf('<div class="wp-block-bye-card">%s%s%s%s%s</div>',$el_img,$el_cardname,$el_cardtype,$el_cardstats, $this->format_cardtext($el_cardtext));
    }

    function format_cardtype($type) {
        return $type;
    }

    function format_level($level, $type) {
        return sprintf('Level %s', $level);
    }

    function format_attribute($attribute, $type) {
        return $attribute;
    }

    function format_race($race, $type) {
        return $race;
    }

    function format_atkdef($atk, $def, $type) {
        return sprintf("ATK %s / DEF %s", $atk, $def);
    }

    function format_cardtext($text) {
        return str_replace('\\"', '"',
            str_replace('\\\'', '\'',
                str_replace("\n","<br/>",$text)));
    }
}