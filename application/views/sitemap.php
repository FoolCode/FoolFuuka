<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php

foreach($sitemap as $item){
	
	echo '<url>

		<loc>'.$item["loc"].'</loc>';

		if($item["lastmod"])
		{
			echo '<lastmod>'.date(DATE_W3C, strtotime($item["lastmod"])).'</lastmod>';
		}
		
		echo '<changefreq>'.$item['changefreq'].'</changefreq>

		<priority>'.$item['priority'].'</priority>

	</url>';
}

?>
</urlset> 
