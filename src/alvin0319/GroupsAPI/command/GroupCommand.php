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
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\PluginOwnedTrait;
use function array_shift;
use function count;
use function implode;
use function trim;

final class GroupCommand extends Command implements PluginOwned{
	use PluginOwnedTrait;

	public function __construct(){
		parent::__construct("group", "See the group info");
		$this->setUsage("/group <name>");
		$this->setPermission("groupsapi.command.group");
		$this->owningPlugin = GroupsAPI::getInstance();
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testPermission($sender)){
			return false;
		}
		if(count($args) < 1){
			throw new InvalidCommandSyntaxException();
		}
		$groupName = array_shift($args);
		if(trim($groupName ?? "") === ""){
			throw new InvalidCommandSyntaxException();
		}
		$group = GroupsAPI::getInstance()->getGroupManager()->getGroup($groupName);
		if($group === null){
			$sender->sendMessage(GroupsAPI::$prefix . "Group {$groupName} does not exist.");
			return false;
		}
		$sender->sendMessage(GroupsAPI::$prefix . "Showing {$group->getName()} group info.");
		$sender->sendMessage(GroupsAPI::$prefix . "Name: {$group->getName()}, priority: {$group->getPriority()}");
		$sender->sendMessage(GroupsAPI::$prefix . "Permissions: " . implode(", ", $group->getPermissions()));
		return true;
	}
}