<?php
require 'vendor/autoload.php';
$sql = "SELECT e0_.id AS id_0, e0_.name AS name_1, e0_.slug AS slug_2, e0_.description AS description_3, e0_.capacity AS capacity_4, e0_.location AS location_5, e0_.started_at AS started_at_6, e0_.ended_at AS ended_at_7, e0_.image AS image_8, e0_.latitude AS latitude_9, e0_.longitude AS longitude_10 FROM event e0_ ORDER BY e0_.started_at DESC LIMIT 100";
$analyzer = new AhmedBhs\DoctrineDoctor\Analyzer\Parser\SqlPerformanceAnalyzer();
var_dump($analyzer->hasLimit($sql));
