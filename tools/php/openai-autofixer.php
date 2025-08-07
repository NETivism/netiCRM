#!/usr/bin/php
<?php
if (!php_sapi_name() === 'cli') {
  fwrite(STDERR, "The script can only execute on cli interface.\n");
  exit(1);
}

function usage($long =  FALSE){
    $output = <<<'EOT'
      Usage: This tool parses error logs line by line, utilizes OpenAI to
      generate corrective code, and applies these changes to the respective files.
      Currently, only support one line auto replacement.

        php openai-autofixer.php \
          --input-file=<file_name> \
          --filename-match="<regex>" \
          --linenum-match="<regex>" \
          --prompt-file=<file_name> \
          --report-file=<file_name>
          --openai-keyfile=<file_name> \
          --context=<AnBn|func> \

    EOT;
  if ($long) {
    $output .= <<<'EOT'
        --input-file [required]: This is the input log file with each error logged on a separate line.
          Utilize --filename-match and --linenum-match to identify the code file you wish to correct.

        --filename-match [required]: Use this to match the filename from each log line.
          Examples:
          --filename-match="*.civicrm/(CRM.*php$)"
          --filename-match="^[^:]+"

        --linenum-match [required]: Use this to match the line number of a specific file.
          Examples:
          --linenum-match="line\s(\d+)"
          --linenum-match="^[^:]+:(\d+):"

        --prompt-file [required]: This is the prompt template file.
          Use {{code-block}} and {{log-line}} placeholders when parsing each log line.
          Example prompt file:
            You are a PHP programmer. Please help me using PHP rewrite below code based on error message. Return PHP program only without any note or explanation. The return PHP code should wrap with triple backtick.

            Code:
            ```
            {{code-block}}
            ```
            Error message:
            {{log-line}}

        --report-file [required]: This is the output report file.
          The log from the parsing process will be stored in this file. This may contain code samples in response to OpenAI queries.

        --openai-keyfile: Contains the content for the HTTP header: Authorization.
          Only send a request to OpenAI if this is provided. Otherwise, the command preview will be output.
          Utilize a format that's compatible with the HTTP header, based on OpenAI's documentation.
          Example:
            Bearer your_api_key

        --context: Use this to extract lines before or after specific lines for the GPT.
          Without any context, only a single line will be used as a prompt placeholder.
          Examples:
          --context=B10
            Extracts 10 lines before the specific line
          --context=A10
            Extracts 10 lines after the specific line
          --context=B3A10
            Extracts 3 lines before and 10 lines after the specific line
          --context=func
            Extracts from the closest function to the specific line
    EOT;
  }
  else {
    $output .= '
    Use --help to show full help.
';
  }
  fwrite(STDERR, $output."\n");
}

function prompt($logLine, $fileName, $lineNum, $promptTemplate, $params){
  $fileLines = [];
  $fileLines = file($fileName);
  if (!empty($params['context']) && $params['context'] != 'func') {
    if (!empty($params['context-before'])) {
      $before = ($lineNum-1) - $params['context-before'];
    }
    if (!empty($params['context-after'])) {
      $after = ($lineNum-1) + $params['context-after'] + 1;
    }
    if ($before && $after && isset($fileLines[$before]) && isset($fileLines[$after])) {
      $code = implode("", array_slice($fileLines, $before, $after-$before));
    }
    elseif ($before && isset($fileLines[$before]) && isset($fileLines[$lineNum-1])) {
      $code = implode("", array_slice($fileLines, $before, ($lineNum)-$before));
    }
    elseif ($after && isset($fileLines[$after]) && isset($fileLines[$lineNum-1])) {
      $code = implode("", array_slice($fileLines, $lineNum-1, $after-($lineNum-1)));
    }
  }
  elseif (!empty($params['context']) && $params['context'] == 'func') {
    $funcLine = NULL;
    for($i = $lineNum-1; $i>=0; $i--) {
      if (preg_match('/(public|private|protected)?\s?(static)?\s?function\s+[^(]+\(/', $fileLines[$i])) {
        $funcLine = $i;
        break;
      }
    }
    if (is_int($funcLine) && isset($fileLines[$funcLine]) && isset($fileLines[$lineNum-1])) {
      $code = implode("", array_slice($fileLines, $funcLine, ($lineNum)-$funcLine));
    }
  }
  else {
    // single line
    $code = trim($fileLines[$lineNum-1]);
  }
  return str_replace(['{{code-block}}', '{{log-line}}'], [$code, $logLine], $promptTemplate);
}

