<?PHP

namespace ZYX;

const KNOWN_TYPE = ['definition', 'number', 'string', 'null', 'boolean', 'text', 'uuid'];
const SCHEMA_BOOL = [ 'STRUCTURAL', 'AUXILIARY', 'ABSTRACT', 'SINGLE-VALUE', 'OBSOLETE', 'COLLECTIVE', 'NO-USER-MODIFICATION' ];
const LDAP_SYNTAXES = [
  '1.3.6.1.4.1.1466.115.121.1.4' => [ 'name' => 'audio', 'binary' => true ],
  '1.3.6.1.4.1.1466.115.121.1.5' => [ 'name' => 'binary', 'binary' => true ],
  '1.3.6.1.4.1.1466.115.121.1.6' => [ 'name' => 'bistring', 'binary' => true ],
  '1.3.6.1.4.1.1466.115.121.1.7' => [ 'name' => 'boolean', 'binary' => true ],
  '1.3.6.1.4.1.1466.115.121.1.8' => [ 'name' => 'cert', 'binary' => true ],
  '1.3.6.1.4.1.1466.115.121.1.9' => [ 'name' => 'cert-list', 'binary' => true ],
  '1.3.6.1.4.1.1466.115.121.1.10' => [ 'name' => 'cert-pair', 'binary' => true ],
  '1.3.6.1.4.1.4203.666.11.10.2.1' => [ 'name' => 'x509-attr', 'binary' => true ],
  '1.3.6.1.4.1.1466.115.121.1.12' => [ 'name' => 'dn', 'binary' => false ],
  '1.2.36.79672281.1.5.0' => [ 'name' => 'rdn', 'binary' => false ],
  '1.3.6.1.4.1.1466.115.121.1.14' => [ 'name' => 'delivery-method', 'binary' => false ],
  '1.3.6.1.4.1.1466.115.121.1.15' => [ 'name' => 'directory-string', 'binary' => false ],
  '1.3.6.1.4.1.1466.115.121.1.22' => [ 'name' => 'fax-number', 'binary' => false ],
  '1.3.6.1.4.1.1466.115.121.1.24' => [ 'name' => 'generalized-time', 'binary' => false ],
  '1.3.6.1.4.1.1466.115.121.1.26' => [ 'name' => 'ia5-string', 'binary' => false ],
  '1.3.6.1.4.1.1466.115.121.1.27' => [ 'name' => 'integer', 'binary' => false ],
  '1.3.6.1.4.1.1466.115.121.1.28' => [ 'name' => 'jpeg', 'binary' => true ],
  '1.3.6.1.4.1.1466.115.121.1.34' => [ 'name' => 'name-uid', 'binary' => false ],
  '1.3.6.1.4.1.1466.115.121.1.36' => [ 'name' => 'numeric-string', 'binary' => false ],
  '1.3.6.1.4.1.1466.115.121.1.38' => [ 'name' => 'oid', 'binary' => false ],
  '1.3.6.1.4.1.1466.115.121.1.39' => [ 'name' => 'other-mailbox', 'binary' => false ],
  '1.3.6.1.4.1.1466.115.121.1.40' => [ 'name' => 'octet-string', 'binary' => false ],
  '1.3.6.1.4.1.1466.115.121.1.41' => [ 'name' => 'postal-address', 'binary' => false ],
  '1.3.6.1.4.1.1466.115.121.1.44' => [ 'name' => 'printable-string', 'binary' => false ],
  '1.3.6.1.4.1.1466.115.121.1.11' => [ 'name' => 'country-string', 'binary' => false ],
  '1.3.6.1.4.1.1466.115.121.1.45' => [ 'name' => 'subtree-desc', 'binary' => false ],
  '1.3.6.1.4.1.1466.115.121.1.49' => [ 'name' => 'supported-algorithm', 'binary' => true ],
  '1.3.6.1.4.1.1466.115.121.1.50' => [ 'name' => 'phone-number', 'binary' => false ],
  '1.3.6.1.4.1.1466.115.121.1.52' => [ 'name' => 'telex-number', 'binary' => false ],  
  '1.3.6.1.1.1.0.0' => [ 'name' => 'nis-triple', 'binary' => false ],
  '1.3.6.1.1.1.0.1' => [ 'name' => 'boot-parameter', 'binary' => false ],
  '1.3.6.1.1.16.1' => [ 'name' => 'uuid', 'binary' => false ]
];
class ZYXObject {
  /* conversion from app side to ldap side is done on writing which is less often */
  protected $AttrNameMap = [
    'zyxUuid' => 'uuid',
    'zyxReference' => 'reference',
    'zyxType' => 'type'
  ];
  protected $OptionNameMap = [
    'lang' => 'language',
    'max' => 'maximum',
    'min' => 'minimum',
    'uprefix' => 'siprefix'
  ];

