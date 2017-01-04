a<script src="js/jquery-1.10.1.min.js"></script>
<?php

include('dbcon.php');

if($_GET['max'] == 'all (warning: long loading time!)') {
	$limit = '100000';
}
if(isset($_GET['max']) && is_numeric($_GET['max'])) {
	$limit = $_GET['max'];
}
if(!isset($_GET['max'])) {
	$limit = '100';
} 

$active_window = time('now')-(60*60*24*7);
$valid_entries = mysqli_fetch_array(mysqli_query($stream, "SELECT COUNT(`id`) as `valid` FROM `mythic` WHERE `sum` != '0' AND `llog` >= '" .$active_window. "'"));
$total_entries = mysqli_fetch_array(mysqli_query($stream , "SELECT COUNT(`id`) AS `total` FROM `mythic`"));

echo '<div id="core">
<center>
<h1><a href="http://mythic.artifactpower.info/">Mythic Dungeon Comparison</a></h1>
<p>created by <a href="http://eu.battle.net/wow/en/character/blackmoore/Xepheris/simple">Xepheris (EU-Blackmoore)</a> | back to <a href="http://artifactpower.info">artifactpower.info</a></p>
<p>display criterias: online last 7 days, 1+ completed mythic dungeons.<br />Currently showing <u>top ' .$limit. ' out of ' .number_format($valid_entries['valid']). ' currently valid</u> entries (total entries: ' .number_format($total_entries['total']). ').</p>
<p>collected information about mythic dungeons available at <a href="https://mythicpl.us/">mythicpl.us</a></p>
</center>
<hr>';


