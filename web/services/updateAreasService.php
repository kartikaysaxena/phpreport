<?php
/*
 * Copyright (C) 2009 Igalia, S.L. <info@igalia.com>
 *
 * This file is part of PhpReport.
 *
 * PhpReport is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PhpReport is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PhpReport.  If not, see <http://www.gnu.org/licenses/>.
 */


   include_once('phpreport/web/services/WebServicesFunctions.php');
   include_once('phpreport/model/facade/AdminFacade.php');
   include_once('phpreport/model/vo/AreaVO.php');

    $parser = new XMLReader();

    $request = trim(file_get_contents('php://input'));

    /*$request = '<?xml version="1.0" encoding="ISO-8859-15"?><areas><area><id>11</id><name>Cafeteras</name></area><area><id>10</id><name>Viciadas</name></area></areas>';*/

    $parser->XML($request);

    do {

        $parser->read();

        if ($parser->name == 'areas')
        {

            $sid = $parser->getAttribute("sid");

            $parser->read();

        }

        /* We check authentication and authorization */
        require_once('phpreport/util/LoginManager.php');

        $user = LoginManager::isLogged($sid);

        if (!$user)
        {
            $string = "<return service='updateAreas'><error id='2'>You must be logged in</error></return>";
            break;
        }

        if (!LoginManager::isAllowed($sid))
        {
            $string = "<return service='updateAreas'><error id='3'>Forbidden service for this User</error></return>";
            break;
        }

        do {

            if ($parser->name == "area")
            {

                $areaVO = new AreaVO();

                $parser->read();

                while ($parser->name != "area") {

                    switch ($parser->name ) {

                        case "name":$parser->read();
                                if ($parser->hasValue)
                                {
                                    $areaVO->setName(unescape_string($parser->value));
                                    $parser->next();
                                    $parser->next();
                                }
                                break;

                        case "id":$parser->read();
                                if ($parser->hasValue)
                                {
                                    $areaVO->setId($parser->value);
                                    $parser->next();
                                    $parser->next();
                                }
                                break;

                        default:    $parser->next();
                                break;

                    }

                }

                $updateAreas[] = $areaVO;

            }

        } while ($parser->read());


        if (count($updateAreas) >= 1)
            foreach((array)$updateAreas as $updateArea)
            {
                if (AdminFacade::UpdateArea($updateArea) == -1)
                {
                    $string = "<return service='updateAreas'><error id='1'>There was some error while updating the areas</error></return>";
                    break;
                }

            }



        if (!$string)
        {

            $string = "<return service='updateAreas'><ok>Operation Success!</ok><areas>";

            foreach((array) $updateAreas as $updateArea)
                $string = $string . "<area><id>{$updateArea->getId()}</id><name>{$updateArea->getName()}</name></area>";

            $string = $string . "</areas></return>";

        }

    } while (false);


    // make it into a proper XML document with header etc
    $xml = simplexml_load_string($string);

   // send an XML mime header
    header("Content-type: text/xml");

   // output correctly formatted XML
    echo $xml->asXML();