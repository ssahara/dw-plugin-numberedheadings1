<?php
/**
 * DokuWiki Plugin Numbered Headings: add tiered numbers for hierarchical headings
 *
 * Usage:   ====== # Heading Level 1======
 *          ===== # Heading Level 2 =====
 *          ==== # Heading Level 3 ====
 *          ...
 *
 * =>       1. Heading Level 1
 *          1.1 Heading Level 2
 *          1.1.1 Heading Level 3
 *          ...
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Lars J. Metz <dokuwiki@meistermetz.de>
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

// must be run within DokuWiki
if(!defined('DOKU_INC')) die();

class syntax_plugin_numberedheadings extends DokuWiki_Syntax_Plugin {

    protected $startlevel, $tailingdot;

    function __construct() {
        // retrieve once config settings
        //   startlevel: upper headline level for hierarchical numbering (default = 2)
        //   tailingdot: show a tailing dot after numbers (default = 0)
        $this->startlevel = $this->getConf('startlevel');
        $this->tailingdot = $this->getConf('tailingdot');
    }

    function getType(){
        return 'substition';
    }

    function getSort() {
        return 45;
    }

    /**
     * Connect pattern to lexer
     */
    protected $mode, $pattern;

    function preConnect() {
        // syntax mode, drop 'syntax_' from class name
        $this->mode = substr(get_class($this), 7);

        // syntax pattern
        // Numbers only, no title headings : ex. "#1.", "#1.2", "#1.2.3"
        $this->pattern[0] = '^[ \t]*={2,} ?#\d+(?:\.\d*)* *={2,}[ \t]*(?=\n)';
        // Numbered headings : ex. "# Title", "#0 Title"
        $this->pattern[5] = '^[ \t]*={2,} ?#\d* [^\n]+={2,}[ \t]*(?=\n)';
    }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern($this->pattern[0], $mode, $this->mode);
        $this->Lexer->addSpecialPattern($this->pattern[5], $mode, $this->mode);
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {

        // counter for hierarchical numbering
        static $headingCount = [ 1 => 0, 2=> 0, 3 => 0, 4 => 0, 5 => 0];

        // get level of the heading
        $title = trim($match);
        $level = 7 - min(strspn($title, '='), 6);

        $title = trim($title, '= ');  // drop heading markup

        if ($title[0] == '#') {
        error_log(' ! heading Lv='.$level.' tier='.$tier.' param='.$param.' title='.$title);
            $title = substr($title, 1); // drop #
            $param = substr($title, 0, strspn($title, '.0123456789'));
            $title = ltrim(substr($title, strlen($param)));
            $tier = $level - $this->startlevel +1;
        }

        error_log(' ! heading Lv='.$level.' tier='.$tier.' param='.$param.' title='.$title);

        // param with tierd numbers
        if (strpos($param, '.') !== false) { // syntax pattern[0]
            // make current heading level as tier 1
            $this->startlevel = $level;
            // set number of tier 1, and clear numbers of sub-tier's level
            $numbers = explode('.', $param);
            foreach ($numbers as $k => $number) {
                $headingCount[$level + $k] = $number +0;
            }

        error_log(' ! heading counter:'.var_export($headingCount, 1));

            return false; // nothing rendered
        }

        // set number for current level, and clear numbers of sub-tier's level
        $headingCount[$level] = ($param) ? $param +0 : $headingCount[$level] +1;
        for ($i = $level +1; $i <= 5; $i++) {
            $headingCount[$i] = 0;
        }

        // build tiered numbers for hierarchical headings
        $numbers = array_slice($headingCount, $this->startlevel -1, $tier);
        $tieredNumber = implode('.', $numbers);
        if (count($numbers) == 1) {
                // append always tailing dot for single tiered number
                $tieredNumber .= '.';
        } elseif ($this->tailingdot) {
                // append tailing dot if wished
                $tieredNumber .= '.';
        }
        // append figure space after tiered number to distinguish title
        $tieredNumber .= ' '; // U+2007 figure space

        // revise the match
        $markup = str_repeat('=', 7 - $level);
        $match = $markup . $tieredNumber . $title . $markup;

        // ... and return to original behavior
        $handler->header($match, $state, $pos);

        return false;
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {
        //do nothing (already done by original render-method)
    }
}
