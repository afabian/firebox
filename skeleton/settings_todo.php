<?php

$fbx['settings'] = array('name' => 'Firebox To-Do List',
                      'password' => 'test',
                      'default_action' => 'todo.show_list',
                      'development_plugins' => array( 'profiler', 'queries', 'debug', 'menu' ),
                      'production_plugins' => array( 'debug' ),
                      'pre' => array(),
                      'post' => array(),
                      );
                      
