import {registerBlockType} from "@wordpress/blocks";

import * as block_card from "./blocks/bye-cardviewer-card";
//import * as block_hw from "./blocks/bye-cardviewer-helloworld";
import "./styles.scss";

registerBlockType(block_card.name, block_card.settings);
//registerBlockType(block_hw.name, block_hw.settings);