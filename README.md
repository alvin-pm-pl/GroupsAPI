# GroupsAPI

Yet another asynchronous permission management plugin for PocketMine-MP.

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
| /addgroup &lt;player&gt; &lt;group&gt;                |Add a player to a group| groupsapi.command.addgroup     |
| /removegroup &lt;player&gt; &lt;group&gt;             |Remove a player from a group| groupsapi.command.removegroup  |
| /checkgroup &lt;player&gt;                      |Check a player's group| groupsapi.command.checkgroup   |
| /editgroup &lt;group&gt;                        |Edit a group| groupsapi.command.editgroup    |
| /group &lt;group&gt;                            |Shows group info| groupsapi.command.group        |
| /groups                                   |Shows all groups| groupsapi.command.groups       |
| /newgroup &lt;group&gt;                         |Create a new group| groupsapi.command.newgroup     |
| /permissions &lt;index&gt;                      |Check a permission list| groupsapi.command.permissions |
| /pcheck &lt;player&gt;                          |Check a player's group|groupsapi.command.pcheck|
| /tempgroup &lt;player&gt; &lt;group&gt; &lt;date format&gt; |Give a temporary group to player|groupsapi.command.tempgroup|

# TODO
* [ ] Make all processes to UI
* [ ] Add more commands
* [ ] InfoAPI Support (Lazy to do, Please make support for this [SOFe](https://github.com/SOF3/InfoAPI)!)

If you have any idea, Please kindly open an issue on GitHub!
