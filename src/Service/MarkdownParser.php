<?php

namespace SamFreeze\SymfonyCrudBundle\Service;

class MarkdownParser extends \Parsedown {
    protected function inlineLink($Excerpt)
    {
        $Element = parent::inlineLink($Excerpt);

        $Element['element']['attributes']['class'] = 'button is-primary';

        return $Element;
    }
}