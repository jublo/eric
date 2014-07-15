<?php

/**
 * Eric demo file
 *
 * @package eric
 * @author jublonet
 * @link https://github.com/jublonet/eric
 * @copyright 2011
 * @access public
 */

// include Eric class
require_once dirname(__file__) . '/../src/eric.php';

// the mail content should be in UTF-8

// send text mail

Eric::send_mail($to = 'to@example.com', $subject = 'Test message text-only', $content =
    'A simple text-only mail.', $html = false, $from = 'sender@example.com', $from_name =
    'Sender name');

// send html mail

Eric::send_mail($to = 'to@example.com', $subject = 'Test message html', $content =
    'A simple <strong>html</strong> mail.', $html = true, $from =
    'sender@example.com', $from_name = 'Sender name');

// send mail with attachments

$arrFiles = array();
$arrFiles[] = array(__file__, 'demo.php.txt');

Eric::send_mail($to = 'to@example.com', $subject = 'Test message with attm', $content =
    'A mail with attachment.', $html = false, $from = 'sender@example.com', $from_name =
    'Sender name', $files = $arrFiles);

// send mail with inline html image

$arrFiles = array();
$arrFiles[] = array(__file__, 'demo.php.txt', false);
$arrFiles[] = array(dirname(__file__) . '/../demo-data/demo.png', 'demo.png', true); // third parameter = inline

Eric::send_mail($to = 'to@example.com', $subject =
    'Test message with inline image', $content =
    'A mail with attachment. <br /> <img src="cid:demo.png" width="100" height="45" alt="Demo image" />',
    $html = true, $from = 'sender@example.com', $from_name = 'Sender name', $files =
    $arrFiles);
