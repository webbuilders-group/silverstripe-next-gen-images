<?php
use SilverStripe\View\Parsers\ShortcodeParser;
use WebbuildersGroup\NextGenImages\Shortcodes\ImageShortcodeProvider;

ShortcodeParser::get('default')->unregister('image');
ShortcodeParser::get('default')->register('image', [ImageShortcodeProvider::class, 'handle_shortcode']);
