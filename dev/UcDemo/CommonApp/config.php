<?php
  $travis = !empty($_ENV['TRAVIS']);
  return array(
    'config' => array(),
    'db' => array(
      'dsn' => 'mysql:host=localhost;dbname=ukrcms_core_test',
      'username' => $travis ? 'travis' : 'root',
      'password' => $travis ? '' : '1111',
      'tablePrefix' => 'uc_',
    ),
  );
