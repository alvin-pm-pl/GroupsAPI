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

namespace alvin0319\GroupsAPI\command;

use alvin0319\GroupsAPI\GroupsAPI;
use alvin0319\GroupsAPI\user\Member;
use alvin0319\GroupsAPI\util\Util;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\PluginOwnedTrait;
use pocketmine\promise\Promise;
use function count;

final class AddGroupCommand extends Command implements PluginOwned{
	use PluginOwnedTrait;

	public function __construct(){
		parent::__construct("addgroup", "Adds player to group");
		$this->setPermission("groupsapi.command.addgroup");
		$this->setUsage("/addgroup <player> <group>");
		$this->owningPlugin = GroupsAPI::getInstance();
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testPermission($sender)){
			return false;
		}
		if(count($args) < 2){
			throw new InvalidCommandSyntaxException();
		}
		[$name, $groupName] = $args;
		$group = GroupsAPI::getInstance()->getGroupManager()->getGroup($groupName);
		if($group === null){
			$sender->sendMessage(GroupsAPI::$prefix . "Group {$groupName} does not found.");
			return false;
		}
		if($sender instanceof Player){
			$senderMember = GroupsAPI::getInstance()->getMemberManager()->loadMember($sender->getName());
			if($senderMember instanceof Promise){
				$sender->sendMessage(GroupsAPI::$prefix . "An unknown error occurred. try again later.");
				return false;
			}
			$highestGroup = $senderMember->getHighestGroup();
			if($highestGroup === null){
				return false;
			}
			if(!Util::canInteractTo($highestGroup, $group)){
				$sender->sendMessage(GroupsAPI::$prefix . "You can't add this group to other player because you are lower than {$group->getName()}");
				return false;
			}
		}
		$member = GroupsAPI::getInstance()->getMemberManager()->loadMember($name);
		if($member instanceof Member){
			if(!$member->hasGroup($group)){
				$member->addGroup($group);
				$sender->sendMessage(GroupsAPI::$prefix . "Added {$group->getName()} group to {$member->getName()}.");
			}else{
				$sender->sendMessage(GroupsAPI::$prefix . "Player {$member->getName()} is already in {$group->getName()} group.");
			}
		}elseif($member instanceof Promise){
			$member->onCompletion(function(Member $member) use ($sender, $group) : void{
				if(!$member->hasGroup($group)){
					$member->addGroup($group);
					if($sender instanceof Player){
						if($sender->isOnline()){
							$sender->sendMessage(GroupsAPI::$prefix . "Added {$group->getName()} group to {$member->getName()}.");
						}
					}else{
						$sender->sendMessage(GroupsAPI::$prefix . "Added {$group->getName()} group to {$member->getName()}");
					}
				}else{
					if($sender instanceof Player){
						if($sender->isOnline()){
							$sender->sendMessage(GroupsAPI::$prefix . "Player {$member->getName()} is already in {$group->getName()} group.");
						}
					}else{
						$sender->sendMessage(GroupsAPI::$prefix . "Player {$member->getName()} is already in {$group->getName()} group.");
					}
				}
			}, function() use ($sender, $name) : void{
				$sender->sendMessage(GroupsAPI::$prefix . "Player {$name} is not registered on database.");
			});
		}else{
			$sender->sendMessage(GroupsAPI::$prefix . "Something went wrong");
		}
		return true;
	}
}