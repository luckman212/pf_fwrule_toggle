# pf_fwrule_toggle

Helper script for pfSense that lists rules and toggles them on/off via CLI. The script lists and operates on both `nat` and `filter` (standard) rule types.

# Setup

- copy the php script to your firewall—one easy way to do this is to ssh to your firewall, choose option 8 and then paste this command:
```
fetch https://raw.githubusercontent.com/luckman212/pf_fwrule_toggle/main/fwrule_toggle.php
```
- run it using the syntax below
- you need to supply one or more rule ids as a parameter, or `-l` to list all rules
- prefixing the ruleIDs with `n` e.g. `n3` will operate on NAT rule #3
- optionally set the action to `enable`, `disable`, or `toggle` (if not specified, it defaults to `toggle`)
- you can use the following long args instead, if you prefer: `--list`, `--desc`, `--help`
- if you place the script in your `$PATH` somewhere, e.g. `/root/bin/` and make it executable (`chmod +x /root/bin/fwrule_toggle.php`) then you can call the script directly, without prefixing with `php -q`

# Usage

## Specify rules by ID

This mode accepts 1 or more numeric rule IDs. You can prefix the number with `n` to select NAT rules, as opposed to filter rules which are the default.
```sh
php -q fwrule_toggle.php <ruleid>[,ruleid...] [action]
```
- `action` can be `enable`, `disable`, or `toggle`

## Specify rules by Description

This mode operates on the rule description. If multiple rules share an identical description, they will be operated on **as a group**.
```sh
php -q fwrule_toggle.php -d <rule_desc> [type] [action]
```
- `type` can be `nat` or `filter`—if omitted, defaults to filter
- if the description contains spaces, you must surround it with quotes e.g. `'my fancy rule'`

# Examples

## list rules
```sh
php -q fwrule_toggle.php -l
```

## toggle filter (standard) rule 36
```sh
php -q fwrule_toggle.php 36
```

## turn NAT rule 23 **on**
```sh
php -q fwrule_toggle.php n23 enable
```

## turn rules 5,11 and 17 **off**
```sh
php -q fwrule_toggle.php 5,11,17 disable
```

## disable all filter rules named `external access`
```sh
php -q fwrule_toggle.php -d 'external access' filter disable
```


# Based on

- https://www.reddit.com/r/PFSENSE/comments/usdlaf/anybody_have_a_shell_script_to_disableenable_a/
- https://forum.netgate.com/topic/51063/enable-disable-existing-rule-via-script/
- https://forum.netgate.com/topic/172552/list-or-toggle-rules-on-off-via-cli
