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

namespace alvin0319\GroupsAPI\util;

final class SQLQueries{

	public const CREATE_DEFAULT_USER_TABLE = "groupsapi.init";

	public const CREATE_DEFAULT_GROUPS_TABLE = "groupsapi.default_group_table";

	public const CREATE_GROUP = "groupsapi.create_group";

	public const CREATE_USER = "groupsapi.create_user";

	public const GET_ALL_GROUPS = "groupsapi.get_groups";

	public const GET_GROUP = "groupsapi.get_group";

	// TODO: public const GET_USERS = "SELECT * FROM `users`";

	public const GET_USER = "groupsapi.get_user";

	// TODO: public const UPDATE_GROUP_PERMISSIONS = "UPDATE `groups` SET `permissions` = '%1' WHERE `name` = '%0'";

	public const UPDATE_USER = "groupsapi.update_user";

	public const DELETE_GROUP = "groupsapi.delete_group";

	public const UPDATE_GROUP = "groupsapi.update_group";
}