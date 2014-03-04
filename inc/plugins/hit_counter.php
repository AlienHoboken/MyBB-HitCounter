<?php
/**
 * Hit Counter
 * Jeremiah Johnson
 * http://jwjdev.com/
 */

if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly");
}

$plugins->add_hook("global_start", "hit_counter_global_start");
$plugins->add_hook("index_start", "hit_counter_index_start");
$plugins->add_hook('admin_config_settings_change_commit', 'hit_counter_admin_config_settings_change_commit');

function hit_counter_info()
{
return array(
        "name"  => "Hit Counter",
        "description"=> "Implements a hit counter for tracking total page views. Supports unique visitors, spider blocking, etc.",
        "website"        => "http://jwjdev.com/",
        "author"        => "Jeremiah Johnson",
        "authorsite"    => "http://jwjdev.com/",
        "version"        => "1.2",
        "guid"             => "56256eb16b53bad10b65378ace727632",
        "compatibility" => "16*"
    );
}

function hit_counter_is_installed()
{
   global $db;
 
   $query = $db->simple_select("settinggroups", "name", "name='hit_counter'");
    
   $result = $db->fetch_array($query);

   if($result) {
      return 1;
   } else {
      return 0;
   }
}

function hit_counter_install()
{	
   global $db;
   $setting_group = array(
		'gid'			=> 'NULL',
		'name'			=> 'hit_counter',
		'title'			=> 'Hit Counter',
		'description'	=> 'Settings for Hit Counter.',
		'disporder'		=> "1",
		'isdefault'		=> 'no',
	);

   $db->insert_query('settinggroups', $setting_group);
   $gid = $db->insert_id();
	
   $myplugin_setting = array(
		'name'			=> 'hit_counter_on',
		'title'			=> 'On/Off',
		'description'	=> 'Turn Hit Counter On or Off',
		'optionscode'	=> 'yesno',
		'value'			=> '1', 
		'disporder'		=> 1,
		'gid'			=> intval($gid),
	);

   $db->insert_query('settings', $myplugin_setting);

   $myplugin_setting = array(
		'name'			=> 'hit_counter_seperator',
		'title'			=> 'Number Seperator',
		'description'	=> 'The seperator to be used in numbers over 999, eg. 1,237,294',
		'optionscode'	=> 'text',
		'value'			=> ',',
		'disporder'		=> 2,
		'gid'			=> intval($gid),
	);

   $db->insert_query('settings', $myplugin_setting);

   $myplugin_setting = array(
		'name'			=> 'hit_counter_unique_only',
		'title'			=> 'Unique Visitors',
		'description'	=> 'Only display hits from unique visitors? This will only show one hit per visitor. Uses cookies to track unique users.',
		'optionscode'	=> 'yesno',
		'value'			=> '1',
		'disporder'		=> 3,
		'gid'			=> intval($gid),
	);

   $db->insert_query('settings', $myplugin_setting);

   $myplugin_setting = array(
		'name'			=> 'hit_counter_unique_expire',
		'title'			=> 'Unique Visitor Reset',
		'description'	=> 'How many days before a user is considered unique again. Typically this is 30 days. Use 0 days for never expire.',
		'optionscode'	=> 'text',
		'value'			=> '30',
		'disporder'		=> 4,
		'gid'			=> intval($gid),
	);

   $db->insert_query('settings', $myplugin_setting);

   $myplugin_setting = array(
		'name'			=> 'hit_counter_main_only',
		'title'			=> 'Main Page Only',
		'description'	=> 'Only count it hits on main page? ie. http://yourdomain.tld/forums/',
		'optionscode'	=> 'yesno',
		'value'			=> '0',
		'disporder'		=> 5,
		'gid'			=> intval($gid),
	);

   $db->insert_query('settings', $myplugin_setting);

   $myplugin_setting = array(
		'name'			=> 'hit_counter_registered_only',
		'title'			=> 'Registered Members Only',
		'description'	=> 'Only count hits from registered members? Helpful to block out non-mybb spiders.',
		'optionscode'	=> 'yesno',
		'value'			=> '0',
		'disporder'		=> 6,
		'gid'			=> intval($gid),
	);

   $db->insert_query('settings', $myplugin_setting);

   $myplugin_setting = array(
		'name'			=> 'hit_counter_count_spiders',
		'title'			=> 'Count Spiders',
		'description'	=> 'Count hits from spiders?',
		'optionscode'	=> 'yesno',
		'value'			=> '0',
		'disporder'		=> 7,
		'gid'			=> intval($gid),
	);

   $db->insert_query('settings', $myplugin_setting);
   
   $myplugin_setting = array(
		'name'			=> 'hit_counter_reset',
		'title'			=> 'Reset Hit Counter',
		'description'	=> 'This will reset the hit counter. WARNING: THIS CANNOT BE UNDONE.',
		'optionscode'	=> 'yesno',
		'value'			=> '0',
		'disporder'		=> 8,
		'gid'			=> intval($gid),
	);

   $db->insert_query('settings', $myplugin_setting);

   //setting to store hits in for template usage
   $myplugin_setting = array(
		'name'			=> 'hit_counter_hits',
		'title'			=> '',
		'description'	=> '',
		'optionscode'	=> '',
		'value'			=> '0',
		'disporder'		=> 9,
		'gid'			=> -1,
	);

   $db->insert_query('settings', $myplugin_setting);

   rebuild_settings();
}
function hit_counter_activate() {
	//setup templates
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	
	find_replace_templatesets(
		"index_stats",
		'#'.preg_quote('{$lang->stats_posts_threads}<br />').'#',
		'{$lang->stats_posts_threads}<br />
These forums have had {$mybb->settings[\'hit_counter_hits\']} hits<br />'
	);	

	find_replace_templatesets(
		"stats",
		'#'.preg_quote('{$lang->posts} <strong>{$stats[\'numposts\']}</strong><br />').'#',
		'Hits: <strong>{$mybb->settings[\'hit_counter_hits\']}</strong><br />
{$lang->posts} <strong>{$stats[\'numposts\']}</strong><br />'
	);	
	
}
function hit_counter_deactivate() {
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	
	//repair templates
	find_replace_templatesets(
		"index_stats",
		'#'.preg_quote('
These forums have had {$mybb->settings[\'hit_counter_hits\']} hits<br />').'#',
		''
	);


	find_replace_templatesets(
		"stats",
		'#'.preg_quote('Hits: <strong>{$mybb->settings[\'hit_counter_hits\']}</strong><br />
').'#',
		''
	);

}
function hit_counter_uninstall()
{
   global $db;
   $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN 
('hit_counter_on','hit_counter_unique_only','hit_counter_unique_expire','hit_counter_main_only','hit_counter_registered_only','hit_counter_count_spiders','hit_counter_reset','hit_counter_hits')");
   $db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='hit_counter'");
   rebuild_settings(); 
}

function uniqueHit()
{
   global $db,$mybb;
   $ip = $_SERVER['REMOTE_ADDR']; //we are going to use the user's ip later

   if($_COOKIE["hit_user"] && $_COOKIE["hit_user"] == $ip) //if this cookie exists and ip is same, not unique
   {
      return;
   } else { //they are unique
      if(is_numeric($mybb->settings['hit_counter_unique_expire'])) //if they have a proper value for custom unique expire
      {
         if($mybb->settings['hit_counter_unique_expire'] > 0) //they want it to expire at some point
         {
            $expire=time()+60*60*24*$mybb->settings['hit_counter_unique_expire']; //expire in set number of days
         } else {
            $expire=time()+60*60*24*365*50; //I don't think they will be here in 50 years
         }
      } else {
         $expire=time()+60*60*24*30; //use a month as fallback
      }
      setcookie("hit_user", $ip, $expire); //set the cookie using ip so we can comply with IFABC Global Web Standards
      $db->query("UPDATE ".TABLE_PREFIX."settings SET value=value+1 WHERE name='hit_counter_hits'"); //and remember they are new, so do the hit count. 
      rebuild_settings();
   }
}

function hit_counter_index_start()
{
   global $db,$mybb;

   if($mybb->settings['hit_counter_on']) { //if we are using hit counter
      if($mybb->session->is_spider && !$mybb->settings['hit_counter_count_spiders']) { //don't count spider hits unless user wants them
         return;
      }
      if($mybb->user['gid'] == 1 && $mybb->settings['hit_counter_registered_only']) { //don't count unregistered if the user doesn't want them counted
         return;
      }
      if($mybb->settings['hit_counter_main_only']) { //only use this increment if we are doing main only hits
         if($mybb->settings['hit_counter_unique_only']) {
            uniqueHit();
         } else { //just add the hit
            $db->query("UPDATE ".TABLE_PREFIX."settings SET value=value+1 WHERE name='hit_counter_hits'");
            rebuild_settings();
         }
      }
   }
}

function hit_counter_global_start()
{
   global $db,$mybb;

   if($mybb->settings['hit_counter_on']) { //if we are using hit counter
      if($mybb->session->is_spider && !$mybb->settings['hit_counter_count_spiders']) { //don't count spider hits unless user wants them
         return;
      }
      if($mybb->user['gid'] == 1 && $mybb->settings['hit_counter_registered_only']) { //don't count unregistered if the user doesn't want them counted
         return;
      }
      if(!$mybb->settings['hit_counter_main_only']) { //only use this increment if we are doing non main site hits as well
         if($mybb->settings['hit_counter_unique_only']) {
            uniqueHit();
         } else { //just add the hit
            $db->query("UPDATE ".TABLE_PREFIX."settings SET value=value+1 WHERE name='hit_counter_hits'");
            rebuild_settings();
         }
      }
   }
}

function hit_counter_admin_config_settings_change_commit()
{
   global $db,$mybb;
   
   if($mybb->settings['hit_counter_reset']) { //if they reset the counter
      $db->query("UPDATE ".TABLE_PREFIX."settings SET value=0 WHERE name='hit_counter_hits'");; //reset the hit counter
   }
   
   $db->query("UPDATE ".TABLE_PREFIX."settings SET value=0 WHERE name='hit_counter_reset'"); //put the setting back to no
   rebuild_settings();
}

?>