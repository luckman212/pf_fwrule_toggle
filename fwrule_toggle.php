<?php

/** based on
  https://www.reddit.com/r/PFSENSE/comments/usdlaf/anybody_have_a_shell_script_to_disableenable_a/
  https://forum.netgate.com/topic/51063/enable-disable-existing-rule-via-script/
**/

require_once("config.inc");
require_once("filter.inc");
global $config;
parse_config(true);

$id = intval($argv[1]) or exit("specify rule id\n");
$force = $argv[2];
$rule = &$config['filter']['rule'][$id];

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

write_config();
filter_configure();
print("rule $id: $s\n");

?>
