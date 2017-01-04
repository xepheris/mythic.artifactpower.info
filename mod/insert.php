<?php

include('dbcon.php');

$fetch = mysqli_fetch_array(mysqli_query($stream, "SELECT `c`, `r`, `s` FROM `ap` ORDER BY `lu` DESC LIMIT 1"));

$server = $fetch['s'];
$char = $fetch['c'];
$region = $fetch['r'];

$url = 'http://artifactpower.info/mythic/index.php?na=' .$char. '&reg=' .$region. '&ser=' .$server. '';
	
get_headers($url);

echo '<meta http-equiv="refresh" content="3;url=http://artifactpower.info/mythic/mod/insert.php" />';

?>