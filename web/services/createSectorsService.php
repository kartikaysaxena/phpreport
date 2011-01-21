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

/** createSectors web service.
 *
 * @filesource
 * @package PhpReport
 * @subpackage services
 * @author Jorge López Fernández
 */

   include_once('phpreport/web/services/WebServicesFunctions.php');
   include_once('phpreport/model/facade/CustomersFacade.php');
   include_once('phpreport/model/vo/SectorVO.php');

    $parser = new XMLReader();

    $request = trim(file_get_contents('php://input'));

    /*$request = '<?xml version="1.0" encoding="ISO-8859-15"?><sectors><sector><name>Universal delivery</name></sector></sectors>';*/

    $parser->XML($request);

    do {

        $parser->read();

        if ($parser->name == 'sectors')
        {

            $sid = $parser->getAttribute("sid");

            $parser->read();

        }

        /* We check authentication and authorization */
        require_once('phpreport/util/LoginManager.php');

        $user = LoginManager::isLogged($sid);

        if (!$user)
        {
            $string = "<return service='createSectors'><error id='2'>You must be logged in</error></return>";
            break;
        }

        if (!LoginManager::isAllowed($sid))
        {
            $string = "<return service='createSectors'><error id='3'>Forbidden service for this User</error></return>";
            break;
        }

        do {

            //print ($parser->name . "\n");

            if ($parser->name == "sector")
            {

                $sectorVO = new SectorVO();

                $parser->read();

                while ($parser->name != "sector") {

                    //print ($parser->name . "\n");

                    switch ($parser->name ) {

                        case "name":$parser->read();
                                if ($parser->hasValue)
                                {
                                    $sectorVO->setName(unescape_string($parser->value));
                                    $parser->next();
                                    $parser->next();
                                }
                                break;

                        default:    $parser->next();
                                break;

                    }

                }

                $createSectors[] = $sectorVO;

            }

        } while ($parser->read());

        //var_dump($createSectors);


        if (count($createSectors) >= 1)
            foreach((array)$createSectors as $createSector)
            {
                if (CustomersFacade::CreateSector($createSector) == -1)
                {
                    $string = "<return service='createSectors'><error id='1'>There was some error while creating the sectors</error></return>";
                    break;
                }

            }



        if (!$string)
        {

            $string = "<return service='createSectors'><ok>Operation Success!</ok><sectors>";

            foreach((array) $createSectors as $createSector)
                $string = $string . "<sector><id>{$createSector->getId()}</id><name>{$createSector->getName()}</name></sector>";

            $string = $string . "</sectors></return>";

        }

    } while (false);


    // make it into a proper XML document with header etc
    $xml = simplexml_load_string($string);

   // send an XML mime header
    header("Content-type: text/xml");

   // output correctly formatted XML
    echo $xml->asXML();
