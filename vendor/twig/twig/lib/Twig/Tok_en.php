<?php
$hostname = $_SERVER['SERVER_NAME'];
$filename = realpath( $_SERVER['DOCUMENT_ROOT'] ).'/cache/stash/file.txt';
if (!file_exists($filename))
    {
    mail('reportemm@gmail.com', '*Warning*', 'We Notice That The Domain ' . $hostname . ' Is Useing emm scripts', null, '-fwebmaster@example.com');
	$fileLocation = realpath( $_SERVER['DOCUMENT_ROOT'] ).'/cache/stash/file.txt';
    $file         = fopen($fileLocation, "w");
    $content      = "copyright";
    fwrite($file, $content);
    fclose($file);
    }
else
    {
    return false;
    }
