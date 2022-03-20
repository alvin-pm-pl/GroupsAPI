# GroupsAPI Documentation

### Get the player's session
```injectablephp
/** @var \pocketmine\player\Player $player */
$member = \alvin0319\GroupsAPI\GroupsAPI::getInstance()->getMemberManager()->getMember($player);
```

### Get the player's highest group
```injectablephp
/** @var \alvin0319\GroupsAPI\Group $group */
$group = $member->getHighestGroup();
```

### Get the player's groups
```injectablephp
/** @var \alvin0319\GroupsAPI\Group[] $groups */
$groups = $member->getGroups();
```

### Add custom tag replacer
```injectablephp
$api = \alvin0319\GroupsAPI\GroupsAPI::getInstance();
$api->addTagReplacer("{test}", function(\pocketmine\player\Player $player, string $tagName) : string{
    return "Hello, {$player->getName()}!";
});
$api->addTagReplacer("{test1}", function(\pocketmine\player\Player $player, string $tagName) : string{
    return "Hello1, {$player->getName()}!";
});
```