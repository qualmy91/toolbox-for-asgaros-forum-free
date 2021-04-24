<?php


namespace Tfaf\Inc\Base;

class Deactivate
{
    /**
     * Deactivate the plugin
     */
    public static function deactivate()
    {
        flush_rewrite_rules();
    }

}