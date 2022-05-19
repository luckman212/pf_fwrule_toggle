# pf_fwrule_toggle

php helper script to toggle pfSense firewall rules on/off

# setup

- copy the php script to your firewall with scp or whatever other method suits you
- run it using the syntax below
- you need to supply a rule id as a parameter
- rules will be toggled on/off

# example usage

```
php -q fwrule_toggle.php 36
```

# based on

- https://www.reddit.com/r/PFSENSE/comments/usdlaf/anybody_have_a_shell_script_to_disableenable_a/
- https://forum.netgate.com/topic/51063/enable-disable-existing-rule-via-script/
