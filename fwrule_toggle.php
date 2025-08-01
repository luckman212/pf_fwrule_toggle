#!/usr/bin/env php
<?php

/* based on
  https://www.reddit.com/r/PFSENSE/comments/usdlaf/anybody_have_a_shell_script_to_disableenable_a/
  https://forum.netgate.com/topic/51063/enable-disable-existing-rule-via-script/
*/

require_once("config.inc");
require_once("filter.inc");

$eids = array();
$dids = array();
$iids = array();

function show_help() {
  $helptext = sprintf('list rules:' . PHP_EOL .
    '  %1$s -l' . PHP_EOL .
    'modify rules:' . PHP_EOL .
    '  %1$s <ruleid>[,ruleid...] [enable|disable|toggle]' . PHP_EOL .
    '  %1$s -d <rule_desc> <nat|filter> [enable|disable|toggle]' . PHP_EOL .
    '  (prefix ruleid with `n` to operate on NAT rules)' . PHP_EOL,
    basename(__FILE__)
  );
  print($helptext);
  exit();
}

function rule_id_from_desc($desc, $type): array {
  $matched_ids = array();
  if (strlen($type) == 0) {
    $type = 'filter';
  }
  printf("looking for %s rules named `%s`\n", $type, $desc);
  $rules = config_get_path("{$type}/rule", []);
  foreach ($rules as $id => $rule) {
    // printf("processing a %s rule, id:%s if:%s desc:%s\n", $type, $id, $rule['interface'], $rule['descr']);
    if ($rule['descr'] === $desc) {
      $matched_ids[] = ($type === 'nat') ? "n$id" : "$id";
    }
  }
  return $matched_ids;
}

function validate_rule_ids($rule_ids) {
  foreach ($rule_ids as $id) {
    $id_test = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
    if (!ctype_digit($id_test) || intval($id_test) < 0) {
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
  global $eids, $dids, $iids;
  $oid = trim($oid);
  if ($oid === '') {
    return false;
  }
  $action = $action ?? 'toggle';
  $rtype = (strpos($oid, 'n') === 0) ? 'nat' : 'filter';
  $id = filter_var($oid, FILTER_SANITIZE_NUMBER_INT);
  $rule_path = "{$rtype}/rule/{$id}";
  $rule = config_get_path($rule_path, null);
  if ($rule === null) {
    printf("rule %s does not exist on this system\n", $oid);
    return false;
  }
  $is_disabled = config_path_enabled($rule_path, "disabled");
  $err = '';
  if ($action === 'toggle') {
    $action = $is_disabled ? 'enable' : 'disable';
  }
  switch ($action) {
    case 'enable':
      if ($is_disabled) {
        config_del_path("{$rule_path}/disabled");
        $eids[] = $oid;
      }
      break;
    case 'disable':
      if (!$is_disabled) {
        config_set_path("{$rule_path}/disabled", true);
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
    if ($desc === '') {
      print("description cannot be blank\n");
      show_help();
    }
    $rule_ids = rule_id_from_desc($desc, $argv[3]);
    $action = $argv[4];
    if (count($rule_ids) > 0) {
      printf("matched ids: %s\n", implode(',', $rule_ids));
      process_all_rules($rule_ids, $action);
    } else {
      print("no matching rules found\n");
    }
    break;
  case '-l':
  case '--list':
    $rules = config_get_path('filter/rule', []);
    $natrules = config_get_path('nat/rule', []);
    if (count($rules) > 0) {
      echo "[Standard (filter) rules, *=disabled]\n";
      foreach ($rules as $id => $rule) {
        $stat = isset($rule['disabled']) ? '*' : ' ';
        printf("%4d %s %s (%s)\n", $id, $stat, $rule['interface'], $rule['descr']);
      }
    }
    if (count($natrules) > 0) {
      echo "[NAT rules] (iface/desc, *=disabled)\n";
      foreach ($natrules as $id => $rule) {
        $stat = isset($rule['disabled']) ? '*' : ' ';
        printf("%4d %s %s (%s)\n", $id, $stat, $rule['interface'], $rule['descr']);
      }
    }
    exit();
  case '-h':
  case '--help':
    show_help();
  default:
    if (strlen($argv[1]) === 0) {
      show_help();
    }
    $rule_ids = explode(',', $argv[1]);
    $action = $argv[2];
    process_all_rules($rule_ids, $action);
    break;
}

if ((count($eids) + count($dids)) > 0) {
  $msg = "ruleset updated,";
  if (count($eids) > 0) {
    $msg .= " enabled:[" . implode(',', $eids) . "]";
  }
  if (count($dids) > 0) {
    $msg .= " disabled:[" . implode(',', $dids) . "]";
  }
  if (count($iids) > 0) {
    $msg .= " ignored:[" . implode(',', $iids) . "]";
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
