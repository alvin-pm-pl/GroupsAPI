-- #!sqlite
-- #{ groupsapi
-- # { init
CREATE TABLE IF NOT EXISTS users (name VARCHAR(20) NOT NULL PRIMARY KEY, `groups` TEXT NOT NULL, `loaded` int NOT NULL DEFAULT 0)
-- # }

-- # { default_group_table
CREATE TABLE IF NOT EXISTS `groups` (name VARCHAR(15) NOT NULL PRIMARY KEY, permissions TEXT NOT NULL NOT NULL, priority INT NOT NULL)
    -- # }

-- # { create_group
-- #   :name string
-- #   :permission string
-- #   :priority int
INSERT INTO `groups` (name, permissions, priority) VALUES (:name, :permission, :priority)
-- # }

-- # { create_user
-- #   :name string
-- #   :group_list string
INSERT INTO users (name, `groups`, `loaded`) VALUES (:name, :group_list, 0)
-- # }

-- # { get_groups
SELECT * FROM `groups`
-- # }

-- # { get_group
-- #   :name string
SELECT * FROM `groups` WHERE name = :name
-- # }

-- # { get_user
-- #   :name string
SELECT * FROM users WHERE name = :name
-- # }

-- # { update_user
-- #   :name string
-- #   :group_list string
UPDATE users SET `groups` = :group_list WHERE name = :name
-- # }

-- # { delete_group
-- #   :name string
DELETE FROM `groups` WHERE name = :name
-- # }

-- # { update_group
-- #   :name string
-- #   :permission string
-- #   :priority int
UPDATE `groups` SET permissions = :permission, priority = :priority WHERE name = :name
-- # }

-- # { migrate_user_table_add_loaded_column
ALTER TABLE users ADD COLUMN loaded int NOT NULL DEFAULT 0
-- # }

-- # { get_users_only_one
SELECT * FROM users LIMIT 1
-- # }

-- # { update_state
-- #   :name string
-- #   :loaded int
UPDATE users SET loaded = :loaded WHERE name = :name
-- # }
-- #}