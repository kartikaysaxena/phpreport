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


/** File for GetUserProjectReportAction
 *
 *  This file just contains {@link GetUserProjectReportAction}.
 *
 * @filesource
 * @package PhpReport
 * @subpackage facade
 * @author Jorge López Fernández <jlopez@igalia.com>
 */

include_once(PHPREPORT_ROOT . '/model/facade/action/Action.php');
include_once(PHPREPORT_ROOT . '/model/dao/DAOFactory.php');
include_once(PHPREPORT_ROOT . '/model/vo/UserVO.php');

/** Get User Project Report Action
 *
 *  This action is used for retrieving information about worked hours in Tasks done by a User for each Project.
 *
 * @package PhpReport
 * @subpackage facade
 * @author Jorge López Fernández <jlopez@igalia.com>
 */
class GetUserProjectReportAction extends Action{

    /** The User.
     *
     * This variable contains the User whose Tasks report we want to retrieve.
     *
     * @var UserVO
     */
    private $userVO;

    /** The date interval init.
     *
     * This variable contains the initial date of the interval whose Tasks information we want to retrieve.
     *
     * @var DateTime
     */
    private $init;

    /** The date interval end.
     *
     * This variable contains the ending date of the interval whose Tasks information we want to retrieve.
     *
     * @var DateTime
     */
    private $end;

    /** GetUserProjectReportAction constructor.
     *
     * This is just the constructor of this action. We can pass dates with optional parameters <var>$init</var> and <var>$end</var>
     * if we want to retrieve information about only an interval.
     *
     * @param UserVO $userVO the User whose tasks' report we want to retrieve.
     * @param DateTime $init the initial date of the interval whose Tasks report we want to retrieve.
     * @param DateTime $end the ending date of the interval whose Tasks report we want to retrieve.
     */
    public function __construct(UserVO $userVO, DateTime $init = NULL, DateTime $end = NULL) {

        $this->userVO = $userVO;

        if (is_null($init))
            $this->init =  date_create("1900-01-01");
        else
            $this->init = $init;

        if (is_null($end))
            $this->end =  new DateTime();
        else
            $this->end = $end;

        $this->preActionParameter="GET_USER_PROJECT_REPORT_PREACTION";
        $this->postActionParameter="GET_USER_PROJECT_REPORT_POSTACTION";

    }

    /** Specific code execute.
     *
     * This is the function that contains the code that returns the Tasks reports.
     *
     *@return array an associative array with the worked hours data, with the User login as first level key and
     * the Project description as second level one.
     */
    protected function doExecute() {

        if (is_null($this->userVO->getLogin()))
        {
            $dao = DAOFactory::getUserDAO();
            $this->userVO = $dao->getById($this->userVO->getId());
        } elseif (is_null($this->userVO->getId()))
        {
            $dao = DAOFactory::getUserDAO();
            $this->userVO = $dao->getByUserLogin($this->userVO->getLogin());
        }

        $dao = DAOFactory::getTaskDAO();

        $dao2 = DAOFactory::getProjectDAO();

        $doubleResults = $dao->getTaskReport($this->userVO, $this->init, $this->end, "PROJECT");

        foreach ($doubleResults as $doubleResult)
        {
            if (is_null($doubleResult['projectid']))
                $projectName = '--- Unknown ---';
            else
            {
                $project = $dao2->getById($doubleResult['projectid']);
                $projectName = $project->getDescription();
            }
            $results[$this->userVO->getLogin()][$projectName] =  $doubleResult['add_hours'];
        }
        return $results;

    }

}


/*//Test code;

$user = new UserVO();

$user->setId(31);

$action= new GetUserProjectReportAction($user);
//var_dump($action);
$result = $action->execute();
var_dump($result);
*/
