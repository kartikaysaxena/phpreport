<?php
/*
 * Copyright (C) 2010 Igalia, S.L. <info@igalia.com>
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

define('PHPREPORT_ROOT', __DIR__ . '/../');

$sid = $_GET["sid"];

/* We check authentication and authorization */
require_once(PHPREPORT_ROOT . '/web/auth.php');

/* Include the generic header and sidebar*/
define('PAGE_TITLE', "PhpReport - My tasks");
include_once("include/header.php");
include_once("include/sidebar.php");

?>

<script type="text/javascript">

Ext.onReady(function () {

    <?php if ($sid) {?>

    var sessionId = <?php echo $sid;?>;

    <?php } ?>

    var userId = <?php echo $_SESSION['user']->getId()?>;

    /* Schema of the information about projects */
    var projectRecord = new Ext.data.Record.create([
        {name:'id'},
        {name:'description'},
    ]);

    /* Store object for the projects */
    var projectsStore = new Ext.data.Store({
        parent: this,
        autoLoad: true,
        autoSave: false,
        baseParams: {
            'order': 'description',
        },
        proxy: new Ext.data.HttpProxy({
            url: 'services/getCustomerProjectsService.php',
            method: 'GET'
        }),
        reader: new Ext.data.XmlReader(
            {record: 'project', id:'id'}, projectRecord),
        remoteSort: false,
        sortInfo: {
            field: 'description',
            direction: 'ASC',
        },
    });

    var filtersPanel = new Ext.FormPanel({
        labelWidth: 100,
        frame: true,
        width: 350,
        renderTo: 'content',
        defaults: {width: 230},
        items: [{
            fieldLabel: 'Dates between',
            name: 'start',
            xtype: 'datefield',
            format: 'd/m/Y',
            id: 'startDate',
            vtype:'daterange',
            endDateField: 'endDate' // id of the end date field
        },{
            fieldLabel: 'and',
            name: 'end',
            xtype: 'datefield',
            format: 'd/m/Y',
            id: 'endDate',
            vtype:'daterange',
            startDateField: 'startDate' // id of the start date field
        },{
            fieldLabel: 'Task description',
            name: 'filterText',
            xtype: 'textfield',
            id: 'filterText',
        },{
            fieldLabel: 'Project',
            name: 'project',
            xtype: 'combo',
            id: 'project',
            store: projectsStore,
            mode: 'local',
            valueField: 'id',
            typeAhead: true,
            triggerAction: 'all',
            displayField: 'description',
            forceSelection: true,
        },{
            fieldLabel: 'Story',
            name: 'filterStory',
            xtype: 'textfield',
            id: 'filterStory',
        },{
            fieldLabel: 'Telework',
            name: 'telework',
            xtype: 'combo',
            id: 'telework',
            mode: 'local',
            valueField: 'value',
            displayField: 'displayText',
            triggerAction:'all',
            store: new Ext.data.ArrayStore({
                fields: [
                    'value',
                    'displayText'
                ],
                data: [
                    ['yes', 'yes'],
                    ['no', 'no'],
                ],
            }),
        }],

        buttons: [{
            text: 'Find tasks',
            handler: function () {
                var baseParams = {
                    'userId': userId,
                    <?php if ($sid) {?>
                        'sid': sessionId,
                    <?php } ?>
                };
                if (Ext.getCmp('startDate').getRawValue() != "") {
                    var date = Ext.getCmp('startDate').getValue();
                    baseParams.filterStartDate = date.getFullYear() + "-"
                        + (date.getMonth()+1) + "-" + date.getDate();
                }
                if (Ext.getCmp('endDate').getRawValue() != "") {
                    var date = Ext.getCmp('endDate').getValue();
                    baseParams.filterEndDate = date.getFullYear() + "-"
                        + (date.getMonth()+1) + "-" + date.getDate();
                }
                if (Ext.getCmp('filterText').getRawValue() != "") {
                    baseParams.filterText = Ext.getCmp('filterText').getValue();
                }
                if (Ext.getCmp('project').getRawValue() != "") {
                    var value = Ext.getCmp('project').getValue();
                    baseParams.projectId = value;
                }
                if (Ext.getCmp('filterStory').getRawValue() != "") {
                    baseParams.filterStory =
                            Ext.getCmp('filterStory').getValue();
                }
                if (Ext.getCmp('telework').getRawValue() != "") {
                    var value = Ext.getCmp('telework').getValue();
                    baseParams.telework = (value == 'yes')? true : false;
                }

                tasksStore.baseParams = baseParams;
                tasksStore.load();
            }
        }],
    });

    /* Schema of the information about tasks */
    var taskRecord = new Ext.data.Record.create([
        {name:'id'},
        {name:'date'},
        {name:'initTime'},
        {name:'endTime'},
        {name:'story'},
        {name:'telework'},
        {name:'ttype'},
        {name:'text'},
        {name:'phase'},
        {name:'userId'},
        {name:'projectId'},
        {name:'customerId'},
        {name:'taskStoryId'}
    ]);

    /* Proxy to the services related with load/save Projects */
    var proxy = new Ext.data.HttpProxy({
        api: {
            read: {url: 'services/getTasksFiltered.php', method: 'GET'},
        },
    });

    /* Store object for the tasks */
    var tasksStore = new Ext.data.Store({
        id: 'tasksStore',
        autoLoad: false,
        autoSave: false,
        storeId: 'tasks',
        proxy: proxy,
        reader: new Ext.data.XmlReader(
                {record: 'task', idProperty:'id' }, taskRecord),
        remoteSort: false,
        sortInfo: {
            field: 'date',
            direction: 'ASC',
        },
    });

    var columnModel = new Ext.grid.ColumnModel([
        {
            header: 'Date',
            xtype: 'datecolumn',
            format: 'd/m/Y',
            sortable: true,
            dataIndex: 'date',
        },{
            header: 'Init time',
            sortable: true,
            dataIndex: 'initTime',
        },{
            header: 'End time',
            sortable: true,
            dataIndex: 'endTime',
        },{
            header: 'Telework',
            sortable: true,
            dataIndex: 'telework',
            xtype: 'booleancolumn',
            trueText: "<span style='color:green;'>Yes</span>",
            falseText: "<span style='color:red;'>No</span>",
        },{
            header: 'Story',
            sortable: true,
            dataIndex: 'story',
        },{
            header: 'Description',
            sortable: true,
            dataIndex: 'text',
        }
    ]);

    // setup the panel for the grid of tasks
    var tasksGrid = new Ext.grid.GridPanel({
        id: 'tasksGrid',
        renderTo: 'content',
        frame: true,
        height: 500,
        width: '100%',
        iconCls: 'silk-book',
        store: tasksStore,
        frame: true,
        title: 'Tasks',
        style: 'margin-top: 10px',
        renderTo: 'content',
        loadMask: true,
        stripeRows: true,
        colModel: columnModel,
        columnLines: true,
    });

});
</script>

<div id="content">
</div>
<?php
/* Include the footer to close the header */
include("include/footer.php");
?>
