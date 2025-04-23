<?php

define('TESTING_FEATURE', $_ENV['TESTING_FEATURE']);
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', $_ENV['DB_PASS']);
define('DB_NAME', 'project_manager');

// OpenAI API configuration
define('OPENAI_API_KEY', $_ENV['OPENAI_API_KEY']);
define('OPENAI_MODEL', $_ENV['OPENAI_MODEL']);



