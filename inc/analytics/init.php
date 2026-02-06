<?php
/**
 * ParkourONE Analytics - Initialisierung
 *
 * @package ParkourONE
 */

if (!defined('ABSPATH')) {
	exit;
}

// Klasse laden
require_once __DIR__ . '/class-analytics.php';

// Initialisieren
PO_Analytics::get_instance();
