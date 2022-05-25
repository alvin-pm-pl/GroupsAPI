<?php

declare(strict_types=1);

namespace alvin0319\GroupsAPI\event;

use alvin0319\GroupsAPI\user\Member;
use pocketmine\event\player\PlayerEvent;

final class MemberLoadEvent extends PlayerEvent{

	public function __construct(private Member $member){
		$this->player = $this->member->getPlayer();
	}

	public function getMember() : Member{
		return $this->member;
	}
}