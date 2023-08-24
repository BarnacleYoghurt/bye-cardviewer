<?php


use bye_plugin\CardInfo;
use bye_plugin\Database;
use bye_plugin\DBException;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends WP_UnitTestCase
{
    private int $test_exp_id;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        (new Database())->setup_tables(); //may cause some error messages if tables already exist from previous run
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->classInstance = new Database();
        $this->setupTestData();
    }

    public function setupTestData()
    {
        global $wpdb;

        $wpdb->insert($this->classInstance->table_expansions(), array(
            'code' => 'test',
            'name' => 'Test Expansion'
        ));
        $this->test_exp_id = $wpdb->insert_id;
        $wpdb->insert($this->classInstance->table_expansions(), array(
            'code' => 'tost',
            'name' => 'Toast Expansion'
        ));
        $tost_exp_id = $wpdb->insert_id;
        $wpdb->insert($this->classInstance->table_cards(), array(
            'code' => 1,
            'expansion_id' => $this->test_exp_id,
            'version' => '000001',
            'type' => CardInfo::TYPE_MONSTER
        ));
        $wpdb->insert($this->classInstance->table_cardtexts(), array(
            'card_id' => $wpdb->insert_id,
            'name' => 'Test Monster'
        ));
        $wpdb->insert($this->classInstance->table_cards(), array(
            'code' => 1,
            'expansion_id' => $this->test_exp_id,
            'version' => '000002',
            'type' => CardInfo::TYPE_MONSTER
        ));
        $wpdb->insert($this->classInstance->table_cardtexts(), array(
            'card_id' => $wpdb->insert_id,
            'name' => 'Test Monster v2'
        ));
        $wpdb->insert($this->classInstance->table_cards(), array(
            'code' => 2,
            'expansion_id' => $tost_exp_id,
            'version' => '000001',
            'type' => CardInfo::TYPE_SPELL
        ));
        $tsp_id = $wpdb->insert_id;
        $wpdb->insert($this->classInstance->table_cardtexts(), array(
            'card_id' => $tsp_id,
            'name' => 'Test Spell'
        ));
        $wpdb->insert($this->classInstance->table_cardtexts(), array(
            'card_id' => $tsp_id,
            'lang' => 'de',
            'name' => 'Testzauber'
        ));
        $wpdb->insert($this->classInstance->table_cards(), array(
            'code' => 2,
            'expansion_id' => $tost_exp_id,
            'version' => '000000',
            'type' => CardInfo::TYPE_SPELL
        ));
        $wpdb->insert($this->classInstance->table_cardtexts(), array(
            'card_id' => $wpdb->insert_id,
            'name' => 'Test Spell v0'
        ));
    }

    public function tearDown(): void
    {
        parent::tearDown(); //Apparently also clears data from tables???

        global $wpdb;
        $wpdb->query('SET FOREIGN_KEY_CHECKS = 0;');
        $wpdb->query("TRUNCATE TABLE {$this->classInstance->table_expansions()}");
        $wpdb->query("TRUNCATE TABLE {$this->classInstance->table_cards()}");
        $wpdb->query("TRUNCATE TABLE {$this->classInstance->table_cardtexts()}");
        $wpdb->query('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function testAllExpansionsContainsCorrectNumberOfExpansions()
    {
        $expansions = $this->classInstance->all_expansions();

        $this->assertEquals(2, count($expansions));
    }

    public function testAllCardsContainsCorrectNumberOfCards(){
        $cards = $this->classInstance->all_cards();

        $this->assertEquals(2, count($cards));
    }

    public function testAllCardsContainsOnlyLatestVersion() {
        $cards = $this->classInstance->all_cards();
        //comes out to exactly ([1] => count($cards)) if code column is unique
        $code_counts = array_count_values(array_count_values(array_column($cards, 'code')));

        $this->assertEquals(1, count($code_counts));
        $this->assertEquals(count($cards), $code_counts[1]);
        $this->assertContains('0.0.2', array_column($cards, 'version'));
    }

    public function testAllCardsWithMaxVersionContainsOnlyCardsUpToVersion(){
        $cards = $this->classInstance->all_cards('0.0.1');
        $this->assertNotContains('0.0.2', array_column($cards, 'version'));
    }

    public function testAllCardsWithLanguageContainsOnlyCardsInLanguage(){
        $cards = $this->classInstance->all_cards('99.99.99', 'de');

        $this->assertContains('Testzauber', array_column($cards, 'name'));
        $this->assertNotContains('Test Spell', array_column($cards, 'name'));
    }

    public function testAllCardsInExpansionContainsCorrectNumberOfCards(){
        $cards = $this->classInstance->all_cards_in_expansion('test');

        $this->assertEquals(1, count($cards));
    }

    public function testAllCardsInExpansionIsEmptyForNonExistingExpansion(){
        $cards = $this->classInstance->all_cards_in_expansion('vwxy');

        $this->assertEquals(0, count($cards));
    }

    public function testAllVersionsOfCardContainsCorrectVersions() {
        $versions = $this->classInstance->all_versionsOfCard(2);

        $this->assertEquals(2, count($versions));
        $this->assertArrayHasKey('0.0.0', array_column($versions, null, 'version'));
        $this->assertArrayHasKey('0.0.1', array_column($versions, null, 'version'));
    }

    public function testAllVersionsOfCardContainsOnlyVersionsWithMatchingLanguage() {
        $versions = $this->classInstance->all_versionsOfCard(2, 'de');

        $this->assertEquals(1, count($versions));
        $this->assertArrayHasKey('0.0.1', array_column($versions, null, 'version'));
    }

    public function testAllVersionsOfCardEmptyForInvalidCard()
    {
        $versions = $this->classInstance->all_versionsOfCard(99);

        $this->assertEquals(0, count($versions));
    }

    public function testAllVersionsOfCardEmptyForInvalidLanguage()
    {
        $versions = $this->classInstance->all_versionsOfCard(2, 'es');

        $this->assertEquals(0, count($versions));
    }

    public function testAllLanguagesOfCardContainsCorrectLanguages() {
        $langs = $this->classInstance->all_languagesOfCard(2, '0.0.1');

        $this->assertEquals(2, count($langs));
        $this->assertArrayHasKey('de', array_column($langs, null, 'lang'));
        $this->assertArrayHasKey('en', array_column($langs, null, 'lang'));
    }

    public function testAllLanguagesOfCardContainsOnlyLanguagesWithMatchingVersion() {
        $langs = $this->classInstance->all_languagesOfCard(2, '0.0.0');

        $this->assertEquals(1, count($langs));
        $this->assertArrayHasKey('en', array_column($langs, null, 'lang'));
    }

    public function testAllLanguagesOfCardEmptyForInvalidCard()
    {
        $langs = $this->classInstance->all_languagesOfCard(99, '0.0.0');

        $this->assertEquals(0, count($langs));
    }

    public function testAllLanguagesOfCardEmptyForInvalidVersion()
    {
        $langs = $this->classInstance->all_languagesOfCard(2, '7.7.7');

        $this->assertEquals(0, count($langs));
    }

    public function testFindCardWithCodeAndExactVersionReturnsCorrectCard()
    {
        $card = $this->classInstance->find_card(1, '0.0.1');

        $this->assertEquals('Test Monster', $card->getName());
    }

    public function testFindCardWithCodeAndHigherVersionReturnsCorrectCard()
    {
        $card = $this->classInstance->find_card(1, '1.0.0');

        $this->assertEquals('Test Monster v2', $card->getName());
    }

    public function testFindCardWithCodeExactVersionAndLanguageReturnsCorrectCard()
    {
        $card = $this->classInstance->find_card(2, '0.0.1', 'de');

        $this->assertEquals('Testzauber', $card->getName());
    }

    public function testFindCardThrowsExceptionOnFailure()
    {
        $this->expectException(DBException::class);
        $this->classInstance->find_card(1, '0.0.0');
    }


    public function testCreateExpansionReturnsIdOnSuccess()
    {
        $expansion_id = $this->classInstance->create_expansion('tast', 'Tasty Expansion');

        global $wpdb;
        $this->assertGreaterThan(0, $wpdb->query("SELECT * FROM {$this->classInstance->table_expansions()} WHERE id={$expansion_id}"));
    }

    public function testCreateExpansionThrowsExceptionForDuplicateCode()
    {
        $this->expectException(DBException::class);
        $this->classInstance->create_expansion('test', 'Test Expansion (again)');
    }


    public function testUpdateExpansionNameSetsNewName()
    {
        $this->classInstance->update_expansion_name($this->test_exp_id, 'Changed!');

        global $wpdb;
        $this->assertEquals('Changed!', $wpdb->get_var("SELECT name FROM {$this->classInstance->table_expansions()} WHERE id={$this->test_exp_id}"));
    }

    public function testUpdateExpansionCodeSetsNewCode()
    {
        $this->classInstance->update_expansion_name($this->test_exp_id, 'tist');

        global $wpdb;
        $this->assertEquals('tist', $wpdb->get_var("SELECT name FROM {$this->classInstance->table_expansions()} WHERE id={$this->test_exp_id}"));
    }

    public function testCreateCardWithValidDataCreatesCardAndCardtextEntriesAndReturnsCardId()
    {
        $card_id = $this->classInstance->create_card(array(
            'code' => 3,
            'expansion_id' => $this->test_exp_id,
            'version' => '0.0.2',
            'type' => CardInfo::TYPE_TRAP,
            'name' => 'Test Trap',
            'description' => ''
        ));

        global $wpdb;
        $card = $wpdb->get_row("SELECT * FROM {$this->classInstance->table_cards()} WHERE id = {$card_id}");
        $cardtext = $wpdb->get_row("SELECT * FROM {$this->classInstance->table_cardtexts()} WHERE card_id = {$card_id}");

        $this->assertEquals(3, $card->code);
        $this->assertEquals('Test Trap', $cardtext->name);
    }

    public function testCreateCardWithInvalidDataThrowsExceptionAndInsertsNothing()
    {
        $this->expectException(DBException::class);
        $card_id = $this->classInstance->create_card(array(
            'code' => 1,
            'expansion_id' => $this->test_exp_id,
            'version' => '0.0.1',
            'type' => CardInfo::TYPE_TRAP,
            'name' => 'Test Trap',
            'description' => ''
        ));

        global $wpdb;
        $card = $wpdb->get_row("SELECT * FROM {$this->classInstance->table_cards()} WHERE id = {$card_id}");
        $cardtext = $wpdb->get_row("SELECT * FROM {$this->classInstance->table_cardtexts()} WHERE card_id = {$card_id}");

        $this->assertNull($card);
        $this->assertNull($cardtext);
    }

    public function testGetExpansionReturnsCorrectRow()
    {
        $expansion = $this->classInstance->get_expansion($this->test_exp_id);

        $this->assertEquals('test', $expansion->code);
    }

    public function testGetExpansionThrowsExceptionOnFailure()
    {
        $this->expectException(DBException::class);
        $this->classInstance->get_expansion(0);
    }

    public function testFindExpansionReturnsCorrectRow()
    {
        $expansion = $this->classInstance->find_expansion('test');

        $this->assertEquals($this->test_exp_id, $expansion->id);
    }

    public function testFindExpansionThrowsExceptionOnFailure()
    {
        $this->expectException(DBException::class);
        $this->classInstance->find_expansion('vwxy');
    }
}
