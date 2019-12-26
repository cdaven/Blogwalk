-- 
-- Struktur för tabell `blog`
-- 

CREATE TABLE `blog` (
 `index` int(11) NOT NULL auto_increment,
 `name` varchar(255) NOT NULL default '',
 `description` text,
 `location` varchar(255) NOT NULL default '',
 `url` varchar(255) NOT NULL default '',
 `feedurl` varchar(255) default NULL,
 `tool` int(11) NOT NULL default '0',
 `feedtitle` varchar(255) NOT NULL default '',
 PRIMARY KEY  (`index`),
 UNIQUE KEY `url` (`url`),
 FULLTEXT KEY `name` (`name`,`description`)
) TYPE=MyISAM PACK_KEYS=1 AUTO_INCREMENT=1397 ;

-- --------------------------------------------------------

-- 
-- Struktur för tabell `cache_blogposts`
-- 

CREATE TABLE `cache_blogposts` (
 `blog` int(11) NOT NULL default '0',
 `post` int(11) NOT NULL default '0',
 `time` int(11) NOT NULL default '0',
 PRIMARY KEY  (`blog`,`post`)
) TYPE=MyISAM;

-- --------------------------------------------------------

-- 
-- Struktur för tabell `cache_locationblogs`
-- 

CREATE TABLE `cache_locationblogs` (
 `lan` int(11) NOT NULL default '0',
 `index` int(11) NOT NULL default '0',
 `time` int(11) NOT NULL default '0',
 PRIMARY KEY  (`lan`,`index`)
) TYPE=MyISAM;

-- --------------------------------------------------------

-- 
-- Struktur för tabell `cache_similarposts`
-- 

CREATE TABLE `cache_similarposts` (
 `original` int(11) NOT NULL default '0',
 `similar` int(11) NOT NULL default '0',
 `time` int(11) NOT NULL default '0',
 PRIMARY KEY  (`original`,`similar`)
) TYPE=MyISAM;

-- --------------------------------------------------------

-- 
-- Struktur för tabell `lan`
-- 

CREATE TABLE `lan` (
 `index` int(11) NOT NULL auto_increment,
 `name` varchar(100) NOT NULL default '',
 `num_blogs` int(11) default '0',
 PRIMARY KEY  (`index`),
 UNIQUE KEY `name` (`name`)
) TYPE=MyISAM AUTO_INCREMENT=33 ;

-- --------------------------------------------------------

-- 
-- Struktur för tabell `log`
-- 

CREATE TABLE `log` (
 `index` int(11) NOT NULL auto_increment,
 `ip` tinytext NOT NULL,
 `time` int(11) NOT NULL default '0',
 `article` varchar(50) NOT NULL default '',
 `tag` varchar(50) NOT NULL default '',
 `referrer` varchar(255) NOT NULL default '',
 `agent` varchar(255) NOT NULL default '',
 PRIMARY KEY  (`index`)
) TYPE=MyISAM AUTO_INCREMENT=397251 ;

-- --------------------------------------------------------

-- 
-- Struktur för tabell `podcast`
-- 

CREATE TABLE `podcast` (
 `post` int(11) NOT NULL default '0',
 `url` varchar(255) NOT NULL default '',
 PRIMARY KEY  (`post`,`url`)
) TYPE=MyISAM;

-- --------------------------------------------------------

-- 
-- Struktur för tabell `post`
-- 

CREATE TABLE `post` (
 `index` int(11) NOT NULL auto_increment,
 `title` tinytext NOT NULL,
 `summary` text NOT NULL,
 `time` datetime NOT NULL default '0000-00-00 00:00:00',
 `url` varchar(255) NOT NULL default '',
 `blog` int(11) NOT NULL default '0',
 `keywords` text NOT NULL,
 PRIMARY KEY  (`index`),
 UNIQUE KEY `url` (`url`),
 FULLTEXT KEY `title` (`title`,`summary`)
) TYPE=MyISAM PACK_KEYS=1 AUTO_INCREMENT=61345 ;

-- --------------------------------------------------------

-- 
-- Struktur för tabell `post_tags`
-- 

CREATE TABLE `post_tags` (
 `post` int(11) NOT NULL default '0',
 `tag` int(11) NOT NULL default '0',
 PRIMARY KEY  (`post`,`tag`)
) TYPE=MyISAM;

-- --------------------------------------------------------

-- 
-- Struktur för tabell `supertags`
-- 

CREATE TABLE `supertags` (
 `supertag` varchar(100) NOT NULL default '',
 `tag` int(11) NOT NULL default '0',
 PRIMARY KEY  (`supertag`,`tag`)
) TYPE=MyISAM;

-- --------------------------------------------------------

-- 
-- Struktur för tabell `tag`
-- 

CREATE TABLE `tag` (
 `index` int(11) NOT NULL auto_increment,
 `name` varchar(255) NOT NULL default '',
 PRIMARY KEY  (`index`),
 UNIQUE KEY `name` (`name`)
) TYPE=MyISAM AUTO_INCREMENT=2496 ;

-- --------------------------------------------------------

-- 
-- Struktur för tabell `tag_friends`
-- 

CREATE TABLE `tag_friends` (
 `tag1` int(11) NOT NULL default '0',
 `tag2` int(11) NOT NULL default '0',
 PRIMARY KEY  (`tag1`,`tag2`)
) TYPE=MyISAM;

-- --------------------------------------------------------

-- 
-- Struktur för tabell `tool`
-- 

CREATE TABLE `tool` (
 `index` int(11) NOT NULL auto_increment,
 `name` varchar(100) NOT NULL default '',
 PRIMARY KEY  (`index`,`name`),
 UNIQUE KEY `name` (`name`)
) TYPE=MyISAM AUTO_INCREMENT=95 ;
