
--
-- tfb4rt_proxystats
--
CREATE TABLE tfb4rt_proxystats (
  ua VARCHAR(255) NOT NULL default 'unknown',
  ct INT(10) UNSIGNED NOT NULL default '0',
  ts TIMESTAMP(14)
) TYPE=MyISAM;
