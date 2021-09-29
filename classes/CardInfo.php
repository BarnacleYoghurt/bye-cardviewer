<?php

namespace bye_plugin;

class CardInfo
{
    public const TYPE_MONSTER       = 0x1;
    public const TYPE_SPELL         = 0x2;
    public const TYPE_TRAP          = 0x4;
    public const TYPE_NORMAL        = 0x10;
    public const TYPE_EFFECT        = 0x20;
    public const TYPE_FUSION        = 0x40;
    public const TYPE_RITUAL        = 0x80;
    public const TYPE_SPIRIT        = 0x200;
    public const TYPE_UNION         = 0x400;
    public const TYPE_GEMINI        = 0x800;
    public const TYPE_TUNER         = 0x1000;
    public const TYPE_SYNCHRO       = 0x2000;
    public const TYPE_TOKEN         = 0x4000;
    public const TYPE_QUICKPLAY     = 0x10000;
    public const TYPE_CONTINUOUS    = 0x20000;
    public const TYPE_EQUIP         = 0x40000;
    public const TYPE_FIELD         = 0x80000;
    public const TYPE_COUNTER       = 0x100000;
    public const TYPE_FLIP          = 0x200000;
    public const TYPE_TOON          = 0x400000;
    public const TYPE_XYZ           = 0x800000;
    public const TYPE_PENDULUM      = 0x1000000;
    public const TYPE_LINK          = 0x4000000;

    public const ATTRIBUTE_EARTH  = 0x01;
    public const ATTRIBUTE_WATER  = 0x02;
    public const ATTRIBUTE_FIRE   = 0x04;
    public const ATTRIBUTE_WIND   = 0x08;
    public const ATTRIBUTE_LIGHT  = 0x10;
    public const ATTRIBUTE_DARK   = 0x20;
    public const ATTRIBUTE_DIVINE = 0x40;

    public const RACE_WARRIOR      = 0x1;
    public const RACE_SPELLCASTER  = 0x2;
    public const RACE_FAIRY        = 0x4;
    public const RACE_FIEND        = 0x8;
    public const RACE_ZOMBIE       = 0x10;
    public const RACE_MACHINE      = 0x20;
    public const RACE_AQUA         = 0x40;
    public const RACE_PYRO         = 0x80;
    public const RACE_ROCK         = 0x100;
    public const RACE_WINGEDBEAST  = 0x200;
    public const RACE_PLANT        = 0x400;
    public const RACE_INSECT       = 0x800;
    public const RACE_THUNDER      = 0x1000;
    public const RACE_DRAGON       = 0x2000;
    public const RACE_BEAST        = 0x4000;
    public const RACE_BEASTWARRIOR = 0x8000;
    public const RACE_DINOSAUR     = 0x10000;
    public const RACE_FISH         = 0x20000;
    public const RACE_SEASERPENT   = 0x40000;
    public const RACE_REPTILE      = 0x80000;
    public const RACE_PSYCHIC      = 0x100000;
    public const RACE_DIVINE       = 0x200000;
    public const RACE_CREATORGOD   = 0x400000;
    public const RACE_WYRM         = 0x800000;
    public const RACE_CYBERSE      = 0x1000000;

    private int $code;
    private string $version;
    private int $expansion_id;
    private int $type;
    private int $attribute;
    private int $race;
    private int $level;
    private int $atk;
    private int $def;
    private string $lang;
    private string $name;
    private string $description;

    public function __construct(int $code, string $version, int $expansion_id, int $type, int $attribute, int $race, int $level, int $atk, int $def, string $lang, string $name, string $description)
    {
        $this->code = $code;
        $this->version = $version;
        $this->expansion_id = $expansion_id;
        $this->type = $type;
        $this->attribute = $attribute;
        $this->race = $race;
        $this->level = $level;
        $this->atk = $atk;
        $this->def = $def;
        $this->lang = $lang;
        $this->name = $name;
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return int
     */
    public function getExpansionId(): int
    {
        return $this->expansion_id;
    }


    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    public function isMonster(): bool {
        return ($this->type & self::TYPE_MONSTER) == self::TYPE_MONSTER;
    }

    public function getTypeName(): string {
        //Defines names for individual type constant
        //Names are combined in the order they are listed here
        $terms = array(
            self::TYPE_NORMAL => "Normal",
            self::TYPE_TOKEN => "Token",
            self::TYPE_FUSION => "Fusion",
            self::TYPE_RITUAL => "Ritual",
            self::TYPE_SYNCHRO => "Synchro",
            self::TYPE_XYZ => "Xyz",
            self::TYPE_PENDULUM => "Pendulum",
            self::TYPE_LINK => "Link",
            self::TYPE_GEMINI => "Gemini",
            self::TYPE_TUNER => "Tuner",
            self::TYPE_TOON => "Toon",
            self::TYPE_SPIRIT => "Spirit",
            self::TYPE_UNION => "Union",
            self::TYPE_FLIP => "Flip",
            self::TYPE_EFFECT => "Effect",
            self::TYPE_MONSTER => "Monster",
            self::TYPE_CONTINUOUS => "Continuous",
            self::TYPE_QUICKPLAY => "Quick-Play",
            self::TYPE_EQUIP => "Equip",
            self::TYPE_FIELD => "Field",
            self::TYPE_COUNTER => "Counter",
            self::TYPE_SPELL => "Spell",
            self::TYPE_TRAP => "Trap"
        );

        $typename = '';
        foreach ($terms as $k=>$v) {
            if (($this->type & $k) == $k) {
                if ($typename !== '') {
                    $typename .= ' ';
                }
                $typename .= $v;
            }
        }

        return $typename;
    }

    /**
     * @return int
     */
    public function getAttribute(): int
    {
        return $this->attribute;
    }

    /**
     * @return int
     */
    public function getRace(): int
    {
        return $this->race;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @return int
     */
    public function getAtk(): int
    {
        return $this->atk;
    }

    /**
     * @return int
     */
    public function getDef(): int
    {
        return $this->def;
    }

    /**
     * @return string
     */
    public function getLang(): string
    {
        return $this->lang;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

}