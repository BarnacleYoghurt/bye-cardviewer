<?php

namespace bye_plugin;

use Exception;

include_once("CardInfo.php");

class Database
{
    private const DB_VERSION = '0.0.3';
    private const TN_EXPANSIONS = 'bye_expansions';
    private const TN_CARDS = 'bye_cards';
    private const TN_CARDTEXTS = 'bye_cardtexts';

    function table_expansions()
    {
        global $wpdb;
        return $wpdb->prefix . self::TN_EXPANSIONS;
    }

    function table_cards()
    {
        global $wpdb;
        return $wpdb->prefix . self::TN_CARDS;
    }

    function table_cardtexts()
    {
        global $wpdb;
        return $wpdb->prefix . self::TN_CARDTEXTS;
    }

    function setup_tables()
    {
        global $wpdb;
        $installed_db_version = get_option('bye_cardviewer_db_version');

        if ($installed_db_version != self::DB_VERSION) {
            $sql_expansions =
                "CREATE TABLE {$this->table_expansions()} (
			        id INT NOT NULL AUTO_INCREMENT,
			        code varchar(6) NOT NULL UNIQUE,
			        name varchar(255) NOT NULL,
			        PRIMARY KEY (id)
		        )";
            $sql_cards =
                "CREATE TABLE {$this->table_cards()} (
                    id INT NOT NULL AUTO_INCREMENT,
                    code INT NOT NULL,
                    version varchar(8) NOT NULL,
                    expansion_id INT NOT NULL,
                    type INT NOT NULL,
                    attribute INT NOT NULL DEFAULT 0,
                    race INT NOT NULL DEFAULT 0,
                    level INT NOT NULL DEFAULT 0,
                    atk INT NOT NULL DEFAULT 0,
                    def INT NOT NULL DEFAULT 0,
                    PRIMARY KEY  (id)
                )";
            $sql_cardtexts =
                "CREATE TABLE {$this->table_cardtexts()} (
                    id INT NOT NULL AUTO_INCREMENT,
                    card_id INT NOT NULL,
                    lang char(2) NOT NULL DEFAULT 'en',
                    name varchar(255) NOT NULL,
                    description TEXT NOT NULL DEFAULT '',
                    PRIMARY KEY (id)
                )";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_expansions);
            dbDelta($sql_cards);
            dbDelta($sql_cardtexts);
            $wpdb->query("ALTER TABLE {$this->table_cards()} ADD CONSTRAINT `u_cards_code_version` UNIQUE (code, version)");
            $wpdb->query("ALTER TABLE {$this->table_cardtexts()} ADD CONSTRAINT `u_cardtexts_cardid_lang` UNIQUE (card_id, lang)");
            $wpdb->query(
                "ALTER TABLE {$this->table_cards()} ADD CONSTRAINT `fk_cards_expansions`
    		    FOREIGN KEY IF NOT EXISTS (expansion_id) REFERENCES {$this->table_expansions()} (id)
    			ON DELETE RESTRICT
    			ON UPDATE CASCADE");
            $wpdb->query(
                "ALTER TABLE {$this->table_cardtexts()} ADD CONSTRAINT `fk_cardtexts_cards`
                FOREIGN KEY IF NOT EXISTS (card_id) REFERENCES {$this->table_cards()} (id)
                ON DELETE RESTRICT 
                ON UPDATE CASCADE");
        }

        add_option('bye_cardviewer_db_version', self::DB_VERSION);
    }

    function find_card($code, $max_version, $lang = 'en'): CardInfo
    {
        global $wpdb;
        $canonical_version = $this->canonicalize_version_string($max_version);

        $raw_data = $wpdb->get_row($wpdb->prepare("SELECT c.*, t.*, e.id as expansion_id FROM {$this->table_cards()} c 
                                JOIN {$this->table_expansions()} e ON c.expansion_id = e.id 
                                JOIN {$this->table_cardtexts()} t ON c.id = t.card_id 
								WHERE c.code=%d
								AND STRCMP(c.version, %s)<=0
								AND t.lang=%s
								ORDER BY c.version DESC LIMIT 1", $code, $canonical_version, $lang));

        if (is_null($raw_data)) {
            throw new DBException('Card not found!');
        } else {
            return new CardInfo(
                $raw_data->code,
                $this->decanonicalize_version_string($raw_data->version),
                $raw_data->expansion_id,
                $raw_data->type,
                $raw_data->attribute,
                $raw_data->race,
                $raw_data->level,
                $raw_data->atk,
                $raw_data->def,
                $raw_data->lang,
                $raw_data->name,
                $raw_data->description
            );
        }
    }

    function create_card($data): int
    {
        global $wpdb;
        $card_data = $data;
        unset($card_data['name']);
        unset($card_data['description']);
        unset($card_data['lang']);
        $card_data['version'] = $this->canonicalize_version_string($card_data['version']);

        $wpdb->query('START TRANSACTION');
        $text_data = array('name' => $data['name'], 'description' => $data['description']);
        if (isset($data['lang'])) {
            $text_data['lang'] = $data['lang'];
        }

        if ($wpdb->insert($this->table_cards(), $card_data)) {
            $card_id = $wpdb->insert_id;
            $text_data['card_id'] = $card_id;
            if ($wpdb->insert($this->table_cardtexts(), $text_data)) {
                $wpdb->query('COMMIT');
                return $card_id;
            } else {
                $wpdb->query('ROLLBACK');
                throw new DBException('Could not save card text data - transaction rolled back!');
            }
        } else {
            $wpdb->query('ROLLBACK');
            throw new DBException('Could not save card base data - transaction rolled back!');
        }
    }

    function all_expansions()
    {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->table_expansions()}");
    }

    function get_expansion($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_expansions()} WHERE id = %d;", $id));
    }

    function find_expansion($code)
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$this->table_expansions()} WHERE code = %s;", $code));

        if (is_null($row)) {
            throw new DBException('Expansion not found!');
        } else {
            return $row;
        }
    }

    function create_expansion($code, $name): int
    {
        global $wpdb;
        if ($wpdb->insert($this->table_expansions(), array('code' => $code, 'name' => $name))) {
            return $wpdb->insert_id;
        } else {
            throw new DBException("Could not create expansion!");
        }
    }

    function update_expansion_code($id, $code)
    {
        global $wpdb;
        $wpdb->update($this->table_expansions(), array('code' => $code), array('id' => $id));
    }

    function update_expansion_name($id, $name)
    {
        global $wpdb;
        $wpdb->update($this->table_expansions(), array('name' => $name), array('id' => $id));
    }

    private function canonicalize_version_string(string $version): string
    {
        $canonical_version = '';
        $parts = explode('.', $version);
        foreach ($parts as $part) {
            $canonical_version .= is_numeric($part) ? sprintf('%02d', $part) : '00';
        }
        return $canonical_version;
    }

    private function decanonicalize_version_string(string $canonical_version): string {
        $version = '';
        for ($i = 0; $i<strlen($canonical_version)-1; $i += 2) {
            $version .= strlen($version) > 0 ? '.' : '';
            $version .= sprintf('%d', substr($canonical_version, $i, 2));
        }
        return $version;
    }
}