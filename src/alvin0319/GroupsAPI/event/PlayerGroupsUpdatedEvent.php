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

namespace alvin0319\GroupsAPI\event;

use alvin0319\GroupsAPI\group\GroupWrapper;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

final class PlayerGroupsUpdatedEvent extends PlayerEvent{

	/** @var GroupWrapper[] */
	protected array $beforeGroups = [];
	/** @var GroupWrapper[] */
	protected array $afterGroups = [];

	public function __construct(Player $player, array $beforeGroups, array $afterGroups){
		$this->player = $player;
		$this->beforeGroups = $beforeGroups;
		$this->afterGroups = $afterGroups;
	}

	/** @return GroupWrapper[] */
	public function getGroups() : array{
		return $this->getAfterGroups();
	}

	/** @return GroupWrapper[] */
	public function getBeforeGroups() : array{ return $this->beforeGroups; }

	/** @return GroupWrapper[] */
	public function getAfterGroups() : array{ return $this->afterGroups; }
}