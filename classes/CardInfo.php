<?php

namespace bye_plugin;

class CardInfo implements \JsonSerializable
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

    public const LINK_MARKER_BOTTOM_LEFT  = 0x001;
    public const LINK_MARKER_BOTTOM       = 0x002;
    public const LINK_MARKER_BOTTOM_RIGHT = 0x004;
    public const LINK_MARKER_LEFT         = 0x008;
    public const LINK_MARKER_RIGHT        = 0x020;
    public const LINK_MARKER_TOP_LEFT     = 0x040;
    public const LINK_MARKER_TOP          = 0x080;
    public const LINK_MARKER_TOP_RIGHT    = 0x100;


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

    public function isXyz(): bool {
        return ($this->type & self::TYPE_XYZ) == self::TYPE_XYZ;
    }

    public function isPendulum(): bool {
        return ($this->type & self::TYPE_PENDULUM) == self::TYPE_PENDULUM;
    }

    public function isLink(): bool {
        return ($this->type & self::TYPE_LINK) == self::TYPE_LINK;
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

    public function getAttributeName(): string
    {
        switch ($this->attribute) {
            case self::ATTRIBUTE_EARTH:
                return 'EARTH';
            case self::ATTRIBUTE_WATER:
                return 'WATER';
            case self::ATTRIBUTE_FIRE:
                return 'FIRE';
            case self::ATTRIBUTE_WIND:
                return 'WIND';
            case self::ATTRIBUTE_LIGHT:
                return 'LIGHT';
            case self::ATTRIBUTE_DARK:
                return 'DARK';
            case self::ATTRIBUTE_DIVINE:
                return 'DIVINE';
        }
        return '?';
    }

    /**
     * @return int
     */
    public function getRace(): int
    {
        return $this->race;
    }

    public function getRaceName(): string {
        switch ($this->race) {
            case self::RACE_WARRIOR:
                return 'Warrior';
            case self::RACE_SPELLCASTER:
                return 'Spellcaster';
            case self::RACE_FAIRY:
                return 'Fairy';
            case self::RACE_FIEND:
                return 'Fiend';
            case self::RACE_ZOMBIE:
                return 'Zombie';
            case self::RACE_MACHINE:
                return 'Machine';
            case self::RACE_AQUA:
                return 'Aqua';
            case self::RACE_PYRO:
                return 'Pyro';
            case self::RACE_ROCK:
                return 'Rock';
            case self::RACE_WINGEDBEAST:
                return 'Winged Beast';
            case self::RACE_PLANT:
                return 'Plant';
            case self::RACE_INSECT:
                return 'Insect';
            case self::RACE_THUNDER:
                return 'Thunder';
            case self::RACE_DRAGON:
                return 'Dragon';
            case self::RACE_BEAST:
                return 'Beast';
            case self::RACE_BEASTWARRIOR:
                return 'Beast-Warrior';
            case self::RACE_DINOSAUR:
                return 'Dinosaur';
            case self::RACE_FISH:
                return 'Fish';
            case self::RACE_SEASERPENT:
                return 'Sea Serpent';
            case self::RACE_REPTILE:
                return 'Reptile';
            case self::RACE_PSYCHIC:
                return 'Psychic';
            case self::RACE_DIVINE:
                return 'Divine-Beast';
            case self::RACE_CREATORGOD:
                return 'Creator God';
            case self::RACE_WYRM:
                return 'Wyrm';
            case self::RACE_CYBERSE:
                return 'Cyberse';
        }
        return '`?';
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        if ($this->isPendulum()) {
            return $this->level % 0x10000;
        }
        else {
            return $this->level;
        }
    }

    public function getLScale(): int
    {
        if ($this->isPendulum()) {
            return $this->level / 0x1000000;
        }
        else {
            return -1;
        }
    }

    public function getRScale(): int
    {
        if ($this->isPendulum()) {
            return ($this->level % 0x1000000) / 0x10000;
        }
        else {
            return -1;
        }
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

    public function isLinkArrow($direction): bool {
        return $this->isLink() && (($this->def & $direction) == $direction);
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

    public function jsonSerialize()
    {
        return array(
            'code' => $this->getCode(),
            'version' => $this->getVersion(),
            'expansion_id' => $this->getExpansionId(),
            'type' => $this->getType(),
            'attribute' => $this->getAttribute(),
            'race' => $this->getRace(),
            'level' => $this->getLevel(),
            'atk' => $this->getAtk(),
            'def' => $this->getDef(),
            'lang' => $this->getLang(),
            'name' => $this->getName(),
            'description' => $this->getDescription());
    }
}