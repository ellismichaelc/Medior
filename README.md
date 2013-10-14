Medior
======

Repository for Medior project! Automatically sort, rename, and keep your TV collection updated.

Medior is a side project of mine, not meant for redistribution or sale.

<pre>
Recommended settings:
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
	('webui_pass', 'utpass'),
	('webui_port', '8181'),
	('webui_user', 'admin');
