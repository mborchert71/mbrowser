start "" php ../copy.php
start "" php -S 127.0.0.1:8005 -t "../../../.."
start "" http://127.0.0.1:8005/?filter=A
