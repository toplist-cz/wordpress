<?php
/*
Plugin Name: TopList.cz
Plugin URI: https://wordpress.org/plugins/toplistcz/
Description: Widget for easy integration of TopList.cz, popular Czech website visit statistics server.
Version: 4.2
Author: Honza Skypala
Author URI: http://www.honza.info
License: WTFPL license applies
*/

if(!class_exists('WP_Http'))
		include_once(ABSPATH . WPINC. '/class-http.php');

class TopList_CZ_Widget extends WP_Widget {
	const version = "4.2";
	const use_cache = true;
	const cache_expiry = 900;  // 15 minutes * 60 seconds

	function __construct() {
		$widget_ops = array('classname' => 'widget_toplist_cz',
												'description' => __('Integrates TOPList.cz statistics into your blog', 'toplistcz') );
		$control_ops = array('width' => 380, 'height' => 500);
		$toplist_title = 'TOPlist.cz';
		$config = self::config();
		if ($config['server'] == 'toplist.sk')
			$toplist_title = 'TOPlist.sk';
		parent::__construct('toplist_cz', $toplist_title, $widget_ops, $control_ops);
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
		add_action('admin_init', array(__CLASS__, 'version_upgrade'));
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('wp_ajax_toplist_cz_dashboard_content', array($this, 'ajax_dashboard_content'));
		add_action('wp_ajax_toplist_cz_save_password', array($this, 'ajax_save_password'));
	}

	static function activate() {
		self::update_users_dashboard_order(); // we do this always on activation
		self::version_upgrade();
	}

	static function version_upgrade() {
		$user = wp_get_current_user();
		if (!in_array('administrator', $user->roles))
			return;
		$registered_version = get_option('toplist_cz_version', '0');
		if (version_compare($registered_version, self::version, '<')) {
			if (version_compare($registered_version, '4.0', '<')) {
				self::update_users_dashboard_order();
				self::update_widget_title();
				self::update_widget_adminlvl();
			}
			update_option('toplist_cz_version', self::version);
		}
	}

	function widget($args, $instance) {
		extract($args);
		extract(wp_parse_args($instance, array(
				'server'     => 'toplist.cz',
				'link'       => 'homepage',
				'logo'       => '',
				'id'         => '1',
				'referrer'   => '',
				'resolution' => '',
				'depth'      => '',
				'pagetitle'  => '',
				'seccode'    => 0,
				'admindsbl'  => '0',
				'adminlvl'   => 'administrator'
			)), EXTR_PREFIX_ALL, 'toplist');

		if (is_numeric($toplist_adminlvl))
			$toplist_adminlvl = self::user_level_to_role($toplist_adminlvl);
		if ($toplist_adminlvl == "adminlvl")
			$toplist_adminlvl = "administrator";

		if ($toplist_admindsbl == 0 || !current_user_can(self::role_typical_capability($toplist_adminlvl))) {
			$title='';
			echo $before_widget.$before_title.$title.$after_title;
			
			$security = intval($toplist_seccode) > 0 ? "&seed=" . $toplist_seccode : "";

			if ($toplist_logo=='text') {
				echo '<ilayer left=1 top=1 src="https://'.$toplist_server.'/count.asp?id='.$toplist_id.'&logo=text' . $security . '" width="88" heigth="31"><iframe src="https://'.$toplist_server.'/count.asp?id='.$toplist_id.'&logo=text' . $security . '" scrolling=no style="width: 88px;height: 31px;"></iframe></ilayer>';
			} else {
				$width = "88";
				$height = "31";
				switch ($toplist_logo) {
				case 'mc':
					$height = "60";
					break;
				case 'bc':
					$height = "120";
					break;
				case 'btn':
					 $width = "80";
					$height = "15";
					break;
				case 's':
					 $width = "14";
					$height = "14";
					break;
				}
				switch ($toplist_logo) {
				case '1':
				case '2':
				case '3':
				case 'counter':
				case 'mc':
				case 'bc':
				case 'btn':
				case 's':
					$imgsrc="https://".$toplist_server."/count.asp?logo=".$toplist_logo."&";
					break;
				case 'blank':
					$imgsrc="https://".$toplist_server."/dot.asp?";
					$width = "1";
					$height = "1";
					break;
				default:
					$imgsrc="https://".$toplist_server."/count.asp?";
					break;
				}
				if ($toplist_link == 'stats') {
					$link = 'https://www.'.$toplist_server.'/stat/'.$toplist_id;
				} else {
					$link = 'https://www.'.$toplist_server.'/';
				}
				$as = '<a href="'.$link.'" target="_top">';
				$ae = '</a>';
				$imgurl = $imgsrc.'id='.$toplist_id . $security;
				$imgs = '<img src="'.$imgurl;
				$imge = '" alt="TOPlist" border="0" width="'.$width.'" height="'.$height.'" />';
				$img = $imgs.$imge;
				$js = $nse = '';
				if ($toplist_referrer!='' || $toplist_resolution!='' || $toplist_depth!='' || $toplist_pagetitle!='') {
					$jss = '<script language="JavaScript" type="text/javascript">'."\n<!--\ndocument.write('";
					$jse = "');\n//--></script><noscript>";
					$nse = '</noscript>';
					$jsimg = $imgs;
					if ($toplist_referrer   != '') $jsimg .= '&http=\'+escape(document.referrer)+\'';
					if ($toplist_resolution != '') $jsimg .= '&wi=\'+escape(window.screen.width)+\'&he=\'+escape(window.screen.height)+\'';
					if ($toplist_depth      != '') $jsimg .= '&cd=\'+escape(window.screen.colorDepth)+\'';
					if ($toplist_pagetitle  != '') $jsimg .= '&t=\'+escape(document.title)+\'';
					$js = $jss.$jsimg.$imge.$jse;
				}
				echo $as;
				echo $js;
				echo $img;
				echo $nse;
				echo $ae;
			}
			echo $after_widget;
		}
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		foreach (array(
				'server',
				'link',
				'logo',
				'id',
				'referrer',
				'resolution',
				'depth',
				'pagetitle',
				'seccode',
				'admindsbl',
				'adminlvl',
				'display'
			) as $option) {
				$instance[$option] = strip_tags(stripslashes($new_instance[$option]));
		}
		$instance['title'] = self::get_site_name($instance['id'], $instance['server']);
		return $instance;
	}

