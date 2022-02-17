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

use alvin0319\GroupsAPI\command\AddGroupCommand;
use alvin0319\GroupsAPI\command\CheckGroupCommand;
use alvin0319\GroupsAPI\command\DeleteGroupCommand;
use alvin0319\GroupsAPI\command\EditGroupCommand;
use alvin0319\GroupsAPI\command\GroupCommand;
use alvin0319\GroupsAPI\command\GroupsCommand;
use alvin0319\GroupsAPI\command\NewGroupCommand;
use alvin0319\GroupsAPI\command\PermissionsCommand;
use alvin0319\GroupsAPI\command\PlayerPermissionCommand;
use alvin0319\GroupsAPI\command\RemoveGroupCommand;
use alvin0319\GroupsAPI\command\TempGroupCommand;
use alvin0319\GroupsAPI\group\Group;
use alvin0319\GroupsAPI\group\GroupManager;
use alvin0319\GroupsAPI\user\MemberManager;
use alvin0319\GroupsAPI\util\SQLQueries;
use Closure;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use function array_keys;
use function array_search;
use function count;
use function json_decode;
use function json_encode;
use function usort;

final class GroupsAPI extends PluginBase{
	use SingletonTrait;

	public static function getInstance() : GroupsAPI{
		return self::$instance;
	}

	public static string $prefix = "§b§l[GroupsAPI] §r§7";

	protected GroupManager $groupManager;

	protected MemberManager $memberManager;

	protected int $queryId = 0;
	/** @var Closure[] */
	protected array $queries = [];

	protected DataConnector $connector;

	protected function onLoad() : void{
		self::setInstance($this);
	}

