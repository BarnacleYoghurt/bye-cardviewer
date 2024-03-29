<?php

require_once(__DIR__ . '/../bye-cardviewer.php'); //Manually load plugin so it doesn't need to be in WP Dev directory

use bye_plugin\Blocks;
use bye_plugin\CardInfo;
use bye_plugin\Database;
use bye_plugin\DBException;

class BlocksTest extends WP_UnitTestCase
{
    private Database $dbStub;

    public function setUp(): void
    {
        parent::setUp();

        $this->dbStub = $this->createMock(Database::class);
        $this->classInstance = new Blocks($this->dbStub);
    }

    public function testFormatCardtextReplacesDoubleQuotes()
    {
        $input = 'I am a \\"Test\\"';
        $output_expected = 'I am a "Test"';

        $this->assertEquals($output_expected, $this->classInstance->format_cardtext($input));
    }

    public function testFormatCardtextReplacesSingleQuotes()
    {
        $input = 'I am a \\\'Test\\\'';
        $output_expected = 'I am a \'Test\'';

        $this->assertEquals($output_expected, $this->classInstance->format_cardtext($input));
    }

    public function testFormatCardtextReplacesLinebreaks()
    {
        $input = 'I am a Test' . "\n" . 'with two lines';
        $output_expected = 'I am a Test' . '<br/>' . 'with two lines';

        $this->assertEquals($output_expected, $this->classInstance->format_cardtext($input));
    }


    public function testFormatCardstatsEmptyForST()
    {
        $cardInfo = $this->createMock(CardInfo::class);
        $cardInfo->method('isMonster')->willReturn(false);

        $this->assertEmpty($this->classInstance->format_cardstats($cardInfo));
    }

    public function testFormatCardstatsContainsAttributeForMonster()
    {
        $cardInfo = $this->createMock(CardInfo::class);
        $cardInfo->method('isMonster')->willReturn(true);
        $cardInfo->method('getAttributeName')->willReturn('TEST_ATTR');

        $this->assertStringContainsString('TEST_ATTR', $this->classInstance->format_cardstats($cardInfo));
    }

    public function testFormatCardstatsContainsRaceForMonster()
    {
        $cardInfo = $this->createMock(CardInfo::class);
        $cardInfo->method('isMonster')->willReturn(true);
        $cardInfo->method('getAttributeName')->willReturn('Test_Race');

        $this->assertStringContainsString('Test_Race', $this->classInstance->format_cardstats($cardInfo));
    }

    public function testFormatCardstatsContainsATKForMonster()
    {
        $cardInfo = $this->createMock(CardInfo::class);
        $cardInfo->method('isMonster')->willReturn(true);
        $cardInfo->method('getAtk')->willReturn(1234);

        $this->assertStringContainsString('1234', $this->classInstance->format_cardstats($cardInfo));
    }

    public function testFormatCardstatsContainsLevelForMonsterWithLevel()
    {
        $cardInfo = $this->createMock(CardInfo::class);
        $cardInfo->method('isMonster')->willReturn(true);
        $cardInfo->method('isXyz')->willReturn(false);
        $cardInfo->method('isLink')->willReturn(false);
        $cardInfo->method('getLevel')->willReturn(3);

        $this->assertStringContainsString('Level 3', $this->classInstance->format_cardstats($cardInfo));
    }

    public function testFormatCardstatsContainsRankForXyzMonster()
    {
        $cardInfo = $this->createMock(CardInfo::class);
        $cardInfo->method('isMonster')->willReturn(true);
        $cardInfo->method('isXyz')->willReturn(true);
        $cardInfo->method('isLink')->willReturn(false);
        $cardInfo->method('getLevel')->willReturn(3);

        $this->assertStringContainsString('Rank 3', $this->classInstance->format_cardstats($cardInfo));
    }

    public function testFormatCardstatsContainsDEFForNonLinkMonster()
    {
        $cardInfo = $this->createMock(CardInfo::class);
        $cardInfo->method('isMonster')->willReturn(true);
        $cardInfo->method('isLink')->willReturn(false);
        $cardInfo->method('getDef')->willReturn(4321);

        $this->assertStringContainsString('4321', $this->classInstance->format_cardstats($cardInfo));
    }

    public function testFormatCardstatsContainsLinkRatingForLinkMonster()
    {
        $cardInfo = $this->createMock(CardInfo::class);
        $cardInfo->method('isMonster')->willReturn(true);
        $cardInfo->method('isXyz')->willReturn(false);
        $cardInfo->method('isLink')->willReturn(true);
        $cardInfo->method('getLevel')->willReturn(3);

        $this->assertStringContainsString('Link-3', $this->classInstance->format_cardstats($cardInfo));
    }