	function form($instance) {
		foreach ($instance as &$option)
			$option = htmlspecialchars($option);
		extract(wp_parse_args($instance, array(
				'server'     => 'toplist.cz',
				'link'       => 'homepage',
				'logo'       => '',
				'id'         => '',
				'title'      => '',
				'referrer'   => '',
				'resolution' => '',
				'depth'      => '',
				'pagetitle'  => '',
				'seccode'    => 0,
				'admindsbl'  => '0',
				'adminlvl'   => 'administrator',
				'display'    => 'default'
			)), EXTR_PREFIX_ALL, 'toplist');
		if (is_numeric($toplist_adminlvl))
			$toplist_adminlvl = self::user_level_to_role($toplist_adminlvl);
		if ($toplist_adminlvl == "adminlvl")
			$toplist_adminlvl = "administrator";

		// server choice input
		echo '<table><tr><td><label for="' . $this->get_field_name('server') . '">';
		_e('Server', 'toplistcz');
		echo ': </label></td>';
		echo '<td><input id="' . $this->get_field_id('server') . '" name="' . $this->get_field_name('server') . '" type="radio" value="toplist.cz"'.($toplist_server=='toplist.cz'?' checked':'').'>toplist.cz</input></td>';
		echo '</tr><tr>';
		echo '<td></td>';
		echo '<td><input id="' . $this->get_field_id('server') . '" name="' . $this->get_field_name('server') . '" type="radio" value="toplist.sk"'.($toplist_server=='toplist.sk'?' checked':'').'>toplist.sk</input></td>';
		echo '</tr></table><hr />';

		// toplist ID input
		echo '<p><label for="' . $this->get_field_name('id') . '">'.str_replace('toplist', 'TOPlist', $toplist_server).' ID: </label><input id="' . $this->get_field_id('id') . '" name="' . $this->get_field_name('id') . '" type="text" value="'.intval($toplist_id).'" size="7" /><input id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="hidden" value="'.$toplist_title.'" /></p>'."\n";
		echo '<p style="margin: 5px 10px;"><em>'.str_replace('%server%', $toplist_server, __('Your ID on <a href="https://www.%server%" target="_blank">www.%server%</a> server. If you don\'t have one yet, please <a href="https://www.%server%/edit/?a=e" target="_blank">register</a>.', 'toplistcz')).'</em></p><hr />';

		// logo selection
		echo '<table><tr>';
		echo '<td><label for="' . $this->get_field_name('logo') . '">';
		_e('Logo', 'toplistcz');
		echo ':&nbsp;</label></td>';
		echo '<td><input id="' . $this->get_field_id('logo') . '" name="' . $this->get_field_name('logo') . '" type="radio" value=""'.($toplist_logo==''?' checked':'').' /></td><td><img src="https://i.toplist.cz/img/logo.gif" width="88" height="31" /></td>';
		echo '<td>&nbsp;</td>';
		echo '<td><input id="' . $this->get_field_id('logo') . '" name="' . $this->get_field_name('logo') . '" type="radio" value="1"'.($toplist_logo=='1'?' checked':'').' /></td><td style="background-color: black;"><img src="https://i.toplist.cz/img/logo1.gif" width="88" height="31" /></td>';
		echo '<td>&nbsp;</td>';
		echo '<td><input id="' . $this->get_field_id('logo') . '" name="' . $this->get_field_name('logo') . '" type="radio" value="2"'.($toplist_logo=='2'?' checked':'').' /></td><td><img src="https://i.toplist.cz/img/logo2.gif" width="88" height="31" /></td>';
		echo "</tr><tr><td></td>";
		echo '<td><input id="' . $this->get_field_id('logo') . '" name="' . $this->get_field_name('logo') . '" type="radio" value="3"'.($toplist_logo=='3'?' checked':'').' /></td><td><img src="https://i.toplist.cz/img/logo3.gif" width="88" height="31" /></td>';
		echo '<td>&nbsp;</td>';
		echo '<td><input id="' . $this->get_field_id('logo') . '" name="' . $this->get_field_name('logo') . '" type="radio" value="blank"'.($toplist_logo=='blank'?' checked':'').' /></td><td style="text-align: center">'.__('nothing', 'toplistcz').'</td>';
		echo '<td>&nbsp;</td>';
		echo '<td><input id="' . $this->get_field_id('logo') . '" name="' . $this->get_field_name('logo') . ' "type="radio" value="text"'.($toplist_logo=='text'?' checked':'').' /></td><td style="text-align: center"><font size ="2"><b>867314</b><br /><font size="1"><a href="https://www.'.$toplist_server.'" target="_top"><b>www.'.$toplist_server.'<b></a></font></td>';
		echo "</tr><tr><td></td>";
		echo '<td><input id="' . $this->get_field_id('logo') . '" name="' . $this->get_field_name('logo') . '" type="radio" value="counter"'.($toplist_logo=='counter'?' checked':'').' /></td><td><img src="https://www.'.$toplist_server.'/images/counter.asp?s=904182" width="88" height="31" /></td>';
		echo '<td>&nbsp;</td>';
		echo '<td><input id="' . $this->get_field_id('logo') . '" name="' . $this->get_field_name('logo') . '" type="radio" value="btn"'.($toplist_logo=='btn'?' checked':'').' /></td><td style="text-align: center"><img src="https://www.'.$toplist_server.'/images/counter.asp?a=btn&amp;s=722890" width="80" height="15" /></td>';
		echo '<td>&nbsp;</td>';
		echo '<td><input id="' . $this->get_field_id('logo') . '" name="' . $this->get_field_name('logo') . '" type="radio" value="s"'.($toplist_logo=='s'?' checked':'').' /></td><td style="text-align: center"><img src="https://i.'.$toplist_server.'/img/sqr.gif" width="14" height="14" /></td>';
		echo "</tr><tr><td></td>";
		echo '<td><input id="' . $this->get_field_id('logo') . '" name="' . $this->get_field_name('logo') . '" type="radio" value="mc"'.($toplist_logo=='mc'?' checked':'').' /></td><td><img src="https://www.'.$toplist_server.'/images/counter.asp?a=mc&amp;ID=1" width="88" height="60" /></td>';
		echo '<td>&nbsp;</td>';
		echo '<td><input id="' . $this->get_field_id('logo') . '" name="' . $this->get_field_name('logo') . '" type="radio" value="bc"'.($toplist_logo=='bc'?' checked':'').' /></td><td><img src="https://www.'.$toplist_server.'/images/counter.asp?a=bc&amp;ID=1" width="88" height="120" /></td>';
		echo '</tr></table>';

		// display
		echo '<p><label for="' . $this->get_field_name('display') . '">';
		_e('Display', 'toplistcz');
		echo ': </label>';
		echo '<select id="' . $this->get_field_id('display') . '" name="' . $this->get_field_name('display') . '">';
		echo '<option value="default"' . ($toplist_display=='default'?' selected':'') . '>' . __('Default (specified by css of your theme)', 'toplistcz') . '</option>';
		echo '<option value="center"' . ($toplist_display=='center'?' selected':'') . '>' . __('Center (enforced)', 'toplistcz') . '</option>';
		echo '<option value="hidden"' . ($toplist_display=='hidden'?' selected':'') . '>' . __('Hidden (enforced)', 'toplistcz') . '</option>';
		echo '</select>';
		echo '</p><hr />';

		// monitoring details settings
		echo '<p><input id="' . $this->get_field_id('referrer') . '" name="' . $this->get_field_name('referrer') . '" type="checkbox" '.($toplist_referrer!=''?'checked ':'').' />';
		echo ' <label for="' . $this->get_field_name('referrer') . '">';
		_e('Monitor where visitors came from', 'toplistcz');
		echo '</label><br />';
		echo '<input id="' . $this->get_field_id('resolution') . '" name="' . $this->get_field_name('resolution') . '" type="checkbox" '.($toplist_resolution!=''?'checked ':'').' />';
		echo ' <label for="' . $this->get_field_name('resolution') . '">';
		_e('Monitor browser graphical resolution', 'toplistcz');
		echo '</label><br />';
		echo '<input id="' . $this->get_field_id('depth') . '" name="' . $this->get_field_name('depth') . '" type="checkbox" '.($toplist_depth!=''?'checked ':'').' />';
		echo ' <label for="' . $this->get_field_name('depth') . '">';
		_e('Monitor color depth', 'toplistcz');
		echo '</label><br />';
		echo '<input id="' . $this->get_field_id('pagetitle') . '" name="' . $this->get_field_name('pagetitle') . '" type="checkbox" '.($toplist_pagetitle!=''?'checked ':'').' />';
		echo ' <label for="' . $this->get_field_name('pagetitle') . '">';
		_e('Record webpage title', 'toplistcz');
		echo '</label><br />';
		echo '<label for="' . $this->get_field_name('seccode') . '">' . __('Security code:', 'toplistcz') . '</label> ';
		echo '<input id="' . $this->get_field_id('seccode') . '" name="' . $this->get_field_name('seccode') . '" type="number" min="0" max="200" value="' . $toplist_seccode . '" />';
		echo '[<a href="https://wiki.toplist.cz/Tipy_a_triky#4" target="_blank"><span style="font-size:x-small">' . __('what is this?', 'toplistcz') . '</span></a>]';
		echo '</p><hr />';

		// hyperlink settings
		echo '<table><tr><td><label for="' . $this->get_field_name('link') . '">';
		_e('Link', 'toplistcz');
		echo ': </label></td>';
		echo '<td><input id="' . $this->get_field_id('link') . '" name="' . $this->get_field_name('link') . '" type="radio" value="homepage"'.($toplist_link=='homepage'?' checked':'').'>'.$toplist_server.'</input></td>';
		echo '</tr><tr>';
		echo '<td></td>';
		echo '<td><input id="' . $this->get_field_id('link') . '" name="' . $this->get_field_name('link') . '" type="radio" type="radio" value="stats"'.($toplist_link=='stats'?' checked':'').'>'.__('Detailed statistics', 'toplistcz').'</input></td>';
		echo '</tr></table>';
		echo '<hr />';

		// tracking admin users
		echo '<table><tr><td width="190px"><label for="' . $this->get_field_name('admindsbl') . '">';
		_e('WordPress admin logging', 'toplistcz');
		echo ': </label></td>';
		echo '<td>';

		echo "<select name='".$this->get_field_name('admindsbl')."' id='".$this->get_field_id('admindsbl')."'>\n";

		echo "<option value='0'";
		if($toplist_admindsbl == '0')
			echo " selected='selected'";
		echo ">" . __('Enabled', 'toplistcz') . "</option>\n";

		echo "<option value='1'";
		if($toplist_admindsbl == '1')
			echo" selected='selected'";
		echo ">" . __('Disabled', 'toplistcz') . "</option>\n";

		echo "</select>\n<br />";
		echo '</td></tr><tr><td colspan="2">';

		$roles_combo = "<select name='".$this->get_field_name('adminlvl')."' id='".$this->get_field_id('adminlvl')."'>";
		foreach (array("administrator", "editor", "author", "contributor", "subscriber") as $role) {
			$roles_combo .= "<option value=\"$role\""
										. ($role == $toplist_adminlvl ? " selected" : "")
										. ">"
										. _x(ucfirst($role), "User role")
										. "</option>";
		}
		$roles_combo .= "</select>";

		?>
		<p style="margin: 5px 10px;"><em><?php printf(__('Disabling this option will prevent visits from all logged-in WordPress administrators from showing up on your %1$s reports. Eventually, all logged-in users with role %2$s or higher are prevented from being logged by %1$s. Your role is %3$s.', 'toplistcz'), str_replace('toplist', 'TOPlist', $toplist_server), $roles_combo, _x(ucfirst(wp_get_current_user()->roles[0]), "User role")); ?></em></p>
		<?php
		echo '</td></tr></table>';
	}