  protected $ClassNameMap = [
    'object' => 'zyxObject'
  ];
  protected $Servers = [
    'ro' => [],
    'rw' => []
  ];

  /* attribue => forced prefix */
  protected $NumberPrefix = [
  ];
 
  function addForcedPrefix ($attribute, $prefix) {
    switch ($prefix) {
      case 'Y':
      case 'Z':
      case 'E':
      case 'P':
      case 'T':
      case 'G':
      case 'M':
      case 'k':
      case 'h':
      case 'da':
      case 'd':
      case 'c':
      case 'm':
      case 'u':
      case 'n':
      case 'p':
      case 'a':
      case 'z':
      case 'y':
        $this->NumberPrefix[$attribute] = $prefix;
        return true;
    }
    return false;
  }
  
  /* normalize unicode string */
  function strAttrCheck (&$attr) {
    if (!is_string($attr)) { return false; }
    if (!preg_match('/^[a-zA-Z0-9\-;]+$/', $attr)) { return false; }
    $attr = \Normalizer::normalize($attr, \Normalizer::FORM_KC);
    return true;
  }

  /* forward map : appoc -> ldapoc */
  function mapClassName ($appoc) {
    $objectName = array_search($appoc, $this->ClassNameMap, true);
    if (!$objectName) { return $appoc; }
    return $objectName;
  }
  
  /* reverse map : ldapoc -> appoc */
  function rmapClassName($ldapoc) {
    if (!empty($this->ClassNameMap[$ldapoc]) && is_string($this->ClassNameMap[$ldapoc])) {
      return $this->ClassNameMap[$ldapoc];
    }
    return $ldapoc;
  }

  /* forward map : appoption -> ldapoption */
  function mapOptionName ($appoption) {
    $optionName = array_search($appoption, $this->OptionNameMap, true);
    if (!$optionName) { return $appoption; }
    return $optionName;
  }
  
  /* reverse map : ldapoption -> appoption */
  function rmapOptionName ($ldapoption) {
    if (!empty($this->OptionNameMap[$ldapoption]) && is_string($this->OptionNameMap[$ldapoption])) {
      return $this->OptionNameMap[$ldapoption];
    }
    return $ldapoption;
  }

  /* forward map : appname -> ldapname */
  function mapAttrName ($appname) {
    $attrName = array_search($appname, $this->AttrNameMap, true);
    if (!$attrName) { return $appname; }
    return $attrName;
  }

  /* reverse map : ldapname -> appname */
  function rmapAttrName ($ldapname) {
    if (!empty($this->AttrNameMap[$ldapname]) && is_string($this->AttrNameMap[$name])) {
      return $this->AttrNameMap[$ldapname];
    }
    return $ldapname;
  }

  function mapLdapType ($ldaptype) {
    switch ($ldaptype['name']) {
      default:
        if ($ldaptype['binary']) { return 'binary'; }
        else { return 'string'; }
      case 'integer':
        return 'number';
      case 'uuid':
        return 'uuid';
    }
  }
  
