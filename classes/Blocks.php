<?php

namespace bye_plugin;

use Exception;

class Blocks
{
    private Database $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    function register_categories($block_categories)
    {
        return array_merge(
            $block_categories,
            [
                [
                    'slug' => 'bye-blocks',
                    'title' => esc_html__('BYE', 'text-domain')
                ],
            ]
        );
    }

    function register_blocks()
    {
        register_block_type(__DIR__ . '/../block-meta/bye-cardviewer-card', array('render_callback' => array($this, 'bye_cardviewer_card_render')));
        //register_block_type(__DIR__ . '/../block-meta/bye-cardviewer-helloworld', array());

        wp_add_inline_script( //still doesn't work for some reason
            'bye-cardviewer-card',
            'var _siteUrl = ' . get_site_url() . ';',
            'before'
        );
    }

    function bye_cardviewer_card_render($block_attributes, $content)
    {
        try {
            if (array_key_exists('fromUrlParams', $block_attributes) && $block_attributes['fromUrlParams']) {
                //Note: Params like cardId[card1] won't work here because PHP is an array-expanding little shit
                $block_attributes['cardId'] = $_GET[$block_attributes['urlParamCardId']] ?? $block_attributes['cardId'];
                $block_attributes['version'] = $_GET[$block_attributes['urlParamVersion']] ?? $block_attributes['version'];
            }
            if (array_key_exists('cardOfTheDay', $block_attributes) && $block_attributes['cardOfTheDay']) {
                $carddata  =  $this->database->find_card_ofTheDay($block_attributes['language'] ?? 'en');
            }
            else {
                $carddata = $this->database->find_card($block_attributes['cardId'], $block_attributes['version'] ?? '99.99.99',
                    $block_attributes['language'] ?? 'en');
            }
            $expansion = $this->database->get_expansion($carddata->getExpansionId());
            $image_url = '/cards/' . $carddata->getVersion() . '/' . $expansion->code . '/' . $carddata->getLang() . '/' . $carddata->getCode() . '.png';
            if (!file_exists(wp_upload_dir()['basedir'] . $image_url)) {
                $image_url = substr($image_url, 0, strlen($image_url) - 4) . '.jpg';
            }
            $image_url = wp_upload_dir()['baseurl'] . $image_url;

            $el_img = sprintf('<a class="bye-card-image" href="%s"><img src="%s"/></a>', $image_url, $image_url);
            $el_cardname = sprintf('<h3 class="bye-card-cardname">%s</h3>', $carddata->getName());
            $el_cardtype = sprintf('<span class="bye-card-cardtype">%s</span>', $carddata->getTypeName());
            $el_cardstats = sprintf('<span class="bye-card-cardstats">%s</span>', $this->format_cardstats($carddata));
            $el_cardtext = sprintf('<p class="bye-card-cardtext"><span>%s</span></p>', $this->format_cardtext($carddata->getDescription()));
            $el_metadata = sprintf('<span class="bye-card-meta">%s (v%s)</span>', $expansion->name, $carddata->getVersion());

            return sprintf('<div %s>%s%s%s%s%s%s</div>', get_block_wrapper_attributes(),
                $el_img, $el_cardname, $el_cardtype, $el_cardstats, $el_cardtext, $el_metadata);
        } catch (DBException $e) {
            return sprintf('<div class="bye-card-error">
                                        <h3>Cardviewer Error!</h3>
                                        <p>Could not display card %s from %s v%s</p>
                                        <p>Error mesage: %s</p>
                                    </div>', $block_attributes['cardId'], $block_attributes['expansion'], $block_attributes['version'], $e->getMessage());
        }
    }

    function format_cardstats($carddata)
    {
        if ($carddata->isMonster()) {
            if ($carddata->isXyz()) {
                $stats = sprintf('Rank %d', $carddata->getLevel());
            } elseif ($carddata->isLink()) {
                $arrows = '';
                if ($carddata->isLinkArrow(CardInfo::LINK_MARKER_LEFT)) {
                    $arrows .= '&#9664; '; //◀
                }
                if ($carddata->isLinkArrow(CardInfo::LINK_MARKER_TOP_LEFT)) {
                    $arrows .= '&#8598; '; //↖
                }
                if ($carddata->isLinkArrow(CardInfo::LINK_MARKER_TOP)) {
                    $arrows .= '&#9650; '; //▲
                }
                if ($carddata->isLinkArrow(CardInfo::LINK_MARKER_TOP_RIGHT)) {
                    $arrows .= '&#8599; '; //↗
                }
                if ($carddata->isLinkArrow(CardInfo::LINK_MARKER_BOTTOM_LEFT)) {
                    $arrows .= '&#8601; '; //↙
                }
                if ($carddata->isLinkArrow(CardInfo::LINK_MARKER_BOTTOM)) {
                    $arrows .= '&#9660; '; //▼
                }
                if ($carddata->isLinkArrow(CardInfo::LINK_MARKER_BOTTOM_RIGHT)) {
                    $arrows .= '&#8600; '; //↘
                }
                if ($carddata->isLinkArrow(CardInfo::LINK_MARKER_RIGHT)) {
                    $arrows .= '&#9654; '; //▶
                }
                $arrows = trim($arrows); //Remove trailing space

                $stats = sprintf('Link-%d [%s]', $carddata->getLevel(), $arrows);
            } else {
                $stats = sprintf('Level %d', $carddata->getLevel());
            }

            if ($carddata->isPendulum()) {
                $stats = sprintf('%s | Scale %d/%d', $stats, $carddata->getLScale(), $carddata->getRScale());
            }

            $stats = sprintf('%s | %s %s | ATK %d', $stats, $carddata->getAttributeName(), $carddata->getRaceName(), $carddata->getAtk());
            if (!$carddata->isLink()) {
                $stats = sprintf('%s / DEF %d', $stats, $carddata->getDef());
            }

            return $stats;
        } else {
            return '';
        }
    }

    function format_cardtext($text)
    {
        return str_replace('\\"', '"',
            str_replace('\\\'', '\'',
                str_replace("\n", "<br/>", $text)));
    }

    function shortcode_cotd(){
        $cardinfo = $this->database->find_card_ofTheDay();
        $expansion = $this->database->get_expansion($cardinfo->getExpansionId());
        $image_url = '/cards/' . $cardinfo->getVersion() . '/' . $expansion->code . '/en/' . $cardinfo->getCode() . '.png';
        if (!file_exists(wp_upload_dir()['basedir'] . $image_url)) {
            $image_url = substr($image_url, 0, strlen($image_url) - 4) . '.jpg';
        }
        $image_url = wp_upload_dir()['baseurl'] . $image_url;

        return '<h2 class="widget-title">Card of the Day</h2>'.sprintf('<a class="bye-card-image" href="%s"><img src="%s"/></a>',get_option('cotd-page'),$image_url);
    }
}