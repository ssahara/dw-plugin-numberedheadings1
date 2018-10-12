<?php
/**
 * Plugin Numbered Headings: Plugin to add numbered headings to DokuWiki-Syntax
 *
 * Usage:   ====== - Heading Level 1======
 *          ===== - Heading Level 2 =====
 *          ===== - Heading Level 2 =====
 *                   ...
 *
 * =>       1 Heading Level 1
 *              1.1 Heading Level 2
 *              1.2 Heading Level 2
 *          ...
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Lars J. Metz <dokuwiki@meistermetz.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class syntax_plugin_numberedheadings extends DokuWiki_Syntax_Plugin {

    // is now set in configuration manager
    var $startlevel = 0; // level to start with numbered headings (default = 2)
    var $tailingdot = 0; // show a tailing dot after numbers (default = 0)

    var $headingCount = [ 1 => 0, 2=> 0, 3 => 0, 4 => 0, 5 => 0];

    function __construct() {
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
    protected $mode;

    function preConnect() {
        // syntax mode, drop 'syntax_' from class name
        $this->mode = substr(get_class($this), 7);
    }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern(
                        '{{header>[1-5]}}', $mode, $this->mode);
        $this->Lexer->addSpecialPattern(
                        '{{startlevel>[1-5]}}', $mode, $this->mode);

        $this->Lexer->addSpecialPattern(
                        '^[ \t]*={2,6}\s?\-[^\n]+={2,6}[ \t]*(?=\n)', $mode, $this->mode);
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {

        // obtain the startlevel from the page if defined
        if (preg_match('/{{[a-z]{6,10}>([1-5]+)}}/', $match, $startlevel)) {
            $this->startlevel = $startlevel[1];
            return true;
        }

        // get level of the heading
        $title = trim($match);
        $level = 7 - strspn($title, '=');

        // obtain the startnumber if defined
        $title = trim($title, '= ');  // drop heading markup
        $title = ltrim($title, '- '); // not drop tailing -
        if ($title[0] == '#') {
            $title = substr($title, 1); // drop #
            $i = strspn($title, '0123456789');
            $number = substr($title, 0, $i) + 0;
            $title  = substr($title, $i);
            // set the number of the heading
            $this->headingCount[$level] = $number;
        } else {
            // increment the number of the heading
            $this->headingCount[$level]++;
        }

        // build the actual number
        $headingNumber = '';
        for ($i = $this->startlevel; $i <= 5; $i++) {

            // reset the number of the subheadings
            if ($i > $level) {
                $this->headingCount[$i] = 0;
            }

            // build the number of the heading
            $headingNumber .= ($this->headingCount[$i] != 0) ? $this->headingCount[$i].'.' : '';
        }

        // delete the tailing dot if wished (default)
        $headingNumber = ($this->tailingdot) ? $headingNumber : substr($headingNumber,0,-1);

        // revise the match
        $markup = str_repeat('=', 7 - $level);
        $match = $markup.' '.$headingNumber.' '.$title.' '.$markup;

        // ... and return to original behavior
        $handler->header($match, $state, $pos);

        return true;
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {
        //do nothing (already done by original render-method)
    }
}
