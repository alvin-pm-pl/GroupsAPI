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

use alvin0319\GroupsAPI\event\MemberLoadEvent;
use alvin0319\GroupsAPI\event\PlayerGroupsUpdatedEvent;
use alvin0319\GroupsAPI\group\Group;
use alvin0319\GroupsAPI\group\GroupWrapper;
use alvin0319\GroupsAPI\GroupsAPI;
use alvin0319\GroupsAPI\util\ScoreHudUtil;
use DateTime;
use Generator;
use pocketmine\permission\PermissionAttachment;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Utils;
use SOFe\AwaitGenerator\Await;
use function array_values;
use function count;
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

	private string $nameTagFormat = "";

	private string $chatFormat = "";

	private bool $loaded = false;

	private int $dbRequestTime = 0;

	/** @var \Closure[] */
	private array $pendingClosures = [];

	public function __construct(string $name, array $groups){
		$this->plugin = GroupsAPI::getInstance();
		$this->name = $name;
		if(count($groups) > 0){
			$this->buildGroupsFromArray($groups);
		}
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
			$res[$groupWrapper->getGroup()->getName()] = $groupWrapper->getExpireAt()?->format("m-d-Y H:i:s");
		}
		return $res;
	}

	public function getPlayer() : ?Player{
		return $this->player ?? ($this->player = Server::getInstance()->getPlayerExact($this->name));
	}

	public function getNameTagFormat() : string{
		return $this->nameTagFormat;
	}

	public function getChatFormat() : string{
		return $this->chatFormat;
	}

	private function sortGroup() : void{
		usort($this->groups, static function(GroupWrapper $a, GroupWrapper $b) : int{
			if($a->getGroup()->getPriority() === $b->getGroup()->getPriority()){
				return 0;
			}
			return $a->getGroup()->getPriority() < $b->getGroup()->getPriority() ? -1 : 1;
		});
		$group = array_values($this->groups)[0] ?? null;
		$this->highestGroup = $group?->getGroup();
	}


	public function addGroup(Group $group, ?DateTime $expireAt = null, bool $update = true) : void{
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

		if($update){
			Await::g2c($this->updateGroups());
		}
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
		Await::g2c($this->updateGroups());
	}

	public function hasGroup(Group $group) : bool{
		foreach($this->groups as $groupWrapper){
			if($groupWrapper->getGroup() === $group){
				return true;
			}
		}
		return false;
	}

	public function updateGroups() : Generator{
		if(!$this->loaded){
			return;
		}
		yield from GroupsAPI::getDatabase()->updateUser($this->name, json_encode($this->getMappedGroups(), JSON_THROW_ON_ERROR));
		$this->buildFormat();
		$this->applyNameTag();
		if($this->getPlayer() !== null){
			ScoreHudUtil::update($this->player, $this);
		}
	}

	private function buildGroupsFromArray(array $groups) : void{
		foreach($groups as $groupName => $expireDate){
			if($expireDate !== null){
				$expireDate = DateTime::createFromFormat("m-d-Y H:i:s", $expireDate);
			}
			$group = GroupsAPI::getInstance()->getGroupManager()->getGroup($groupName);
			if($group !== null){
				$this->addGroup($group, $expireDate);
			}
		}
	}

	public function buildFormat() : void{
		if($this->getPlayer() !== null){
			$chatFormat = str_replace("{name}", $this->player->getName(), $this->plugin->getChatFormat($this->getHighestGroup()?->getName()));
			$this->chatFormat = $this->processTag($chatFormat);
			$nameTagFormat = str_replace(["{name}", "{group}"], [$this->player->getName(), $this->getHighestGroup()?->getName()], $this->plugin->getNameTagFormat($this->getHighestGroup()));
			$this->nameTagFormat = $this->processTag($nameTagFormat);
		}
	}

	private function processTag(string $format) : string{
		foreach($this->plugin->getTagReplacers() as $tagName => $replacer){
			$format = str_replace($tagName, $replacer($this->player, $tagName), $format);
		}
		return str_replace(["{group}", "{name}"], [$this->getHighestGroup()?->getName(), $this->player->getName()], $format);
	}

	public function onGroupRemoved(Group $group) : Generator{
		$this->removeGroup($group);
		yield from $this->updateGroups();
	}

	public function applyNameTag() : void{
		$this->player?->setNameTag(str_replace(["{name}", "{group}"], [$this->player->getName(), $this->getHighestGroup()->getName()], $this->plugin->getNameTagFormat($this->getHighestGroup())));
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
	 * Returns whether the data has been loaded from database.
	 * @return bool
	 */
	public function isLoaded() : bool{ return $this->loaded; }

	/**
	 * Mark the data as loaded.
	 *
	 * @param bool $loaded
	 *
	 * @return void
	 * @internal do not use this as an API.
	 */
	public function setLoaded(bool $loaded) : void{
		$this->loaded = $loaded;
	}

	/**
	 * @return void
	 * @internal
	 */
	public function onLoad() : void{
		$this->sortGroup();
		$this->buildFormat();
		$this->applyNameTag();
		(new MemberLoadEvent($this))->call();
		foreach($this->pendingClosures as $closure){
			$closure($this);
		}
	}

	/** @return \Closure[] */
	public function getPendingClosures() : array{
		return $this->pendingClosures;
	}

	public function addPendingClosure(\Closure $closure) : void{
		Utils::validateCallableSignature(function(Member $member) : void{ }, $closure);
		if($this->loaded){
			$closure($this);
			return;
		}
		$this->pendingClosures[] = $closure;
	}

	/**
	 * Called every 1 seconds
	 */
	public function tick() : void{
		if(!$this->loaded){
			Await::f2c(function() : Generator{
				$result = yield from GroupsAPI::getDatabase()->getUser($this->name);
				$groups = json_decode($result[0]["groups"], true);
				if(count($result) > 0){
					$loaded = (int) $result[0]["loaded"];
					if($loaded === 0){
						$this->buildGroupsFromArray($groups);
						$this->loaded = true;
						$this->onLoad();
						$this->plugin->getLogger()->debug("Resolved all data for member " . $this->name);
						yield from GroupsAPI::getDatabase()->updateState($this->name, 1);
					}else{
						++$this->dbRequestTime;
						if($this->dbRequestTime >= 3){
							$this->dbRequestTime = 0;
							$this->loaded = true;
							$this->buildGroupsFromArray($groups);
							$this->plugin->getLogger()->debug("Forced to set the state of the member to 1 due to timeout of db request");
							$this->onLoad();
						}
					}
				}else{
					$this->loaded = true;
				}
			});
			return;
		}
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