function request($prompt, $params, &$result) {
  $request = [
    'model' => $params['model'] ?? 'gpt-3.5-turbo',
    'temperature' => $params['temperature'] ?? 0,
    'messages' => [
      ['role' => 'user', 'content' => $prompt],
    ]
  ];
  if (!empty($params['max-tokens'])) {
    $request['max_tokens'] = $params['max-tokens'];
  }

  if (!empty($params['openai-keyfile']) && file_exists($params['openai-keyfile'])) {
    file_put_contents('/tmp/openai-autofixer.tmp', json_encode($request));
    $command = 'cat /tmp/openai-autofixer.tmp | http https://api.openai.com/v1/chat/completions Authorization:@'.$params['openai-keyfile'];
    $output = [];
    exec($command, $output);
    if (!empty($output[0])) {
      $decoded = json_decode(implode('', $output), TRUE);
      if (!empty($decoded['choices'][0]['message']['content'])) {
        $snippet = $decoded['choices'][0]['message']['content'];
        preg_match('/```(?:php)?(.*?)```/s', $snippet, $matches);
        if (!empty($matches[1])) {
          $result = trim($matches[1]);
        }
        else {
          $result = $snippet;
        }
        return TRUE;
      }
      else {
        $result = $output[0];
        return FALSE;
      }
    }
    else {
      $result = "Something wrong with openai output";
    }
    return FALSE;
  }
  else {
    $preview = "preview request commend:\n";
    $preview .= "echo -n '".json_encode($request)."'".'| http https://api.openai.com/v1/chat/completions Authorization:@<openai-keyfile>';
    $result = $preview;
    return FALSE;
  }
  return "";
}

function replace($new, $fileName, $lineNum, &$outcome) {
  $fileLines = [];
  $fileLines = file($fileName);
  $newLineCount = count(explode("\n",$new));
  if ($newLineCount == 1) {
    $old = $fileLines[$lineNum-1];
    preg_match('/^\s*/', $old, $matches);
    if (!empty($matches)) {
      $new = $matches[0].trim($new)."\n";
    }
    $fileLines[$lineNum-1] = $new;
    file_put_contents($fileName, implode("", $fileLines));
    $msg = "-".trim($old, "\n")."\n";
    $msg .= "+".trim($new, "\n");
    $outcome = $msg;
    return TRUE;
  }
  else {
    $msg = "We won't replace code because result code is more than 1 line. Here is OpenAI generated code:\n";
    $msg .= $new;
    $outcome = $msg;
    return FALSE;
  }
}

function parseArgv($argv, &$params) {
  $params['long-help'] = false;
  foreach($argv as $argument){
    if (preg_match('/^--([a-z-]+)=(.*)$/', $argument, $matches)) {
      $params[$matches[1]] = trim($matches[2], '"'."'");
    }
    elseif ($argument === '--help') {
      $params['long-help'] = true;
    }
  }
}

$validatedParams = [];
parseArgv($argv, $validatedParams);

$stdErrs = [];
if (empty($validatedParams['input-file'])) {
  $stdErrs[] = "Error: --input-file is required.";
}
elseif (!file_exists($validatedParams['input-file'])) {
  $stdErrs[] = "Error: File of --input-file is not a valid file.";
}
if (empty($validatedParams['filename-match'])) {
  $stdErrs[] = "Error: --filename-match is required.";
}
if (empty($validatedParams['linenum-match'])) {
  $stdErrs[] = "Error: --linenum-match is required.";
}

if (empty($validatedParams['prompt-file'])) {
  $stdErrs[] = "Error: --prompt-file is required.";
}
elseif (!file_exists($validatedParams['prompt-file'])) {
  $stdErrs[] = "Error: File of --prompt-file is not a valid file.";
}
else {
  $promptTemplate = file_get_contents($validatedParams['prompt-file']);
  if (!strstr($promptTemplate, '{{code-block}}') || !strstr($promptTemplate, '{{log-line}}')) {
    $stdErrs[] = "Error: Missing placeholder of your prompt file.\nPlace {{code-block}} and {{log-line}} into your prompt file.";
  }
}

