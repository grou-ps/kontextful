<?php

define('FACEBOOK_APPID', '');
define('FACEBOOK_SECRET', '');

define('AMQP_HOST','backend1.kontextful');
define('AMQP_PORT',5672);
define('AMQP_USER','guest');
define('AMQP_PASS','guest');
define('AMQP_VHOST', '/');
define('AMQP_DEBUG', false);
define('AMQP_EXCHANGE', 'kontextfuld_exchange_3');

define('PIPE_TYPE', 'file'); // either stdin or file
define('DATA_DIR', __DIR__ . '/../data/');
define('LOG_DATA',  true); // WARNING: never turn off (Persist depends on Fetch's groups.log file) 
define('KONTEXTFULD_LOG_DIR', __DIR__ . '/../logs/kontextfuld.log');
define('STOPWORDS_FILE', __DIR__ . '/../assets/db/stopwords/english.txt');

define("LOG_OUTPUT", 'stdout'); // none, stdout or a file name

define("WORDNET_ENABLED", true);
define("WORDNETD_HOST", "db1.kontextful");
define("WORDNETD_PORT", 4444);


define("REDIS_ENABLED", true);
define("REDIS_HOST", "db1.kontextful");
define("REDIS_PORT", 6379);

define("MYSQL_HOST", "db1.kontextful");
define("MYSQL_PORT", 3306);
define("MYSQL_DB", "kontextful");
define("MYSQL_USER", "root");
define("MYSQL_PASS", "");

// how long the Normalize worker should wait for more input.
// default 5*60 (5 minutes) 
// set 0 for testing.
define("WAIT_TIME", 3*60);

define("LEGIT_CODES", json_encode(array()));

define("COMBINER_RED_ALERT_THRESHOLD", 60*60*3); // 3 hours

define('COMPLETION_ROUNDING_ERROR', 0.9);


define('NOTIFY_USER', true);
define('NOTIFY_ADMINS', json_encode(array("")));
define('SMTP_HOST', 'smtp.mandrillapp.com');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');

