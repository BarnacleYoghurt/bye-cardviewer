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

    function setup_menu() {
        add_menu_page('BYE Cards', 'BYE Cards', 'manage_options', 'bye-cards', array($this,'admin_page'));
    }

    function admin_page() {
        if (current_user_can('manage_options')) {
            $uploaddir = get_temp_dir();

            if (isset($_POST['ids'])) { //Import selected cards
                $filename = $_POST['version'] . '_' . $_POST['expansion'] . '.cdb';
                ?>

                <h1>BYE Card Upload Phase 3/3</h1>
                <p>The following actions were performed:</p>
                <ul>

                <?php
                $cdb = new SQLite3($uploaddir . $filename);
                foreach ($_POST['ids'] as $id) {
                    $q = $cdb->prepare('SELECT d.*, t.name, t.desc FROM datas d JOIN texts t ON d.id == t.id WHERE d.id=:id');
                    $q->bindValue(':id', $id, SQLITE3_INTEGER);
                    $card = $q->execute()->fetchArray(SQLITE3_ASSOC);

                    $expansion_id = $this->database->find_or_create_expansion($_POST['expansion']);

                    $card_insert_res = $this->database->create_card(array(
                        'code' => $id,
                        'version' => $_POST['version'],
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

                    if ($card_insert_res) {
                        echo("<li>Card {$id} ({$card['name']}) inserted into database.</li>");
                    } else{
                        echo("<li>Could not insert card {$id} ({$card['name']}).</li>");
                    }
                }
                ?>

                </ul>

                <?php
                unlink($uploaddir . $filename);
            }
            elseif (isset($_FILES['cdb'])) { //Select cards to import
                $filename = $_POST['version'] . '_' . $_POST['expansion'] . '.cdb';

                if (move_uploaded_file($_FILES['cdb']['tmp_name'], $uploaddir . $filename)) {
                    try {
                        $cdb = new SQLite3($uploaddir . $filename);
                        $cards = $cdb->query('SELECT id, name FROM texts;');
                        ?>

                        <h1>BYE Card Upload Phase 2/3</h1>
                        <p>Please select the cards you want to upload from <?= $_POST['expansion'] ?> version <?= $_POST['version'] ?></p>
                        <form method="POST">
                            <input type="hidden" name="version" value="<?= $_POST['version'] ?>"/>
                            <input type="hidden" name="expansion" value="<?= $_POST['expansion'] ?>"/>
                        <?php
                        while($card = $cards->fetchArray(SQLITE3_ASSOC)) {
                            ?>
                            <div><input name="ids[]" value="<?= $card['id'] ?>" type="checkbox"/><span><?= $card['name'] ?></span></div>
                            <?php
                        }
                        submit_button('Import');
                        ?>
                        </form>
                        <?php
                    }
                    catch (Exception $e) {
                        echo("Access to card database failed ({$e->getMessage()})");
                    }
                }
                else {
                    echo("Could not accept uploaded file.");
                }
            }
            else { //Input CDB + metadata
                ?>
                <div class="wrap">
                    <h1>BYE Card Upload Phase 1/3</h1>
                    <form enctype="multipart/form-data" method="POST">
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="t_version" style="display:inline-block;width:16ch">Version</label></th>
                                <td><input id="t_version" name="version" type="text" required/></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="t_expansion" style="display:inline-block;width:16ch">Expansion Code</label></th>
                                <td><input id="t_expansion" name="expansion" type="text" required/></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="u_cdb" style="display:inline-block;width:16ch">CDB File</label></th>
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
}