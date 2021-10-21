<?php

use bye_plugin\ApiController;
use bye_plugin\Database;

class ApiControllerTest extends WP_UnitTestCase
{
    private Database $dbStub;

    public function setUp(): void
    {
        parent::setUp();

        $this->dbStub = $this->createMock(Database::class);
        $this->classInstance = new ApiController($this->dbStub);
    }

    public function testGetExpansionsReturnsExpansions()
    {
        $fake_exp = array('exp1', 'exp2');
        $this->dbStub->method('all_expansions')->willReturn($fake_exp);

        $response = $this->classInstance->get_expansions(array());
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals($fake_exp, $response->get_data());
    }

    public function testGetCardsWithEmptyExpansionArgReturnsAllCards()
    {
        $fake_cards = array('c1', 'c2', 'c3');
        $this->dbStub->method('all_cards')->willReturn($fake_cards);

        $response = $this->classInstance->get_cards(array('expansion_code' => ''));
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals($fake_cards, $response->get_data());
    }


    public function testGetCardsWithNonEmptyExpansionArgReturnsCardsInExpansion()
    {
        $fake_cards = array('c1', 'c2');
        $this->dbStub->method('all_cards_in_expansion')->willReturn($fake_cards);

        $response = $this->classInstance->get_cards(array('expansion_code' => 'test'));
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals($fake_cards, $response->get_data());
    }
}
