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

namespace alvin0319\GroupsAPI\form;

use alvin0319\GroupsAPI\group\Group;
use alvin0319\GroupsAPI\GroupsAPI;
use pocketmine\form\Form;
use pocketmine\player\Player;
use function count;
use function implode;
use function is_numeric;

final class GroupEditForm implements Form{

	public function __construct(private Group $group){

	}

	public function jsonSerialize() : array{
		return [
			"type" => "custom_form",
			"title" => "Group edit: {$this->group->getName()}",
			"content" => [
				[
					"type" => "input",
					"text" => "Group priority",
					"default" => (string) $this->group->getPriority()
				],
				[
					"type" => "input",
					"text" => "Group permissions (separate by comma)",
					"default" => implode(",", $this->group->getPermissions())
				]
			]
		];
	}

	public function handleResponse(Player $player, $data) : void{
		if($data === null || count($data) !== 2){
			return;
		}
		[$priority, $permissions] = $data;
		if(!is_numeric($priority) || ($priority = (int) $priority) < 0){
			$player->sendMessage(GroupsAPI::$prefix . "Invalid priority given.");
			return;
		}
		$permissions = explode(",", $permissions);
		$this->group->setPermissions($permissions);
		$this->group->setPriority($priority);
		$player->sendMessage(GroupsAPI::$prefix . "Successfully edited group.");
		GroupsAPI::getInstance()->getGroupManager()->updateGroup($this->group);
	}
}