  function prefixMultiple ($prefix, $inv = false) {
    switch ($prefix) {
      case 'Y': return pow(10, $inv ? -21 : 24);
      case 'Z': return pow(10, $inv ? -21 : 21);
      case 'E': return pow(10, $inv ? -18 : 18);
      case 'P': return pow(10, $inv ? -15 : 15);
      case 'T': return pow(10, $inv ? -12 : 12);
      case 'G': return pow(10, $inv ? -9 : 9);
      case 'M': return pow(10, $inv ? -6 : 6);
      case 'k': return pow(10, $inv ? -3 : 3);
      case 'h': return pow(10, $inv ? -2 : 2);
      case 'da': return pow(10, $inv ? -1 : 1);
      case 'd': return pow(10, $inv ? 1 : -1);
      case 'c': return pow(10, $inv ? 2 : -2);
      case 'm': return pow(10, $inv ? 3 : -3);
      case 'u': return pow(10, $inv ? 6 : -6);
      case 'n': return pow(10, $inv ? 9 : -9);
      case 'p': return pow(10, $inv ? 12 : -12);
      case 'f': return pow(10, $inv ? 15 : -15);
      case 'a': return pow(10, $inv ? 18 : -18);
      case 'z': return pow(10, $inv ? 21 : -21);
      case 'y': return pow(10, $inv ? 24 : -24);
    }
  }
  /* only integer allowed */
  function numberNormalize(&$attribute, $number) {
    $attrParts = explode(';', $attribute);

    $wPrefix = empty($this->NumberPrefix[$attrParts[0]]) ? null : $this->NumberPrefix[$attrParts[0]];
    $cPrefix = '';
    $prPos = 0;
    for ($i = 1; !empty($attrParts[$i]); $i++) {
      $opt = explode('=', $attrParts[$i]);
      if ($opt[0] === 'uprefix') {
        $cPrefix = $opt[1];
        $prPos = $i;
        break;
      }
    }

    $number = floatval($number);
    /* no forced prefix and no need for prefix */
    if ($wPrefix === null && floatval(intval($number)) === $number) {
      return intval($number);
    }

    /* forced prefix */
    if ($wPrefix !== null) {
      if ($cPrefix !== '') { $number *= $this->prefixMultiple($cPrefix); }
      $number = intval(round($number * $this->prefixMultiple($wPrefix, true), 0));
      if ($prPos > 0) {
        $attrParts[$prPos] = 'uprefix=' . $wPrefix;
      } else {
        $attrParts[] = 'uprefix=' . $wPrefix;
      }
      $attribute = implode(';', $attrParts);
      return $number;
    }

    /* no forced prefix, go up each prefix until the best one is found */
    if ($cPrefix !== '') { $number *= $this->prefixMultiple($cPrefix); }
    $prefix = null;
    foreach(['d', 'c', 'm', 'u', 'n', 'p', 'f', 'a', 'z', 'y'] as $p) {
      $m = $this->prefixMultiple($p, true);
      if (floatval(intval($number * $m)) === $number * $m) {
        $prefix = $p;
        break;
      }
    }

    /* if none fit the biggest is needed */
    if ($p === null) {
      $p = 'Y';
    }
    if ($prPos > 0) {
      $attrParts[$prPos] = 'uprefix=' . $p;
    } else {
      $attrParts[] = 'uprefix=' . $p;
    }
    $attribute = implode(';', $attrParts);
    return intval($number * $this->prefixMultiple($p, true));
  }

