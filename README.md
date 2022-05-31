# pf_fwrule_toggle

php helper script for pfSense that lists rules and toggles them on/off via CLI

# setup

- copy the php script to your firewall with scp or whatever other method suits you
- run it using the syntax below
- you need to supply a rule id as a parameter, or `-l` to list all rules
- optionally add a 0 or 1 to explicitly set a rule on/off
- otherwise, rule status will be toggled

# example usage

list rules
```
php -q fwrule_toggle.php -l
```

toggle rule 36
```
php -q fwrule_toggle.php 36
```

turn rule 23 **on**
```
php -q fwrule_toggle.php 23 1
```

turn rule 5 **off**
```
php -q fwrule_toggle.php 5 0
```

# based on

- https://www.reddit.com/r/PFSENSE/comments/usdlaf/anybody_have_a_shell_script_to_disableenable_a/
- https://forum.netgate.com/topic/51063/enable-disable-existing-rule-via-script/
- https://forum.netgate.com/topic/172552/list-or-toggle-rules-on-off-via-cli
