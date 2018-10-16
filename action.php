<?php
/**
 * DokuWiki Plugin Numbered Headings; action component
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

if(!defined('DOKU_INC')) die();

class action_plugin_numberedheadings extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     */
    function register(Doku_Event_Handler $controller) {
        $controller->register_hook(
            'RENDERER_CONTENT_POSTPROCESS', 'AFTER', $this, '_tieredNumber'
        );
    }

    /**
     * RENDERER_CONTENT_POSTPROCESS
     * enclose tiered numbers for hierarchical headings in span tag
     */
    function _tieredNumber(Doku_Event $event) {
        if ($event->data[0] == 'xhtml') {
            $search = '/(<h\d.*?>)([\d.]+)(?: )/u'; // U+2007 figure space
            $replacement = '${1}<span class="tiered_number">${2}</span>'."\t";
            $event->data[1] = preg_replace($search, $replacement, $event->data[1]);
        }
    }

}
