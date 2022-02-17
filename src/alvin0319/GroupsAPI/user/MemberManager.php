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

use alvin0319\GroupsAPI\GroupsAPI;
use alvin0319\GroupsAPI\util\SQLQueries;
use JsonException;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use Throwable;
use function array_values;
use function count;
use function json_encode;
use function strtolower;

final class MemberManager{

	/** @var Member[] */
	protected array $members = [];

	protected GroupsAPI $plugin;

	public function __construct(){
		$this->plugin = GroupsAPI::getInstance();
	}

	/**
	 * @param string $name
	 * @param bool   $createOnMissing
	 *
	 * @return Member|Promise returns Member if member is loaded from database, returns Promise when it needs to be loaded from database.
	 */
	public function loadMember(string $name, bool $createOnMissing = false) : Promise|Member{
		if(isset($this->members[strtolower($name)])){
			return $this->members[strtolower($name)];
		}
		$promise = new PromiseResolver();
		$this->plugin->getconnector()->executeSelect(SQLQueries::GET_USER, [
			"name" => strtolower($name)
		], function(array $rows) use ($name, $promise, $createOnMissing) : void{
			try{
				if(count($rows) > 0){
					$groups = json_decode($rows[0]["groups"], true, 512, JSON_THROW_ON_ERROR);
					$member = new Member(strtolower($rows[0]["name"]), $groups);
					$this->plugin->getMemberManager()->registerMember($member);
					$promise->resolve($member);
				}elseif($createOnMissing){
					$groups = GroupsAPI::getInstance()->getDefaultGroups();
					$defaultGroups = [];
					foreach($groups as $group){
						$defaultGroups[$group] = null;
					}
					$member = new Member(strtolower($name), $defaultGroups);
					$this->registerMember($member, true);
					$promise->resolve($member);
				}else{
					$promise->reject();
				}
			}catch(Throwable $e){
				try{
					$promise->reject();
				}catch(Throwable $ignore){
				}
				$this->plugin->getLogger()->logException($e);
			}
		});
		return $promise->getPromise();
	}

	public function registerMember(Member $member, bool $sync = false) : void{
		$this->members[strtolower($member->getName())] = $member;
		if($sync){
			try{
				$this->plugin->getConnector()->executeInsert(SQLQueries::CREATE_USER, [
					"name" => $member->getName(),
					"group_list" => json_encode($member->getMappedGroups(), JSON_THROW_ON_ERROR)
				], function(int $insertId, int $affectedRows) use ($member) : void{
					if($affectedRows > 0){
						$this->plugin->getLogger()->debug("Registered new player {$member->getName()}");
					}
				});
			}catch(JsonException $e){
			}
		}
	}

	/** @return Member[] */
	public function getMembers() : array{
		return array_values($this->members);
	}

	public function unloadMember(Member $member) : void{
		$member->updateGroups();
		unset($this->members[strtolower($member->getName())]);
	}

	public function getMember(string $name) : ?Member{
		return $this->members[strtolower($name)] ?? null;
	}

	public function schedule() : void{
		foreach($this->members as $name => $member){
			if($member->getPlayer() === null){
				$this->unloadMember($member);
				continue;
			}
			$member->tick();
		}
	}
}