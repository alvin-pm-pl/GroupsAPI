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

namespace alvin0319\GroupsAPI\user;

use alvin0319\GroupsAPI\event\PlayerGroupsUpdatedEvent;
use alvin0319\GroupsAPI\group\Group;
use alvin0319\GroupsAPI\group\GroupWrapper;
use alvin0319\GroupsAPI\GroupsAPI;
use alvin0319\GroupsAPI\util\ScoreHudUtil;
use alvin0319\GroupsAPI\util\SQLQueries;
use DateTime;
use JsonException;
use pocketmine\permission\PermissionAttachment;
use pocketmine\player\Player;
use pocketmine\Server;
use function array_values;
use function json_encode;
use function str_replace;
use function usort;

final class Member{

	protected string $name;
	/** @var GroupWrapper[] */
	protected array $groups = [];
	/** @var PermissionAttachment[] */
	private array $permissionAttachments = [];

	protected ?Player $player = null;

	protected ?Group $highestGroup = null;

	protected GroupsAPI $plugin;

	public function __construct(string $name, array $groups){
		$this->plugin = GroupsAPI::getInstance();
		$this->name = $name;
		foreach($groups as $groupName => $expireDate){
			if($expireDate !== null){
				$expireDate = DateTime::createFromFormat("m-d-Y H:i:s", $expireDate);
			}
			$group = GroupsAPI::getInstance()->getGroupManager()->getGroup($groupName);
			if($group !== null){
				$this->addGroup($group, $expireDate);
			}
		}
		$this->sortGroup();
	}

	public function getName() : string{ return $this->name; }

	/**
	 * Returns the groups which were sorted by priority.
	 *
	 * @return GroupWrapper[]
	 */
	public function getGroups() : array{ return $this->groups; }

	public function getMappedGroups() : array{
		$res = [];
		foreach($this->groups as $groupWrapper){
			$res[$groupWrapper->getGroup()->getName()] = $groupWrapper->getExpireAt() !== null ? $groupWrapper->getExpireAt()->format("m-d-Y H:i:s") : null;
		}
		return $res;
	}

	public function getPlayer() : ?Player{
		return $this->player ?? ($this->player = Server::getInstance()->getPlayerExact($this->name));
	}

	private function sortGroup() : void{
		usort($this->groups, static function(GroupWrapper $a, GroupWrapper $b) : int{
			if($a->getGroup()->getPriority() === $b->getGroup()->getPriority()){
				return 0;
			}
			return $a->getGroup()->getPriority() < $b->getGroup()->getPriority() ? -1 : 1;
		});
		$group = array_values($this->groups)[0] ?? null;
		if($group !== null){
			$this->highestGroup = $group->getGroup();
		}else{
			$this->highestGroup = null;
		}
	}


	public function addGroup(Group $group, ?DateTime $expireAt = null) : void{
		$oldGroups = $this->groups;
		$this->groups[] = new GroupWrapper($group, $expireAt);
		$this->sortGroup();

		$player = $this->getPlayer();

		if($player !== null){
			(new PlayerGroupsUpdatedEvent($player, $oldGroups, $this->groups))->call();
			foreach($group->getPermissions() as $permission){
				$this->permissionAttachments[] = $player->addAttachment(GroupsAPI::getInstance(), $permission, true);
			}
			$player->recalculatePermissions();
		}

		$this->updateGroups();
	}

	/**
	 * Only get called when the group was updated
	 * on mysql, don't call it directly.
	 * Use Member::addGroup() instead.
	 *
	 * @param GroupWrapper[] $groups
	 *
	 * @see Member::addGroup()
	 *
	 * @internal
	 */
	public function setGroups(array $groups) : void{
		$this->groups = $groups;
		$this->sortGroup();

		$player = $this->getPlayer();
		if($player !== null){
			(new PlayerGroupsUpdatedEvent($player, $this->groups, $groups))->call();
			$player->recalculatePermissions();
		}
	}

