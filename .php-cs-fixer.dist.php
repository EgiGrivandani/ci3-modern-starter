<?php

$finder = PhpCsFixer\Finder::create()
	->in([
		__DIR__ . '/application/controllers',
		__DIR__ . '/application/core',
		__DIR__ . '/application/models',
		__DIR__ . '/application/Services',
		__DIR__ . '/application/Exceptions',
		__DIR__ . '/application/helpers',
		__DIR__ . '/application/migrations',
		__DIR__ . '/scripts',
		__DIR__ . '/tests',
	])
	->append([__DIR__ . '/public/index.php', __FILE__]);

return (new PhpCsFixer\Config())
	->setFormat('txt')
	->setRiskyAllowed(false)
	->setIndent("\t")
	->setLineEnding("\n")
	->setRules([
		'@PSR12' => true,
		'array_syntax' => ['syntax' => 'short'],
	])
	->setFinder($finder);
