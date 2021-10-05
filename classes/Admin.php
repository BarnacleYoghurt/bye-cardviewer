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
            if (isset($_FILES['cdb'])) {
                try {
                    $cdb = new SQLite3($_FILES['cdb']['tmp_name']);
                    $cards = $cdb->query('SELECT id, name FROM texts;');
                    ?>

                    <form method="POST">

                    <?php

                    foreach ($cards->fetchArray(SQLITE3_ASSOC) as $card) {
                        ?>
                        <div><input name="<?= $card['id'] ?>" type="checkbox"/><span><?= $card['name'] ?></span></div>
                        <?php
                    }

                    ?>
                    </form>
                    <?php
                }
                catch (Exception $e) {
                    //TODO: handle
                    echo($e->getMessage());
                }
            }
            elseif (isset($_POST['code'])) {
                $expansion_id = $this->database->find_expansion($_POST['expansion'])->id;
                if (is_null($expansion_id)) {
                   $expansion_id = $this->database->create_expansion($_POST['expansion'], $_POST['expansion']);
                }

                $card_data = $_POST;
                unset($card_data['image']);
                unset($card_data['expansion']);
                unset($card_data['submit']);
                $card_data['expansion_id'] = $expansion_id;


                $card_insert_res = $this->database->create_card($card_data);

                echo "<p> Card {$card_insert_res} inserted </p>";
            }
            else { ?>
                <form enctype="multipart/form-data" method="POST">
                    <div>
                        <label for="t_code" style="display:inline-block;width:16ch">CardID</label>
                        <input id="t_code" name="code" type="text" required/>
                        <label for="t_version" style="display:inline-block;width:16ch">Version</label>
                        <input id="t_version" name="version" type="text" required/>
                        <label for="t_expansion" style="display:inline-block;width:16ch">Expansion Code</label>
                        <input id="t_expansion" name="expansion" type="text" required/>
                    </div>
                    <div>
                        <input type="hidden" name="MAX_FILE_SIZE" value="10000000"/>
                        <label for="u_cdb" style="display:inline-block;width:16ch">CDB File</label>
                        <input id="u_cdb" name="cdb" type="file" required>
                    </div>
                    <?php submit_button() ?>
                </form>
                <?php
            }
        }
    }
}