  /* setup connection (with bind and all) and add server to server pool */
  function addServer ($uri, $user, $pw, $readonly = false) {
    $conn = ldap_connect($uri);
    if (!$conn) { return false; }

    $dseResult = ldap_read($conn, '', '(objectclass=*)', ['+']);
    if (!$dseResult) { return false; }
    $dse = ldap_get_entries($conn, $dseResult);
    if (empty($dse) || $dse['count'] <= 0) { return false; }
    $dse = $dse[0]; // don't handle more than one 

    if (empty($dse['supportedldapversion']) ||
        $dse['supportedldapversion']['count'] <= 0) {
      return false;
    }
    if (!ldap_set_option(
      $conn,
      \LDAP_OPT_PROTOCOL_VERSION,
      $dse['supportedldapversion'][0]
    )) {
      ldap_close($conn);
      return false;
    }
    
    if (empty($dse['namingcontexts']) || $dse['namingcontexts']['count'] <= 0) {
      ldap_close($conn);
      return false;
    }

    if (!ldap_bind($conn, $user, $pw)) {
      ldap_close($conn);
      return false;
    }

    /* load schema for this server */
    $this->Schemas = [];
    if (!empty($dse['subschemasubentry']) && $dse['subschemasubentry']['count'] > 0) {
      $schemas = [];
      for ($i = 0; $i < $dse['subschemasubentry']['count']; $i++) {
        $result = ldap_read($conn, $dse['subschemasubentry'][$i], '(objectclass=*)', ['+']);
        if (!$result) { continue; }
        $schemas = ldap_get_entries($conn, $result);
        if (empty($schemas) || $schemas['count'] <= 0) { continue; }
        foreach ($schemas as $schema) {
          foreach (['objectclasses', 'attributetypes'] as $type) {
            if (empty($schema[$type]) || $schema[$type]['count'] <= 0) { continue; }
            if (!empty($this->Schemas[$type])) { $this->Schemas[$type] = []; }
            for ($j = 0; $j < $schema[$type]['count']; $j++) {
              $_schemas = $this->parseSchemaEntry($schema[$type][$j]);
              foreach ($_schemas as $_schema) {
                if ($_schema[0] !== null) {
                  $this->Schemas[$type][$_schema[0]] = $_schema[1];
                }
              }
            }
          }
        }
      }
    }

    $server = ['conn' => $conn, 'base' => []];
    for ($i = 0; $i < $dse['namingcontexts']['count']; $i++) {
      $server['base'][] = $dse['namingcontexts'][$i];
    }
    $this->Servers[$readonly ? 'ro' : 'rw'][] = $server;
  }

  function queryAttrType ($server, $name) {
    if (empty($server->Schemas)) { return false; }
    if (empty($server->Schemas['attributetypes'])) { return false; }
    $name = strtowlower($name);     
    if (empty($server->Schemas['attributetypes'][$name])) { return false; }

    while (!empty($server->Schemas['attributetypes'][$name]['_ref'])) {
      $name = $server->Schemas['attributetypes'][$name]['_ref'];
      if (empty($server->Schemas['attributetypes'][$name])) { return false; }
    }
    
    if ($server->Schemas['attributestype'][$name]['SYNTAX']) {
      if (!empty($server->Schemas['attributestype'][$name]['SUP'])) {
        return $this->queryAttrType(strtolower($server, $server->Schemas['attributestype'][$name]['SUP']));
      }
      return false;
    }
    if (empty(LDAP_SYNTAXES[$server->Schemas['attributestype'][$name]['SYNTAX']])) { return false; }
    return LDAP_SYNTAXES[$server->Schemas['attributestype'][$name]['SYNTAX']];
  }
  
  function getServers ($readonly = false, $base = null) {
    $servers = [];
    if ($base === null) {
      return $readonly ? $this->Servers['ro'] : $this->Servers['rw'];
    }
    
    $type = $readonly ? 'ro' : 'rw';
    foreach ($this->Servers[$type] as $server) {
      if(in_array($base, $server['base'])) {
        $servers[] = $server;
      }
    }

    return $servers;
  }

