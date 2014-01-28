<?php

  $travis = getenv('TRAVIS');

  return array(
    'config' => array(),
    'db' => array(
      'dsn' => 'mysql:host=localhost;dbname=ukrcms_core_test',
      'username' => $travis ? 'travis' : 'root',
      'password' => $travis ? '' : '1111',
      'tablePrefix' => 'uc_',
      'tableLangsSuffix' => "_langs",

    ),

    'language' => array(
      'languages' => array(
        'ua' => 'localhost/ukrcmsblog',
        'en' => 'localhost/ukrcmsblog/en',
      ),
      'default' => 'ua',
    )
  );