	protected function onEnable() : void{
		if(!class_exists(libasynql::class)){
			$this->getLogger()->error("GroupsAPI requires libasynql to run.");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		$this->saveDefaultConfig();

		$this->connector = libasynql::create($this, $this->getConfig()->get("database"), [
			"mysql" => "mysql.sql",
			"sqlite" => "sqlite.sql"
		]);

		$this->groupManager = new GroupManager();
		$this->memberManager = new MemberManager();

		$this->startTasks();
		$this->doDefaultQueries();
		$this->registerCommands();
		if($this->getConfig()->get("remove-op-and-deop", true)){
			$this->unregisterCommands();
		}

		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
	}

	private function unregisterCommands() : void{
		$opCommand = $this->getServer()->getCommandMap()->getCommand("op");
		if($opCommand !== null){
			$this->getServer()->getCommandMap()->unregister($opCommand);
		}
		$deopCommand = $this->getServer()->getCommandMap()->getCommand("deop");
		if($deopCommand !== null){
			$this->getServer()->getCommandMap()->unregister($deopCommand);
		}
	}

	private function registerCommands() : void{
		$this->getServer()->getCommandMap()->registerAll("GroupsAPI", [
			new AddGroupCommand(),
			new CheckGroupCommand(),
			new DeleteGroupCommand(),
			new EditGroupCommand(),
			new GroupCommand(),
			new GroupsCommand(),
			new NewGroupCommand(),
			new PermissionsCommand(),
			new PlayerPermissionCommand(),
			new RemoveGroupCommand(),
			new TempGroupCommand()
		]);
	}

	private function doDefaultQueries() : void{
		$this->connector->executeGeneric(SQLQueries::CREATE_DEFAULT_GROUPS_TABLE);
		$this->connector->executeGeneric(SQLQueries::CREATE_DEFAULT_USER_TABLE);
		foreach($this->getConfig()->get("default-groups", []) as $groupName => $groupData){
			$this->getLogger()->info("Querying default groups {$groupName}");
			$this->connector->executeSelect(SQLQueries::GET_GROUP, [
				"name" => $groupName
			], function(array $rows) use ($groupName, $groupData) : void{
				if(count($rows) === 0){
					$this->connector->executeInsert(SQLQueries::CREATE_GROUP, [
						"name" => $groupName,
						"permission" => json_encode($groupData["permissions"], JSON_THROW_ON_ERROR),
						"priority" => (int) $groupData["priority"]
					], function(int $insertId, int $affectedRows) use ($groupName) : void{
						if($affectedRows > 0){
							$this->getLogger()->debug("Created group $groupName");
						}
					});
				}
			});
		}
		$this->connector->waitAll();
	}

	private function startTasks() : void{
		$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
			$this->getMemberManager()->schedule();
			/*
			// FIXME: This eats too much CPUs
			// TODO: make this to run on an another thread
			foreach($this->getMemberManager()->getMembers() as $name => $member){
				$this->addQuery(SQLQueries::GET_USER, [
					$member->getName()
				], static function(int $columns, array $data) use ($member) : void{
					if($columns === 0){
						return; // not created
					}
					$groupsData = json_decode($data[0]["groups"], true, 512, JSON_THROW_ON_ERROR);
					$groups = [];
					foreach($groupsData as $groupName => $expiredAt){
						$group = CosmoGroup::getInstance()->getGroupManager()->getGroup($groupName);
						if($group !== null){
							if($expiredAt !== null){
								$expiredAt = DateTime::createFromFormat("m-d-Y H:i:s", $expiredAt);
							}
							$groups[] = new GroupWrapper($group, $expiredAt);
						}
					}
					usort($groups, static function(GroupWrapper $a, GroupWrapper $b) : int{
						if($a->getGroup()->getPriority() === $b->getGroup()->getPriority()){
							return 0;
						}
						return $a->getGroup()->getPriority() < $b->getGroup()->getPriority() ? -1 : 1;
					});
					if($groups !== $member->getGroups()){
						$member->setGroups($groups);
					}
				});
			}
			*/
		}), 20);

		$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
			$this->connector->executeSelect(SQLQueries::GET_ALL_GROUPS, [], function(array $row) : void{
				foreach($row as $key => $value){
					$group = GroupsAPI::getInstance()->getGroupManager()->getGroup($value["name"]);
					if($group === null){
						GroupsAPI::getInstance()->getGroupManager()->registerGroup($value["name"], (int) $value["priority"], (array) json_decode($value["permissions"], true, 512, JSON_THROW_ON_ERROR));
					}
				}
			});

		}), 1200);
	}

	protected function onDisable() : void{
		$this->connector->close();
	}

	public function getGroupManager() : GroupManager{
		return $this->groupManager;
	}

	public function getMemberManager() : MemberManager{
		return $this->memberManager;
	}

	public function getConnector() : DataConnector{
		return $this->connector;
	}

	public function getDefaultGroups() : array{
		return $this->getConfig()->get("default-groups-player", ["Member"]);
	}

	public function getChatFormat(string $group) : string{
		$format = $this->getConfig()->getNested("chat-format.{$group}", "");
		if($format === ""){
			$format = $this->getConfig()->getNested("chat-format.default", "[{group}] {name} > {message}");
		}
		return $format;
	}

	public function getNameTagFormat(Group $group) : string{
		$format = $this->getConfig()->getNested("nametag-format.{$group->getName()}", "");
		if($format === ""){
			$format = $this->getConfig()->getNested("nametag-format.default", "[{group}] {name}");
		}
		return $format;
	}

	public function getGroupPriority(Group $group) : int{
		static $keys = [];
		if(count($keys) === 0){
			$keys = array_keys($this->getConfig()->get("default-groups", []));
		}
		if($this->getConfig()->getNested("default-groups.{$group->getName()}", -1) !== -1){
			return (int) array_search($group->getName(), $keys, true);
		}
		static $groups = [];

		if($groups !== $this->groupManager->getGroups()){
			$groups = $this->groupManager->getGroups();
		}
		usort($groups, static function(Group $a, Group $b) : int{
			if($a->getPriority() === $b->getPriority()){
				return 0;
			}
			return $a->getPriority() < $b->getPriority() ? -1 : 1;
		});
		return (int) array_search($group, $groups, true);
	}
}