  /* parse a schema entry */
  function parseSchemaEntry ($se) {
    /* 0 => not yet, 1 => next, 2 => now */
    $state = [
      'started' => 0,
      'oid' => 0,
      'attr' => 0,
      'value' => 0,
      'inlist' => 0,
      'instring' => 0
    ];
    $entry = [];
    $currentAttr = '';
    $tks = explode(' ', $se);
    foreach ($tks as $tk) {
      if ($tk === '(' && $state['started'] === 2 && $state['value'] === 1) {
        $state['inlist'] = 1;
        $state['value'] = 2;
        continue;
      }
      if ($tk === '(' && $state['started'] === 0) {
        $state['oid'] = 1;
        $state['started'] = 2;
        continue;
      }
      if ($tk === ')' && $state['inlist'] > 0) {
        $state['inlist'] = 0;
        $state['attr'] = 1;
        continue;
      }
      if ($tk === ')' && $state['inlist'] === 0) {
        break;
      }
      
      /* oid */
      if ($state['oid'] === 1) {
        $state['attr'] = 1;
        $state['oid'] = 0;
        $entry['oid'] = $tk;
        continue;
      }
      /* attribute */
      if ($state['attr'] === 1) {
        if (in_array($tk, SCHEMA_BOOL)) {
          $entry[$tk] = true;
          $state['attr'] = 1;
          $state['value'] = 0;
        } else {
          $state['attr'] = 0;
          $state['value'] = 1;
          $currentAttr = $tk;
        }
        continue;
      }
      /* value */
      if ($state['value'] === 2 && $state['inlist'] === 1) {
        $state['inlist'] = 2;
        $entry[$currentAttr] = [str_replace('\'', '', $tk)];
        continue;
      }
      if ($state['value'] === 2 && $state['inlist'] === 2) {
        if ($tk === '$') {
          continue;
        }
        $entry[$currentAttr][] = str_replace('\'', '', $tk);
        continue;
      }
      if ($state['value'] === 2 && $state['instring'] === 2) {
        $entry[$currentAttr] .= ' ' . $tk;
        if ($tk[strlen($tk) - 1] === '\'') {
          $entry[$currentAttr] = str_replace('\'', '', $entry[$currentAttr]);
          $state['instring'] = 0;
          $state['value'] = 0;
          $state['attr'] = 1;
        }
        continue;
      }
      if ($state['value'] === 1) {
        if ($tk[0] === '\'' && $tk[strlen($tk) - 1] !== '\'') {
          $entry[$currentAttr] = $tk; /* removing ' is left at end of string */
          $state['instring'] = 2;
          $state['value'] = 2;
        } else {
          $entry[$currentAttr] = str_replace('\'', '', $tk);
          $state['attr'] = 1;
          $state['value'] = 0;
        }
        continue;
      }
    }
    
    if (empty($entry)) { return [null, []]; }
    if (empty($entry['NAME'])) { return [null, []]; }
    if (is_array($entry['NAME'])) {
      $s = [[strtolower($entry['NAME'][0]), $entry]];
      for ($i = 1; $i < count($entry['NAME']); $i++) {
        $s[] = [strtolower($entry['NAME'][$i]), ['_ref' => strtolower($entry['NAME'][0])]];
      }
      return $s;
    } else {
      return [[strtolower($entry['NAME']), $entry]];
    }
  }
  
