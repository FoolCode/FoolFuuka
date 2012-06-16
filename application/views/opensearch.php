<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/"
					   xmlns:moz="http://www.mozilla.org/2006/browser/search/">
	<ShortName><?= get_setting('fs_gen_site_title', FOOL_PREF_GEN_WEBSITE_TITLE); ?> Search</ShortName>
	<Description>Search on <?= site_url() ?>.</Description>
	<Url type="text/html" template="<?= site_url('@default/search/') ?>?text={searchTerms}&amp;page={startPage?}" />
	<LongName><?= get_setting('fs_gen_site_title', FOOL_PREF_GEN_WEBSITE_TITLE); ?> Search</LongName>
	<Image height="16" width="16" type="image/vnd.microsoft.icon"><?= site_url('@default/search/') ?>favicon.ico</Image>
	<OutputEncoding>UTF-8</OutputEncoding>
	<InputEncoding>UTF-8</InputEncoding>
</OpenSearchDescription>