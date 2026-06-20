<?php
echo "PDO drivers: ";
print_r(PDO::getAvailableDrivers());
echo "<br>DB_HOST: " . getenv('DB_HOST');
echo "<br>DB_NAME: " . getenv('DB_NAME');
echo "<br>DB_USER: " . getenv('DB_USER');
echo "<br>DB_PASS: " . (getenv('DB_PASSWORD') ? 'ada' : 'kosong');
