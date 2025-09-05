<?php
// logout.php
require_once 'config.php';
require_once 'helpers.php';

session_unset();
session_destroy();
json_response(['success' => true]);
