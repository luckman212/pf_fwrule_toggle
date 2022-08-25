<?php

/** based on
  https://www.reddit.com/r/PFSENSE/comments/usdlaf/anybody_have_a_shell_script_to_disableenable_a/
  https://forum.netgate.com/topic/51063/enable-disable-existing-rule-via-script/
**/

require_once("config.inc");
require_once("filter.inc");
global $config;
parse_config(true);

if ($argv[1] == '-l') {
  $rules = $config['filter']['rule'];
  $natrules = $config['nat']['rule'];
  printf("[%s]\n", "Standard rules");
  foreach ($rules as $id => $rule) {
    $stat = (isset($rule['disabled']) ? '*' : ' ');
    printf("%4d %s %s (%s)\n", $id, $stat, $rule['interface'], $rule['descr']);
  }
  printf("[%s] (%s)\n", "NAT rules", "iface/desc, *=disabled");
  foreach ($natrules as $id => $rule) {
    $stat = (isset($rule['disabled']) ? '*' : ' ');
    printf("%4d %s %s (%s)\n", $id, $stat, $rule['interface'], $rule['descr']);
  }
  exit();
}

$id = $argv[1];
if (strpos($id, 'n') === 0) {
  $rtype = 'nat';
  $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
} else {
  $rtype = 'filter';
}
if ( !(ctype_digit($id)) || intval($id) < 0 ) {
  exit("specify a rule id (use e.g. `n3` for NAT rule) or -l to list rules\n");
}
$rule = &$config[$rtype]['rule'][$id];
$force = $argv[2];
if (isset($force)) {
  $s = ($force ? 'enabled' : 'disabled');
} else {
  $s = (isset($rule['disabled']) ? 'enabled' : 'disabled');
}

switch ($s) {
  case 'enabled':
    unset($rule['disabled']);
    break;
  case 'disabled':
    $rule['disabled'] = true;
    break;
  default:
    print('unknown');
}

$msg = "$rtype rule $id: $s";
write_config($msg);
filter_configure();
print("$msg\n");

?>
