<?php
/**
 * Shows progress status from log
 */
header('Content-Type: application/json');
$services = array('fetch', 'structure', 'tokenize', 'augment', 'stem', 'score');
$data = array();

$fileName = "/var/log/kontextfuld/combine.log";
//$fileName = "log/log.txt";

//$cmd = "tail -n200 '/var/log/kontextfuld/combine.log'";
//$content = shell_exec($cmd);
$content  = tail($fileName, 200);
$lines = explode("\n", $content);

foreach ($lines as $line){    
    $line = strtolower($line);
    $linearr = explode(" ", $line);

    // Is completed?
    if ($linearr[1]=='done'){
        $serviceName = $linearr[0];
        $jobId = $linearr[2];
        
        if ($serviceName == 'fetch'){
            $data[$jobId]['fetch']['total'] = '100';
    		$data[$jobId]['fetch']['current'] = '100';
        }
        $data[$jobId][$serviceName]['completed'] = 'yes';
    }

    // Get max (should be)
    if ($linearr[1]=='should'){
        $serviceName = $linearr[0];
        $jobId = $linearr[4];
        
        $data[$jobId][$serviceName]['total'] = $linearr[3];
    }

    // Get current status
    if ($linearr[0]=='actual'){
        $serviceName = str_replace(":","",$linearr[1]);
        $jobId = $linearr[3];
        $data[$jobId][$serviceName]['current'] = $linearr[2];
    }
}

echo json_encode($data);

function tail ($file, $lines, $max_chunk_size = 4096 ){
 
  // We actually want to look for +1 newline so we can get the whole first line
  $rows = $lines + 1;
 
  // Open up the file
  $fh = fopen( $file, 'r' );
 
  // Go to the end
  fseek( $fh, 0, SEEK_END );
  $position = ftell( $fh );
 
  $buffer = '';
  $found_newlines = 0;
 
  $break = false;
  while( ! $break ) {
    // If we are at the start then we are done.
    if( $position <= 0 ) { break; }
 
    // We can't seek past the 0 position obviously, so figure out a good chunk size
    $chunk_size = ( $max_chunk_size > $position ) ? $position : $max_chunk_size;
 
    // Okay, now seek there and read the chunk
    $position -= $chunk_size;
    fseek( $fh, $position );
    $chunk = fread( $fh, $chunk_size );
 
    // See if there are any newlines in this chunk, count them if there are
    if( false != strpos( $chunk, "\n" ) ) {
      if( substr( $chunk, -1 ) == "\n" ) { ++$found_newlines; }
      $found_newlines += count( explode( "\n", $chunk ) );
    }
 
    // Have we exceeded our desired rows?
    if( $found_newlines > $rows ) { $break = true; }
 
    // Prepend
    $buffer = $chunk . $buffer;
  }
 
  // Now extract only the lines we requested
  $buffer = explode( "\n", $buffer );
  return implode( "\n", array_slice( $buffer, count( $buffer ) - $lines ) );
}


?>