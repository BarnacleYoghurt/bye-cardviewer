<?php

namespace bye_plugin;

use SQLite3;

class Admin
{
    private Database $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    function setup_menu()
    {
        add_menu_page('BYE Cards', 'BYE Cards', 'manage_options', 'bye-cards', array($this, 'admin_page_cards'));
        add_submenu_page('bye-cards', 'BYE Expansions', 'BYE Expansions', 'manage_options', 'bye-expansions', array($this, 'admin_page_expansions'));
        add_submenu_page('bye-cards', 'BYE Alt Arts', 'BYE Alt Arts', 'manage_options', 'bye-alts', array($this, 'admin_page_alts'));

        add_options_page('BYE Settings', 'BYE Settings', 'manage_options', 'bye-settings', array($this, 'admin_page_settings'));
        add_settings_section('pages','Special Pages',function(){},'bye-settings');
        register_setting('bye-settings', 'cardviewer-page', array(
            'type' => 'string',
            'description' => 'Card Viewer Page',
            'default' => '#'
        ));
        add_settings_field('cardviewer-page', 'Card Viewer Page', array($this, 'admin_field_cardViewerPage'), 'bye-settings', 'pages');
    }

    function admin_page_cards()
    {
        if (current_user_can('manage_options')) {
            $uploaddir = get_temp_dir();

            if (isset($_POST['ids'])) { //Import selected cards
                $filename = $_POST['version'] . '_' . $_POST['expansion'] . '_' . $_POST['lang'] . '.cdb';
                ?>

                <h1>BYE Card Upload Phase 3/3</h1>
                <p>The following actions were performed:</p>
                <ul>

                    <?php
                    $cdb = new SQLite3($uploaddir . $filename);
                    $alts = [];
                    foreach ($_POST['ids'] as $id) {
                        $q = $cdb->prepare('SELECT d.*, t.name, t.desc FROM datas d JOIN texts t ON d.id == t.id WHERE d.id=:id');
                        $q->bindValue(':id', $id, SQLITE3_INTEGER);
                        $card = $q->execute()->fetchArray(SQLITE3_ASSOC);

                        // FIXME surely this does not need to be inside the loop?
                        $expansion_id = $_POST['expansion'];
                        $expansion_code = $this->database->get_expansion($expansion_id)->code;

                        if (is_null($card['alias'])) {
                            try {
                                $this->database->create_card(array(
                                        'code' => $id,
                                        'version' => $_POST['version'],
                                        'lang' => $_POST['lang'],
                                        'expansion_id' => $expansion_id,
                                        'type' => $card['type'],
                                        'attribute' => $card['attribute'],
                                        'race' => $card['race'],
                                        'level' => $card['level'],
                                        'atk' => $card['atk'],
                                        'def' => $card['def'],
                                        'name' => $card['name'],
                                        'description' => $card['desc']
                                ));
                                echo("<li>Card {$id} ({$card['name']}) inserted into database.</li>");
                            } catch (DBException $e) {
                                echo("<li style='color:darkred'>Could not insert card {$id} ({$card['name']}).</li>");
                            }
                        } else {
                            // $id here refers to the alt art while $card['alias'] is the base entry
                            $alts[$id] = $card['alias'];
                            echo("<li>Card {$id} ({$card['name']}) not inserted - alt art of {$card['alias']}.</li>");
                        }

                        $attachment_id = attachment_url_to_postid('cards/' . $_POST['version'] . '/' . $expansion_code . '/' . $_POST['lang'] . '/' . $id . '.png');
                        if ($attachment_id === 0) {
                            $attachment_id = attachment_url_to_postid('cards/' . $_POST['version'] . '/' . $expansion_code . '/' . $_POST['lang'] . '/' . $id . '.jpg');
                        }
                        $formatted_cardtext = str_replace('\\"', '"',
                            str_replace('\\\'', '\'',
                                str_replace("\n", "<br/>", $card['desc']))); //TODO: Extract function from Blocks for reuse here

                        if ($attachment_id !== 0 &&
                            wp_update_post(array('ID' => $attachment_id, 'post_excerpt' => $card['name'], 'post_content' => $formatted_cardtext)) !== 0) {
                            echo("<li>Caption and description on existing image for card {$id} ({$card['name']}) updated.</li>");
                        } else {
                            echo("<li style='color:darkred'>No image found for card {$id} ({$card['name']}), please manually update caption and description after uploading.</li>");
                        }
                    }

                    foreach ($alts as $alias => $code) {
                        $card = false;
                        try {
                            $card = $this->database->find_card($code, $_POST['version'], $_POST['lang']);
                        } catch (DBException $e) {
                            echo("<li style='color:darkred'>Could not find base card {$code} for alias {$alias}.</li>");
                        }
                        if ($card instanceof CardInfo && $card->getCode() == $code) {
                            try {
                                $this->database->store_alt($card->getId(), $alias, $_POST['label']);
                                echo("<li>Stored alias {$alias} ({$_POST['label']}) for {$code} ({$card->getName()}).</li>");
                            } catch (DBException $e) {
                                echo("<li style='color:darkred'>Could not store alias {$alias} for {$code} ({$card->getName()}).</li>");
                            }
                        }
                    }
                    ?>

                </ul>

                <?php
                unlink($uploaddir . $filename);
            } elseif (isset($_FILES['cdb'])) { //Select cards to import
                $filename = $_POST['version'] . '_' . $_POST['expansion'] . '_' . $_POST['lang'] . '.cdb';

                if (move_uploaded_file($_FILES['cdb']['tmp_name'], $uploaddir . $filename)) {
                    try {
                        $cdb = new SQLite3($uploaddir . $filename);
                        $cards = $cdb->query('SELECT id, name FROM texts;');
                        ?>

                        <h1>BYE Card Upload Phase 2/3</h1>
                        <p>Please select the cards you want to upload from <?= $_POST['expansion'] ?>
                            version <?= $_POST['version'] ?></p>
                        <form method="POST">
                            <input type="hidden" name="version" value="<?= $_POST['version'] ?>"/>
                            <input type="hidden" name="expansion" value="<?= $_POST['expansion'] ?>"/>
                            <input type="hidden" name="lang" value="<?= $_POST['lang'] ?>"/>
                            <input type="hidden" name="label" value="<?= $_POST['label'] ?>"/>
                            <?php
                            while ($card = $cards->fetchArray(SQLITE3_ASSOC)) {
                                ?>
                                <div><input name="ids[]" value="<?= $card['id'] ?>"
                                            type="checkbox"/><span><?= $card['name'] ?></span></div>
                                <?php
                            }
                            submit_button('Import');
                            ?>
                        </form>
                        <?php
                    } catch (Exception $e) {
                        echo("<p>Access to card database failed ({$e->getMessage()})</p>");
                    }
                } else {
                    echo("<p>Could not accept uploaded file.</p>");
                }
            } else { //Input CDB + metadata
                ?>
                <div class="wrap">
                    <h1>BYE Card Upload Phase 1/3</h1>
                    <form enctype="multipart/form-data" method="POST">
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="t_version"
                                                       style="display:inline-block;width:16ch">Version</label></th>
                                <td><input id="t_version" name="version" type="text" required/></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="t_expansion" style="display:inline-block;width:16ch">Expansion</label>
                                </th>
                                <td>
                                    <select id="c_expansion" name="expansion" type="text" required>
                                        <?php
                                        $expansions = $this->database->all_expansions();
                                        foreach ($expansions as $expansion) {
                                            ?>
                                            <option value="<?= $expansion->id ?>"><?= $expansion->name ?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="t_lang"
                                                       style="display:inline-block;width:16ch">Language</label></th>
                                <td><input id="t_lang" name="lang" type="text" value="en" required/></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="t_label"
                                                       style="display:inline-block;width:16ch">Alt Art Label</label></th>
                                <td><input id="t_label" name="label" type="text"/></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="u_cdb" style="display:inline-block;width:16ch">CDB
                                        File</label></th>
                                <td><input id="u_cdb" name="cdb" type="file" required></td>
                            </tr>
                        </table>
                        <input type="hidden" name="MAX_FILE_SIZE" value="10000000"/>
                        <p><?php submit_button('Select Cards') ?></p>
                    </form>
                </div>
                <?php
            }
        }
    }

    function admin_page_expansions()
    {
        foreach ($_POST as $k => $v) {
            if (strlen($k) >= 5 && strlen($v) > 0) {
                switch (substr($k, 0, 5)) {
                    case 'code_':
                        $this->database->update_expansion_code(substr($k, 5), $v);
                        break;
                    case 'name_':
                        $this->database->update_expansion_name(substr($k, 5), $v);
                        break;
                }
            }
        }

        if (isset($_POST['code_new']) && strlen($_POST['code_new']) > 0) {
            $code = $_POST['code_new'];
            $name = isset($_POST['name_new']) && strlen($_POST['name_new']) > 0 ? $_POST['name_new'] : $code;
            try {
                $this->database->create_expansion($code, $name);
            } catch (DBException $e) {
                echo("<p>Could not create expansion - {$e->getMessage()}</p>");
            }
        }


        $expansions = $this->database->all_expansions();
        ?>

        <div class="wrap">
            <h1>BYE Expansions</h1>
            <form method="POST">
                <table class="form-table" role="presentation">
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Name</th>
                    </tr>
                    <?php
                    foreach ($expansions as $expansion) {
                        ?>
                        <tr>
                            <td><?= $expansion->id ?></td>
                            <td><input name="code_<?= $expansion->id ?>" type="text"
                                       placeholder="<?= $expansion->code ?>"/></td>
                            <td><input name="name_<?= $expansion->id ?>" type="text"
                                       placeholder="<?= $expansion->name ?>"/></td>
                        </tr>
                        <?php
                    }
                    ?>
                    <tr>
                        <td>(new)</td>
                        <td><input name="code_new" type="text"/></td>
                        <td><input name="name_new" type="text"/></td>
                    </tr>
                </table>
                <p><?php submit_button(); ?></p>
            </form>
        </div>

        <?php
    }

    function admin_page_alts() {
        if (current_user_can('manage_options')) {
        ?>
            <div class="wrap">
                <h1>BYE Alt Arts</h1>
                <form method="POST">
                <?php
                if (isset($_POST['code'])) { // manage alts of selected card
                    $card = $this->database->find_card($_POST['code'], $_POST['version'], $_POST['lang']);
                    foreach ($_POST as $k => $v) {
                        if (strlen($k) >= 6 && strlen($v) > 0) {
                            // FIXME wouldn't this have a problem when new is specified, even @expansions?
                            if (substr($k, 0, 6) === 'label_') {
                                $alias = substr($k, 6);
                                if ($alias !== 'new') {
                                    try {
                                        $this->database->store_alt($card->getId(), $alias, $v);
                                    } catch (DBException $e) {
                                        echo("<p>Could not update alt art {$alias} - {$e->getMessage()}</p>");
                                    }
                                }
                            }
                        }
                    }

                    if (isset($_POST['alias_new']) && strlen($_POST['alias_new']) > 0 &&
                        isset($_POST['label_new']) && strlen($_POST['label_new']) > 0) {
                        $alias = $_POST['alias_new'];
                        $label = $_POST['label_new'];
                        try {
                            $this->database->store_alt($card->getId(), $alias, $label);
                        } catch (DBException $e) {
                            echo("<p>Could not store new alt art - {$e->getMessage()}</p>");
                        }
                    }

                    $card = $this->database->find_card($_POST['code'], $_POST['version'], $_POST['lang']);
                    ?>
                    <p>Managing alts of "<?= $card->getName() ?>, v<?= $card->getVersion() ?> (<?= $card->getLang() ?>)."</p>
                    <?php
                    $alts = $card->getAltArts();
                    ?>
                    <input name="code" type="hidden" value="<?= $_POST['code'] ?>"/>
                    <input name="version" type="hidden" value="<?= $_POST['version'] ?>"/>
                    <input name="lang" type="hidden" value="<?= $_POST['lang'] ?>"/>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th>Alias</th>
                            <th>Label</th>
                        </tr>
                        <?php
                        foreach ($alts as $alias => $label) {
                        ?>
                            <tr>
                                <td><?= $alias ?></td>
                                <td><input name="label_<?= $alias ?>" type="text"
                                           placeholder="<?= $label ?>"/></td>
                            </tr>
                        <?php
                        }
                        ?>
                        <tr>
                            <td>(new)</td>
                            <td><input name="alias_new" type="text"/></td>
                            <td><input name="label_new" type="text"/></td>
                        </tr>
                    </table>
                    <p><?php submit_button(); ?></p>
                <?php
                } else { // enter card information
                ?>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="t_code"
                                                   style="display:inline-block;width:16ch">Code</label></th>
                            <td><input id="t_code" name="code" type="text" required/></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="t_version"
                                                   style="display:inline-block;width:16ch">Version</label></th>
                            <td><input id="t_version" name="version" type="text" required/></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="t_lang"
                                                   style="display:inline-block;width:16ch">Language</label></th>
                            <td><input id="t_lang" name="lang" type="text" required/></td>
                        </tr>
                    </table>
                    <p><?php submit_button('Manage Arts') ?></p>
                <?php
                }
                ?>
                </form>
            </div>
        <?php
        }
    }

    function admin_page_settings()
    {
        if (current_user_can('manage_options')) {
            ?>
            <div class="wrap">
                <h1>BYE Settings</h1>
                <form action="options.php" method="post">
                    <?php
                    settings_fields('bye-settings');
                    do_settings_sections('bye-settings');
                    submit_button('Save Settings');
                    ?>
                </form>
            </div>
            <?php
        }
    }

    function admin_field_cardViewerPage($args)
    {
        $cardViewerPage = get_option('cardviewer-page');
        ?>
        <input name="cardviewer-page" value="<?php echo $cardViewerPage ?>"/>
        <?php
    }
}