	/**
	 * Remove the given group from the member.
	 *
	 * @param Group $group
	 *
	 * @return void
	 */
	public function removeGroup(Group $group) : void{
		$oldGroups = $this->groups;
		foreach($this->groups as $key => $groupWrapper){
			if($groupWrapper->getGroup() === $group){
				unset($this->groups[$key]);
				$this->sortGroup();
				break;
			}
		}
		$player = $this->getPlayer();

		if($player !== null){
			(new PlayerGroupsUpdatedEvent($player, $oldGroups, $this->groups))->call();
			foreach($this->permissionAttachments as $attachment){
				$duplicate = false;
				$has = false;
				foreach($attachment->getPermissions() as $permission => $allowed){
					if($group->hasPermission($permission)){
						$has = true;
						foreach($this->groups as $otherGroupWrapper){
							$otherGroup = $otherGroupWrapper->getGroup();
							if(($group !== $otherGroup) && $otherGroup->hasPermission($permission)){
								$duplicate = true;
								break 2;
							}
						}
					}
				}
				if(!$duplicate && $has){
					$player->removeAttachment($attachment);
					break;
				}
			}
			$player->recalculatePermissions();
		}
		$this->updateGroups();
	}

	public function hasGroup(Group $group) : bool{
		foreach($this->groups as $groupWrapper){
			if($groupWrapper->getGroup() === $group){
				return true;
			}
		}
		return false;
	}

	public function updateGroups() : void{
		try{
			$this->plugin->getConnector()->executeChange(SQLQueries::UPDATE_USER, [
				"name" => $this->name,
				"group_list" => json_encode($this->getMappedGroups(), JSON_THROW_ON_ERROR)
			], function(int $affectedRows) : void{
				$this->plugin->getLogger()->debug("Successfully updated $this->name's groups");
			});
		}catch(JsonException $e){
		}
		$this->applyNameTag();
		if($this->player !== null){
			ScoreHudUtil::update($this->player, $this);
		}
	}

	public function onGroupRemoved(Group $group) : void{
		$this->removeGroup($group);
		$this->updateGroups();
	}

	public function applyNameTag() : void{
		$this->player?->setNameTag(str_replace(["{name}", "{group}"], [$player->getName(), $this->getHighestGroup()->getName()], $this->plugin->getNameTagFormat($this->getHighestGroup())));
	}

	/**
	 * Returns the highest group of the member.
	 * This should never be null, if it returns null, it must be something went wrong.
	 * You must do @return Group|null
	 * @see Member::sortGroup() to prevent any errors if this method returns null
	 *
	 */
	public function getHighestGroup() : ?Group{ return $this->highestGroup; }

	/**
	 * Called every 1 seconds
	 */
	public function tick() : void{
		foreach($this->groups as $key => $groupWrapper){
			if(($groupWrapper->getExpireAt() !== null) && $groupWrapper->hasExpired()){
				$this->plugin->getLogger()->debug("Removed {$groupWrapper->getGroup()->getName()} from {$this->player->getName()}");
				$this->removeGroup($groupWrapper->getGroup());
			}
		}
		// TODO: make this to use an another thread
//		$this->plugin->getConnector()->executeSelect(SQLQueries::GET_USER, [
//			"name" => $this->name
//		], function(array $rows) : void{
//			if(count($rows) === 0){
//				return;
//			}
//
//			$groupsData = json_decode($rows[0]["groups"], true, 512, JSON_THROW_ON_ERROR);
//			$groups = [];
//			foreach($groupsData as $groupName => $expiredAt){
//				$group = $this->plugin->getGroupManager()->getGroup($groupName);
//				if($group !== null){
//					if($expiredAt !== null){
//						$expiredAt = DateTime::createFromFormat("m-d-Y H:i:s", $expiredAt);
//					}
//					$groups[] = new GroupWrapper($group, $expiredAt);
//				}
//			}
//			usort($groups, static function(GroupWrapper $a, GroupWrapper $b) : int{
//				if($a->getGroup()->getPriority() === $b->getGroup()->getPriority()){
//					return 0;
//				}
//				return $a->getGroup()->getPriority() < $b->getGroup()->getPriority() ? -1 : 1;
//			});
//			if($groups !== $this->getGroups()){
//				$this->setGroups($groups);
//			}
//		});
	}
}