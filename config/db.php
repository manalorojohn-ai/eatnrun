<?php
/**
 * Database Connection Bridge
 * This file exists so legacy require paths like config/db.php work
 * while the real database config lives under config/database/db.php.
 */

return require_once __DIR__ . '/database/db.php';
