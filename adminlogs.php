<?php

        include("config.php");
        include("lib/functions.php");
        require_once 'lib/steam-condenser/lib/steam-condenser.php';
		
        mysql_connect($host, $user, $pass) or die(mysql_error());
        mysql_select_db($table) or die(mysql_error());
		mysql_query('SET NAMES utf8');

	$startTime = head();
	$settings = getsettings();

?>

<?

	if (! isset( $_GET[ 'steamid' ] ) )
                $_GET[ 'steamid' ] = '%';


	echo 'Choose your admin : ';
        echo '<form>';
        echo '<select name="steamid" onchange="this.form.submit()">';
	$users = mysql_query( "select steamid, name from sm_logging group by steamid;" ) or die(mysql_error());
		echo '<option value="%">All Admins</option>';
	while( $row = mysql_fetch_array( $users ) ) {
		$steamid = $row['steamid'];
		$name = $row['name'];
		if ($_GET['steamid'] == $steamid) {
                        echo '<option value="'. $steamid . '" selected="selected">' . $name . ' - ' . $steamid . '</option>';
		}
		else {
			echo '<option value="'. $steamid . '">' . $name . ' - ' . $steamid . '</option>';
		}
        }
        echo '</select>';
        echo '</form><br/>';

	if ($settings['usestats']['config'] == 'yes') {
		$statsinfo[0] = $settings['statsprogram']['config'];
		$statsinfo[1] = $settings['statsurl']['config'];
	}

	$pattern = '/(STEAM_[01]:[01]:[0-9]{1,8})/';
		$page = (int)$_GET['page'];
		if ($page < 0)
			$page = 0;
		$pageSize = 100;
		$result = mysql_query('SELECT COUNT(*) as count FROM servers JOIN sm_logging ON servers.serverid = sm_logging.serverid WHERE sm_logging.steamid LIKE \''. $_GET['steamid'] .'\';');
		
		$start = $page * $pageSize;
		$row = mysql_fetch_array( $result );
		if ($start >= $row['count']) {
			$page = 0;
		}
		$start = $page * $pageSize;
		
		if ($start + $pageSize < $row['count'])
			$next = '?page='. ($page + 1) . '&steamid=' . $_GET['steamid'];
		if ($start - $pageSize >= 0)
			$prev = '?page='. ($page - 1) . '&steamid=' . $_GET['steamid'];
	
	echo '<div id="pagination" style="text-align:center">Page '. ($page + 1) .' of '. ceil($row['count'] / $pageSize);
	if (isset($prev)) {
		echo '<span style="float:left"><a href="'. $prev .'">&lt; Previous page</a></span>';
	}
	if (isset($next)) {
		echo '<span style="float:right"><a href="'. $next .'">Next page &gt;</a></span>';
	}
	//echo '<br />'."select servers.servername, sm_logging.* from servers join sm_logging on servers.serverid = sm_logging.serverid where sm_logging.steamid LIKE '" . $_GET['steamid'] . "' order by sm_logging.time_modified desc limit ". $start .",". $pageSize;
	echo "<table class=\"listtable\" align=\"left\">";
		echo "<tr class=\"headers\"><td class=\"adheaders\">Servername</td><td class=\"adheaders\">User</td><td class=\"adheaders\">Plugin</td><td class=\"adheaders\">Message</td><td class=\"adheaders\">Timestamp</td></tr>";

			
        $result = mysql_query( "select servers.servername, sm_logging.* from servers join sm_logging on servers.serverid = sm_logging.serverid where sm_logging.steamid LIKE '" . $_GET['steamid'] . "' order by sm_logging.time_modified desc limit ". $start .",". $pageSize ) or die(mysql_error());
        while( $row = mysql_fetch_array( $result ) ) {
                $servername = $row['servername'];
				$servername = str_replace( $settings['server_prefix']['config'], '', $row[ 'servername' ]);
                $steamid = $row['steamid'];
                $logtag = $row['logtag'];
                $message = htmlentities( $row['message'] );
                $time = $row['time_modified'];
                $name = $row['name'];
		    try {
		        $profile = SteamId::convertSteamIdToCommunityId($steamid);
		        }
			    catch(Exception $e) {
		            $profile = 'ERROR';
		        }
		preg_match_all($pattern, $message, $matches);
		$steamid2 = $matches[0][1];

		$statsinfo[3] = "right";
		if ($steamid != $steamid2 && $steamid2 != "") {
			$matchprofile = SteamId::convertSteamIdToCommunityId($steamid2);
			$statsinfo[2] = $steamid2;
			$matchurl = "<a href=http://steamcommunity.com/profiles/$matchprofile target=_blank>$steamid2></a>" . getstatsurl($statsinfo);
		}
		$message = str_replace($steamid2, $matchurl, $message);
		$statsinfo[2] = $steamid;
		echo "<tr class=\"elements\">
		<td width=\"200\" nowrap=\"nowrap\">$servername</td>
		<td width=\"200\" nowrap=\"nowrap\"><a href=\"http://steamcommunity.com/profiles/$profile\" title=\"$name\" target=_blank>$name" . getstatsurl($statsinfo) . "</a></td>
		<td width=\"120\" nowrap=\"nowrap\">$logtag</td>
		<td width=\"100%\">$message</td>
		<td width=\"120\" nowrap=\"nowrap\">$time</td>
		</tr>\n";
	}

	echo '</table><div style="clear:both;"></div></div>';

        mysql_close();
        bottom( $startTime );

?>
