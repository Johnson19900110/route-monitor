<?php
/**
 * Created by PhpStorm.
 * User: Xc
 * Date: 2017/8/18
 * Time: 14:44
 */

use App\Server\RouteServer;

require_once 'BaseApp.php';

global $command;

switch ($command)
{
    case "e":
        new RouteServer();
        break;
    default:
        echo "Nothing to do!\n";
}