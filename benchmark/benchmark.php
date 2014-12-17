<?php
	require_once '../vendor/autoload.php';
	$firewind = new Firewind\core;

	// Читаем исходный текст //
	$source = file_get_contents( './source.html' );

	// Засекаем время начала //
	$begin_time = microtime( true );
	echo "Indexing started: $begin_time\n";

	// Индексирование //
	$index = $firewind->make_index( $source );

	// Засекаем время конца //
	$finish_time = microtime( true );
	echo "Indexing finished: $finish_time\n";

	// Результаты //
	$total_time = $finish_time - $begin_time;
	echo "Total time: $total_time\n";
?>