	function enqueue_scripts() {
		$toplist_options = get_option('widget_toplist_cz', array());
		foreach ($toplist_options as $i => $option) {
			switch ($option['display']) {
				case 'center':
					echo "<style type=\"text/css\">
						#toplist_cz-$i {
							text-align: center;
							margin-left: auto;
							margin-right: auto;
						}
					</style>";
					break;
				case 'hidden':
					echo "<style type=\"text/css\">
						#toplist_cz-$i {
							display: none;
						}
					</style>";
					break;
			}
		}
	}

	public function admin_enqueue_scripts($hook) {
		if ($hook != 'index.php')
			return;
		$suffix  = '.min';
		wp_enqueue_style('toplist-cz-admin', plugins_url("/css/admin$suffix.css", __FILE__));
		wp_register_script('toplist-cz-admin', plugins_url("/js/admin$suffix.js", __FILE__), array('jquery'), false, true);
		wp_register_script('flot', plugins_url("/js/jquery.flot.min.js", __FILE__), array('jquery'), '0.8.3', true);
	}

	const dash_widget_slug = "toplist_cz_dashboard";

	function add_dashboard_widget() {
		$user = wp_get_current_user();
		$config = self::config();

		if (!$config)  // no config found => no dashboard widget
			return;
		if ($config['title'] == sprintf(__(self::_not_found_string, 'toplistcz'), $config['id']))
			return;
		if (!in_array(get_option('toplist_cz_dashboard_widget_user_level', 'administrator'), $user->roles))
			return;

		wp_add_dashboard_widget(
								 self::dash_widget_slug,               // Widget slug.
								 $config['server'] == 'toplist.sk' ? 'TOPlist.sk' : 'TOPlist.cz', // Title.
								 array($this, 'draw_dashboard_widget') // Display function.
			);
		$dash_widgets_order = get_user_meta(get_current_user_id(), 'meta-box-order_dashboard');
		if (empty($dash_widgets_order)) { // push the widget to top of second column
			global $wp_meta_boxes;
			$my_widget = $wp_meta_boxes['dashboard']['normal']['core'][self::dash_widget_slug];
			unset($wp_meta_boxes['dashboard']['normal']['core'][self::dash_widget_slug]);
			$wp_meta_boxes['dashboard']['side']['core'] = array_merge(array($my_widget), $wp_meta_boxes['dashboard']['side']['core']);
		}
	}

	function draw_dashboard_widget() {
		wp_enqueue_script("toplist-cz-admin");
		wp_enqueue_script("flot");
		$stats = get_option('toplist-cache-' . date('Y-m-d'), false);
		$cache_valid = self::use_cache && is_array($stats) && time() <= $stats['local_timestamp'] + self::cache_expiry;
		echo '<span class="spinner"' . ($cache_valid ? ' style="display:none;"' : '') . '></span>';
		echo '<span class="erroricon" style="display:none;"></span>';
		echo '<div class="content">';
		if ($cache_valid) {
			echo self::dashboard_html($stats);
		}
		echo '</div>';
	}

	private static function update_users_dashboard_order() {
		global $wpdb;
		if ($user_ids = $wpdb->get_col("SELECT user_id FROM $wpdb->usermeta WHERE meta_key='meta-box-order_dashboard'")) { // instead of traversing through all registered users, we select only the ones with dashboard order meta existing
			foreach ($user_ids as $user_id) {
				$dash_order = get_user_meta($user_id, 'meta-box-order_dashboard', true);
				$found = false;
				foreach ($dash_order as $dash_area)
					if (strstr($dash_area, self::dash_widget_slug) != FALSE) {
						$found = true;
						break;
					}
				if (!$found) {
					$dash_order['side'] = self::dash_widget_slug . ',' . $dash_order['side'];
					update_user_meta($user_id, 'meta-box-order_dashboard', $dash_order);
				}
			}
		}
	}

	private static function update_widget_title() {
		$options = get_option('widget_toplist_cz');
		if (is_array($options))
			foreach ($options as $i => &$option)
				if (is_array($option)) {
					$option['title'] = self::get_site_name($option['id'], $option['server']);
				}
		update_option('widget_toplist_cz', $options);
	}

	private static function update_widget_adminlvl() {
		$options = get_option('widget_toplist_cz');
		if (is_array($options))
			foreach ($options as $i => &$option)
				if (is_array($option)) {
					if (is_numeric($option['adminlvl']))
						$option['adminlvl'] = self::user_level_to_role($option['adminlvl']);
					if ($option['adminlvl'] == "adminlvl")
						$option['adminlvl'] = "administrator";
				}
		update_option('widget_toplist_cz', $options);
	}
	
	private function toplist_request_fields($day = FALSE) {
		$config = $this->config();
		if ($day == FALSE)
			$day = date("w");

		$fields = '';
		$fields .= 'menu=2048'    // Návštěvy za den (tabulka)
						. '&menu=512'     // Návštěvy za měsíc (tabulka)
						. '&menu=256'     // Návštěvy za rok (graf)
						. '&menu=128'     // Zhlédnutí za rok (graf)
						. '&menu=2'       // Top domény
						. '&menu=16'      // Domény
						. '&menu=4'       // Vstupní stránky
						. '&menu=8'       // Odkud přišli
						. '&menu=64'      // Prohlížeče
						. '&menu=32'      // Operační systémy
						. '&menu=8192'    // Rozlišení monitorů
						. '&menu=16384'   // Barevná hloubka
						. '&menu=32768'   // Země
						;
//    $fields .= "&weekday=$day";  // -- dělá problém na profi statistikách
		$fields .= "&n=" . $config['id'];
		$fields .= "&show_stats=1";

		if (isset($config['password']) && $config['password'] != '')
			$fields .= "&heslo=" . self::decrypt($config['password']);

		return $fields;
	}

	private function get_toplist_stats_html($day = FALSE) {
		$config = $this->config();

		$url = "https://www.{$config['server']}/stat/";
		$http = new WP_Http();
		$http_result = $http->request($url, array(
				'method' => 'POST',
				'body'   => self::toplist_request_fields($day)
			));

		if (is_wp_error($http_result))
			return $http_result;

		$body = $http_result['body'];

		if (strpos($body, '<html>Nespr') !== false)
			return new WP_Error('wrong_toplist_password', __( "Wrong or missing password to TOPlist.cz account", "toplistcz"));

		return $body;
	}

	private function password_form() {
		$config = self::config();
		$msg = __('For displaying statistics, you must enter password to your TOPlist.cz account.', 'toplistcz');
		if ($config['server'] == 'toplist.sk')
			$msg = preg_replace('/(toplist)\.cz/i', '\1.sk', $msg);
		$id_label = __('ID', 'toplistcz');
		$pw_label = __('Password');
		$button = __('Save');
		 $ajax_nonce = wp_create_nonce("toplist_dashboard_password");

		return "<p>$msg</p><form id=\"toplist_password_form\"><span><label for=\"toplist_id\">$id_label: </label><input type=\"text\" id=\"toplist_id\" name=\"id\" value=\"{$config['title']}\" disabled /></span><span><label for=\"toplist_password\">$pw_label: </label><input type=\"password\" id=\"toplist_password\" name=\"password\" /><input type=\"button\" value=\"$button\" id=\"toplist_password_submit\" /><span class=\"spinner\"></span></span><input type=\"hidden\" name=\"_wpnonce\" id=\"toplist_password_nonce\" value=\"$ajax_nonce\" /></form>";
	}

	private function config() {
		$options = get_option('widget_toplist_cz');
		$sidebars_widgets = get_option('sidebars_widgets');
		$flat_sidebar = array();
		$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($sidebars_widgets));
		foreach($it as $v) {
			array_push($flat_sidebar, $v);
		}
		if (is_array($options))
			foreach ($options as $i => $option)
				if (is_array($option) && !in_array("toplist_cz-$i", $sidebars_widgets['wp_inactive_widgets']) && in_array("toplist_cz-$i", $flat_sidebar))
					return $option;
		return false;
	}

	public function ajax_dashboard_content() {
		$user = wp_get_current_user();
		if (!is_user_logged_in() || !in_array(get_option('toplist_cz_dashboard_widget_user_level', 'administrator'), $user->roles)) {
			http_response_code(403);
			return;
		}
		wp_send_json(self::dashboard_content());
	}

	private function dashboard_content() {
		$stats = false;
		if (self::use_cache) {
			$stats = get_option('toplist-cache-' . date('Y-m-d'), false);
			if (is_array($stats) && time() > $stats['local_timestamp'] + self::cache_expiry) // cache data still valid
				$stats = false;
		}
		if (!$stats) {
			$html = self::get_toplist_stats_html();
			if (is_wp_error($html)) {
				switch ($html->get_error_code()) {
					case 'wrong_toplist_password':
						return array('success' => false,
												 'html'    => self::password_form());
					default:
						return array('success' => false,
												 'html'    => $html->get_error_message(),
												 'reload'  => true);
				}
			}
	
			$stats = self::extract_toplist_stats($html);
			if (self::use_cache) {
				update_option('toplist-cache-' . date('Y-m-d', $stats['local_timestamp']), $stats);
			}
		}
		return array('success' => true,
								 'html'    => self::dashboard_html($stats));
	}
	
	private function Ymd_from_toplist_timestamp($timestamp) {
		return substr($timestamp, 6, 4) . '-' . substr($timestamp, 3, 2) . '-' . substr($timestamp, 0, 2);
	}
	
	private function table_2_columns($data, $rows = 5) {
		$base = get_option("siteurl");
		$base = str_replace("http://", "", $base);
		$base = str_replace("https://", "", $base);
		
		$return = "";
		if (!empty($data))
			foreach ($data as $key => $value) {
				if (substr($key, 0, strlen($base)) == $base && $key != $base)
					$key = substr($key, strlen($base));
				$return .= "<tr><td>" . $value . "</td><td>" . $key . "</td></tr>";
				if (--$rows == 0) break;
			}
		return $return; 
	}
	
	private function dashboard_html($stats = false) {
		$return = ''
						. '<data id="toplist_stats" value="' . base64_encode(json_encode($stats)) . '"></data>'
						// graf návštěvy za den
						. '<div id="navstevy-za-den">'
						. '<h4>' . $stats['navstevy_za_den']['label'] . '</h4>'
						. '<div class="graph"><span class="spinner"></span></div>'
						. '</div>'
						// vstupní stránky
						. '<div id="vstupni-stranky" class="half-width">'
						. '<h4>' . $stats['vstupni_stranky_label'] . '</h4>'
						. '<table class="toplist-top">'
						. '<thead><tr><th>' . ( $stats['toplist_server'] == "toplist.sk" ? "Koľko" : "Kolik" ) . '</th><th>Adresa</th></tr></thead>'
						. '<tbody>' . self::table_2_columns($stats["vstupni_stranky"]) . '</tbody>'
						. '</table>'
						. '</div>'
						// návštěvy podle domén
						. '<div id="navstevy-podle-domen" class="half-width">'
						. '<h4>' . $stats['domeny_label'] . '</h4>'
						. '<table class="toplist-top">'
						. '<thead><tr><th>' . ( $stats['toplist_server'] == "toplist.sk" ? "Koľko" : "Kolik" ) . '</th><th>Doména</th></tr></thead>'
						. '<tbody>' . self::table_2_columns($stats["domeny"]) . '</tbody>'
						. '</table>'
						. '</div>'
						// graf návštěvy za měsíc
						. '<div id="navstevy-za-mesic">'
						. '<h4>' . $stats['navstevy_za_mesic']['label'] . '</h4>'
						. '<div class="graph"><span class="spinner"></span></div>'
						. '</div>'
		;
		return $return;
	}

	private function toplist_parse_texts($html, $server = "") {
		if ($server == "") {
			preg_match("#/(toplist\.(cz|sk))\.css#", $html, $matches);
			$server = $matches[1];
		}
		if ($server == "toplist.sk") {
			return array(
									 'navstevy_za_den'   => 'Návštevy za deň (tabuľka)',
									 'navstevy_za_mesic' => 'Návštevy za mesiac :',
									 'navstevy_za_rok'   => 'Návštevy za rok',
									 'zhlednuti_za_rok'  => 'Zobrazenia za rok',
									 'top_domeny'        => 'Návštevy podľa top domén',
									 'domeny'            => 'Návštevy podľa domén',
									 'vstupni_stranky'   => 'Vstupné stránky',
									 'odkud_prisli'      => 'Z ktorých stránok prišli návštevníci (prvých 50 )',
									 'operacni_systemy'  => 'Operačné systémy',
									 'prohlizece'        => 'Prehliadače',
									 'rozliseni'         => 'Rozlíšenie monitoru',
									 'barevna_hloubka'   => 'Farebná hĺbka (v bitoch)',
									 'zeme'              => 'Krajina',
									 'datum'             => 'Dátum:'
									);
		} else {
			return array(
									 'navstevy_za_den'   => 'Návštěvy za den (tabulka)',
									 'navstevy_za_mesic' => 'Návštěvy za měsíc:',
									 'navstevy_za_rok'   => 'Návštěvy za rok',
									 'zhlednuti_za_rok'  => 'Zhlédnutí za rok',
									 'top_domeny'        => 'Návštěvy podle top domén',
									 'domeny'            => 'Návštěvy podle domén',
									 'vstupni_stranky'   => 'Vstupní stránky',
									 'odkud_prisli'      => 'Z kterých stránek příšli návštěvníci (prvních 50)',
									 'operacni_systemy'  => 'Operační systémy',
									 'prohlizece'        => 'Prohlížeče',
									 'rozliseni'         => 'Rozlišení monitorů',
									 'barevna_hloubka'   => 'Barevná hloubka (v bitech)',
									 'zeme'              => 'Země',
									 'datum'             => 'Datum:'
									);
		}
	}
	
	private function extract_toplist_stats($html) {
		$return = array();
		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		if ($dom->loadHTML($html) !== false) {
			$return['local_timestamp'] = time();
			preg_match("#/(toplist\.(cz|sk))\.css#", $html, $matches);
			
			$return['toplist_server'] = $matches[1];
			extract(self::toplist_parse_texts($html, $matches[1]), EXTR_PREFIX_ALL, "parsetext");
			$xpath = new DOMXPath($dom);

			$return['toplist_profi'] = $profi = substr($xpath->query("//table[normalize-space(tr[1]/th) = '$parsetext_top_domeny']/tr[2]/td")->item(0)->textContent, 0, 2) == "<<";


			$toplist_id = $xpath->query("//table[@id = 'info']/tr[th = 'ID:']/td[1]")->item(0)->textContent;
			$return['toplist_id'] = intval(substr($toplist_id, 0, strpos($toplist_id, " ")));
			$return['toplist_timestamp'] = $xpath->query("//table[@id = 'info']/tr[th = '$parsetext_datum']/td[1]")->item(0)->textContent;
			preg_match("#(\d+).(\d+)\.(\d+)\s\d+:\d#i", $return['toplist_timestamp'], $date_matches);
			$days_in_month = cal_days_in_month(CAL_GREGORIAN, intval($date_matches[2]), intval($date_matches[3]));

			$navstevy_za_den = $xpath->query("//table[normalize-space(tr[1]/th) = '$parsetext_navstevy_za_den']")->item(0);
			$return['navstevy_za_den'] = array();
			$return['navstevy_za_den']['label']    = $xpath->query("tr[1]/th", $navstevy_za_den)->item(0)->textContent;
			$return['navstevy_za_den']['label']    = trim(substr($return['navstevy_za_den']['label'], 0, strpos($return['navstevy_za_den']['label'], "(")));
			$return['navstevy_za_den']['navstevy'] = self::DOMNodeList_2_array($xpath->query("tr[position()>2 and position()<27]/td[2]", $navstevy_za_den));
			$return['navstevy_za_den']['reload']   = self::DOMNodeList_2_array($xpath->query("tr[position()>2 and position()<27]/td[3]", $navstevy_za_den));
			$return['navstevy_za_den']['zhlednuti'] = array();
			foreach ($return['navstevy_za_den']['navstevy'] as $i => $value)
				$return['navstevy_za_den']['zhlednuti'][$i] = intval($value) + intval($return['navstevy_za_den']['reload'][$i]);

			$navstevy_za_mesic = $xpath->query("//table[starts-with(normalize-space(tr[1]/th), '$parsetext_navstevy_za_mesic')]")->item(0);
			$return['navstevy_za_mesic'] = array();
			$return['navstevy_za_mesic']['mesic']     = $xpath->query("tr[1]/th/select/option[@selected]", $navstevy_za_mesic)->item(0)->textContent;
			$return['navstevy_za_mesic']['label']     = $xpath->query("tr[1]/th", $navstevy_za_mesic)->item(0)->textContent;
			$return['navstevy_za_mesic']['label']     = trim(substr($return['navstevy_za_mesic']['label'], 0, strpos($return['navstevy_za_mesic']['label'], ":")));
			$return['navstevy_za_mesic']['navstevy']  = array_slice(self::DOMNodeList_2_array($xpath->query("tr[position()>2 and position()<last()]/td[2]", $navstevy_za_mesic), 1), 0, $days_in_month, true);
			$return['navstevy_za_mesic']['zhlednuti'] = array_slice(self::DOMNodeList_2_array($xpath->query("tr[position()>2 and position()<last()]/td[3]", $navstevy_za_mesic), 1), 0, $days_in_month, true);

			$return['navstevy_za_rok'] = array();
			$navstevy_za_rok = $xpath->query("//table[starts-with(normalize-space(tr[1]/th), '$parsetext_navstevy_za_rok')]/tr[@class = 'w2']/td");
			$return['navstevy_za_rok']['label']    = trim($xpath->query("//table[starts-with(normalize-space(tr[1]/th), '$parsetext_navstevy_za_rok')]/tr[1]/th")->item(0)->textContent);
			$return['navstevy_za_rok']['label']    = trim(substr($return['navstevy_za_rok']['label'], 0, strrpos($return['navstevy_za_rok']['label'], " ")));
			for ($i = 0; $i < 12; 
				$return['navstevy_za_rok'][$i+1] = self::DOMNodeList_2_array($xpath->query("img/@title", $navstevy_za_rok->item($i++)), 1, true));
			$return['navstevy_za_rok'][2] = array_slice($return['navstevy_za_rok'][2], 0, cal_days_in_month(CAL_GREGORIAN, 2, intval(substr($xpath->query("//table/tr[1]/th[starts-with(normalize-space(.), '$parsetext_navstevy_za_rok')]")->item(0)->textContent, -4))), true); // truncate 29th February, if that year didn't have that day

			$return['zhlednuti_za_rok'] = array();
			$zhlednuti_za_rok = $xpath->query("//table[normalize-space(tr[1]/th) = '$parsetext_zhlednuti_za_rok']/tr[@class = 'w2']/td");
			$return['zhlednuti_za_rok']['label']    = trim($xpath->query("//table[normalize-space(tr[1]/th) = '$parsetext_zhlednuti_za_rok']/tr[1]/th")->item(0)->textContent);
			for ($i = 0; $i < 12;
				$return['zhlednuti_za_rok'][$i+1] = self::DOMNodeList_2_array($xpath->query("img/@title", $zhlednuti_za_rok->item($i++)), 1, true));

			$return['top_domeny']            = self::extract_assoc_array($xpath, $parsetext_top_domeny, $profi, 1, 2, true);
			$return['top_domeny_label']      = trim($xpath->query("//table[normalize-space(tr[1]/th) = '$parsetext_top_domeny']/tr[1]/th")->item(0)->textContent);
			$return['domeny']                = self::extract_assoc_array($xpath, $parsetext_domeny, $profi, 2, 1);
			$return['domeny_label']          = trim($xpath->query("//table[normalize-space(tr[1]/th) = '$parsetext_domeny']/tr[1]/th")->item(0)->textContent);
			$return['vstupni_stranky']       = self::extract_assoc_array($xpath, $parsetext_vstupni_stranky, $profi, 2, 1);
			$return['vstupni_stranky_label'] = trim($xpath->query("//table[normalize-space(tr[1]/th) = '$parsetext_vstupni_stranky']/tr[1]/th")->item(0)->textContent);
			$return['odkud_prisli']          = self::extract_assoc_array($xpath, $parsetext_odkud_prisli, $profi, 2, 1, true);
			$return['odkud_prisli_label']    = trim($xpath->query("//table[normalize-space(tr[1]/th) = '$parsetext_odkud_prisli']/tr[1]/th")->item(0)->textContent);

			$return['operacni_systemy']         = self::extract_assoc_array($xpath, $parsetext_operacni_systemy, $profi);
			$return['operacni_systemy_label']   = trim($xpath->query("//table[normalize-space(tr[1]/th) = '$parsetext_operacni_systemy']/tr[1]/th")->item(0)->textContent);
			$return['prohlizece']               = self::extract_assoc_array($xpath, $parsetext_prohlizece, $profi);
			$return['prohlizece_label']         = trim($xpath->query("//table[normalize-space(tr[1]/th) = '$parsetext_prohlizece']/tr[1]/th")->item(0)->textContent);
			$return['rozliseni_monitoru']       = self::extract_assoc_array($xpath, $parsetext_rozliseni, $profi);
			$return['rozliseni_monitoru_label'] = trim($xpath->query("//table[normalize-space(tr[1]/th) = '$parsetext_rozliseni']/tr[1]/th")->item(0)->textContent);
			$return['barevna_hloubka']          = self::extract_assoc_array($xpath, $parsetext_barevna_hloubka, $profi);
			$return['barevna_hloubka_label']    = trim($xpath->query("//table[normalize-space(tr[1]/th) = '$parsetext_barevna_hloubka']/tr[1]/th")->item(0)->textContent);
			$return['zeme']                     = self::extract_assoc_array($xpath, $parsetext_zeme, $profi);
			$return['zeme_label']               = trim($xpath->query("//table[normalize-space(tr[1]/th) = '$parsetext_zeme']/tr[1]/th")->item(0)->textContent);

		} else {
			$return = false;
		}
		libxml_clear_errors();
		return $return;
	}

	private function DOMNodeList_2_array(DOMNodeList $node_list, $start_index = 0, $graph_title = false) {
		$nodes = array();
		foreach($node_list as $node) {
			$nodes[$start_index++] = $graph_title ? intval(substr($node->textContent, strrpos($node->textContent, " ") + 1)) : $node->textContent;
		}
		return $nodes;
	}

	private function extract_assoc_array($xpath, $table_th, $profi, $key_index = 1, $value_index = 2, $skip_last_row = false) {
		$start_row = $profi ? 3 : 2;
		$extra_cond = '';
		if ($skip_last_row) $extra_cond = ' and position()<last()';
		$table = $xpath->query("//table[normalize-space(tr[1]/th) = '$table_th']")->item(0);
		return array_combine(
			self::DOMNodeList_2_array($xpath->query("tr[position()>$start_row$extra_cond]/td[$key_index]", $table)),
			self::DOMNodeList_2_array($xpath->query("tr[position()>$start_row$extra_cond]/td[$value_index]", $table))
		);
	}

	public function ajax_save_password() {
		check_ajax_referer("toplist_dashboard_password");
		$options = get_option('widget_toplist_cz', FALSE);
		if ($options == FALSE || !is_array($options) || empty($options))
			return FALSE;
		foreach ($options as $i => &$option)
			if (is_array($option))
				$option['password'] = self::encrypt($_POST['password']);

		update_option('widget_toplist_cz', $options);

		wp_send_json(self::dashboard_content());
	}

	const _not_found_string = '%1$s NOT FOUND';
	private function get_site_name($id, $server = 'toplist.cz') {
		$return = $id;
		$url = "https://www.$server/stat/" . $id;
		$html = wp_remote_fopen($url);
		if($html !== false) {
			$dom = new DOMDocument();
			libxml_use_internal_errors(true);
			if ($dom->loadHTML($html) !== false) {
				if ($dom->getElementById('info') == NULL)
					$return = sprintf(__(self::_not_found_string, 'toplistcz'), $id);
				else {
					$xpath = new DOMXPath($dom);
					$return = $id . " (" . $xpath->query("//table[@id='info']/tr[2]/td")->item(0)->textContent . ")";
				}
			}
			libxml_clear_errors();
		}
		return $return;
	}

	private function user_level_to_role($level) {
		if (level > 7)
			return "administrator";
		else if (level > 2)
			return "editor";
		else if (level > 1)
			return "author";
		else if (level > 0)
			return "contributor";
		else
			return "subscriber";
	}

	private function role_typical_capability($role) {
		switch ($role) {
			case "subscriber":
				return "read";
			case "contributor":
				return "edit_posts";
			case "author":
				return "publish_posts";
			case "editor":
				return "edit_others_posts";
			default:
				return "edit_theme_options"; // we require the highest role as default
		}
	}
	
	private function salt() {
		static $salt = '';
		if ($salt == '') {
			if (defined('AUTH_KEY'))
				$salt = AUTH_KEY;
			else
				$salt = str_pad('', 24, get_option('siteurl', 'toplist'));
			
			if (strlen($salt) > 24)
				$salt = substr($salt, 0, 24);
			else if (strlen($salt) < 24)
				$salt = str_pad($salt, 24, 'toplist');
		}
		return $salt;
	}
	
	private function encrypt($text) {
		return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, self::salt(), $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
	}
	
	private function decrypt($text) {
		return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, self::salt(), base64_decode($text), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
	}
}

register_activation_hook(__FILE__, 'TopList_CZ_Widget::activate');  // activation of plugin
add_action('init', create_function('', 'load_plugin_textdomain("toplistcz", false, basename(dirname(__FILE__)) . "/lang/");'));
add_action('widgets_init', create_function('', 'register_widget("TopList_CZ_Widget");'));
?>