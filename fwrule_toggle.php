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
$rule = &$config['filter']['rule'][$id];
if (isset($rule['disabled'])) {
  unset($rule['disabled']);
  $s = 'enabled';
} else {
  $rule['disabled'] = true;
  $s = 'disabled';
}

write_config();
filter_configure();
print("rule $id: $s\n");

?>
