<?php

declare(strict_types=1);

namespace alvin0319\GroupsAPI\util;

use alvin0319\GroupsAPI\user\Member;
use Ifera\ScoreHud\event\PlayerTagUpdateEvent;
use Ifera\ScoreHud\scoreboard\ScoreTag;
use pocketmine\player\Player;
use pocketmine\Server;

final class ScoreHudUtil{

	public static bool $scoreHudDetected = false;

	public static function init() : void{
		self::$scoreHudDetected = Server::getInstance()->getPluginManager()->getPlugin("ScoreHud") !== null;
	}

	public static function update(Player $player, Member $member) : void{
		if(self::$scoreHudDetected){
			(new PlayerTagUpdateEvent($player, new ScoreTag("{groupsapi.group}", $member->getHighestGroup()?->getName() ?? "Unknown")))->call();
		}
	}
}