function apicall($char, $server, $region) {
	global $stream;
	
	$char = ucwords(strtolower($char));
		
	$lastupdate = mysqli_fetch_array(mysqli_query($stream, "SELECT `lupd` FROM `mythic` WHERE `na` = '" .$char. "' AND `ser` = '" .$server. "' AND `reg` = '" .$region. "'"));
	$calcdiff = time('now')-$lastupdate['lupd'];
		
	if($calcdiff >= '60') {
		// ENABLE SSL
		$arrContextOptions=array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, ),);  
		
		// REMOVE SPACES IN SERVER NAME TO PREVENT BUGS IN URL
		if(strpos($server, ' ') !== false) {
			$server = str_replace(' ', '%20', $server);
		}
		// REMOVE SLASHES IN SERVER NAME TO ALLOW ACTUAL SEARCH AGAIN
		$server = stripslashes($server);
		// ARMORY API LINK ACHIEVEMENTS
		$url = 'https://' .$region. '.api.battle.net/wow/character/' .$server. '/' .$char. '?fields=statistics&locale=en_GB&apikey=KEY_HERE';
		$data = @file_get_contents($url, false, stream_context_create($arrContextOptions));
	
		if($data === FALSE) {
			echo '<p id="error">Sorry, either the armory is busy at the moment or the data you entered is simply wrong.<br />If you are sure your data is correct, retry. Here is what you entered:</p>
			<p id="error"><a href="http://' .$region. '.battle.net/wow/en/character/' .$server. '/' .$char. '/simple">"' .$char. ' (' .$region. ' - ' .$server. ')</a></p>';
		}
		elseif($data != '') {
			$data = json_decode($data, true);
			if($data['level'] == '110') {
				
				$llog = substr($data['lastModified'], '0', '10');
								
				$eoa = $data['statistics']['subCategories']['5']['subCategories']['6']['statistics']['2']['quantity'];
				$dht = $data['statistics']['subCategories']['5']['subCategories']['6']['statistics']['5']['quantity'];
				$nel = $data['statistics']['subCategories']['5']['subCategories']['6']['statistics']['8']['quantity'];
				$hov = $data['statistics']['subCategories']['5']['subCategories']['6']['statistics']['11']['quantity'];
				$vow = $data['statistics']['subCategories']['5']['subCategories']['6']['statistics']['20']['quantity'];
				$brh = $data['statistics']['subCategories']['5']['subCategories']['6']['statistics']['23']['quantity'];
				$mos = $data['statistics']['subCategories']['5']['subCategories']['6']['statistics']['26']['quantity'];
				$arc = $data['statistics']['subCategories']['5']['subCategories']['6']['statistics']['27']['quantity'];
				$cos = $data['statistics']['subCategories']['5']['subCategories']['6']['statistics']['28']['quantity'];
			
				if(strpos($server, '%20') !== false) {
					$server = str_replace('%20', ' ', $server);
				}
				
				$searcholduser = mysqli_fetch_array(mysqli_query($stream, "SELECT `id`, `na`, `reg`, `ser` FROM `mythic` WHERE `na` = '" .$char. "' AND `ser` = '" .addslashes($server). "' AND `reg` = '" .$region. "'"));

				// IF YES, UPDATE INFORMATION TO AVOID DUPLICATES
				if($searcholduser != '') {
										
					$updateuser = mysqli_query($stream, "UPDATE `mythic` SET `lupd` = '" .time('now'). "', `llog` = '" .$llog. "', `brh` = '" .$brh. "', `cos` = '" .$cos. "', `dht` = '" .$dht. "', `eoa` = '" .$eoa. "', `hov` = '" .$hov. "', `mos` = '" .$mos. "', `nel` = '" .$nel. "', `arc` = '" .$arc. "', `vow` = '" .$vow. "', `sum` = '" .($brh+$cos+$dht+$eoa+$hov+$mos+$nel+$arc+$vow). "' WHERE `id` = '" .$searcholduser['id']. "'");		
					
					$position = mysqli_fetch_array(mysqli_query($stream, "SELECT COUNT(`id`) AS `position` FROM `mythic` WHERE `sum` >= '" .($brh+$cos+$dht+$eoa+$hov+$mos+$nel+$arc+$vow). "' ORDER BY `sum` DESC"));
				
					echo '<p style="color: green; text-align: center;">Thanks for revisiting, your stats have been updated.</p><div id="t">
					<div id="tr"><div id="td">Position</div><div id="td">Character</div><div id="td">ARC</div><div id="td">BRH</div><div id="td">COS</div><div id="td">DHT</div><div id="td">EOA</div><div id="td">HOV</div><div id="td">MOS</div><div id="td">NEL</div><div id="td">VOW</div><div id="td">SUM</div></div>
					<div id="tr"><div id="td">' .$position['position']. '</div><div id="td"><a href="http://' .$region. '.battle.net/wow/en/character/' .$server. '/' .$char. '/simple">' .$char. ' (' .$region. '-' .$server. ')</a></div><div id="td">' .$arc. '</div><div id="td">' .$brh. '</div><div id="td">' .$cos. '</div><div id="td">' .$dht. '</div><div id="td">' .$eoa. '</div><div id="td">' .$hov. '</div><div id="td">' .$mos. '</div><div id="td">' .$nel. '</div><div id="td">' .$vow. '</div><div id="td">' .($brh+$cos+$dht+$eoa+$hov+$mos+$nel+$arc+$vow). '</div></div></div>';
				}
				else {
				// ELSE INSERT NEW USER
					$data1 = mysqli_query($stream, "INSERT INTO `mythic` (`lupd`, `llog`, `na`, `ser`, `reg`, `brh`, `cos`, `dht`, `eoa`, `hov`, `mos`, `nel`, `arc`, `vow`, `sum`) VALUES ('" .time('now'). "', '" .$llog. "', '" .$char. "', '" .addslashes($server). "', '" .$region. "', '" .$brh. "', '" .$cos. "', '" .$dht. "', '" .$eoa. "', '" .$hov. "', '" .$mos. "', '" .$nel. "', '" .$arc. "', '" .$vow. "', '" .($brh+$cos+$dht+$eoa+$hov+$mos+$nel+$arc+$vow). "')");	
					
					$position = mysqli_fetch_array(mysqli_query($stream, "SELECT COUNT(`id`) AS `position` FROM `mythic` WHERE `sum` >= '" .($brh+$cos+$dht+$eoa+$hov+$mos+$nel+$arc+$vow). "' ORDER BY `sum` DESC"));
					
					echo '<p style="color: green; text-align: center;">You have been added. Your data:</p>
					<div id="t">
					<div id="tr"><div id="td">Position</div><div id="td">Character</div><div id="td">ARC</div><div id="td">BRH</div><div id="td">COS</div><div id="td">DHT</div><div id="td">EOA</div><div id="td">HOV</div><div id="td">MOS</div><div id="td">NEL</div><div id="td">VOW</div><div id="td">SUM</div></div>
					<div id="tr"><div id="td">' .$position['position']. '</div><div id="td"><a href="http://' .$region. '.battle.net/wow/en/character/' .$server. '/' .$char. '/simple">' .$char. ' (' .$region. '-' .$server. ')</a></div><div id="td">' .$arc. '</div><div id="td">' .$brh. '</div><div id="td">' .$cos. '</div><div id="td">' .$dht. '</div><div id="td">' .$eoa. '</div><div id="td">' .$hov. '</div><div id="td">' .$mos. '</div><div id="td">' .$nel. '</div><div id="td">' .$vow. '</div><div id="td">' .($brh+$cos+$dht+$eoa+$hov+$mos+$nel+$arc+$vow). '</div></div></div>';
				}
				
			}
			// IF CHARACTER EXISTS BUT IS NOT 110
			elseif($data['level'] != '110') {
				echo '<p id="error">Sorry, this character is not level 110. Here is what you entered:</p>
			<p id="error"><a href="http://' .$region. '.battle.net/wow/en/character/' .$server. '/' .$char. '/simple">"' .$char. '" (' .$region. ' - ' .$server. ')</a></p>';
			}
		}
	}
	else {
		echo '<p id="error">Sorry, updating allowed only once every minute. Time left: ' .(60-$calcdiff). ' seconds.';
	}
}