    public function testFormatCardstatsContainsLinkArrowsForLinkMonster()
    {
        $cardInfo = $this->createMock(CardInfo::class);
        $cardInfo->method('isMonster')->willReturn(true);
        $cardInfo->method('isXyz')->willReturn(false);
        $cardInfo->method('isLink')->willReturn(true);
        $linkArrowMap = [
            [CardInfo::LINK_MARKER_BOTTOM, true],
            [CardInfo::LINK_MARKER_TOP_RIGHT, true],
            [CardInfo::LINK_MARKER_TOP_LEFT, true],
            [CardInfo::LINK_MARKER_TOP, false],
            [CardInfo::LINK_MARKER_BOTTOM_RIGHT, false],
            [CardInfo::LINK_MARKER_BOTTOM_LEFT, false],
            [CardInfo::LINK_MARKER_LEFT, false],
            [CardInfo::LINK_MARKER_RIGHT, false]
        ];
        $cardInfo->method('isLinkArrow')->will($this->returnValueMap($linkArrowMap));

        $stats = $this->classInstance->format_cardstats($cardInfo);
        $this->assertStringContainsString('&#9660;', $stats);
        $this->assertStringContainsString('&#8599;', $stats);
        $this->assertStringContainsString('&#8598;', $stats);
    }

    public function testFormatCardstatsDoesNotContainDEFForLinkMonster()
    {
        $cardInfo = $this->createMock(CardInfo::class);
        $cardInfo->method('isMonster')->willReturn(true);
        $cardInfo->method('isXyz')->willReturn(false);
        $cardInfo->method('isLink')->willReturn(true);
        $cardInfo->method('getDef')->willReturn(4321);


        $this->assertStringNotContainsString('4321', $this->classInstance->format_cardstats($cardInfo));
    }

    public function testBYECardviewerCardRenderSuccessOutputContainsCardNameAndType()
    {
        $cardInfo = $this->createMock(CardInfo::class);
        $cardInfo->method('getName')->willReturn('THE CARD NAME');
        $cardInfo->method('getTypeName')->willReturn('THE CARD TYPE');
        $this->dbStub->method('find_card')->willReturn($cardInfo);
        $this->dbStub->method('get_expansion')->willReturn((object)array('name' => 'Test Expansion', 'code' => 'test'));

        $blockAttributes = array(
            'cardId' => 0,
            'version' => '0.0.0',
            'expansion' => 'test',
            'className' => ''
        );

        \WP_Block_Supports::$block_to_render = array(
            'blockName' => '',
            'attrs' => ''); //Pretend we are actually WP rendering the block
        $output = $this->classInstance->bye_cardviewer_card_render($blockAttributes, '');
        \WP_Block_Supports::$block_to_render = null;

        $this->assertStringContainsString('THE CARD NAME', $output);
        $this->assertStringContainsString('THE CARD TYPE', $output);
    }

    public function testBYECardviewerCardRenderSuccessPrioritizesURLParamsIfCardIdGiven()
    {
        $cardInfo0 = $this->createMock(CardInfo::class);
        $cardInfo0->method('getName')->willReturn('THE CARD NAME 0');
        $cardInfo0->method('getTypeName')->willReturn('THE CARD TYPE 0');
        $cardInfo1 = $this->createMock(CardInfo::class);
        $cardInfo1->method('getName')->willReturn('THE CARD NAME 1');
        $cardInfo1->method('getTypeName')->willReturn('THE CARD TYPE 1');
        $cardInfo2 = $this->createMock(CardInfo::class);
        $cardInfo2->method('getName')->willReturn('THE CARD NAME 2');
        $cardInfo2->method('getTypeName')->willReturn('THE CARD TYPE 2');
        $cardInfo1de = $this->createMock(CardInfo::class);
        $cardInfo1de->method('getName')->willReturn('DER KARTENNAME 1');
        $cardInfo1de->method('getTypeName')->willReturn('DER KARTENTYP 1');
        $this->dbStub->method('find_card')->will($this->returnValueMap([
            [0,'0.0.0','en',$cardInfo0],
            [1,'0.0.1','en',$cardInfo1],
            [1,'0.0.1','de',$cardInfo1de],
        ]));
        $this->dbStub->method('find_card_ofTheDay')->willReturn($cardInfo2);
        $this->dbStub->method('get_expansion')->willReturn((object)array('name' => 'Test Expansion', 'code' => 'test'));

        $blockAttributes = array(
            'cardId' => 0,
            'version' => '0.0.0',
            'expansion' => 'test',
            'className' => '',
            'fromUrlParams' => true,
            'urlParamCardId' => 'cardId',
            'urlParamVersion' => 'version',
            'urlParamLanguage' => 'language',
            'cardOfTheDay' => true
        );

        \WP_Block_Supports::$block_to_render = array(
            'blockName' => '',
            'attrs' => ''); //Pretend we are actually WP rendering the block
        $_GET['cardId'] = 1;
        $_GET['version'] = '0.0.1';
        $_GET['language'] = 'de';
        $output = $this->classInstance->bye_cardviewer_card_render($blockAttributes, '');
        \WP_Block_Supports::$block_to_render = null;

        $this->assertStringContainsString($cardInfo1de->getName(), $output);
        $this->assertStringContainsString($cardInfo1de->getTypeName(), $output);
    }

