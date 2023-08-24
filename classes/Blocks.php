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
        wp_enqueue_script('cardlink-events', plugin_dir_url(__FILE__) . '../scripts/cardlink-events.js');
    }

    function enqueue_cardviewer_select_events() {
        wp_enqueue_script('cardviewer-select-events', plugin_dir_url(__FILE__) . '../scripts/cardviewer-select-events.js');
    }

    function bye_cardviewer_card_render($block_attributes, $content)
    {
        try {
            /*
             * TODO: Add an optional frontend card selection for use in the card viewer
             * > Card (Expansion + Name)/Version/Language dropdowns can be toggled separately in block config
             * >> Expansion has fixed options [DONE]
             * >> Name has cards in expansion [DONE]
             * >> Version only what exists for this card [DONE]
             * >> Language only what exists for this card [DONE]
             * > The actual card displayed is the first of the following that applies [DONE]
             * >> 1) The card selected in the dropdowns
             * >> 2) The card given in the URL parameters
             * >> 3) The card of the day (if enabled) => this allows us to use the generic card viewer as CotD!
             * >> 4) The card configured in the backend
             * > If dropdowns are present AND an initial card is present (via methods 2/3/4),
             *   the dropdown values are also initialized appropriately [DONE]
             * > If the displayed card is card of the day, regardless of the method it was selected by,
             *   display "🎉 Card of the Day!" (or similar) somewhere [DONE]
             */

            $wrapper_attr = [];
            $cotd = $this->database->find_card_ofTheDay($block_attributes['language'] ?? 'en');
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
                if ($_GET[$block_attributes['urlParamCardId']] ?? false) { // URL specifies card, prioritize over CotD
                    $carddata = $this->database->find_card($block_attributes['cardId'], $block_attributes['version'] ?? '99.99.99',
                        $block_attributes['language'] ?? 'en');
                } // otherwise we just proceed with the overridden auxiliary attributes
            }
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
            $image_url = '/cards/' . $carddata->getVersion() . '/' . $expansion->code . '/' . $carddata->getLang() . '/' . $carddata->getCode() . '.png';
            if (!file_exists(wp_upload_dir()['basedir'] . $image_url)) {
                $image_url = substr($image_url, 0, strlen($image_url) - 4) . '.jpg';
            }
            $image_url = wp_upload_dir()['baseurl'] . $image_url;

            if (
                (array_key_exists('selectableCard', $block_attributes) && $block_attributes['selectableCard']) ||
                (array_key_exists('selectableVersion', $block_attributes) && $block_attributes['selectableVersion']) ||
                (array_key_exists('selectableLanguage', $block_attributes) && $block_attributes['selectableLanguage'])
            ) {
                // The controls need to know which block to update if we have multiple, so an ID is needed
                // Secret blockId param allows retaining same id when reloading a block
                $block_id = array_key_exists('blockId', $block_attributes) ? $block_attributes['blockId']
                    : uniqid(); // This is based on the microsecond and hopefully unique enough for this purpose

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
                $el_select = sprintf('<div class="bye-card-select">%s%s%s%s</div>',
                    $el_select_expansions,$el_select_card,$el_select_version,$el_select_lang);
                $wrapper_attr += [ 'id' => sprintf('bye-cardviewer-card-%s', $block_id) ];
            }
            else {
                $el_select = '';
            }
            // In some cases such as dynamic block rendering via API, the lightbox plugin cannot attach to the link
            // Open in new tab for those cases, still better than navigating away from the current page
            // TODO: Look into a lightbox plugin that can attach to dynamic content as well!
            $el_img = sprintf('<a class="bye-card-image" target="_blank" href="%s"><img src="%s"/></a>',
                $image_url, $image_url);

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
        $cardId = $atts['id'] ?? 0;
        $version = $atts['version'] ?? '99.99.99';
        return sprintf('
            <a href="%s?cardId=%s&version=%s" target="_blank" title="Click to open card viewer" 
                data-cardid="%s" data-version="%s" 
                onmouseenter="show_cardlink(event)" onmouseleave="hide_cardlink(event)">
                %s
            </a>',
            get_option('cardviewer-page'), $cardId, $version, $cardId, $version, $content);
    }
}