if (empty($validatedParams['report-file'])) {
  $stdErrs[] = "Error: --report-file is required.";
}
elseif (!file_exists($validatedParams['report-file'])) {
  $reportFile = fopen($validatedParams['report-file'], 'a');
  if ($reportFile === FALSE) {
    $stdErrs[] = "Error: File of --report-file is not a valid file.";
  }
}
else {
  $reportFile = fopen($validatedParams['report-file'], 'a');
  if ($reportFile === FALSE) {
    $stdErrs[] = "Error: File of --report-file doesn't have write permission.";
  }
}

$checkHttpie = 'whereis httpie | grep "bin.*httpie" | wc -l';
$result = exec($checkHttpie);
if ($result !== '1') {
  $stdErrs[] = "Error: Missing required command httpie. Install from https://httpie.io/docs/cli/installation";
}

$checkUnCommitChanges = 'cd $PWD/../../ && git status -suno | grep -v "^ M neticrm$" | grep -v "^ M drupal$"';
$result = exec($checkUnCommitChanges);
if ($result !== "") {
  $stdErrs[] = "Error: You have uncommit changes. Using this script will alter your repository. Commit any changes before using this";
}

$tmpFile = file_put_contents('/tmp/openai-autofixer.tmp', "");
if ($tmpFile === FALSE) {
  $stdErrs[] = "Error: Make sure you have permission of /tmp directory to save tmp file /tmp/openai-autofixer.tmp";
}

if (!empty($stdErrs)) {
  $output = implode("\n", $stdErrs);
  fwrite(STDERR, $output."\n\n");
  usage($validatedParams['long-help']);
  exit(1);
}

if (!empty($validatedParams['context'])) {
  if(preg_match('/A(\d+)/', $validatedParams['context'], $matches)) {
    $validatedParams['context-after'] = $matches[1];
  }
  if(preg_match('/B(\d+)/', $validatedParams['context'], $matches)) {
    $validatedParams['context-before'] = $matches[1];
  }
}

$logs = file_get_contents($validatedParams['input-file']);
fwrite(STDERR, "Using this command to check report file\n");
fwrite(STDERR, "  tail -f {$validatedParams['report-file']}\n");
$logLines = explode("\n", $logs);
chdir(__DIR__.'/../../');
if (!empty($logLines)) {
  foreach($logLines as $num => $line) {
    $num = $num+1;
    fwrite(STDERR, "Processing line {$num}");
    fwrite($reportFile, "Processing line {$num}... ", 1000);
    if (empty(trim($line))) {
      fwrite($reportFile, "empty line. Skipped\n", 1000);
      continue;
    }
    if ($line[0] === '#') {
      fwrite($reportFile, "comment at start. Skipped\n", 1000);
      continue;
    }
    preg_match('/'.$validatedParams['linenum-match'].'/', $line, $matches);
    $lineNum = 0;
    if (!empty($matches[1]) && is_numeric($matches[1])) {
      $lineNum = $matches[1];
    }
    elseif (!empty($matches[0]) && is_numeric($matches[0])) {
      $lineNum = (int) $matches[0];
    }
    if (empty($lineNum)) {
      fwrite($reportFile, "cannot find line number. Skipped\n", 1000);
      continue;
    }

    $fileName = "";
    preg_match('/'.$validatedParams['filename-match'].'/', $line, $matches);
    if (!empty($matches[1]) && file_exists($matches[1])) {
      $fileName = $matches[1];
    }
    elseif (!empty($matches[0]) && file_exists($matches[0])) {
      $fileName = $matches[0];
    }
    if (empty($fileName)) {
      fwrite($reportFile, "cannot find filename or file not exists. Skipped\n", 1000);
      continue;
    }

    fwrite(STDERR, " of {$fileName}:$lineNum");
    fwrite($reportFile, $line."\n", 1000);

    $prompt = prompt($line, $fileName, $lineNum, $promptTemplate, $validatedParams);
    $result = "";
    $success = request($prompt, $validatedParams, $result);
    if ($success) {
      $successReplace = replace($result, $fileName, $lineNum, $outcome);
      fwrite($reportFile, $outcome."\n", 5000);
      if ($successReplace) {
        fwrite(STDERR, " ... Success\n");
      }
      else {
        fwrite(STDERR, " ... Failed\n");
      }
    }
    else {
      fwrite($reportFile, $result."\n", 5000);
      fwrite(STDERR, " ... No request, Skipped\n");
    }
  }
  fwrite(STDERR, "\n\nNOTE: Make sure review OpenAI replacement code before commit.\n");
}