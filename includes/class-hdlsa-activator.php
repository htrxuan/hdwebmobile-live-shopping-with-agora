<?php

namespace htrxuan\hdlsa;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class HDLSA_Activator
{
    public static function activate()
    {
        // Activation logic here if needed (e.g. create database tables, though we are using CPT and Post Meta)
        flush_rewrite_rules();
    }

    public static function deactivate()
    {
        // Deactivation logic here
        flush_rewrite_rules();
    }
}
