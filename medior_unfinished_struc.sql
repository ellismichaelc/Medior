-- Quick, dirty layout for DB

--
-- Database: `medior`
--

-- --------------------------------------------------------

--
-- Table structure for table `downloads`
--

CREATE TABLE IF NOT EXISTS `downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `show_id` int(11) NOT NULL,
  `episode_id` int(11) NOT NULL,
  `torrent_id` int(11) NOT NULL,
  `folder_hash` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `episodes`
--

CREATE TABLE IF NOT EXISTS `episodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `show_id` int(11) NOT NULL,
  `imdb` varchar(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `desc` text,
  `season` int(11) NOT NULL,
  `episode` int(11) NOT NULL,
  `airdate` int(11) DEFAULT NULL,
  `state` text,
  `aired` tinyint(1) NOT NULL DEFAULT '0',
  `ignore` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1235 ;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE IF NOT EXISTS `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `show_id` int(11) NOT NULL,
  `episode_id` int(11) DEFAULT NULL,
  `season` int(11) DEFAULT NULL,
  `episode` int(11) DEFAULT NULL,
  `status` enum('exists','deleted') NOT NULL,
  `type` varchar(64) NOT NULL,
  `added` int(11) NOT NULL,
  `path` varchar(512) NOT NULL,
  `resolution` varchar(20) DEFAULT NULL,
  `hash` text NOT NULL,
  `identified` tinyint(1) NOT NULL DEFAULT '0',
  `video_identified` tinyint(1) NOT NULL DEFAULT '0',
  `updated` int(11) NOT NULL,
  `format` text,
  `bit_rate` bigint(20) DEFAULT NULL,
  `duration` float DEFAULT NULL,
  `size` bigint(20) DEFAULT NULL,
  `frame_height` int(11) DEFAULT NULL,
  `frame_width` int(11) DEFAULT NULL,
  `json_info` text,
  `codec` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1846 ;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE IF NOT EXISTS `settings` (
  `name` varchar(50) NOT NULL,
  `value` text NOT NULL,
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `shows`
--

CREATE TABLE IF NOT EXISTS `shows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `imdb` varchar(11) NOT NULL,
  `last_download` int(11) NOT NULL DEFAULT '0',
  `last_update` int(11) NOT NULL DEFAULT '0',
  `active` int(11) NOT NULL DEFAULT '1',
  `start_season` int(11) NOT NULL DEFAULT '0',
  `start_episode` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=13 ;

-- --------------------------------------------------------

--
-- Table structure for table `torrents`
--

CREATE TABLE IF NOT EXISTS `torrents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `show_id` int(11) NOT NULL,
  `episode_id` int(11) NOT NULL,
  `size` bigint(20) NOT NULL,
  `name` text NOT NULL,
  `link` text NOT NULL,
  `user` text,
  `downloading` tinyint(1) NOT NULL DEFAULT '0',
  `points` float NOT NULL DEFAULT '0',
  `seed` int(11) NOT NULL DEFAULT '0',
  `leech` int(11) NOT NULL DEFAULT '0',
  `hash` text,
  `path` text,
  `ignore` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=760 ;


INSERT INTO `settings` (`name`, `value`) VALUES
('episode_format', '[%02dx%02d] %s'),
('extensions_delete', 'nfo,txt,sfv,dat,db,jpg,png,bmp,idx,sub,mds,xml'),
('extensions_videos', 'mp4,mkv,avi,wmv,wma,mpg,mpeg,m4v,divx'),
('hash_algo', 'adler32'),
('imdb_update_interval', '12'),
('path_duplicates', 'D:/Media/Duplicates'),
('path_ffmpeg', './bin/ffmpeg.exe'),
('path_ffprobe', './bin/ffprobe.exe'),
('path_movies', 'D:/Media/Movies'),
('path_shows', 'D:/Media/Shows'),
('preferred_keywords', '1080p,720p,480p'),
('required_keywords', ''),
('trusted_users', 'eztv,ettv,bozx'),
('webui_host', 'localhost'),
('webui_pass', ''),
('webui_port', ''),
('webui_user', 'admin');

