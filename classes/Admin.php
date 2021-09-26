<?php

namespace bye_plugin;

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
            if (isset($_POST['code'])) {
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
        }
        ?>


        <form method="POST">
            <div>
                <label for="code" style="display:inline-block;width:16ch">CardID</label>
                <input name="code" type="text" required/>
                <label for="version" style="display:inline-block;width:16ch">Version</label>
                <input name="version" type="text" required/>
                <label for="expansion" style="display:inline-block;width:16ch">Expansion Code</label>
                <input name="expansion" type="text" required/>
            </div>
            <div>
                <label for="name" style="display:inline-block;width:16ch">Name</label>
                <input name="name" type="text" required/>
                <label for="type"style="display:inline-block;width:16ch">Card Type</label>
                <input name="type" type="text" required/>
                <label for="image" style="display:inline-block;width:16ch">Image</label>
                <input name="image" type="file"/>
            </div>
            <div>
                <label for="race" style="display:inline-block;width:16ch">Type</label>
                <input name="race" type="text"/>
                <label for="attribute" style="display:inline-block;width:16ch">Attribute</label>
                <input name="attribute" type="text"/>
                <label for="level" style="display:inline-block;width:16ch">Level</label>
                <input name="level" type="text"/>
            </div>
            <div>
                <label for="atk" style="display:inline-block;width:16ch">ATK</label>
                <input name="atk" type="text"/>
                <label for="def" style="display:inline-block;width:16ch">DEF</label>
                <input name="def" type="text"/>
            </div>
            <div>
                <label for="description" style="display:inline-block;width:16ch">Card Text</label>
                <textarea name="description" style="width:100ch"></textarea>
            </div>
            <?php submit_button() ?>
        </form>
        <?php
    }
}