  function decompose ($data) {
    $object = ['+children'=> []];

    if (!empty($object['+children']) && is_array($object['_children'])) {
      foreach ($object['+children'] as $child) {
        $object['+children'][] = $this.decompose($child);
      }
    }

    foreach ($data as $key => $value) {    
      if (strpos($key, '+') === 0) { continue; }
      if (empty($value['value']) || $value['value'] === null) { continue; }
      if (empty($value['type']) || $value['type'] === null) { continue; }
      if (!in_array(strtolower($value['type']), KNOWN_TYPE)) { continue; }

      $keys = [];
      if (!empty($value['params'])) {
        foreach ($value['params'] as $opt => $optval) {
          foreach ($optval as $idx => $val) {
            if (empty($key[$idx])) {
              $keys[$idx] = [];
            }
            $option = $this->mapOptionName($opt);
            /* make difference between tag and option with value :
             *   - Tag is ;max
             *   - Option with value is ;lang-fr
             */
            if (is_string($val)) {
              $option = sprintf('%s-%s', $option, $val);
            } else if (!$val) { 
              continue;
            }

            $keys[$idx][] = $option;
          }
        }
      }

      if (!is_array($value['value'])) { $value['value'] = [$value['value']]; }
      foreach ($value['value'] as $idx => $val) {
        $attribute = $this->mapAttrName($key);
        if ($attribute === false) { continue; }
        if (!empty($keys[$idx])) {
          $attribute .= ';' . implode(';', $keys[$idx]);
        }
        if (!$this->strAttrCheck($attribute)) { continue; }
        if (!isset($object[$attribute])) { $object[$attribute] = []; }
        switch ($value['type']) {
          case 'text':
          case 'string':
          case 'uuid':
            $object[$attribute][] = \Normalizer::normalize($val, \Normalizer::FORM_KC);
            break;
          case 'definition':
            echo $val . PHP_EOL;
            $object[$attribute][] = $this->mapClassName($val);
          case 'number':
            $object[$attribute][] = $this->numberNormalize($attribute, $val);
            break;
          case 'boolean':
            $object[$attribute][] = $val ? 'TRUE' : 'FALSE';
            break;
          case 'null':
            $object[$attribute][] = '\0';
            break;
        }
      }
    }

    return $object;
  }

  function composeEntry ($server, $entryid) {
    $entry = [];
    for ($attr = ldap_first_attribut($server['conn'], $entryid);
      $attr;
      $attr = ldap_next_attribute($server['conn'], $entryid)) {
      $type = $this->queryAttributeType($server, $attr);
      if ($type === false) { continue; }
      
      $value = false;
      if ($type['binary']) {
        $value = ldap_get_values_len($server['conn'], $entryid, $attr);
      } else {
        $value = ldap_get_values($server['conn'], $entryid, $attr);
      }
      if ($value === false) { continue; }
      
      $attrName = $this->rmapAttrName($attr);
      switch ($attrName) {
        default:
          if ($value['count'] <= 0) { $value = null; }
          else { unset($value['count']); }
          $entry[$attrName] = [
            'type' => $this->mapLdapType($type),
            'readonly' => false,
            'value' => $value
          ];
          break;
        case 'uuid':
          if ($value['count'] <= 0) { return false; }
          $entry[$attrName] = [
            'type' => 'uuid',
            'readonly' => true,
            'value' => $value[0]
          ];
          break;
        case 'objectclass':
          $_value = [];
          for ($i = 0; $i < $value['count']; $i++) {
            $_value[] = $this->rmapClassName($value[$i]);
          }
          $entry[$attrName] = [
            'type' => 'definition',
            'readonly' => true,
            'value' => $_value
          ];
          break;
      }
    }
    if (empty($entry)) { return false; }
    $entry['+children'] = [];
    
    return $entry;
  }
  
  function fromLDAP ($base) {
    $server = $this->getServers(true, $base)[0];
    $result = ldap_read($server['conn'], $base, '(objectclass=*)', ['*']);
    if (!$result) { return null; }
    if (ldap_count_entries($server['conn'], $result) <= 0) { return null; }
    $entry = $this->composeEntry($server, ldap_first_entry($server['conn'], $result));
    if ($entry === FALSE) { return null; }
    $subresults = ldap_list($server['conn'], $base, '(objectclass=*)', ['*']);
    for ($ldapentry = ldap_first_entry($server['conn'], $subresults);
      $ldapentry;
      $ldapentry = ldap_next_entry($server['conn'], $subresults)) {
      $subentry = $this->composeEntry($server, $ldapentry);
      if ($subentry !== FALSE) {
        $entry['+children'][] = $subentry;
      }
    }
    return $entry;
  }
  
