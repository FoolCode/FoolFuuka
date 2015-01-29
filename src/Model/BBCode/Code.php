<?php

namespace Foolz\FoolFuuka\Model\BBCode;

class Code extends \JBBCode\CodeDefinition
{
    public function __construct()
    {
        parent::__construct();

        $this->setTagName('code');
        $this->setParseContent(false);
    }

    public function asHtml(\JBBCode\ElementNode $el)
    {
        $content = $this->getContent($el);

        if (preg_match('/\r|\n|\r\n/i', $content, $match)) {
            return '<pre>'.str_replace(["\r\n", "\n", "\r"], '<br>', $content).'</pre>';
        } else {
            return '<code>'.$content.'</code>';
        }
    }
}