if(isset($_POST['exec'])){
	if(strpos($server, "'") !== false) {
		$server = addslashes($server);
	}
	
	$char = $_POST['char'];
	$server = $_POST['server'];
	$region = $_POST['region'];

	apicall($char, $server, $region);
}

if(isset($_GET['upd']) && is_numeric($_GET['upd'])) {
	$data = mysqli_fetch_array(mysqli_query($stream, "SELECT `na`, `ser`, `reg` FROM `mythic` WHERE `id` = '" .$_GET['upd']. "'"));
	
	if(strpos($data['ser'], "'") !== false) {
		$data['ser'] = addslashes($data['ser']);
	}

	$char = $data['na'];
	$server = $data['ser'];
	$region = $data['reg'];
	
	apicall($char, $server, $region);
}

if(isset($_GET['ser']) && isset($_GET['reg']) && isset($_GET['na'])) {
	$char = $_GET['na'];
	$server = $_GET['ser'];
	$region = $_GET['reg'];
	
	apicall($char, $server, $region);
	die();
}

////////// RETRIEVAL FORM
$server_EU = array();
$server_US = array();

$regions = array('EU', 'US');
			
foreach($regions as $region) {
	$sql = mysqli_query($stream, "SELECT * FROM `server_" .$region. "` ORDER BY `server` ASC");
	while($servers = mysqli_fetch_array($sql)) {				
		$servervar = 'server_' .$region. '';
		array_push($$servervar, $servers['server']);					
	}
}

echo '<center>
<form action="" method="POST">
<input type="text" name="char" value="" placeholder="character name" maxlength="12"/>
<select name="region" id="region">';
			
foreach($regions as $region) {
	echo '<option value="' .$region. '">' .$region. '</option>';
}
			
