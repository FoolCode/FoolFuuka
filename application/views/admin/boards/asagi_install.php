<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

echo nl2br(__("
Asagi is the Open Source (GPLv3) 4chan data fetcher coded by Eksopl. It can dump the threads, images and thumbnails from the Yotsuba boards in 4chan into your server for easy consumption.

If you're interested in using FoOlFuuka as a 4chan archive, you need to download, setup and run Asagi.

We can do the downloading and the setup, and all that is left to you is running it since we can't reliably run it off the web interface. FoOlFuuka will keep the settings of Asagi updated for you, so you can manage your archives through the boards manager.

Requirements: Java installed on your server.
"));
?>
<br/>
<?php

echo form_open();
echo form_submit(array('name' => 'install', 'value' => __('Install'), 'class' => 'btn btn-success btn-large'));
echo ' <a href="https://github.com/eksopl/asagi" target="_blank" class="btn btn-large">Go to the Asagi project page to know more.</a>';
echo form_close();
?>