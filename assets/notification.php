<?php
// Set headers to make browser recognize as MP3 file
header('Content-Type: audio/mpeg');
header('Content-Disposition: attachment; filename="notification.mp3"');

// Path to a simple notification sound (base64 encoded MP3)
 