echo '</select>
<select name="server" id="server">
</select>	
<button type="submit" name="exec">Retrieve</button>
</form>
<br />
<form action="" method="GET">
<select name="max" onChange="this.form.submit()">
<option selected disabled>Select amount of entries to be shown</option>';
$maxvalues = array('25', '100', '500', '1000', '10000', 'all (warning: long loading time!)');
foreach($maxvalues as $value) {	
	echo '<option value="' .$value. '">' .$value. '</option>';
}
echo '</select></form>
<br />
<form method="GET">Filter region and server
<select name="region2" id="region2">';
			
foreach($regions as $region) {
	echo '<option value="' .$region. '">' .$region. '</option>';
}
			
echo '</select>
<select name="server2" id="server2"></select>
<button type="submit">Filter</button>
</form>

</center>
<hr>';
//////////

$dungeons = array("The Arcway" => "ARC", "Black Rook Hold" => "BRH", "Court of Stars" => "COS", "Darkheart Thicket" => "DHT", "Eye of Azshara" => "EOA", "Halls of Valor" => "HOV", "Maw of Souls" => "MOS", "Neltharion's Lair" => "NEL", "Vault of the Wardens" => "VOW");
echo '<table>
<thead>
<td>#</td><td>Character</td>';

foreach($dungeons as $dungeon => $short) {	
	if(isset($_GET['server2']) && isset($_GET['region2'])) {
		$var = '' .$_SERVER['REQUEST_URI']. '&';
	}
	else {
		$var = '?';
	}
	echo '<td><a href="' .$var. 'sort=' .strtolower($short). '" title="' .$dungeon. '">' .$short. '</a></td>';
}

echo '<td><a href="index.php">TOTAL</a></td><td>Logged out (GMT +1)</td><td>Updated (click)</td>
</thead><tbody>';

if(isset($_GET['region2']) && isset($_GET['server2'])) {
	if(!isset($_GET['sort'])) {
		$_GET['sort'] = 'sum';
	}
	$sql = "SELECT * FROM `mythic` WHERE `sum` != '0' AND `llog` >= '" .$active_window. "' AND `reg` = '" .$_GET['region2']. "' AND `ser` = '" .addslashes($_GET['server2']). "' ORDER BY `" .$_GET['sort']. "` DESC LIMIT " .$limit. "";
}
elseif(!isset($_GET['server2']) && !isset($_GET['region2']) && !isset($_GET['sort'])) {
	$sql = "SELECT * FROM `mythic` WHERE `sum` != '0' AND `llog` >= '" .$active_window. "' ORDER BY `sum` DESC LIMIT " .$limit. "";
}
elseif(isset($_GET['sort'])) {
	$sql = "SELECT * FROM `mythic` WHERE `sum` != '0' AND `llog` >= '" .$active_window. "' ORDER BY `" .$_GET['sort']. "` DESC LIMIT " .$limit. "";
}


$data = mysqli_query($stream, $sql);
$num = '0';

while($indiv_data = mysqli_fetch_array($data)) {
	if ($num % 2 != 0) {
		$style = 'style="background-color: grey;"';
	}
	else {
		$style = '';
	}
	
	echo '
	<tr ' .$style. '><td>' .($num+1). '</td><td><a href="http://' .$indiv_data['reg']. '.battle.net/wow/en/character/' .$indiv_data['ser']. '/' .$indiv_data['na']. '/simple">' .$indiv_data['na']. ' (' .$indiv_data['reg']. '-' .$indiv_data['ser']. ')</a></td><td>' .$indiv_data['arc']. '</td><td>' .$indiv_data['brh']. '</td><td>' .$indiv_data['cos']. '</td><td>' .$indiv_data['dht']. '</td><td>' .$indiv_data['eoa']. '</td><td>' .$indiv_data['hov']. '</td><td>' .$indiv_data['mos']. '</td><td>' .$indiv_data['nel']. '</td><td>' .$indiv_data['vow']. '</td><td>' .$indiv_data['sum']. '</td><td>' .date('d.m.Y - H:i', $indiv_data['llog']). '</td><td><a href="?upd=' .$indiv_data['id']. '">' .date('d.m.Y - H:i', $indiv_data['lupd']). '</a></td></tr>';
	$num++;
}