  function toLDAP($data, $base = null, $add = false) {
    $decomposed = $this->decompose($data);
    if ($add && $base) {
      return $this->ldapAdd($decomposed, $base);
    }
    $attrs = [];
    foreach (array_keys($decomposed) as $k) {
      if (strpos($k, '+') === 0) { continue; }
      $attrs[] = explode(';', $k)[0];
    }

    /* search on all servers we know if $base is null */
    $servers = $this->getServers(true, $base);
    $conns = [];
    $bases = [];
    foreach ($servers as $server) {
      $conns[] = $server['conn'];
      $bases[] = $server['base'];
    }
    $results = ldap_search(
      $conns,
      $bases,
      sprintf('(zyxUuid=%s)', $decomposed['zyxUuid'][0]),
      $attrs
    );

    $daResult = -1;
    foreach ($results as $i => $result) {
      if ($result === false) { continue; }
      if (ldap_count_entries($conns[$i], $result) !== 1) { continue; }
      $daResult = $i;
      break;
    }

    if ($daResult < 0) {
      /* not found, add object to LDAP and all its childs */
      if(
        ($base = $this->ldapAdd(
          $decomposed,
          $base === null ? $this->getDefaultBase() : $base,
          true)) !== FALSE) {
        foreach ($data['+children'] as $child) {
          $this->toLDAP($child, $base);
        }
      }
    } else {
      /* found */
      if (
        ($base = $this->ldapMod(
          $decomposed,
          $bases[$daResult],
          $conns[$daResult],
          $result)) !== FALSE) {
        foreach ($data['+children'] as $child) {
          $this->toLDAP($child, $base);
        }
      }
    }
  }

  function ldapAdd($decomposed, $base) {
    $ldapEntry = [];
    /* exclude unwanted attribute entry  */
    foreach($decomposed as $k => $v) {
      if (strpos($k, '+') === 0) { continue; }
      if (strpos($k, '-') === 0) { continue; }
      $ldapEntry[$k] = $v;
    }
    $dn = implode(',', ['zyxUuid=' . $ldapEntry['zyxUuid'][0], $base]);
    
    /* write connection */
    $wconn = $this->getServers(false, $base);
    if (!$wconn && !empty($wconn[0])) { return false; }
    $wconn = $wconn[0]['conn'];

    return ldap_add($wconn, $dn, $ldapEntry);
  }
  
  function ldapMod ($decomposed, $base, $conn, $src) {
    $entry = ldap_first_entry($conn, $src);

    /* write connection */
    $wconn = $this->getServers(false, $base);
    if (!$wconn && !empty($wconn[0])) { return false; }
    $wconn = $wconn[0]['conn'];
    
    if (!$entry) { return false; }
    $dn = ldap_get_dn($conn, $entry);

    $mods = [];
    for ($attr = ldap_first_attribute($conn, $entry) ;
      $attr !== FALSE;
      $attr = ldap_next_attribute($conn, $entry)) {
      if (!empty($decomposed[$attr])) {
        $mods[] = [
          'attrib' => $attr,
          'modtype' => LDAP_MODIFY_BATCH_REPLACE,
          'values' => $decomposed[$attr]
        ];
      }
      unset($decomposed[$attr]);
    }
    foreach ($decomposed as $attr => $value) {
      if (strpos($attr, '+') === 0) { continue; }
      if (strpos($attr, '-') === 0) {
        $mods[] = [
          'attrib' => substr($attr, 1),
          'modtype' => LDAP_MODIFY_BATCH_REMOVE_ALL
        ];
      } else {
        $mods[] = [
          'attrib' => $attr,
          'modtype' => LDAP_MODIFY_BATCH_ADD,
          'values' => $decomposed[$attr]
        ];
      }
    }

    return ldap_modify_batch($wconn, $dn, $mods);
  }
}

?>
