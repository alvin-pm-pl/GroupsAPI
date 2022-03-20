<?php

/**
 * ____                              _    ____ ___
 * / ___|_ __ ___  _   _ _ __  ___   / \  |  _ \_ _|
 * | |  _| '__/ _ \| | | | '_ \/ __| / _ \ | |_) | |
 * | |_| | | | (_) | |_| | |_) \__ \/ ___ \|  __/| |
 * \____|_|  \___/ \__,_| .__/|___/_/   \_\_|  |___|
 * |_|
 *
 * MIT License
 *
 * Copyright (c) 2022 alvin0319
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @noinspection PhpUnusedParameterInspection
 */

declare(strict_types=1);

namespace alvin0319\GroupsAPI;

use alvin0319\GroupsAPI\user\Member;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\promise\Promise;
use function str_replace;

final class EventListener implements Listener{

	protected GroupsAPI $plugin;

	public function __construct(){ $this->plugin = GroupsAPI::getInstance(); }

	/**
	 * @param PlayerJoinEvent $event
	 *
	 * @priority LOWEST
	 */
	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();

		$member = $this->plugin->getMemberManager()->loadMember($player->getName(), true);
		if($member instanceof Member){
			return;
		}
		if(!$member instanceof Promise){
			return;
		}
		$member->onCompletion(function(Member $member) use ($player) : void{
//			$player->sendMessage("Your data was successfully loaded.");
//			$player->sendMessage("Your groups: " . implode(", ", array_map(fn(GroupWrapper $groupWrapper) => $groupWrapper->getGroup()->getName(), $member->getGroups())));
			$member->buildFormat();
			$member->applyNameTag();
		}, function() use ($player) : void{
			// should never fail
			$player->kick("Failed to load group data");
		});
	}

	/**
	 * @param PlayerQuitEvent $event
	 *
	 * @priority MONITOR
	 */
	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();

		$member = $this->plugin->getMemberManager()->getMember($player->getName());
		if($member !== null){
			$this->plugin->getMemberManager()->unloadMember($member);
		}
	}

	/**
	 * @param PlayerChatEvent $event
	 *
	 * @priority NORMAL
	 */
	public function onPlayerChat(PlayerChatEvent $event) : void{
		$player = $event->getPlayer();
		$member = $this->plugin->getMemberManager()->getMember($player->getName());
		if($member === null){
			return;
		}
		$group = $member->getHighestGroup();
		if($group === null){
			return;
		}
		$event->setFormat(str_replace("{message}", $event->getMessage(), $member->getChatFormat()));
	}
}