    public function testBYECardviewerCardRenderSuccessPrioritizesCOTDIfNoCardIdInURL()
    {
        $cardInfo0 = $this->createMock(CardInfo::class);
        $cardInfo0->method('getName')->willReturn('THE CARD NAME 0');
        $cardInfo0->method('getTypeName')->willReturn('THE CARD TYPE 0');
        $cardInfo1 = $this->createMock(CardInfo::class);
        $cardInfo1->method('getName')->willReturn('THE CARD NAME 1');
        $cardInfo1->method('getTypeName')->willReturn('THE CARD TYPE 1');
        $cardInfo2 = $this->createMock(CardInfo::class);
        $cardInfo2->method('getName')->willReturn('THE CARD NAME 2');
        $cardInfo2->method('getTypeName')->willReturn('THE CARD TYPE 2');
        $this->dbStub->method('find_card')->will($this->returnValueMap([
            [0,'0.0.0','en',$cardInfo0],
            [1,'0.0.1','en',$cardInfo1],
        ]));
        $this->dbStub->method('find_card_ofTheDay')->willReturn($cardInfo2);
        $this->dbStub->method('get_expansion')->willReturn((object)array('name' => 'Test Expansion', 'code' => 'test'));

        $blockAttributes = array(
            'cardId' => 0,
            'version' => '0.0.0',
            'expansion' => 'test',
            'className' => '',
            'fromUrlParams' => true,
            'urlParamCardId' => 'cardId',
            'urlParamVersion' => 'version',
            'cardOfTheDay' => true
        );

        \WP_Block_Supports::$block_to_render = array(
            'blockName' => '',
            'attrs' => ''); //Pretend we are actually WP rendering the block
        $_GET['version'] = '0.0.1';
        $output = $this->classInstance->bye_cardviewer_card_render($blockAttributes, '');
        \WP_Block_Supports::$block_to_render = null;

        $this->assertStringContainsString('THE CARD NAME 2', $output);
        $this->assertStringContainsString('THE CARD TYPE 2', $output);
    }

    public function testBYECardviewerCardRenderSuccessUseStaticConfigWithURLParamsIfNeitherCOTDNorCardIDInURL()
    {
        $cardInfo0 = $this->createMock(CardInfo::class);
        $cardInfo0->method('getName')->willReturn('THE CARD NAME 0');
        $cardInfo0->method('getTypeName')->willReturn('THE CARD TYPE 0');
        $cardInfo1 = $this->createMock(CardInfo::class);
        $cardInfo1->method('getName')->willReturn('THE CARD NAME 1');
        $cardInfo1->method('getTypeName')->willReturn('THE CARD TYPE 1');
        $cardInfo2 = $this->createMock(CardInfo::class);
        $cardInfo2->method('getName')->willReturn('THE CARD NAME 2');
        $cardInfo2->method('getTypeName')->willReturn('THE CARD TYPE 2');
        $this->dbStub->method('find_card')->will($this->returnValueMap([
            [0, '0.0.0', 'en', $cardInfo0],
            [0, '0.0.1', 'en', $cardInfo1],
            [1, '0.0.1', 'en', $cardInfo2],
        ]));
        $this->dbStub->method('get_expansion')->willReturn((object)array('name' => 'Test Expansion', 'code' => 'test'));

        $blockAttributes = array(
            'cardId' => 0,
            'version' => '0.0.0',
            'expansion' => 'test',
            'className' => '',
            'fromUrlParams' => true,
            'urlParamCardId' => 'cardId',
            'urlParamVersion' => 'version',
        );

        \WP_Block_Supports::$block_to_render = array(
            'blockName' => '',
            'attrs' => ''); //Pretend we are actually WP rendering the block
        $_GET['version'] = '0.0.1';
        $output = $this->classInstance->bye_cardviewer_card_render($blockAttributes, '');
        \WP_Block_Supports::$block_to_render = null;

        $this->assertStringContainsString('THE CARD NAME 1', $output);
        $this->assertStringContainsString('THE CARD TYPE 1', $output);
    }

    public function testBYECardviewerCardRenderErrorOutputShowsError()
    {
        $this->dbStub->method('find_card')->willThrowException(new DBException());

        $blockAttributes = array(
            'cardId' => 0,
            'version' => '0.0.0',
            'expansion' => 'test',
            'className' => ''
        );

        $output = $this->classInstance->bye_cardviewer_card_render($blockAttributes, '');

        $this->assertStringContainsString('Error', $output);
    }
}
