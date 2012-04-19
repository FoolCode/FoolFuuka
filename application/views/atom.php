<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
?>
<feed xmlns="http://www.w3.org/2005/Atom">

	<title><?php echo $feed_name; ?></title>
	<link href="<?php echo $feed_url; ?>"/>
	<updated><?php //echo date(DATE_W3C, strtotime($posts["chapters"][0]["created"])) ?></updated>
	<author>
		<name>Archive</name>
	</author>
	<id><?php echo site_url() ?></id>
	
	<?php foreach ($posts["threads"] as $entry): ?>
		<entry>
			<title><?php echo xml_convert($entry["title"]); ?></title>
			<link href="<?php echo $entry["href"] ?>"/>
			<id><?php echo $entry["href"] ?></id>
			<updated><?php echo date(DATE_W3C, strtotime($entry["created"])) ?></updated>
			<summary><?php if ($entry["thumb"])
		echo '<img src="' . $entry["thumb"] . '"  />' ?></summary>
		</entry>
	<?php endforeach; ?>

</feed>