echo '</tbody></table></div>';

?>


<script type="text/javascript">
server_EU=new Array("Aegwynn","Aerie Peak","Agamaggan","aggra-portugues","Aggramar","Ahn'Qiraj","Al'Akir","Alexstrasza","Alleria","Alonsus","Aman'Thul","Ambossar","Anachronos","Anetheron","Antonidas","Anub'arak","Arak-arahm","Arathi","Arathor","Archimonde","Area 52","Argent Dawn","Arthas","Arygos","Aszune","Auchindoun","Azjol-Nerub","Azshara","Azuremyst","Baelgun","Balnazzar","Blackhand","Blackmoore","Blackrock","Blade's Edge","Bladefist","Bloodfeather","Bloodhoof","Bloodscalp","Blutkessel","Boulderfist","Bronze Dragonflight","Bronzebeard","Burning Blade","Burning Legion","Burning Steppes","C'Thun","Chamber of Aspects","Chants \u00e9ternels","Cho'gall","Chromaggus","Colinas Pardas","Confr\u00e9rie du Thorium","Conseil des Ombres","Crushridge","Culte de la Rive Noire","Daggerspine","Dalaran","Dalvengyr","Darkmoon Faire","Darksorrow","Darkspear","Das Konsortium","Das Syndikat","Deathwing","Defias Brotherhood","Dentarg","Der abyssische Rat","Der Mithrilorden","Der Rat von Dalaran","Destromath","Dethecus","Die Aldor","Die Arguswacht","Die ewige Wacht","Die Nachtwache","Die Silberne Hand","Die Todeskrallen","Doomhammer","Draenor","Dragonblight","Dragonmaw","Drak'thul","Drek'Thar","Dun Modr","Dun Morogh","Dunemaul","Durotan","Earthen Ring","Echsenkessel","Eitrigg","Eldre'Thalas","Elune","Emerald Dream","Emeriss","Eonar","Eredar","Euskal Encounter","Executus","Exodar","Festung der St\u00fcrme","Forscherliga","Frostmane","Frostmourne","Frostwhisper","Frostwolf","Garona","Garrosh","Genjuros","Ghostlands","Gilneas","Gorgonnash","Grim Batol","Gul'dan","Hakkar","Haomarush","Hellfire","Hellscream","Hyjal","Illidan","Jaedenar","Kael'Thas","Karazhan","Kargath","Kazzak","Kel'Thuzad","Khadgar","Khaz Modan","Khaz'goroth","Kil'Jaeden","Kilrogg","Kirin Tor","Kor'gall","Krag'jin","Krasus","Kul Tiras","Kult der Verdammten","La Croisade \u00e9carlate","Laughing Skull","Les Clairvoyants","Les Sentinelles","Lightbringer","Lightning's Blade","Lordaeron","Los Errantes","Lothar","Madmortem","Magtheridon","Mal'Ganis","Malfurion","Malorne","Malygos","Mannoroth","Mar\u00e9cage de Zangar","Mazrigos","Medivh","Minahonda","Molten Core","Moonglade","Mug'thol","Nagrand","Nathrezim","Naxxramas","Nazjatar","Nefarian","Nemesis","Neptulon","Ner'zhul","Nera'thor","Nethersturm","Nordrassil","Norgannon","Nozdormu","Onyxia","Outland","Perenolde","Pozzo dell'Eternit\u00e0","Proudmoore","Quel'Thalas","Ragnaros","Rajaxx","Rashgarroth","Ravencrest","Ravenholdt","Rexxar","Runetotem","Sanguino","Sargeras","Saurfang","Scarshield Legion","Sen'jin","Shadowmoon","Shadowsong","Shattered Halls","Shattered Hand","Shattrath","Shen'dralar","Silvermoon","Sinstralis","Skullcrusher","Spinebreaker","Sporeggar","Steamwheedle Cartel","Stonemaul","Stormrage","Stormreaver","Stormscale","Sunstrider","Suramar","Sylvanas","Taerar","Talnivarr","Tarren Mill","Teldrassil","Temple noir","Terenas","Terokkar","Terrordar","The Maelstrom","The Sha'tar","The Venture Co","Theradras","Thrall","Throk'Feroth","Thunderhorn","Tichondrius","Tirion","Todeswache","Trollbane","Turalyon","Twilight's Hammer","Twisting Nether","Tyrande","Uldaman","Uldum","Un'Goro","Varimathras","Vashj","Vek'lor","Vek'nilash","Vol'jin","Warsong","Wildhammer","Wrathbringer","Xavius","Ysera","Ysondre","Zenedar","Zirkel des Cenarius","Zul'jin","Zuluhed","\u0410\u0437\u0443\u0440\u0435\u0433\u043e\u0441","\u0411\u043e\u0440\u0435\u0439\u0441\u043a\u0430\u044f \u0442\u0443\u043d\u0434\u0440\u0430","\u0412\u0435\u0447\u043d\u0430\u044f \u041f\u0435\u0441\u043d\u044f","\u0413\u0430\u043b\u0430\u043a\u0440\u043e\u043d\u0434","\u0413\u043e\u043b\u0434\u0440\u0438\u043d\u043d","\u0413\u043e\u0440\u0434\u0443\u043d\u043d\u0438","\u0413\u0440\u043e\u043c","\u0414\u0440\u0430\u043a\u043e\u043d\u043e\u043c\u043e\u0440","\u041a\u043e\u0440\u043e\u043b\u044c-\u043b\u0438\u0447","\u041f\u0438\u0440\u0430\u0442\u0441\u043a\u0430\u044f \u0431\u0443\u0445\u0442\u0430","\u041f\u043e\u0434\u0437\u0435\u043c\u044c\u0435","\u0420\u0430\u0437\u0443\u0432\u0438\u0439","\u0420\u0435\u0432\u0443\u0449\u0438\u0439 \u0444\u044c\u043e\u0440\u0434","\u0421\u0432\u0435\u0436\u0435\u0432\u0430\u0442\u0435\u043b\u044c \u0414\u0443\u0448","\u0421\u0435\u0434\u043e\u0433\u0440\u0438\u0432","\u0421\u0442\u0440\u0430\u0436 \u0421\u043c\u0435\u0440\u0442\u0438","\u0422\u0435\u0440\u043c\u043e\u0448\u0442\u0435\u043f\u0441\u0435\u043b\u044c","\u0422\u043a\u0430\u0447 \u0421\u043c\u0435\u0440\u0442\u0438","\u0427\u0435\u0440\u043d\u044b\u0439 \u0428\u0440\u0430\u043c","\u042f\u0441\u0435\u043d\u0435\u0432\u044b\u0439 \u043b\u0435\u0441");server_US=new Array(<?php echo str_replace(array('[', ']'), '', htmlspecialchars(json_encode($server_US), ENT_NOQUOTES)); ?>);
		
populateSelect();
			
$(function() {
	$('#region').change(function(){
		populateSelect();
	});
	$('#region2').change(function(){
		populateSelect();
	});
});
			
function populateSelect(){
	region=$('#region').val();
	$('#server').html('');
		
	if(region=='EU'){
		server_EU.forEach(function(t) { 
			$('#server').append('<option>'+t+'</option>');
		});
	}
		
	if(region=='US'){
		server_US.forEach(function(t) {
			$('#server').append('<option>'+t+'</option>');
		});
	}
	
	region2=$('#region2').val();
	$('#server2').html('');
		
	if(region=='EU'){
		server_EU.forEach(function(t) { 
			$('#server2').append('<option>'+t+'</option>');
		});
	}
		
	if(region2=='US'){
		server_US.forEach(function(t) {
			$('#server2').append('<option>'+t+'</option>');
		});
	}
}
</script>
<script src="js/jquery.floatThead.min"></script>
<script type="text/javascript">
	var $table = $('table');
	$table.floatThead();
</script>