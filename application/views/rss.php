<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
?>
<rss version="2.0"
	 xmlns:dc="http://purl.org/dc/elements/1.1/"
	 xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	 xmlns:admin="http://webns.net/mvcb/"
	 xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	 xmlns:content="http://purl.org/rss/1.0/modules/content/">

    <channel>

		<title><?php echo $feed_name; ?></title>

		<link><?php echo $feed_url; ?></link>
		<description><?php echo $page_description; ?></description>
		<dc:language><?php echo $page_language; ?></dc:language>

	<?php //	<admin:generatorAgent rdf:resource="http://trac.foolrulez.com/foolslide" /> ?>

		<?php foreach ($posts["threads"] as $entry): ?>

	        <item>

				<title><?php echo ($entry["title"])?$entry["title"]:'No title'; ?></title>
				<link><?php echo $entry["href"] ?></link>
				<guid><?php echo $entry["href"] ?></guid>

				<description><?php if ($entry["thumb"])
			echo '<img src="' . $entry["thumb"] . '"  />' ?></description>
				<pubDate><?php echo date("D, d M Y H:i:s O", $entry["created"]) ?></pubDate>
	        </item>


		<?php endforeach; ?>

    </channel>
</rss> 