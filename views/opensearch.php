<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/"
					   xmlns:moz="http://www.mozilla.org/2006/browser/search/">
	<ShortName><?= \Preferences::get('ff.gen.website_title'); ?> Search</ShortName>
	<Description>Search on <?= \Uri::base() ?>.</Description>
	<Url type="text/html" template="<?= \Uri::create('_/search/') ?>?text={searchTerms}&amp;page={startPage?}" />
	<LongName><?= \Preferences::get('ff.gen.website_title'); ?> Search</LongName>
	<Image height="16" width="16" type="image/vnd.microsoft.icon"><?= \Uri::base() ?>favicon.ico</Image>
	<OutputEncoding>UTF-8</OutputEncoding>
	<InputEncoding>UTF-8</InputEncoding>
</OpenSearchDescription>