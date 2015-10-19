IF NOT EXIST "../index.php" copy index.php "../index.php"
start "cmd" php -S 127.0.0.1:80 -t ..
start "" http://127.0.0.1:80
