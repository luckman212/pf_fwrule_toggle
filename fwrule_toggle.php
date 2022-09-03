<?php

/** based on
  https://www.reddit.com/r/PFSENSE/comments/usdlaf/anybody_have_a_shell_script_to_disableenable_a/
  https://forum.netgate.com/topic/51063/enable-disable-existing-rule-via-script/
**/

require_once("config.inc");
require_once("filter.inc");
parse_config(true);
$eids = array();
$dids = array();
$iids = array();

function show_help() {
  $helptext = sprintf('usage:' . "\n" .
    '  php -q %1$s <ruleid>[,ruleid...] [enable|disable|toggle]' . "\n" .
    '  php -q %1$s -d <rule_desc> <nat|filter> [enable|disable|toggle]' . "\n",
    basename(__FILE__)
  );
  print($helptext);
  exit();
}

function rule_id_from_desc($desc, $type): array {
  global $config;
  $matched_ids = array();
  if (strlen($type) == 0) {
    $type = 'filter';
  }  
  printf("looking for %s rules named `%s`\n", $type, $desc);
  $rules = $config[$type]['rule'];
  foreach ($rules as $id => $rule) {
    // printf("processing a %s rule, id:%s if:%s desc:%s\n", $type, $id, $rule['interface'], $rule['descr']);
    if ($rule['descr'] == $desc) {
      if ($type == 'nat') {
        $matched_ids[] = sprintf("n%d", $id);
      } else {
        $matched_ids[] = $id;
      }
    }
  }
  return $matched_ids;
}

function validate_rule_ids($rule_ids) {
  foreach ($rule_ids as $id) {
    $id_test = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
    if ( !(ctype_digit($id_test)) || intval($id_test) < 0 ) {
      print("invalid rule id: {$id}\n");
      return false;
    }
  }
  return true;
}

function process_all_rules($rule_ids, $action) {
  if (!validate_rule_ids($rule_ids)) {
    exit("specify only comma-separated, numeric rule ids (prefix with `n` for NAT rules)\n");
  }
  foreach ($rule_ids as $id) {
    process_rule($id, $action);
  }
}

function process_rule($oid, $action) {
  global $config, $eids, $dids, $iids;
  $oid = trim($oid);
  if (strlen($oid) == 0) {
    return false;
  }
  if (!isset($action)) {
    $action = 'toggle';
  }
  if (strpos($oid, 'n') === 0) {
    $rtype = 'nat';
  } else {
    $rtype = 'filter';
  }
  $id = filter_var($oid, FILTER_SANITIZE_NUMBER_INT);
  $rule = &$config[$rtype]['rule'][$id];
  if (!isset($rule)) {
    printf("rule %s does not exist on this system\n", $oid);
    return false;
  }
  if ($action == 'toggle') {
    $action = (isset($rule['disabled']) ? 'enable' : 'disable');
  }
  switch ($action) {
    case 'enable':
      if (isset($rule['disabled'])) {
        unset($rule['disabled']);
        $eids[] = $oid;
      }
      break;
    case 'disable':
      if (!isset($rule['disabled'])) {
        $rule['disabled'] = true;
        $dids[] = $oid;
      }
      break;
    default:
      $err = "(invalid action)";
      $iids[] = $oid;
      break;
  }
  printf("%s rule %s: %s %s\n", $rtype, $oid, $action, $err);
}

switch ($argv[1]) {
  case '-d':
  case '--desc':
    $desc = trim($argv[2]);
    if (strlen($desc) == 0) {
      print("description cannot be blank\n");
      show_help();
    }
    $rule_ids = rule_id_from_desc($desc, $argv[3]);
    $action = $argv[4];
    if (count($rule_ids) > 0) {
      printf("matched ids: %s\n", implode(',', $rule_ids));
      process_all_rules($rule_ids, $argv[4]);
    } else {
      print("no matching rules found\n");
    }
    break;
  case '-l':
  case '--list':
    $rules = $config['filter']['rule'];
    $natrules = $config['nat']['rule'];
    if (count($rules) > 0) {
      printf("[%s]\n", "Standard (filter) rules, *=disabled");
      foreach ($rules as $id => $rule) {
        $stat = (isset($rule['disabled']) ? '*' : ' ');
        printf("%4d %s %s (%s)\n", $id, $stat, $rule['interface'], $rule['descr']);
      }
    }
    if (count($natrules) > 0) {
      printf("[%s] (%s)\n", "NAT rules", "iface/desc, *=disabled");
      foreach ($natrules as $id => $rule) {
        $stat = (isset($rule['disabled']) ? '*' : ' ');
        printf("%4d %s %s (%s)\n", $id, $stat, $rule['interface'], $rule['descr']);
      }
    }
    exit();
  case '-h':
  case '--help':
    show_help();
  default:
    if (strlen($argv[1]) == 0) {
      show_help();
    }
    $rule_ids = explode(',', $argv[1]);
    $action = $argv[2];
    process_all_rules($rule_ids, $action);
    break;
}

if ((count($eids) + count($dids)) > 0) {
  $msg = "ruleset updated,";
  if (count($eids)>0) {
    $msg .= sprintf(" enabled:[%s]", implode(',', $eids));
  }
  if (count($dids)>0) {
    $msg .= sprintf(" disabled:[%s]", implode(',', $dids));
  }
  if (count($iids)>0) {
    $msg .= sprintf(" ignored:[%s]", implode(',', $iids));
  }
  filter_configure();
  write_config($msg);
} else {
  $msg = sprintf("script called but no changes were made to the ruleset, args: [%s]",
    implode('|', array_slice($argv, 1))
  );
  log_error($msg);
}

print("$msg\n");

?>
