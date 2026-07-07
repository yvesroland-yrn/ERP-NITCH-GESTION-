<?php
require 'config/config.php';
$db = getDB();
$result = $db->prepare("UPDATE tables_club SET statut='Occupée' WHERE id=1")->execute();
echo $result ? 'OK' : 'FAIL';
?>
