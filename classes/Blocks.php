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

    function enqueue_cardlink_events() {
        wp_enqueue_script('cardlink-events', plugin_dir_url(__DIR__) . 'scripts/cardlink-events.js');
    }

    function enqueue_cardviewer_select_events() {
        wp_enqueue_script('cardviewer-select-events', plugin_dir_url(__DIR__) . 'scripts/cardviewer-select-events.js');
    }

    function bye_cardviewer_card_render($block_attributes, $content)
    {
        try {
            $wrapper_attr = [];
            if (array_key_exists('fromUrlParams', $block_attributes) && $block_attributes['fromUrlParams']) {
                //Note: Params like cardId[card1] won't work here because PHP is an array-expanding little shit
                //explicit null fallback handles case where neither $_GET nor $block_attributes has the value set
                if (array_key_exists('urlParamCardId', $block_attributes)) {
                    $block_attributes['cardId'] = $_GET[$block_attributes['urlParamCardId']] ??
                        ($block_attributes['cardId'] ?? null);
                }
                if (array_key_exists('urlParamVersion', $block_attributes)) {
                    $block_attributes['version'] = $_GET[$block_attributes['urlParamVersion']] ??
                        ($block_attributes['version'] ?? null);
                }
                if (array_key_exists('urlParamLanguage', $block_attributes)) {
                    $block_attributes['language'] = $_GET[$block_attributes['urlParamLanguage']] ??
                        ($block_attributes['language'] ?? null);
                }
                if (array_key_exists('urlParamArt', $block_attributes)) {
                    $block_attributes['art'] = $_GET[$block_attributes['urlParamArt']] ??
                        ($block_attributes['art'] ?? null);
                }
                if ($_GET[$block_attributes['urlParamCardId']] ?? false) { // URL specifies card, prioritize over CotD
                    $carddata = $this->database->find_card($block_attributes['cardId'], $block_attributes['version'] ?? '99.99.99',
                        $block_attributes['language'] ?? 'en');
                } // otherwise we just proceed with the overridden auxiliary attributes
            }

            $cotd = $this->database->find_card_ofTheDay($block_attributes['language'] ?? 'en');
            if (!isset($carddata)) { // If card isn't given by URL, first try CotD and only then the static config
                if (array_key_exists('cardOfTheDay', $block_attributes) && $block_attributes['cardOfTheDay']) {
                    $carddata = $cotd;
                }
                else {
                    $carddata = $this->database->find_card($block_attributes['cardId'], $block_attributes['version'] ?? '99.99.99',
                        $block_attributes['language'] ?? 'en');
                }
            }
            $expansion = $this->database->get_expansion($carddata->getExpansionId());

            if (
                (array_key_exists('selectableCard', $block_attributes) && $block_attributes['selectableCard']) ||
                (array_key_exists('selectableVersion', $block_attributes) && $block_attributes['selectableVersion']) ||
                (array_key_exists('selectableLanguage', $block_attributes) && $block_attributes['selectableLanguage']) ||
                (array_key_exists('selectableArt', $block_attributes) && $block_attributes['selectableArt'])
            ) {
                // The controls need to know which block to update if we have multiple, so an ID is needed
                // Secret blockId param allows retaining same id when reloading a block
                $block_id = array_key_exists('blockId', $block_attributes) ? $block_attributes['blockId']
                    : uniqid(); // This is based on the microsecond and hopefully unique enough for this purpose

                // art selection is not handled here since its UI is placed on the image
                // if only art is set to selectable, this whole section basically just generates the block_id
                $el_select_expansions = '';
                $el_select_card = '';
                $el_select_version = '';
                $el_select_lang = '';

                if (array_key_exists('selectableCard', $block_attributes) && $block_attributes['selectableCard']) {
                    $opt_expansions = array_map(
                        function ($exp) use ($carddata) {
                            return sprintf('<option value="%s" %s>%s</option>',
                                $exp->code, $exp->id == $carddata->getExpansionId() ? 'selected' : '', $exp->name);
                        }, $this->database->all_expansions());
                    $cards = $this->database->all_cards_in_expansion($expansion->code); // need this because usort is in-place
                    usort($cards, function ($c1, $c2) {
                        return $c1->code - $c2->code;
                    });
                    $opt_cards = array_map(
                        function ($c) use ($carddata) {
                            return sprintf('<option value="%s" %s>%s</option>',
                                $c->code, $c->code == $carddata->getCode() ? 'selected' : '', $c->name);
                        }, $cards);

                    $el_select_expansions = sprintf(
                        '<select autocomplete="off" 
                                onchange="update_cardviewer_cardlist(event)" 
                                id="c_expansion-%s"
                                title="Expansion">
                                %s
                        </select>', $block_id, implode('', $opt_expansions));
                    $el_select_card = sprintf(
                        '<select autocomplete="off"
                                    onchange="update_cardviewer_card(event)" 
                                    id="c_card-%s"
                                    title="Card">
                                    %s
                            </select>', $block_id, implode('', $opt_cards));
                }
                if (array_key_exists('selectableVersion', $block_attributes) && $block_attributes['selectableVersion']) {
                    $opt_versions = array_map(
                        function ($c) use ($carddata) {
                            $v = $c->version;
                            return sprintf('<option value="%s" %s>%s</option>',
                                $v, $v == $carddata->getVersion() ? 'selected' : '', $v);
                        },$this->database->all_versionsOfCard($carddata->getCode(), $carddata->getLang()));
                    $el_select_version = sprintf(
                        '<select autocomplete="off"
                                    onchange="update_cardviewer_card(event)"
                                    id="c_version-%s"
                                    title="Version">
                                    %s
                          </select>', $block_id, implode('',$opt_versions));
                }
                if (array_key_exists('selectableLanguage', $block_attributes) && $block_attributes['selectableLanguage']) {
                    $opt_lang = array_map(
                        function ($c) use ($carddata) {
                            $l = $c->lang;
                            return sprintf('<option value="%s" %s>%s</option>',
                                $l, $l == $carddata->getLang() ? 'selected' : '', $l);
                        },$this->database->all_languagesOfCard($carddata->getCode(), $carddata->getVersion()));
                    $el_select_lang = sprintf(
                        '<select autocomplete="off"
                                    onchange="update_cardviewer_card(event)"
                                    id="c_lang-%s"
                                    title="Language">
                                    %s
                          </select>', $block_id, implode('',$opt_lang));
                }
                if ($el_select_expansions . $el_select_card . $el_select_version . $el_select_lang !== '') {
                    $el_select = sprintf('<div class="bye-card-select">%s%s%s%s</div>',
                        $el_select_expansions, $el_select_card, $el_select_version, $el_select_lang);
                } else {
                    $el_select = '';
                }
                $wrapper_attr += [ 'id' => sprintf('bye-cardviewer-card-%s', $block_id) ];
            }
            else {
                $el_select = '';
            }

            $currart = $carddata->getCode();
            if (in_array($block_attributes['art'] ?? -1, array_keys($carddata->getAltArts()))) {
                $currart = $block_attributes['art'];
            }
            $image_url = '/cards/' . $carddata->getVersion() . '/' . $expansion->code . '/' . $carddata->getLang() . '/' . $currart . '.png';
            if (!file_exists(wp_upload_dir()['basedir'] . $image_url)) {
                $image_url = substr($image_url, 0, strlen($image_url) - 4) . '.jpg';
            }
            $image_url = wp_upload_dir()['baseurl'] . $image_url;
            $el_alts = '';

            if (array_key_exists('selectableArt', $block_attributes) && $block_attributes['selectableArt']) {
                if (count($carddata->getAltArts()) > 0) {
                    $arts = [$carddata->getCode() => 'Standard'] + $carddata->getAltArts();
                    $el_alts = sprintf('<div id="alts-%s" class="bye-card-alts"><span>%s</span>%s</div>', $block_id,
                        $arts[$currart],
                        implode('',
                            array_map(function ($alias, $label) use ($image_url, $currart) {
                                $alt_url = substr($image_url, 0, strrpos($image_url, '/') + 1) . $alias . '.png';
                                $alt_path = substr_replace($alt_url, wp_upload_dir()['basedir'], 0, strlen(wp_upload_dir()['baseurl']));
                                if (!file_exists($alt_path)) {
                                    $alt_url = substr($alt_url, 0, strlen($alt_url) - 4) . '.jpg';
                                }
                                // data-slb-active="0" keeps the lightbox plugin from acting on the fallback link!
                                return sprintf(
                                    '<a%s data-slb-active="0" target="_blank" href="%s" title="%s" onclick="update_cardviewer_image(event)">●</a>',
                                    $currart === $alias ? ' class="bye-card-curr-alt"' : '', $alt_url, $label);
                            }, array_keys($arts), array_values($arts))
                        ),
                    );
                } else {
                    // just to tell the frontend that this should be selectable
                    $el_alts = sprintf('<div id="alts-%s" style="display:none"></div>', $block_id);
                }
            }
            // In some cases such as dynamic block rendering via API, the lightbox plugin cannot attach to the link
            // Open in new tab for those cases, still better than navigating away from the current page
            // TODO: Look into a lightbox plugin that can attach to dynamic content as well!
            $el_img = sprintf('<div class="bye-card-image"><a target="_blank" href="%s"><img src="%s"/></a>%s</div>',
                $image_url, $image_url, $el_alts);

            if ($cotd && ($cotd->getCode() == $carddata->getCode())) {
                $el_congrats = '<span class="bye-card-cotd-marker" title="You\'ve found the card of the day!">🎉</span>';
            } else {
                $el_congrats = '';
            }
            $el_cardname = sprintf('<h3 class="bye-card-cardname">%s</h3>', $carddata->getName());
            $el_cardtype = sprintf('<span class="bye-card-cardtype">%s</span>', $carddata->getTypeName());
            $el_cardstats = sprintf('<span class="bye-card-cardstats">%s</span>', $this->format_cardstats($carddata));
            $el_cardtext = sprintf('<p class="bye-card-cardtext"><span>%s</span></p>', $this->format_cardtext($carddata->getDescription()));
            $el_metadata = sprintf('<span class="bye-card-meta">%s (v%s)</span>', $expansion->name, $carddata->getVersion());

            return sprintf('<div %s data-cardid="%s">%s%s%s%s%s%s%s%s</div>', get_block_wrapper_attributes($wrapper_attr),
                $carddata->getCode(), $el_select, $el_img, $el_cardname, $el_cardtype, $el_cardstats, $el_cardtext,
                $el_metadata, $el_congrats);
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

        return '<h2 class="widget-title">Card of the Day</h2>'.sprintf('<a class="bye-card-image" href="%s"><img src="%s"/></a>',
                get_option('cardviewer-page') ,$image_url);
    }

    function shortcode_cardlink($atts, $content) {
        $cardId = $atts['id'] ?? '';
        $version = $atts['version'] ?? '';
        $language = $atts['language'] ?? '';
        $args = array_map(
            function ($name, $a) {
                return strlen($a) > 0 ? sprintf('%s=%s', $name, $a) : '';
            },
            [ 'cardId', 'version', 'language' ],
            [ $cardId, $version, $language ]
        );
        return sprintf('
            <a href="%s?%s" target="_blank" title="Click to open card viewer" 
                data-cardid="%s" data-version="%s" data-language="%s"
                onmouseenter="show_cardlink(event)" onmouseleave="hide_cardlink(event)">
                %s
            </a>',
            get_option('cardviewer-page'), implode('&', array_filter($args)),
            $cardId, $version, $language, $content);
    }
}