<?php

namespace SamFreeze\SymfonyCrudBundle\Service;

class MarkdownParser extends \Parsedown {
    protected function inlineLink($Excerpt)
    {
        $Element = parent::inlineLink($Excerpt);

        
        return $Element;
    }
}