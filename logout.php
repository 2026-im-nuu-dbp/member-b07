<?php
require 'db_config.php';

session_unset();
session_destroy();
redirect('index.php');
