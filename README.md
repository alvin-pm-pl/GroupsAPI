# GroupsAPI

Yet another asynchronous permission management plugin for PocketMine-MP.

# Features that compared to other plugins

|Feature Name|PurePerms|RankSystem|GroupsAPI|Hierarchy|
|---|---|---|---|---|
|Multiple Rank/Groups|No|Yes|Yes|No|
|Asynchronous SQL support|No|Yes|Yes|Yes|
|Flexible APIs|No|I don't know|Yes|Yes|
|Easy to use|Yes|Yes|Yes|No|
|Temporary rank/group|No|Yes|Yes|No|
|PM4 Suppport|Lack|Yes|Yes|Yes|
|Priority system like Discord|No|No|Yes|No|

# Features

* Developer-friendly API
* Temporary rank/group support
* Multiple Group/Rank support
* Priority system like Discord (which means the user who has highest priority cannot add/edit/remove group if their
  group priority is higher than the group's priority)
* Many configurations support

# Commands

| Command                                   |Description| Permission                     |
|-------------------------------------------|---|--------------------------------|
| /addgroup <player> <group>                |Add a player to a group| groupsapi.command.addgroup     |
| /removegroup <player> <group>             |Remove a player from a group| groupsapi.command.removegroup  |
| /checkgroup <player>                      |Check a player's group| groupsapi.command.checkgroup   |
| /editgroup <group>                        |Edit a group| groupsapi.command.editgroup    |
| /group <group>                            |Shows group info| groupsapi.command.group        |
| /groups                                   |Shows all groups| groupsapi.command.groups       |
| /newgroup <group>                         |Create a new group| groupsapi.command.newgroup     |
| /permissions <index>                      |Check a permission list| groupsapi.command.permissions |
| /pcheck <player>                          |Check a player's group|groupsapi.command.pcheck|
| /tempgroup <player> <group> <date format> |Give a temporary group to player|groupsapi.command.tempgroup|

# TODO
* [ ] Make all processes to UI
* [ ] Add more commands
* [ ] InfoAPI Support (Lazy to do, Please make support for this [SOFe](https://github.com/SOF3/InfoAPI)!)

If you have any idea, Please kindly open an issue on GitHub!